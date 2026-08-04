<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeightageOverall extends Model
{
    protected $table = 'weightage_overall';

    protected $fillable = ['building_category', 'architectural_pct', 'me_pct', 'external_pct'];

    protected $casts = [
        'architectural_pct' => 'decimal:2',
        'me_pct'            => 'decimal:2',
        'external_pct'      => 'decimal:2',
    ];

    public static function forCategory(string $category): self
    {
        return static::where('building_category', $category)->firstOrFail();
    }
}
