<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'updated_by',
    ];

    public static function get(string $key): ?string
    {
        return static::query()->where('key', $key)->value('value');
    }

    public static function set(string $key, string $value, ?int $updatedBy = null): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'updated_by' => $updatedBy]
        );
    }
}
