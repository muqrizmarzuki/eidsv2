<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectSample extends Model
{
    protected $fillable = [
        'project_id', 'unit_reference', 'sample_index', 'location_name', 'location_type',
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

    /**
     * Distributes $count samples across $totalUnits, walking each unit's rooms
     * (per $roomNames, cycling if a unit needs more rooms than the list has)
     * before moving to the next unit — matching how an inspector actually
     * walks a site, and CIS 7:2021 §1.7's "distributed as uniformly as
     * possible throughout the project" requirement.
     *
     * @return array<int, array{unit_reference: string, location_name: string}>
     */
    public static function distributeAcrossUnits(int $count, array $roomNames, int $totalUnits): array
    {
        $roomNames  = array_values($roomNames) ?: ['Sample'];
        $roomCount  = count($roomNames);
        $totalUnits = max(1, $totalUnits);
        $plan       = [];

        for ($i = 0; $i < $count; $i++) {
            $roomIdx = $i % $roomCount;
            $unitIdx = intdiv($i, $roomCount) % $totalUnits;
            $plan[]  = [
                'unit_reference' => 'Unit #' . ($unitIdx + 1),
                'location_name'  => $roomNames[$roomIdx] ?? ('Room ' . ($roomIdx + 1)),
            ];
        }

        return $plan;
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
