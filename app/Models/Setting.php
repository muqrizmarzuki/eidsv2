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
                'key'         => 'sample_divisor',
                'value'       => '60',
                'label'       => 'Sample Divisor',
                'description' => 'GFA is divided by this to calculate the number of required samples (N = ceil(GFA ÷ divisor)).',
                'unit'        => 'm²',
                'type'        => 'number',
            ],
            [
                'key'         => 'levelling_max_mm',
                'value'       => '3.0',
                'label'       => 'Max Levelling Tolerance',
                'description' => 'A levelling measurement above this value will be marked FAIL.',
                'unit'        => 'mm',
                'type'        => 'number',
            ],
            [
                'key'         => 'joint_max_mm',
                'value'       => '1.0',
                'label'       => 'Max Joint / Gap Tolerance',
                'description' => 'A joint/gap measurement above this value will be marked FAIL.',
                'unit'        => 'mm',
                'type'        => 'number',
            ],
            [
                'key'         => 'me_score',
                'value'       => '2.00',
                'label'       => 'M&E Fixed Score',
                'description' => 'Fixed points added for Mechanical & Electrical work (always awarded).',
                'unit'        => 'pts',
                'type'        => 'number',
            ],
            [
                'key'         => 'external_score',
                'value'       => '11.80',
                'label'       => 'External Work Fixed Score',
                'description' => 'Fixed points added for external / landscape work (always awarded).',
                'unit'        => 'pts',
                'type'        => 'number',
            ],
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
