<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectSample extends Model
{
    protected $fillable = [
        'project_id', 'sample_index', 'location_name', 'location_type',
    ];

    protected $appends = ['pass_rate'];

    private const SERVICE_KEYWORDS     = ['bathroom', 'kitchen', 'service area', 'yard', 'pantry', 'linen', 'toilet', 'balcony'];
    private const CIRCULATION_KEYWORDS = ['passageway', 'corridor', 'lobby', 'staircase', 'entrance', 'terrace', 'hallway'];

    /**
     * Guess a location's Table 4 category (Principal/Service/Circulation) from
     * its name, per CIS 7:2021's example lists. Defaults to Principal — the
     * broadest bucket — when nothing matches.
     */
    public static function guessLocationType(string $locationName): string
    {
        $name = strtolower($locationName);

        foreach (self::SERVICE_KEYWORDS as $kw) {
            if (str_contains($name, $kw)) return 'service';
        }
        foreach (self::CIRCULATION_KEYWORDS as $kw) {
            if (str_contains($name, $kw)) return 'circulation';
        }
        return 'principal';
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assessments()
    {
        return $this->hasMany(ComponentAssessment::class, 'sample_id');
    }

    public function getPassRateAttribute(): ?float
    {
        if ($this->assessments->isEmpty()) return null;
        $pass  = $this->assessments->where('overall_sample_status', 'PASS')->count();
        $total = $this->assessments->count();
        return round(($pass / $total) * 100, 1);
    }
}
