<?php

namespace App\Services;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\WithdrawalRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The concurrency-safe balance ledger every credit/debit in the wallet
 * feature goes through — gateway payments, withdrawal reservations,
 * withdrawal refunds. Every write locks the wallet row first and recomputes
 * the new balance from the row it just locked (never from a value read
 * earlier outside the lock), so two simultaneous writers can never corrupt
 * the balance — MySQL InnoDB serialises the second lockForUpdate() behind
 * the first transaction's commit. Mirrors the row-lock-then-recheck pattern
 * already used for seat-limit checks in TripJoinRequestService::respond().
 */
class WalletService
{
    public function walletFor(User $user): Wallet
    {
        $wallet = Wallet::query()->where('user_id', $user->id)->first();

        return $wallet ?? Wallet::create(['user_id' => $user->id, 'balance' => 0]);
    }

    public function balanceFor(User $user): float
    {
        return (float) $this->walletFor($user)->balance;
    }

    public function credit(
        User $user,
        float $amount,
        string $reason,
        ?string $relatedType = null,
        ?int $relatedId = null,
        ?string $description = null,
        ?User $actor = null,
    ): WalletTransaction {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Credit amount must be greater than zero.']);
        }

        return DB::transaction(function () use ($user, $amount, $reason, $relatedType, $relatedId, $description, $actor): WalletTransaction {
            $wallet = Wallet::query()->where('user_id', $user->id)->lockForUpdate()->first()
                ?? Wallet::create(['user_id' => $user->id, 'balance' => 0]);

            $newBalance = round((float) $wallet->balance + $amount, 2);
            $wallet->update(['balance' => $newBalance]);

            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'direction' => WalletTransaction::DIRECTION_CREDIT,
                'reason' => $reason,
                'amount' => round($amount, 2),
                'balance_after' => $newBalance,
                'related_type' => $relatedType,
                'related_id' => $relatedId,
                'description' => $description,
                'created_by' => $actor?->id,
                'created_at' => now(),
            ]);
        });
    }

    public function debit(
        User $user,
        float $amount,
        string $reason,
        ?string $relatedType = null,
        ?int $relatedId = null,
        ?string $description = null,
        ?User $actor = null,
    ): WalletTransaction {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Debit amount must be greater than zero.']);
        }

        return DB::transaction(function () use ($user, $amount, $reason, $relatedType, $relatedId, $description, $actor): WalletTransaction {
            $wallet = Wallet::query()->where('user_id', $user->id)->lockForUpdate()->first()
                ?? Wallet::create(['user_id' => $user->id, 'balance' => 0]);

            // Checked inside the same lock the balance was read under — this is
            // what makes two overlapping withdrawal requests unable to both
            // succeed against the same balance.
            if ($amount > (float) $wallet->balance) {
                throw ValidationException::withMessages(['amount' => 'Insufficient wallet balance.']);
            }

            $newBalance = round((float) $wallet->balance - $amount, 2);
            $wallet->update(['balance' => $newBalance]);

            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'direction' => WalletTransaction::DIRECTION_DEBIT,
                'reason' => $reason,
                'amount' => round($amount, 2),
                'balance_after' => $newBalance,
                'related_type' => $relatedType,
                'related_id' => $relatedId,
                'description' => $description,
                'created_by' => $actor?->id,
                'created_at' => now(),
            ]);
        });
    }

    public function paginateTransactions(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return WalletTransaction::query()
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->paginate($perPage);
    }

    /**
     * Lifetime credited/debited computed on read via one grouped query, and
     * the currently-reserved (pending withdrawal) total — deliberately not
     * denormalised counters on the wallet row, so nothing can drift from the
     * ledger it's summarising.
     */
    public function summaryFor(User $user): array
    {
        $rows = WalletTransaction::query()
            ->where('user_id', $user->id)
            ->select('direction', DB::raw('SUM(amount) as total'))
            ->groupBy('direction')
            ->pluck('total', 'direction');

        $pendingWithdrawals = (float) WithdrawalRequest::query()
            ->where('user_id', $user->id)
            ->where('status', WithdrawalRequest::STATUS_PENDING)
            ->sum('amount');

        return [
            'balance' => $this->balanceFor($user),
            'lifetime_credited' => (float) ($rows[WalletTransaction::DIRECTION_CREDIT] ?? 0),
            'lifetime_debited' => (float) ($rows[WalletTransaction::DIRECTION_DEBIT] ?? 0),
            'pending_withdrawals' => $pendingWithdrawals,
        ];
    }
}
