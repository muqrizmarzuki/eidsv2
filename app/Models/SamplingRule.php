<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SamplingRule extends Model
{
    protected $fillable = ['building_category', 'gfa_divisor', 'min_samples', 'max_samples'];

    protected $casts = [
        'gfa_divisor' => 'decimal:2',
        'min_samples' => 'integer',
        'max_samples' => 'integer',
    ];

    public static function forCategory(string $category): self
    {
        return static::where('building_category', $category)->firstOrFail();
    }

    public function sampleCountFor(float $gfaSqm): int
    {
        $raw = (int) ceil($gfaSqm / (float) $this->gfa_divisor);
        return max($this->min_samples, min($this->max_samples, $raw));
    }
}
