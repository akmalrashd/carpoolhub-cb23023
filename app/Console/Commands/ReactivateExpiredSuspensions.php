<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserNotification;
use App\Services\AdminAuditService;
use Illuminate\Console\Command;

/**
 * Scheduled every 5 minutes (see bootstrap/app.php). A temporary suspension
 * (users.suspended_until) is only a timestamp. Nothing else in the app looks
 * at it on each request, so without this command a suspension that has
 * expired would stay in place forever, exactly like a permanent one.
 * Runs frequently, not daily, because a suspension can expire at any minute
 * and a suspended user shouldn't have to wait up to a day past it.
 */
class ReactivateExpiredSuspensions extends Command
{
    protected $signature = 'users:reactivate-expired-suspensions';

    protected $description = 'Auto-reactivate accounts whose temporary suspension has passed';

    public function handle(AdminAuditService $adminAuditService): int
    {
        $users = User::query()
            ->where('is_active', false)
            ->whereNotNull('suspended_until')
            ->where('suspended_until', '<=', now())
            ->get();

        foreach ($users as $user) {
            $user->update([
                'is_active' => true,
                'deactivation_reason' => null,
                'suspended_until' => null,
            ]);

            UserNotification::query()->create([
                'user_id' => $user->id,
                'type' => 'system',
                'title' => 'Account Reactivated',
                'message' => 'Your temporary suspension has ended and your account is active again.',
                'related_type' => 'settings',
                'related_id' => null,
                'is_read' => false,
            ]);

            // There is no admin to credit here, which is why $admin is
            // allowed to be null on log().
            $adminAuditService->log(null, 'user.auto_reactivated', 'user', $user->id, 'Temporary suspension expired');
        }

        $this->info("Reactivated {$users->count()} account(s) whose suspension expired.");

        return self::SUCCESS;
    }
}
