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

    /**
     * Annex C element codes this project has flagged as present (§5/§6 —
     * project-setup toggle, defaults per ExternalElement::default_present).
     */
    public function activeExternalElementCodes(): array
    {
        $overrides = $this->externalElementSettings->keyBy('element_code');

        return ExternalElement::ordered()
            ->filter(fn ($el) => $overrides->has($el->element_code)
                ? $overrides[$el->element_code]->present
                : $el->default_present)
            ->keys()
            ->all();
    }

    public function externalElementPresent(string $elementCode): bool
    {
        return in_array($elementCode, $this->activeExternalElementCodes(), true);
    }

    /**
     * Component codes that go through the per-sample inspection grid — the
     * architectural registry minus declaration-scored items (QP declarations
     * aren't inspected per sample, see QpDeclarationController) and minus
     * whichever optional elements (Car Park, Apron/Drain) this project has
     * flagged as absent, per Table 2.
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
     * Everything the inspector actually walks through per sample: the
     * architectural components plus M&E Fittings (Annex B) — assessed at the
     * same sample locations per Table 5's note, but scored under its own
     * Table 1 weightage bucket rather than Table 2's, so it's kept out of
     * activeComponentCodes()/redistributedWeights().
     */
    public function inspectableComponentCodes(): array
    {
        return array_merge($this->activeComponentCodes(), ['ME_FITTING']);
    }

    public function elementPresent(string $componentCode): bool
    {
        return match ($componentCode) {
            'A10_CAR_PARK'     => (bool) $this->car_park_present,
            'A9_APRON_DRAIN'   => (bool) $this->apron_drain_present,
            default            => true,
        };
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
                return $waiting('grid_on', "Inspector {$name} is still inspecting — {$inspectedCount}/{$totalSamples} sample units done.");
            }
            if ($openDefects > 0 || $pendingVerify > 0) {
                $count = $openDefects + $pendingVerify;
                return $waiting('warning', "{$count} defect(s) still open — waiting on Contractor & Inspector verification.");
            }
            return $actionable('analytics', 'Inspection complete — ready to generate the signed G-IDS PDF.', 'reports.show', ['project' => $this], 'Generate PDF Report');
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
                    'grid_on', "Continue — {$inspectedCount}/{$totalSamples} sample units done.",
                    'projects.components', ['project' => $this], 'Continue Inspecting'
                );
            }
            return $actionable('task_alt', 'Inspection complete — notify your Admin.', 'projects.score', ['project' => $this], 'View G-IDS Score');
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
