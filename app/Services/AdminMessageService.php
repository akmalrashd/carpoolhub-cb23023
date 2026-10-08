<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Validation\ValidationException;

class AdminMessageService
{
    public function __construct(private readonly AdminAuditService $adminAuditService)
    {
    }

    /**
     * @param array{audience: string, user_id?: int|string|null, role?: string|null, title: string, message: string} $data
     */
    public function send(User $admin, array $data): int
    {
        $recipients = $this->resolveRecipients($data);

        if ($recipients->isEmpty()) {
            throw ValidationException::withMessages([
                'audience' => 'No matching recipients found.',
            ]);
        }

        // related_type is left null on purpose. A broadcast message has no
        // specific record to open, and the notification model already sends
        // anything without a related type to the notifications page, which is
        // exactly where this should land.
        foreach ($recipients as $recipient) {
            UserNotification::query()->create([
                'user_id' => $recipient->id,
                'type' => 'system',
                'title' => $data['title'],
                'message' => $data['message'],
                'related_type' => null,
                'related_id' => null,
                'is_read' => false,
            ]);
        }

        $count = $recipients->count();
        $this->adminAuditService->log(
            $admin,
            'message.broadcast_sent',
            null,
            null,
            "Sent \"{$data['title']}\" to {$count} recipient(s) (audience: {$data['audience']})"
        );

        return $count;
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    private function resolveRecipients(array $data): \Illuminate\Support\Collection
    {
        return match ($data['audience']) {
            'user' => User::query()->where('id', $data['user_id'])->get(),
            'role' => User::query()->where('role', $data['role'])->get(),
            'all' => User::query()->get(),
            default => collect(),
        };
    }
}
