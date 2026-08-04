<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeightageArchitecturalElement extends Model
{
    protected $fillable = [
        'component_code', 'name', 'group', 'breakdown_pct',
        'scoring_mode', 'optional', 'sort_order',
    ];

    protected $casts = [
        'breakdown_pct' => 'decimal:2',
        'optional'      => 'boolean',
        'sort_order'    => 'integer',
    ];

    public static function ordered()
    {
        return static::orderBy('sort_order')->get()->keyBy('component_code');
    }
}
