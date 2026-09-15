<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ComponentAssessment extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'project_id', 'sample_id', 'external_sample_id', 'arch_sample_id', 'component_code', 'na',
        'overall_sample_status', 'photo_path', 'remarks',
    ];

    protected $casts = [
        'na' => 'boolean',
    ];

    /**
     * Up to three photos per record — the ceiling is enforced at validation
     * (AttachesPhotos::photoRules) so an over-limit upload is refused outright
     * rather than silently pushing the oldest evidence out of the collection.
     *
     * No conversions are registered on purpose. Nothing in the app renders a
     * derived size, and generating them would make every upload download the
     * original back from R2, resize it, and push two more objects — expensive
     * work inside a serverless request. Register them here if a thumbnail is
     * ever actually needed, and give the queue a worker.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos');
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if ($this->hasMedia('photos')) {
            return $this->getFirstMediaUrl('photos');
        }
        return $this->photo_path ? Storage::url($this->photo_path) : null;
    }

    /**
     * Kept for callers that ask for a thumbnail — there is no `thumb`
     * conversion, so this is the original photo (see registerMediaCollections).
     */
    public function getThumbUrlAttribute(): ?string
    {
        return $this->photo_url;
    }

    /**
     * Every photo on this assessment as a browser-reachable URL, oldest first.
     *
     * @return Collection<int, string>
     */
    public function getPhotoUrlsAttribute(): Collection
    {
        if ($this->hasMedia('photos')) {
            return $this->getMedia('photos')->map(fn (Media $media) => $media->getUrl())->values();
        }

        return collect([$this->photo_url])->filter()->values();
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function sample()
    {
        return $this->belongsTo(ProjectSample::class, 'sample_id');
    }

    public function externalSample()
    {
        return $this->belongsTo(ExternalSample::class, 'external_sample_id');
    }

    public function archSample()
    {
        return $this->belongsTo(ArchExternalSample::class, 'arch_sample_id');
    }

    public function defect()
    {
        return $this->hasOne(Defect::class, 'assessment_id');
    }

    public function answers()
    {
        return $this->hasMany(AssessmentAnswer::class);
    }
}
