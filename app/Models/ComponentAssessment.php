<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ComponentAssessment extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'project_id', 'sample_id', 'component_code', 'component_name', 'weightage',
        'finishing_status', 'hollow_status',
        'levelling_mm', 'levelling_status',
        'joint_mm', 'joint_status',
        'crack_status', 'overall_sample_status',
        'photo_path', 'remarks',
    ];

    protected $casts = [
        'levelling_mm' => 'decimal:2',
        'joint_mm'     => 'decimal:2',
        'weightage'    => 'decimal:2',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->sharpen(10);

        $this->addMediaConversion('preview')
            ->width(800)
            ->height(600);
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if ($this->hasMedia('photos')) {
            return $this->getFirstMediaUrl('photos');
        }
        return $this->photo_path ? Storage::url($this->photo_path) : null;
    }

    public function getThumbUrlAttribute(): ?string
    {
        if ($this->hasMedia('photos')) {
            return $this->getFirstMediaUrl('photos', 'thumb');
        }
        return $this->photo_path ? Storage::url($this->photo_path) : null;
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function sample()
    {
        return $this->belongsTo(ProjectSample::class, 'sample_id');
    }

    public function defect()
    {
        return $this->hasOne(Defect::class, 'assessment_id');
    }
}
