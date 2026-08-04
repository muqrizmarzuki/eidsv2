<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalElement extends Model
{
    protected $fillable = ['element_code', 'name', 'group', 'default_present', 'sample_count', 'sort_order'];

    protected $casts = [
        'default_present' => 'boolean',
        'sample_count'     => 'integer',
        'sort_order'       => 'integer',
    ];

    public static function ordered()
    {
        return static::orderBy('sort_order')->get()->keyBy('element_code');
    }
}
