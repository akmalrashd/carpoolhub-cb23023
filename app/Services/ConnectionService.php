<?php

namespace App\Services;

use App\Models\Connection;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConnectionService
{
    public function getIndexData(User $user, ?string $search): array
    {
        $incomingRequests = Connection::query()
            ->with('requester')
            ->where('receiver_id', $user->id)
            ->where('status', 'pending')
            ->latest()
            ->get();

        $outgoingRequests = Connection::query()
            ->with('receiver')
            ->where('requester_id', $user->id)
            ->where('status', 'pending')
            ->latest()
            ->get();

        $acceptedUserIds = Connection::acceptedUserIdsFor($user);

        // The connections page renders name/email/avatar only — never the
        // licence or selfie blobs — so keep the multi-megabyte columns out of
        // both result sets.
        $acceptedConnections = User::query()
            ->withoutHeavyMedia()
            ->whereIn('id', $acceptedUserIds)
            ->orderBy('name')
            ->get();

        $searchResults = collect();
        $search = trim((string) $search);

        if ($search !== '') {
            $searchResults = User::query()
                ->withoutHeavyMedia()
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->where('role', '!=', 'admin')
                ->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                })
                ->orderBy('name')
                ->limit(12)
                ->get()
                ->map(function (User $candidate) use ($user): User {
                    $candidate->relationship_status = $this->relationshipStatus($user->id, $candidate->id);

                    return $candidate;
                });
        }

        return [
            'incomingRequests' => $incomingRequests,
            'outgoingRequests' => $outgoingRequests,
            'acceptedConnections' => $acceptedConnections,
            'searchResults' => $searchResults,
            // Lets the view resolve "is this person a connection of mine?" when
            // applying the contact-visibility settings, without a query per row.
            'connectedUserIds' => $acceptedUserIds->map(fn ($id): int => (int) $id)->all(),
            'q' => $search,
        ];
    }

    public function sendRequest(User $requester, int $receiverId): Connection
    {
        if ($requester->id === $receiverId) {
            throw ValidationException::withMessages([
                'receiver_id' => 'You cannot send a connection request to yourself.',
            ]);
        }

        $receiver = User::query()->whereKey($receiverId)->where('is_active', true)->first();

        if (! $receiver) {
            throw ValidationException::withMessages([
                'receiver_id' => 'Target user was not found or is inactive.',
            ]);
        }

        $status = $this->relationshipStatus($requester->id, $receiverId);

        if ($status === 'accepted') {
            throw ValidationException::withMessages([
                'receiver_id' => 'This user is already in your accepted connections.',
            ]);
        }

        if ($status === 'outgoing_pending') {
            throw ValidationException::withMessages([
                'receiver_id' => 'A request is already pending for this user.',
            ]);
        }

        if ($status === 'incoming_pending') {
            throw ValidationException::withMessages([
                'receiver_id' => 'This user has already sent you a request. Respond from incoming requests.',
            ]);
        }

        if ($status === 'blocked') {
            throw ValidationException::withMessages([
                'receiver_id' => 'Connection is blocked for this user pair.',
            ]);
        }

        return DB::transaction(function () use ($requester, $receiverId): Connection {
            $connection = Connection::query()->firstOrNew([
                'requester_id' => $requester->id,
                'receiver_id' => $receiverId,
            ]);

            $connection->status = 'pending';
            $connection->responded_at = null;
            $connection->save();

            UserNotification::query()->create([
                'user_id'      => $receiverId,
                'type'         => 'connection',
                'title'        => 'New Connection Request',
                'message'      => "{$requester->name} sent you a connection request. Accept to stay connected for future trips.",
                'related_type' => 'connection',
                'related_id'   => $connection->id,
                'is_read'      => false,
            ]);

            return $connection;
        });
    }

    public function respond(User $receiver, Connection $connection, string $action): Connection
    {
        if ($connection->receiver_id !== $receiver->id || $connection->status !== 'pending') {
            throw ValidationException::withMessages([
                'action' => 'This request is no longer available.',
            ]);
        }

        $nextStatus = $action === 'accept' ? 'accepted' : 'rejected';

        return DB::transaction(function () use ($connection, $receiver, $nextStatus): Connection {
            $connection->status = $nextStatus;
            $connection->responded_at = now();
            $connection->save();

            UserNotification::query()->create([
                'user_id'      => $connection->requester_id,
                'type'         => 'connection',
                'title'        => $nextStatus === 'accepted' ? 'Connection Accepted' : 'Connection Declined',
                'message'      => $nextStatus === 'accepted'
                    ? "{$receiver->name} accepted your connection request. You can now see each other in trip recommendations."
                    : "{$receiver->name} declined your connection request.",
                'related_type' => 'connection',
                'related_id'   => $connection->id,
                'is_read'      => false,
            ]);

            return $connection;
        });
    }

    public function removeAccepted(User $actor, User $target): void
    {
        if ((int) $actor->id === (int) $target->id) {
            throw ValidationException::withMessages([
                'connection' => 'You cannot remove yourself from connections.',
            ]);
        }

        $removed = Connection::query()
            ->where('status', 'accepted')
            ->where(function ($query) use ($actor, $target): void {
                $query->where(function ($pair) use ($actor, $target): void {
                    $pair->where('requester_id', $actor->id)
                        ->where('receiver_id', $target->id);
                })->orWhere(function ($pair) use ($actor, $target): void {
                    $pair->where('requester_id', $target->id)
                        ->where('receiver_id', $actor->id);
                });
            })
            ->delete();

        if ($removed === 0) {
            throw ValidationException::withMessages([
                'connection' => 'No accepted connection found for this user.',
            ]);
        }
    }

    public function relationshipStatus(int $userId, int $targetId): string
    {
        $connections = $this->connectionsBetween($userId, $targetId);

        if ($connections->contains(fn (Connection $connection) => $connection->status === 'blocked')) {
            return 'blocked';
        }

        if ($connections->contains(fn (Connection $connection) => $connection->status === 'accepted')) {
            return 'accepted';
        }

        if ($connections->contains(fn (Connection $connection) => $connection->status === 'pending' && $connection->requester_id === $userId)) {
            return 'outgoing_pending';
        }

        if ($connections->contains(fn (Connection $connection) => $connection->status === 'pending' && $connection->requester_id === $targetId)) {
            return 'incoming_pending';
        }

        if ($connections->contains(fn (Connection $connection) => $connection->status === 'rejected' && $connection->requester_id === $userId)) {
            return 'rejected_by_you';
        }

        if ($connections->contains(fn (Connection $connection) => $connection->status === 'rejected' && $connection->requester_id === $targetId)) {
            return 'rejected_you';
        }

        return 'none';
    }

    private function connectionsBetween(int $userId, int $targetId): Collection
    {
        return Connection::query()
            ->where(function ($query) use ($userId, $targetId): void {
                $query->where('requester_id', $userId)->where('receiver_id', $targetId);
            })
            ->orWhere(function ($query) use ($userId, $targetId): void {
                $query->where('requester_id', $targetId)->where('receiver_id', $userId);
            })
            ->latest('id')
            ->get();
    }
}
