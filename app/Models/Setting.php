<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function valueOf(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting.$key", 300, fn () => static::query()->where('key', $key)->value('value') ?? $default);
    }

    public static function bool(string $key, bool $default = false): bool
    {
        return filter_var(static::valueOf($key, $default), FILTER_VALIDATE_BOOLEAN);
    }
}
