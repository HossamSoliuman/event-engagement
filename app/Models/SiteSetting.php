<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SiteSetting extends Model
{
    protected $primaryKey = 'key';
    protected $keyType    = 'string';
    public $incrementing  = false;
    protected $fillable   = ['key', 'value'];

    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("site_setting_{$key}", 3600, function () use ($key, $default) {
            return static::where('key', $key)->value('value') ?? $default;
        });
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("site_setting_{$key}");
    }

    public static function privacyPolicyUrl(): ?string
    {
        $path = static::get('privacy_policy_path');
        return $path ? Storage::disk('public')->url($path) : null;
    }
}
