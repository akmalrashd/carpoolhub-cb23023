<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class AdminUserService
{
    public function __construct(private readonly AdminAuditService $adminAuditService) {}

    public function paginatePendingDrivers(int $perPage = 10): LengthAwarePaginator
    {
        return User::query()
            ->where('role', 'driver')
            ->where('driver_verification_status', 'pending')
            ->oldest() // oldest application first, so reviews stay in a fair order
            ->paginate($perPage, ['*'], 'pending_page')
            ->withQueryString();
    }

    /**
     * The searchable list behind the Driver Verification tab. It covers every
     * driver rather than only the pending ones, so an admin can reopen an
     * application that was already approved or rejected and look at the
     * documents again. It is kept separate from paginateUsers(), which handles
     * the general roster for role and suspension changes
     * for every account, this one is scoped to the verification workflow
     * specifically (driver_verification_status + documents).
     */
    public function paginateDriversForVerification(?string $q, ?string $status, int $perPage = 10): LengthAwarePaginator
    {
        $query = User::query()->where('role', 'driver')->latest();

        $q = trim((string) $q);
        if ($q !== '') {
            $query->where(function ($builder) use ($q): void {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            });
        }

        if (in_array($status, ['pending', 'approved', 'rejected'], true)) {
            $query->where('driver_verification_status', $status);
        }

        return $query->paginate($perPage, ['*'], 'driver_page')->withQueryString();
    }

    public function approveDriver(User $admin, User $target): User
    {
        if ($target->role !== 'driver') {
            throw ValidationException::withMessages([
                'user' => 'Only driver accounts can be approved.',
            ]);
        }

        $target->update([
            'is_active' => true,
            'driver_verification_status' => 'approved',
            'driver_verification_reason' => null,
            'driver_verified_at' => now(),
            'driver_reviewed_by' => $admin->id,
        ]);

        UserNotification::query()->create([
            'user_id' => $target->id,
            'type' => 'system',
            'title' => 'Driver Application Approved',
            'message' => 'Good news, your driver application has been approved. You can now post and manage trips.',
            'related_type' => 'settings',
            'related_id' => null,
            'is_read' => false,
        ]);

        $this->adminAuditService->log($admin, 'driver.approved', 'user', $target->id);

        return $target->refresh();
    }

    public function rejectDriver(User $admin, User $target, string $reason): User
    {
        if ($target->role !== 'driver') {
            throw ValidationException::withMessages([
                'user' => 'Only driver accounts can be rejected.',
            ]);
        }

        $target->update([
            'is_active' => false,
            'driver_verification_status' => 'rejected',
            'driver_verification_reason' => $reason,
            'driver_verified_at' => now(),
            'driver_reviewed_by' => $admin->id,
        ]);

        UserNotification::query()->create([
            'user_id' => $target->id,
            'type' => 'system',
            'title' => 'Driver Application Rejected',
            'message' => "Your driver application was rejected: \"{$reason}\". You can update your documents in Settings and resubmit.",
            'related_type' => 'settings',
            'related_id' => null,
            'is_read' => false,
        ]);

        $this->adminAuditService->log($admin, 'driver.rejected', 'user', $target->id, $reason);

        return $target->refresh();
    }

    public function paginateUsers(?string $q, ?string $role, ?string $active, int $perPage = 15): LengthAwarePaginator
    {
        $query = User::query()->latest();

        $q = trim((string) $q);
        if ($q !== '') {
            $query->where(function ($builder) use ($q): void {
                $builder->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%");
            });
        }

        if (in_array($role, ['admin', 'driver', 'passenger'], true)) {
            $query->where('role', $role);
        }

        if ($active === '1' || $active === '0') {
            $query->where('is_active', $active === '1');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function updateUser(User $admin, User $target, array $data): User
    {
        if ($admin->id === $target->id && isset($data['is_active']) && ! $data['is_active']) {
            throw ValidationException::withMessages([
                'is_active' => 'You cannot deactivate your own account.',
            ]);
        }

        $originalRole = $target->role;
        $wasActive = (bool) $target->is_active;
        $newActive = (bool) $data['is_active'];
        $isDeactivating = $wasActive && ! $newActive;
        $reason = trim((string) ($data['reason'] ?? ''));

        // Rejecting a driver always required a reason, but suspending or
        // reactivating an account never recorded one. A reason is now required,
        // though only at the moment an account is actually being deactivated,
        // so editing the role of an already inactive user does not suddenly
        // ask for one.
        if ($isDeactivating && $reason === '') {
            throw ValidationException::withMessages([
                'reason' => 'A reason is required when suspending an active account.',
            ]);
        }

        $updates = [
            'role' => $data['role'],
            'is_active' => $newActive,
        ];

        if ($isDeactivating) {
            $updates['deactivation_reason'] = $reason;
            // Leaving this blank means the suspension never expires.
            // ReactivateExpiredSuspensions only looks at rows where
            // suspended_until has a value, so a null here keeps the suspension
            // in place until an admin lifts it by hand.
            $updates['suspended_until'] = trim((string) ($data['suspended_until'] ?? '')) !== ''
                ? $data['suspended_until']
                : null;
        } elseif (! $wasActive && $newActive) {
            // Reactivating clears whatever suspension reason/expiry was on file.
            $updates['deactivation_reason'] = null;
            $updates['suspended_until'] = null;
        }

        // Reactivating a driver from the general edit drawer also counts as
        // approving them. Without this the account could end up active while
        // its verification status still said pending or rejected, which would
        // leave the badge and the check in TripController disagreeing with what
        // the account is actually allowed to do.
        // Suspending, meaning is_active goes to false, does NOT touch
        // driver_verification_status: that's what lets an already-approved
        // driver's login message correctly read "suspended" instead of
        // reverting to "pending".
        if ($data['role'] === 'driver' && $newActive && $target->driver_verification_status !== 'approved') {
            $updates['driver_verification_status'] = 'approved';
            $updates['driver_verification_reason'] = null;
            $updates['driver_verified_at'] = now();
            $updates['driver_reviewed_by'] = $admin->id;
        }

        $target->update($updates);

        if ($originalRole !== $target->role) {
            $this->adminAuditService->log($admin, 'user.role_changed', 'user', $target->id, "{$originalRole} -> {$target->role}");
        }
        if ($isDeactivating) {
            // accountStatusLabel()['reason'] rather than the bare $reason
            // variable so the log picks up "Auto-reactivates ..." too when
            // this was a timed suspension, not just the admin's typed reason.
            $this->adminAuditService->log($admin, 'user.suspended', 'user', $target->id, $target->accountStatusLabel()['reason'] ?? $reason);
        } elseif (! $wasActive && $newActive) {
            $this->adminAuditService->log($admin, 'user.reactivated', 'user', $target->id);
        }

        return $target->refresh();
    }
}
