<?php

namespace App\Services;

use App\Models\AdminActionLog;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminAuditService
{
    public function log(User $admin, string $action, ?string $targetType = null, ?int $targetId = null, ?string $description = null): void
    {
        AdminActionLog::create([
            'admin_id' => $admin->id,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'description' => $description,
            'created_at' => now(),
        ]);
    }

    /**
     * @param array{q?: string, action?: string, admin_id?: string, date_from?: string, date_to?: string} $filters
     */
    public function paginateLogs(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = AdminActionLog::query()->with('admin:id,name')->latest('created_at');

        $q = trim((string) ($filters['q'] ?? ''));
        if ($q !== '') {
            $query->where(function ($builder) use ($q): void {
                $builder->where('description', 'like', "%{$q}%")
                    ->orWhere('action', 'like', "%{$q}%")
                    ->orWhereHas('admin', fn ($adminQuery) => $adminQuery->where('name', 'like', "%{$q}%"));
            });
        }

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['admin_id'])) {
            $query->where('admin_id', $filters['admin_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', \Carbon\Carbon::parse($filters['date_from'])->startOfDay());
        }

        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', \Carbon\Carbon::parse($filters['date_to'])->endOfDay());
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Distinct action values actually present in the log, for the filter
     * dropdown — self-maintaining as new action types get added elsewhere,
     * rather than a hardcoded list that silently drifts out of date.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    public function distinctActions(): \Illuminate\Support\Collection
    {
        return AdminActionLog::query()->distinct()->orderBy('action')->pluck('action');
    }

    /**
     * Admins who have at least one logged action, for the filter dropdown.
     *
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function adminsWithLogs(): \Illuminate\Support\Collection
    {
        $adminIds = AdminActionLog::query()->whereNotNull('admin_id')->distinct()->pluck('admin_id');

        return User::query()->whereIn('id', $adminIds)->orderBy('name')->get(['id', 'name']);
    }
}
