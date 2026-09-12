<?php

namespace App\Services;

use App\Models\SystemSetting;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\WalletTransaction;
use App\Models\WithdrawalRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WithdrawalService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly AdminAuditService $adminAuditService,
    ) {}

    /**
     * Debits the wallet immediately at request time rather than waiting for
     * admin approval — a driver could otherwise submit two overlapping
     * requests that each look individually valid against the same balance
     * (e.g. two RM40 requests against RM50), and an admin reviewing them one
     * at a time has no way to see the other already "spoke for" part of it.
     * Debiting up front means the live balance always reflects what's
     * genuinely still available, so WalletService::debit()'s own
     * insufficient-balance check is what prevents the double-spend.
     */
    public function request(User $driver, float $amount): WithdrawalRequest
    {
        $minAmount = (float) (SystemSetting::get('wallet_min_withdrawal_amount') ?? 10);

        if ($amount < $minAmount) {
            throw ValidationException::withMessages([
                'amount' => 'Minimum withdrawal amount is RM'.number_format($minAmount, 2).'.',
            ]);
        }

        if (empty($driver->payment_bank_name) || empty($driver->payment_account_name) || empty($driver->payment_account_number)) {
            throw ValidationException::withMessages([
                'amount' => 'Please add your bank details in Settings before requesting a withdrawal.',
            ]);
        }

        return DB::transaction(function () use ($driver, $amount): WithdrawalRequest {
            $withdrawal = WithdrawalRequest::create([
                'user_id' => $driver->id,
                'amount' => $amount,
                'destination_bank_name' => $driver->payment_bank_name,
                'destination_account_name' => $driver->payment_account_name,
                'destination_account_number' => $driver->payment_account_number,
                'status' => WithdrawalRequest::STATUS_PENDING,
            ]);

            $this->walletService->debit(
                $driver,
                $amount,
                WalletTransaction::REASON_WITHDRAWAL_RESERVED,
                'withdrawal_request',
                $withdrawal->id,
                "Withdrawal request #{$withdrawal->id}",
                $driver,
            );

            UserNotification::query()->create([
                'user_id' => $driver->id,
                'type' => 'payment',
                'title' => 'Withdrawal Requested',
                'message' => 'Your withdrawal request of RM'.number_format($amount, 2).' has been submitted and is awaiting admin review.',
                'related_type' => 'withdrawal_request',
                'related_id' => $withdrawal->id,
                'is_read' => false,
            ]);

            return $withdrawal;
        });
    }

    public function markPaid(User $admin, WithdrawalRequest $withdrawal, ?string $remarks = null): WithdrawalRequest
    {
        if (! $withdrawal->isPending()) {
            throw ValidationException::withMessages([
                'withdrawal' => 'Only a pending withdrawal request can be marked paid.',
            ]);
        }

        return DB::transaction(function () use ($admin, $withdrawal, $remarks): WithdrawalRequest {
            $withdrawal->update([
                'status' => WithdrawalRequest::STATUS_PAID,
                'admin_remarks' => $remarks,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            UserNotification::query()->create([
                'user_id' => $withdrawal->user_id,
                'type' => 'payment',
                'title' => 'Withdrawal Paid',
                'message' => 'Your withdrawal of RM'.number_format((float) $withdrawal->amount, 2).' has been transferred to your bank account.',
                'related_type' => 'withdrawal_request',
                'related_id' => $withdrawal->id,
                'is_read' => false,
            ]);

            $this->adminAuditService->log($admin, 'withdrawal.paid', 'withdrawal_request', $withdrawal->id, $remarks);

            return $withdrawal->refresh();
        });
    }

    public function reject(User $admin, WithdrawalRequest $withdrawal, string $reason): WithdrawalRequest
    {
        if (! $withdrawal->isPending()) {
            throw ValidationException::withMessages([
                'withdrawal' => 'Only a pending withdrawal request can be rejected.',
            ]);
        }

        return DB::transaction(function () use ($admin, $withdrawal, $reason): WithdrawalRequest {
            $withdrawal->update([
                'status' => WithdrawalRequest::STATUS_REJECTED,
                'rejection_reason' => $reason,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);

            $this->walletService->credit(
                $withdrawal->user,
                (float) $withdrawal->amount,
                WalletTransaction::REASON_WITHDRAWAL_REFUND,
                'withdrawal_request',
                $withdrawal->id,
                "Withdrawal request #{$withdrawal->id} rejected: {$reason}",
                $admin,
            );

            UserNotification::query()->create([
                'user_id' => $withdrawal->user_id,
                'type' => 'payment',
                'title' => 'Withdrawal Declined',
                'message' => 'Your withdrawal request of RM'.number_format((float) $withdrawal->amount, 2)." was declined: {$reason}. The amount has been returned to your wallet.",
                'related_type' => 'withdrawal_request',
                'related_id' => $withdrawal->id,
                'is_read' => false,
            ]);

            $this->adminAuditService->log($admin, 'withdrawal.rejected', 'withdrawal_request', $withdrawal->id, $reason);

            return $withdrawal->refresh();
        });
    }

    public function paginateForDriver(User $driver, int $perPage = 20): LengthAwarePaginator
    {
        return WithdrawalRequest::query()
            ->where('user_id', $driver->id)
            ->latest('created_at')
            ->paginate($perPage);
    }

    public function paginateForAdmin(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = WithdrawalRequest::query()->with('user:id,name,email')->latest('created_at');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $query->whereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', "%{$q}%"));
        }

        return $query->paginate($perPage)->withQueryString();
    }
}
