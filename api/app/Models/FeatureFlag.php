<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureFlag extends Model
{
    protected $fillable = ['key', 'enabled', 'description'];

    protected $casts = ['enabled' => 'boolean'];

    public static function enabled(string $key): bool
    {
        return (bool) static::where('key', $key)->value('enabled');
    }
}
