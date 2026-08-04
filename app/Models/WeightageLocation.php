<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeightageLocation extends Model
{
    protected $table = 'weightage_locations';

    protected $fillable = ['building_category', 'principal_pct', 'service_pct', 'circulation_pct'];

    protected $casts = [
        'principal_pct'   => 'decimal:2',
        'service_pct'     => 'decimal:2',
        'circulation_pct' => 'decimal:2',
    ];

    public static function forCategory(string $category): self
    {
        return static::where('building_category', $category)->firstOrFail();
    }
}
