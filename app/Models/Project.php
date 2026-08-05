<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_no', 'project_name', 'location', 'developer_name',
        'contractor_name', 'building_type', 'building_category',
        'car_park_present', 'apron_drain_present',
        'total_units', 'floor_area_sqm',
        'calculated_samples', 'overall_score', 'status', 'created_by', 'assigned_to',
        'assigned_contractor_id',
    ];

    protected $casts = [
        'floor_area_sqm'       => 'decimal:2',
        'overall_score'        => 'decimal:2',
        'total_units'          => 'integer',
        'calculated_samples'   => 'integer',
        'car_park_present'     => 'boolean',
        'apron_drain_present'  => 'boolean',
    ];

    public function samples()
    {
        return $this->hasMany(ProjectSample::class);
    }

    public function assessments()
    {
        return $this->hasMany(ComponentAssessment::class);
    }

    public function qpDeclarations()
    {
        return $this->hasMany(QpDeclaration::class);
    }

    public function externalElementSettings()
    {
        return $this->hasMany(ProjectExternalElement::class);
    }

    public function externalSamples()
    {
        return $this->hasMany(ExternalSample::class);
    }

    public function archExternalSamples()
    {
        return $this->hasMany(ArchExternalSample::class);
    }

    /**
     * Annex C element codes this project has at least one instance of (§5/§6
     * — project-setup quantity, defaults to 1 if ExternalElement::default_present
     * else 0). A project can have multiple instances of the same element (e.g.
     * 3 playgrounds) — see externalElementQuantity().
     */
    public function activeExternalElementCodes(): array
    {
        return ExternalElement::ordered()
            ->filter(fn ($el) => $this->externalElementQuantity($el->element_code) > 0)
            ->keys()
            ->all();
    }

    public function externalElementPresent(string $elementCode): bool
    {
        return $this->externalElementQuantity($elementCode) > 0;
    }

    /**
     * How many physical instances of this Annex C element the project has
     * (e.g. 3 separate playgrounds) — each instance gets its own full set of
     * Table 6 sample sections. Defaults to 1 if the element's registry entry
     * defaults to present, else 0, until the project customizes it.
     */
    public function externalElementQuantity(string $elementCode): int
    {
        $override = $this->externalElementSettings->firstWhere('element_code', $elementCode);
        if ($override) return $override->quantity;

        $el = ExternalElement::ordered()->get($elementCode);
        return $el && $el->default_present ? 1 : 0;
    }

    /**
     * True once every sample unit for every present external element has been
     * assessed — mirrors inspection_progress but for Annex C, since External
     * Works samples aren't generated until an element is toggled present.
     */
    public function externalInspectionComplete(): bool
    {
        $presentCodes = $this->activeExternalElementCodes();
        if (empty($presentCodes)) return true;

        $totalSamples    = $this->externalSamples()->whereIn('element_code', $presentCodes)->count();
        $assessedSamples = $this->assessments()->whereNotNull('external_sample_id')
            ->whereIn('component_code', $presentCodes)->count();

        return $totalSamples > 0 && $assessedSamples >= $totalSamples;
    }

    /**
     * All architectural component codes scored into Table 2 — used for
     * weightage redistribution (ScoringService::redistributedWeights()).
     * Includes both 'room' and 'building' sampling-scope components; excludes
     * declaration-scored items and whichever optional elements (Car Park,
     * Apron/Drain) this project has flagged as absent.
     */
    public function activeComponentCodes(): array
    {
        return WeightageArchitecturalElement::ordered()
            ->reject(fn ($el) => $el->scoring_mode === 'declaration')
            ->reject(fn ($el) => $el->optional && !$this->elementPresent($el->component_code))
            ->keys()
            ->all();
    }

    /**
     * The subset of activeComponentCodes() inspected at internal-finish room
     * samples (Floor, Wall, Ceiling, Door, Window, Fixtures) — excludes Roof/
     * External Wall/Apron/Car Park, which are Table 3's "building" sampling
     * scope and never belong to a specific room.
     */
    public function roomBasedComponentCodes(): array
    {
        $registry = WeightageArchitecturalElement::ordered();
        return array_values(array_filter(
            $this->activeComponentCodes(),
            fn ($code) => $registry[$code]->sampling_scope === 'room'
        ));
    }

    /**
     * The subset of activeComponentCodes() sampled at the building level
     * (Roof, External Wall, Apron/Drain, Car Park) per Table 3 — see
     * ArchExternalSample.
     */
    public function buildingBasedComponentCodes(): array
    {
        $registry = WeightageArchitecturalElement::ordered();
        return array_values(array_filter(
            $this->activeComponentCodes(),
            fn ($code) => $registry[$code]->sampling_scope === 'building'
        ));
    }

    /**
     * Everything the inspector walks through per room sample: the room-scoped
     * architectural components plus M&E Fittings (Annex B) — assessed at the
     * same sample locations per Table 5's note, but scored under its own
     * Table 1 weightage bucket rather than Table 2's, so it's kept out of
     * activeComponentCodes()/redistributedWeights().
     */
    public function inspectableComponentCodes(): array
    {
        return array_merge($this->roomBasedComponentCodes(), ['ME_FITTING']);
    }

    public function elementPresent(string $componentCode): bool
    {
        return match ($componentCode) {
            'A10_CAR_PARK'     => (bool) $this->car_park_present,
            'A9_APRON_DRAIN'   => (bool) $this->apron_drain_present,
            default            => true,
        };
    }

    /**
     * Table 3's sample count for a "building"-scope component: Roof/External
     * Wall get 50% of the project's units treated as sections (min 4); Apron/
     * Drain and Car Park get a flat minimum of 2 length-sections. The standard
     * doesn't give a sharper formula than "50%, min 4 sections" for Roof/Wall,
     * so this is a documented, admin-adjustable approximation, not a literal
     * GFA-style formula like Table 3's internal-finishes row.
     */
    public function buildingSampleCountFor(string $componentCode): int
    {
        return match ($componentCode) {
            'A7_ROOF', 'A8_EXT_WALL' => max(4, (int) ceil($this->total_units * 0.5)),
            'A9_APRON_DRAIN', 'A10_CAR_PARK' => 2,
            default => 1,
        };
    }

    /**
     * True once every building-level sample (Roof/External Wall/Apron/Car
     * Park, for whichever are present) has been assessed — mirrors
     * externalInspectionComplete() but for Table 3's "building" scope.
     */
    public function archExternalInspectionComplete(): bool
    {
        $codes = $this->buildingBasedComponentCodes();
        if (empty($codes)) return true;

        $totalSamples    = $this->archExternalSamples()->whereIn('component_code', $codes)->count();
        $assessedSamples = $this->assessments()->whereNotNull('arch_sample_id')
            ->whereIn('component_code', $codes)->count();

        return $totalSamples > 0 && $assessedSamples >= $totalSamples;
    }

    public function defects()
    {
        return $this->hasMany(Defect::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedInspector()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedContractor()
    {
        return $this->belongsTo(User::class, 'assigned_contractor_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'draf'               => 'Draft',
            'dalam_pemeriksaan'  => 'In Inspection',
            'selesai'            => 'Completed',
            default              => $this->status,
        };
    }

    public function getBuildingTypeLabelAttribute(): string
    {
        return match($this->building_type) {
            'teres'  => 'Terrace',
            'semi_d' => 'Semi-D',
            'banglo' => 'Bungalow',
            default  => $this->building_type,
        };
    }

    public function getRatingAttribute(): string
    {
        $score = (float) $this->overall_score;
        if ($score >= (float) setting('rating_baik', 85)) return 'GOOD';
        if ($score >= (float) setting('rating_sederhana', 70)) return 'MODERATE';
        return 'WEAK';
    }

    public function getInspectionProgressAttribute(): int
    {
        $total = $this->calculated_samples * count($this->inspectableComponentCodes());
        if ($total === 0) return 0;
        $done = $this->assessments()->count();
        return (int) min(100, round(($done / $total) * 100));
    }

    public function nextActionFor(User $user): array
    {
        $hasNamedLocations = $this->samples->contains(fn ($s) => !str_starts_with($s->location_name, 'Sample '));
        $inspectionStarted = $this->assessments()->exists();
        $inspectionDone    = $this->inspection_progress >= 100;
        $inspectedCount    = $this->samples->whereNotNull('pass_rate')->count();
        $totalSamples      = $this->samples->count();
        $openDefects       = $this->defects()->whereIn('status', ['OPEN', 'IN_PROGRESS'])->count();
        $pendingVerify     = $this->defects()->where('status', 'PENDING_VERIFICATION')->count();

        $waiting = fn (string $icon, string $text) => [
            'icon' => $icon, 'text' => $text, 'actionable' => false,
            'route' => null, 'params' => [], 'button_label' => null,
        ];
        $actionable = fn (string $icon, string $text, string $route, array $params, string $buttonLabel) => [
            'icon' => $icon, 'text' => $text, 'actionable' => true,
            'route' => $route, 'params' => $params, 'button_label' => $buttonLabel,
        ];

        if ($user->role === 'admin') {
            if (!$hasNamedLocations) {
                return $actionable('tune', 'Finish naming sample locations.', 'projects.samples', ['project' => $this], 'Configure Samples');
            }
            if (!$inspectionStarted) {
                $name = $this->assignedInspector->name ?? 'the assigned inspector';
                return $waiting('grid_on', "Waiting on Inspector {$name} to begin the Components Grid inspection.");
            }
            if (!$inspectionDone) {
                $name = $this->assignedInspector->name ?? 'the assigned inspector';
                return $waiting('grid_on', "Inspector {$name} is still inspecting: {$inspectedCount}/{$totalSamples} sample units done.");
            }
            if ($openDefects > 0 || $pendingVerify > 0) {
                $count = $openDefects + $pendingVerify;
                return $waiting('warning', "{$count} defect(s) still open, waiting on Contractor & Inspector verification.");
            }
            return $actionable('analytics', 'Inspection complete, ready to generate the signed E-IDS PDF.', 'reports.show', ['project' => $this], 'Generate PDF Report');
        }

        if ($user->role === 'inspector') {
            if ($pendingVerify > 0) {
                return $actionable(
                    'fact_check', "{$pendingVerify} defect(s) awaiting your verification.",
                    'defects.index', ['project_id' => $this->id, 'status' => 'PENDING_VERIFICATION'], 'Review Defects'
                );
            }
            if (!$inspectionStarted) {
                return $actionable('grid_on', 'Start the Components Grid inspection.', 'projects.components', ['project' => $this], 'Start Inspecting Now');
            }
            if (!$inspectionDone) {
                return $actionable(
                    'grid_on', "Continue: {$inspectedCount}/{$totalSamples} sample units done.",
                    'projects.components', ['project' => $this], 'Continue Inspecting'
                );
            }
            if ($openDefects > 0) {
                return $waiting('warning', "{$openDefects} defect(s) still open. Notify the Contractor or await their fix.");
            }
            return $actionable('verified', 'All defects resolved, ready to sign off.', 'projects.show', ['project' => $this], 'Sign Off Project');
        }

        return $waiting('info', '');
    }

    public function scopeVisibleTo($query, User $user)
    {
        return match ($user->role) {
            'admin'      => $query,
            'inspector'  => $query->where('assigned_to', $user->id),
            'contractor' => $query->where('assigned_contractor_id', $user->id),
            default      => $query->whereRaw('1 = 0'),
        };
    }
}
