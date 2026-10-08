<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Swaps a model's URL-facing identifier from its sequential auto-increment
 * id to an unguessable random string, without touching the id itself or any
 * foreign key that points at it. Every route(...) call that already passes
 * a model instance (the pattern used throughout this app) keeps working
 * unchanged, because Laravel builds URLs and resolves route model binding
 * through getRouteKeyName(), so this one override covers both.
 *
 * What actually protects these resources is the permission check, not the
 * hidden id. Every sensitive action already confirms who owns the trip or
 * conversation before doing anything. This only removes the smaller
 * information leak of a sequential id (row-count guessing) and adds a
 * defense-in-depth layer in case an authorization check ever regresses.
 */
trait HasPublicId
{
    public static function bootHasPublicId(): void
    {
        static::creating(function ($model): void {
            if (empty($model->public_id)) {
                $model->public_id = static::generateUniquePublicId();
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    protected static function generateUniquePublicId(): string
    {
        do {
            $candidate = Str::random(26);
        } while (static::query()->where('public_id', $candidate)->exists());

        return $candidate;
    }
}
