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
 * Every credit and debit in the wallet goes through this class, including
 * gateway payments, withdrawal reservations and withdrawal refunds.
 *
 * The important part is how it stays correct when two things happen at once.
 * Each write locks the wallet row first, then works out the new balance from
 * that locked row rather than from a value read earlier. MySQL holds the
 * second request at its lock until the first one commits, so two writers can
 * never both calculate against the same stale balance. This is the same lock
 * then recheck approach used for seat limits in
 * TripJoinRequestService::respond().
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

            // Checked while still holding the lock the balance was read
            // under. That is what stops two overlapping withdrawal requests
            // from both passing against the same balance.
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
     * Works out the lifetime credited and debited totals, plus whatever is
     * currently reserved by a pending withdrawal, using one grouped query.
     *
     * These are calculated when read instead of being stored as counters on
     * the wallet row, so the figures can never drift away from the ledger they
     * are meant to summarise.
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
