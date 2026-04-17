<?php

namespace App\Services;

use App\Models\ArchivedTrip;
use App\Models\ArchivedTripParticipant;
use App\Models\ArchivedTripPayment;
use App\Models\BillingCycle;
use App\Models\Trip;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BillingCycleService
{
    public function getOrCreateOpenCycle(): BillingCycle
    {
        $open = BillingCycle::query()
            ->where('status', 'open')
            ->orderByDesc('id')
            ->first();

        if ($open) {
            return $open;
        }

        return $this->createCycleForMonth(now());
    }

    public function paginateCycles(?User $viewer = null, int $perPage = 12): LengthAwarePaginator
    {
        return BillingCycle::query()
            ->with('closer')
            ->when(
                $viewer && $viewer->role !== 'admin',
                fn (Builder $query) => $query->whereHas('trips', fn (Builder $tripQuery) => $this->applyViewerTripScope($tripQuery, $viewer))
            )
            ->latest('start_date')
            ->paginate($perPage);
    }

    public function summarizeCycle(BillingCycle $cycle, ?User $viewer = null): array
    {
        $tripIds = Trip::query()
            ->where('billing_cycle_id', $cycle->id)
            ->when(
                $viewer && $viewer->role !== 'admin',
                fn (Builder $query) => $this->applyViewerTripScope($query, $viewer)
            )
            ->pluck('id');

        $tripCount = $tripIds->count();
        $fareTotal = (float) Trip::query()
            ->whereIn('id', $tripIds)
            ->sum('fare_total');

        $paymentBase = DB::table('trip_payments')->whereIn('trip_id', $tripIds);

        $pendingAndUnpaid = (float) (clone $paymentBase)
            ->whereIn('payment_status', ['unpaid', 'pending_confirmation'])
            ->sum('amount_due');

        $paid = (float) (clone $paymentBase)
            ->where('payment_status', 'paid')
            ->sum('amount_due');

        return [
            'trip_count' => $tripCount,
            'fare_total' => $fareTotal,
            'unpaid_pending_total' => $pendingAndUnpaid,
            'paid_total' => $paid,
        ];
    }

    public function summarizeCycles(EloquentCollection $cycles, ?User $viewer = null): array
    {
        $summary = [];

        foreach ($cycles as $cycle) {
            $summary[$cycle->id] = $this->summarizeCycle($cycle, $viewer);
        }

        return $summary;
    }

    public function closeCycle(User $actor, BillingCycle $cycle): BillingCycle
    {
        if ($cycle->status !== 'open') {
            throw ValidationException::withMessages([
                'cycle' => 'This billing cycle is already closed.',
            ]);
        }

        return DB::transaction(function () use ($actor, $cycle): BillingCycle {
            $this->assignUnassignedTripsToCycle($cycle);
            $this->archiveCycleTrips($cycle);

            $cycle->update([
                'status' => 'closed',
                'closed_by' => $actor->id,
                'closed_at' => now(),
            ]);

            $this->createNextCycle($cycle);
            $this->notifyCycleClosed($cycle, $actor);

            return $cycle->refresh();
        });
    }

    public function latestClosedCycle(): ?BillingCycle
    {
        return BillingCycle::query()
            ->where('status', 'closed')
            ->latest('closed_at')
            ->latest('id')
            ->first();
    }

    public function canUndoLatestClose(?BillingCycle $cycle): bool
    {
        if (! $cycle) {
            return false;
        }

        return $cycle->month_key === now()->format('Y-m');
    }

    public function undoLatestClose(User $actor): BillingCycle
    {
        $latestClosed = $this->latestClosedCycle();

        if (! $latestClosed) {
            throw ValidationException::withMessages([
                'cycle' => 'No closed billing cycle found to revert.',
            ]);
        }

        if (! $this->canUndoLatestClose($latestClosed)) {
            throw ValidationException::withMessages([
                'cycle' => 'Fallback is only allowed for the latest closed cycle in the current month.',
            ]);
        }

        return DB::transaction(function () use ($latestClosed, $actor): BillingCycle {
            /** @var BillingCycle|null $target */
            $target = BillingCycle::query()
                ->whereKey($latestClosed->id)
                ->lockForUpdate()
                ->first();

            if (! $target || $target->status !== 'closed') {
                throw ValidationException::withMessages([
                    'cycle' => 'Latest closed cycle is no longer available for fallback.',
                ]);
            }

            // Safety check to ensure this is still the latest closed cycle.
            $stillLatest = BillingCycle::query()
                ->where('status', 'closed')
                ->latest('closed_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $stillLatest || (int) $stillLatest->id !== (int) $target->id) {
                throw ValidationException::withMessages([
                    'cycle' => 'Only the latest closed cycle can be reverted.',
                ]);
            }

            $sideEffectOpen = BillingCycle::query()
                ->where('status', 'open')
                ->where('id', '!=', $target->id)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if ($sideEffectOpen) {
                $hasTrips = Trip::query()->where('billing_cycle_id', $sideEffectOpen->id)->exists();
                $hasArchived = ArchivedTrip::query()->where('billing_cycle_id', $sideEffectOpen->id)->exists();

                if ($hasTrips || $hasArchived) {
                    throw ValidationException::withMessages([
                        'cycle' => 'Fallback is no longer safe because the next cycle already has records.',
                    ]);
                }

                $sideEffectOpen->delete();
            }

            $archivedTripIds = ArchivedTrip::query()
                ->where('billing_cycle_id', $target->id)
                ->pluck('id');

            if ($archivedTripIds->isNotEmpty()) {
                $this->restoreArchivedCycleStateToOperationalRecords($target->id);

                ArchivedTripParticipant::query()
                    ->whereIn('archived_trip_id', $archivedTripIds)
                    ->delete();

                ArchivedTripPayment::query()
                    ->whereIn('archived_trip_id', $archivedTripIds)
                    ->delete();

                ArchivedTrip::query()
                    ->whereIn('id', $archivedTripIds)
                    ->delete();
            }

            $target->update([
                'status' => 'open',
                'closed_by' => null,
                'closed_at' => null,
            ]);

            $this->notifyCycleReopened($target, $actor);

            return $target->refresh();
        });
    }

    public function autoCloseOverdueCycles(): int
    {
        $closedCount = 0;

        while (true) {
            $cycle = BillingCycle::query()
                ->where('status', 'open')
                ->whereDate('end_date', '<', now()->startOfDay())
                ->orderBy('end_date')
                ->first();

            if (! $cycle) {
                break;
            }

            DB::transaction(function () use ($cycle): void {
                $this->assignUnassignedTripsToCycle($cycle);
                $this->archiveCycleTrips($cycle);

                $cycle->update([
                    'status' => 'closed',
                    'closed_by' => null,
                    'closed_at' => now(),
                ]);

                $this->createNextCycle($cycle);
                $this->notifyCycleClosed($cycle, null);
            });

            $closedCount++;
        }

        // Ensure there is always one open cycle for current operations.
        $this->getOrCreateOpenCycle();

        return $closedCount;
    }

    private function restoreArchivedCycleStateToOperationalRecords(int $billingCycleId): void
    {
        $archivedTrips = ArchivedTrip::query()
            ->with(['participants', 'payments'])
            ->where('billing_cycle_id', $billingCycleId)
            ->get();

        foreach ($archivedTrips as $archivedTrip) {
            if (! $archivedTrip->original_trip_id) {
                continue;
            }

            $originalTrip = Trip::query()->find($archivedTrip->original_trip_id);
            if (! $originalTrip) {
                continue;
            }

            $originalTrip->update([
                'driver_id' => $archivedTrip->driver_id,
                'saved_route_id' => $archivedTrip->saved_route_id,
                'pickup_name' => $archivedTrip->pickup_name,
                'pickup_latitude' => $archivedTrip->pickup_latitude,
                'pickup_longitude' => $archivedTrip->pickup_longitude,
                'destination_name' => $archivedTrip->destination_name,
                'destination_latitude' => $archivedTrip->destination_latitude,
                'destination_longitude' => $archivedTrip->destination_longitude,
                'parent_trip_id' => $archivedTrip->parent_trip_id,
                'billing_cycle_id' => $billingCycleId,
                'trip_datetime' => $archivedTrip->trip_datetime,
                'trip_mode' => $archivedTrip->trip_mode,
                'visibility' => $archivedTrip->visibility,
                'is_return_trip' => $archivedTrip->is_return_trip,
                'fare_total' => $archivedTrip->fare_total,
                'fare_per_person' => $archivedTrip->fare_per_person,
                'participant_count' => $archivedTrip->participant_count,
                'seat_limit' => $archivedTrip->seat_limit,
                'is_open_for_request' => $archivedTrip->is_open_for_request,
                'note' => $archivedTrip->note,
                'public_note' => $archivedTrip->public_note,
            ]);

            foreach ($archivedTrip->participants as $archivedParticipant) {
                $participantBase = DB::table('trip_participants')
                    ->where('trip_id', $originalTrip->id)
                    ->where('user_id', $archivedParticipant->user_id);

                $participantPayload = [
                    'is_driver' => $archivedParticipant->is_driver,
                    'fare_amount' => $archivedParticipant->fare_amount,
                    'attendance_status' => $archivedParticipant->attendance_status,
                    'updated_at' => now(),
                ];

                if ($participantBase->exists()) {
                    $participantBase->update($participantPayload);
                } else {
                    $participantPayload['trip_id'] = $originalTrip->id;
                    $participantPayload['user_id'] = $archivedParticipant->user_id;
                    $participantPayload['created_at'] = now();
                    DB::table('trip_participants')->insert($participantPayload);
                }
            }

            foreach ($archivedTrip->payments as $archivedPayment) {
                $paymentBase = DB::table('trip_payments')
                    ->where('trip_id', $originalTrip->id)
                    ->where('user_id', $archivedPayment->user_id);

                $paymentPayload = [
                    'amount_due' => $archivedPayment->amount_due,
                    'payment_status' => $archivedPayment->payment_status,
                    'marked_paid_at' => $archivedPayment->marked_paid_at,
                    'confirmed_by' => $archivedPayment->confirmed_by,
                    'confirmed_at' => $archivedPayment->confirmed_at,
                    'payment_method' => $archivedPayment->payment_method,
                    'remarks' => $archivedPayment->remarks,
                    'updated_at' => now(),
                ];

                if ($paymentBase->exists()) {
                    $paymentBase->update($paymentPayload);
                } else {
                    $paymentPayload['trip_id'] = $originalTrip->id;
                    $paymentPayload['user_id'] = $archivedPayment->user_id;
                    $paymentPayload['created_at'] = now();
                    DB::table('trip_payments')->insert($paymentPayload);
                }
            }
        }
    }

    private function assignUnassignedTripsToCycle(BillingCycle $cycle): void
    {
        Trip::query()
            ->whereNull('billing_cycle_id')
            ->whereBetween('trip_datetime', [
                $cycle->start_date->copy()->startOfDay(),
                $cycle->end_date->copy()->endOfDay(),
            ])
            ->update([
                'billing_cycle_id' => $cycle->id,
            ]);
    }

    private function archiveCycleTrips(BillingCycle $cycle): void
    {
        $trips = Trip::query()
            ->with(['participants', 'payments'])
            ->where('billing_cycle_id', $cycle->id)
            ->get();

        foreach ($trips as $trip) {
            $existing = ArchivedTrip::query()
                ->where('original_trip_id', $trip->id)
                ->first();

            if ($existing) {
                continue;
            }

            $archivedTrip = ArchivedTrip::query()->create([
                'original_trip_id' => $trip->id,
                'driver_id' => $trip->driver_id,
                'saved_route_id' => $trip->saved_route_id,
                'pickup_name' => $trip->pickup_name,
                'pickup_latitude' => $trip->pickup_latitude,
                'pickup_longitude' => $trip->pickup_longitude,
                'destination_name' => $trip->destination_name,
                'destination_latitude' => $trip->destination_latitude,
                'destination_longitude' => $trip->destination_longitude,
                'parent_trip_id' => $trip->parent_trip_id,
                'billing_cycle_id' => $cycle->id,
                'trip_datetime' => $trip->trip_datetime,
                'trip_mode' => $trip->trip_mode ?? 'one_way',
                'visibility' => $trip->visibility ?? 'private',
                'is_return_trip' => (bool) $trip->is_return_trip,
                'fare_total' => $trip->fare_total,
                'fare_per_person' => $trip->fare_per_person,
                'participant_count' => $trip->participant_count,
                'seat_limit' => $trip->seat_limit,
                'is_open_for_request' => (bool) $trip->is_open_for_request,
                'note' => $trip->note,
                'public_note' => $trip->public_note,
                'archived_at' => now(),
            ]);

            foreach ($trip->participants as $participant) {
                ArchivedTripParticipant::query()->create([
                    'archived_trip_id' => $archivedTrip->id,
                    'user_id' => $participant->user_id,
                    'is_driver' => $participant->is_driver,
                    'fare_amount' => $participant->fare_amount,
                    'attendance_status' => $participant->attendance_status,
                    'archived_at' => now(),
                ]);
            }

            foreach ($trip->payments as $payment) {
                ArchivedTripPayment::query()->create([
                    'archived_trip_id' => $archivedTrip->id,
                    'user_id' => $payment->user_id,
                    'amount_due' => $payment->amount_due,
                    'payment_status' => $payment->payment_status,
                    'marked_paid_at' => $payment->marked_paid_at,
                    'confirmed_by' => $payment->confirmed_by,
                    'confirmed_at' => $payment->confirmed_at,
                    'payment_method' => $payment->payment_method,
                    'remarks' => $payment->remarks,
                    'archived_at' => now(),
                ]);
            }
        }
    }

    private function createNextCycle(BillingCycle $closedCycle): BillingCycle
    {
        $nextStart = Carbon::parse($closedCycle->end_date)->addDay()->startOfMonth();
        $nextMonthKey = $nextStart->format('Y-m');

        $existing = BillingCycle::query()->where('month_key', $nextMonthKey)->first();
        if ($existing) {
            return $existing;
        }

        return $this->createCycleForMonth($nextStart);
    }

    private function createCycleForMonth(Carbon $date): BillingCycle
    {
        $start = $date->copy()->startOfMonth();
        $end = $date->copy()->endOfMonth();

        return BillingCycle::query()->create([
            'month_key' => $start->format('Y-m'),
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'status' => 'open',
        ]);
    }

    private function notifyCycleClosed(BillingCycle $cycle, ?User $actor): void
    {
        $driverIds = Trip::query()
            ->where('billing_cycle_id', $cycle->id)
            ->distinct()
            ->pluck('driver_id');

        $adminIds = User::query()->where('role', 'admin')->pluck('id');
        $recipientIds = $driverIds->merge($adminIds)->unique();

        foreach ($recipientIds as $userId) {
            UserNotification::query()->create([
                'user_id' => $userId,
                'type' => 'system',
                'title' => 'Billing Cycle Closed',
                'message' => $actor
                    ? "{$actor->name} closed cycle {$cycle->month_key}."
                    : "System auto-closed cycle {$cycle->month_key}.",
                'related_type' => 'billing_cycle',
                'related_id' => $cycle->id,
                'is_read' => false,
            ]);
        }
    }

    private function notifyCycleReopened(BillingCycle $cycle, User $actor): void
    {
        $driverIds = Trip::query()
            ->where('billing_cycle_id', $cycle->id)
            ->distinct()
            ->pluck('driver_id');

        $adminIds = User::query()->where('role', 'admin')->pluck('id');
        $recipientIds = $driverIds->merge($adminIds)->unique();

        foreach ($recipientIds as $userId) {
            UserNotification::query()->create([
                'user_id' => $userId,
                'type' => 'system',
                'title' => 'Billing Cycle Reopened',
                'message' => "{$actor->name} reverted cycle {$cycle->month_key} to open.",
                'related_type' => 'billing_cycle',
                'related_id' => $cycle->id,
                'is_read' => false,
            ]);
        }
    }

    private function applyViewerTripScope(Builder $tripQuery, User $viewer): void
    {
        $tripQuery->where(function (Builder $scope) use ($viewer): void {
            $scope->where('driver_id', $viewer->id)
                ->orWhereHas('participants', fn (Builder $participantQuery) => $participantQuery->where('user_id', $viewer->id));
        });
    }
}
