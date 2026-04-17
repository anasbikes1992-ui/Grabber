<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformConfig extends Model
{
    protected $fillable = ['category', 'key', 'value', 'type', 'is_sensitive'];

    protected $casts = ['is_sensitive' => 'boolean'];

    public static function get(string $category, string $key, mixed $default = null): mixed
    {
        $record = static::where('category', $category)->where('key', $key)->first();
        if (!$record) {
            return $default;
        }
        return match($record->type) {
            'boolean' => filter_var($record->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $record->value,
            'float'   => (float) $record->value,
            'json'    => json_decode($record->value, true),
            default   => $record->value,
        };
    }
}
