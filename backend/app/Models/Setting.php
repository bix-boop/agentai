<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'is_encrypted',
        'is_public',
        'validation_rules',
        'options',
        'sort_order',
    ];

    protected $casts = [
        'is_encrypted' => 'boolean',
        'is_public' => 'boolean',
        'validation_rules' => 'array',
        'options' => 'array',
    ];

    /**
     * Get a setting value by key
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember("setting:{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            
            if (!$setting) {
                return $default;
            }

            $value = $setting->value;

            // Decrypt if encrypted
            if ($setting->is_encrypted && !empty($value)) {
                try {
                    $value = Crypt::decryptString($value);
                } catch (\Exception $e) {
                    return $default;
                }
            }

            // Cast to appropriate type
            return match ($setting->type) {
                'boolean' => (bool) $value,
                'integer' => (int) $value,
                'float' => (float) $value,
                'json' => json_decode($value, true),
                default => $value,
            };
        });
    }

    /**
     * Set a setting value
     */
    public static function set(string $key, $value, array $options = []): void
    {
        $setting = static::firstOrNew(['key' => $key]);
        
        // Handle encryption
        if ($setting->is_encrypted || ($options['encrypt'] ?? false)) {
            $value = Crypt::encryptString($value);
            $setting->is_encrypted = true;
        }

        $setting->fill([
            'value' => is_array($value) || is_object($value) ? json_encode($value) : $value,
            'type' => $options['type'] ?? 'string',
            'group' => $options['group'] ?? 'general',
            'label' => $options['label'] ?? $key,
            'description' => $options['description'] ?? null,
            'is_public' => $options['public'] ?? false,
            'validation_rules' => $options['validation'] ?? null,
            'options' => $options['options'] ?? null,
            'sort_order' => $options['sort_order'] ?? 0,
        ]);

        $setting->save();

        // Clear cache
        Cache::forget("setting:{$key}");
    }

    /**
     * Get all public settings for frontend
     */
    public static function getPublic(): array
    {
        return Cache::remember('settings:public', 3600, function () {
            return static::where('is_public', true)
                ->get()
                ->mapWithKeys(function ($setting) {
                    return [$setting->key => static::get($setting->key)];
                })
                ->toArray();
        });
    }

    /**
     * Get settings by group
     */
    public static function getByGroup(string $group): array
    {
        return Cache::remember("settings:group:{$group}", 3600, function () use ($group) {
            return static::where('group', $group)
                ->orderBy('sort_order')
                ->get()
                ->mapWithKeys(function ($setting) {
                    return [$setting->key => static::get($setting->key)];
                })
                ->toArray();
        });
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache(): void
    {
        Cache::flush();
    }
}
