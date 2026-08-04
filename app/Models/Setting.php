<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'label', 'description', 'unit', 'type'];

    private static array $cache = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        if (empty(self::$cache)) {
            try {
                self::$cache = static::pluck('value', 'key')->toArray();
            } catch (\Throwable) {
                // Table may not exist yet during migrations
                return $default;
            }
        }

        return self::$cache[$key] ?? $default;
    }

    public static function clearCache(): void
    {
        self::$cache = [];
    }

    public static function defaults(): array
    {
        return [
            [
                'key'         => 'rating_baik',
                'value'       => '85',
                'label'       => 'GOOD Rating Threshold',
                'description' => 'Projects scoring at or above this value are rated GOOD.',
                'unit'        => 'pts',
                'type'        => 'number',
            ],
            [
                'key'         => 'rating_sederhana',
                'value'       => '70',
                'label'       => 'MODERATE Rating Threshold',
                'description' => 'Projects scoring at or above this value (but below GOOD) are rated MODERATE.',
                'unit'        => 'pts',
                'type'        => 'number',
            ],
            [
                'key'         => 'default_locations',
                'value'       => '["Living Room","Service Area","Passageway","Bedroom 1","Bedroom 2","Bedroom 3","Bathroom"]',
                'label'       => 'Default Sample Locations',
                'description' => 'Pre-filled location names assigned to samples when a new project is created.',
                'unit'        => null,
                'type'        => 'json',
            ],
        ];
    }
}
