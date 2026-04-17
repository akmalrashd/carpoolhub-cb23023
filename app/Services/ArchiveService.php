<?php

namespace App\Services;

use App\Models\ArchivedTrip;
use App\Models\ArchivedTripPayment;
use App\Models\BillingCycle;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ArchiveService
{
    public function getMonthOptions()
    {
        return BillingCycle::query()
            ->where('status', 'closed')
            ->orderByDesc('month_key')
            ->pluck('month_key');
    }

    public function getArchivedPaymentMonthOptions(User $viewer): array
    {
        $baseQuery = ArchivedTripPayment::query()
            ->selectRaw('billing_cycles.month_key as month_key')
            ->selectRaw("COALESCE(SUM(CASE WHEN archived_trip_payments.payment_status IN ('unpaid', 'pending_confirmation') THEN archived_trip_payments.amount_due ELSE 0 END), 0) as outstanding_amount")
            ->join('archived_trips', 'archived_trips.id', '=', 'archived_trip_payments.archived_trip_id')
            ->join('billing_cycles', 'billing_cycles.id', '=', 'archived_trips.billing_cycle_id')
            ->where('archived_trip_payments.user_id', $viewer->id)
            ->groupBy('billing_cycles.month_key');

        if ($viewer->role !== 'admin') {
            $baseQuery->where(function ($scope) use ($viewer): void {
                $scope->where('archived_trips.driver_id', $viewer->id)
                    ->orWhereExists(function ($participantQuery) use ($viewer): void {
                        $participantQuery->selectRaw('1')
                            ->from('archived_trip_participants')
                            ->whereColumn('archived_trip_participants.archived_trip_id', 'archived_trips.id')
                            ->where('archived_trip_participants.user_id', $viewer->id);
                    });
            });
        }

        $amountsByMonth = $baseQuery
            ->get()
            ->mapWithKeys(fn ($row) => [
                (string) $row->month_key => (float) $row->outstanding_amount,
            ]);

        return BillingCycle::query()
            ->where('status', 'closed')
            ->orderByDesc('month_key')
            ->pluck('month_key')
            ->map(fn ($monthKey) => [
                'value' => (string) $monthKey,
                'label' => sprintf('%s (RM %.2f)', $monthKey, (float) ($amountsByMonth[(string) $monthKey] ?? 0)),
                'outstanding_amount' => (float) ($amountsByMonth[(string) $monthKey] ?? 0),
            ])
            ->all();
    }

    public function summaryForMonth(User $viewer, ?string $monthKey): array
    {
        $tripBase = ArchivedTrip::query()
            ->when($monthKey, fn ($query) => $query->whereHas('billingCycle', fn ($billingQuery) => $billingQuery->where('month_key', $monthKey)));

        if ($viewer->role !== 'admin') {
            $tripBase->where(function (Builder $scope) use ($viewer): void {
                $scope->where('driver_id', $viewer->id)
                    ->orWhereHas('participants', fn (Builder $participantQuery) => $participantQuery->where('user_id', $viewer->id));
            });
        }

        $visibleTripIds = (clone $tripBase)->pluck('id');

        $paymentBase = ArchivedTripPayment::query()
            ->whereIn('archived_trip_id', $visibleTripIds);

        return [
            'trip_count' => $visibleTripIds->count(),
            'payment_count' => (clone $paymentBase)->count(),
            'fare_total' => (float) (clone $tripBase)->sum('fare_total'),
            'paid_total' => (float) (clone $paymentBase)->where('payment_status', 'paid')->sum('amount_due'),
            'pending_total' => (float) (clone $paymentBase)->where('payment_status', 'pending_confirmation')->sum('amount_due'),
            'unpaid_total' => (float) (clone $paymentBase)->where('payment_status', 'unpaid')->sum('amount_due'),
        ];
    }

    public function paginateArchivedTrips(User $viewer, ?string $monthKey, int $perPage = 12): LengthAwarePaginator
    {
        $query = ArchivedTrip::query()
            ->with([
                'driver',
                'savedRoute',
                'billingCycle',
                'participants.user',
                'payments.user',
                'returnTrip',
            ])
            ->whereNull('parent_trip_id')
            ->latest('trip_datetime');

        if ($monthKey) {
            $query->whereHas('billingCycle', fn ($billingQuery) => $billingQuery->where('month_key', $monthKey));
        }

        if ($viewer->role !== 'admin') {
            $query->where(function (Builder $scope) use ($viewer): void {
                $scope->where('driver_id', $viewer->id)
                    ->orWhereHas('participants', fn (Builder $participantQuery) => $participantQuery->where('user_id', $viewer->id));
            });
        }

        return $query->paginate($perPage);
    }

    public function paginateArchivedPaymentsForUser(User $viewer, ?string $monthKey, int $perPage = 12): LengthAwarePaginator
    {
        return ArchivedTripPayment::query()
            ->with(['archivedTrip.driver', 'archivedTrip.savedRoute', 'archivedTrip.participants.user', 'archivedTrip.parentTrip', 'archivedTrip.returnTrip', 'user'])
            ->where('user_id', $viewer->id)
            ->whereHas('archivedTrip', function (Builder $tripQuery) use ($viewer, $monthKey): void {
                $this->applyArchivedTripVisibilityScope($tripQuery, $viewer, $monthKey);
            })
            ->latest('id')
            ->paginate($perPage, ['*'], 'mine_page');
    }

    public function paginateArchivedPaymentsForDriver(
        User $viewer,
        ?string $monthKey,
        int $perPage = 12,
        string $pageName = 'driver_page',
        ?array $statuses = null
    ): ?LengthAwarePaginator
    {
        if (! in_array($viewer->role, ['admin', 'driver'], true)) {
            return null;
        }

        return ArchivedTripPayment::query()
            ->with(['archivedTrip.driver', 'archivedTrip.savedRoute', 'archivedTrip.participants.user', 'archivedTrip.parentTrip', 'archivedTrip.returnTrip', 'user'])
            ->when(! empty($statuses), fn ($query) => $query->whereIn('payment_status', $statuses))
            ->when(
                $viewer->role === 'admin',
                fn ($query) => $query->whereHas('archivedTrip', function (Builder $tripQuery) use ($viewer, $monthKey): void {
                    $this->applyArchivedTripVisibilityScope($tripQuery, $viewer, $monthKey);
                }),
                fn ($query) => $query->whereHas('archivedTrip', function (Builder $tripQuery) use ($viewer, $monthKey): void {
                    $this->applyArchivedTripVisibilityScope($tripQuery, $viewer, $monthKey);
                    $tripQuery->where('driver_id', $viewer->id);
                })->where('user_id', '!=', $viewer->id)
            )
            ->latest('id')
            ->paginate($perPage, ['*'], $pageName);
    }

    public function summarizeArchivedPayments(User $viewer, ?string $monthKey): array
    {
        $myBase = ArchivedTripPayment::query()
            ->where('user_id', $viewer->id)
            ->whereHas('archivedTrip', fn (Builder $tripQuery) => $this->applyArchivedTripVisibilityScope($tripQuery, $viewer, $monthKey));

        $driverBase = ArchivedTripPayment::query()
            ->when(
                $viewer->role === 'admin',
                fn ($query) => $query->whereHas('archivedTrip', fn (Builder $tripQuery) => $this->applyArchivedTripVisibilityScope($tripQuery, $viewer, $monthKey)),
                fn ($query) => $query->whereHas('archivedTrip', function (Builder $tripQuery) use ($viewer, $monthKey): void {
                    $this->applyArchivedTripVisibilityScope($tripQuery, $viewer, $monthKey);
                    $tripQuery->where('driver_id', $viewer->id);
                })->where('user_id', '!=', $viewer->id)
            );

        return [
            'my' => $this->summarizeByStatus($myBase),
            'driver' => $this->summarizeByStatus($driverBase),
        ];
    }

    private function summarizeByStatus(Builder $baseQuery): array
    {
        $statuses = ['unpaid', 'pending_confirmation', 'paid'];
        $summary = [];

        foreach ($statuses as $status) {
            $statusQuery = (clone $baseQuery)->where('payment_status', $status);
            $summary[$status] = [
                'count' => (int) (clone $statusQuery)->count(),
                'amount' => (float) (clone $statusQuery)->sum('amount_due'),
            ];
        }

        $summary['total'] = [
            'count' => (int) (clone $baseQuery)->count(),
            'amount' => (float) (clone $baseQuery)->sum('amount_due'),
        ];

        return $summary;
    }

    private function applyArchivedTripVisibilityScope(Builder $query, User $viewer, ?string $monthKey): void
    {
        if ($monthKey) {
            $query->whereHas('billingCycle', fn ($billingQuery) => $billingQuery->where('month_key', $monthKey));
        }

        if ($viewer->role === 'admin') {
            return;
        }

        $query->where(function (Builder $scope) use ($viewer): void {
            $scope->where('driver_id', $viewer->id)
                ->orWhereHas('participants', fn (Builder $participantQuery) => $participantQuery->where('user_id', $viewer->id));
        });
    }
}
