<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class AdminUserService
{
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

        $target->update([
            'role' => $data['role'],
            'is_active' => (bool) $data['is_active'],
        ]);

        return $target->refresh();
    }
}

