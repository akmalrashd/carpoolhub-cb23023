<?php

namespace App\Services;

use App\Models\SavedRoute;
use App\Models\Trip;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SavedRouteService
{
    public function paginateForUser(User $user, int $perPage = 10): LengthAwarePaginator
    {
        return SavedRoute::query()
            ->where('user_id', $user->id)
            ->latest()
            ->paginate($perPage);
    }

    public function create(User $user, array $data): SavedRoute
    {
        return SavedRoute::query()->create([
            ...$data,
            'user_id' => $user->id,
        ]);
    }

    public function update(SavedRoute $savedRoute, array $data): SavedRoute
    {
        $savedRoute->update($data);

        return $savedRoute->refresh();
    }

    public function toggleActive(SavedRoute $savedRoute): SavedRoute
    {
        $savedRoute->update([
            'is_active' => ! (bool) $savedRoute->is_active,
        ]);

        return $savedRoute->refresh();
    }

    public function delete(SavedRoute $savedRoute): void
    {
        DB::transaction(function () use ($savedRoute): void {
            $tripIds = Trip::query()
                ->where('saved_route_id', $savedRoute->id)
                ->pluck('id');

            if ($tripIds->isNotEmpty()) {
                UserNotification::query()
                    ->where('related_type', 'trip')
                    ->whereIn('related_id', $tripIds)
                    ->delete();
            }

            UserNotification::query()
                ->where('related_type', 'route')
                ->where('related_id', $savedRoute->id)
                ->delete();

            $savedRoute->delete();
        });
    }

}
