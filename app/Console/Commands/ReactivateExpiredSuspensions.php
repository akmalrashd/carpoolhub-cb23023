<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserNotification;
use App\Services\AdminAuditService;
use Illuminate\Console\Command;

/**
 * Scheduled every 5 minutes (see bootstrap/app.php). A temporary suspension
 * (users.suspended_until) is only a timestamp — nothing else in the app
 * checks it on every request, so without this command an expired timed
 * suspension would stay suspended forever, identical to a permanent one.
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

            // No admin to attribute this to — $admin is nullable on log()
            // specifically for this call.
            $adminAuditService->log(null, 'user.auto_reactivated', 'user', $user->id, 'Temporary suspension expired');
        }

        $this->info("Reactivated {$users->count()} account(s) whose suspension expired.");

        return self::SUCCESS;
    }
}
