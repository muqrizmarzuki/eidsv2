<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Defect extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $fillable = [
        'project_id', 'assessment_id', 'component_name', 'location',
        'defect_description', 'photo_path', 'severity', 'status',
        'contractor_notified_at',
    ];

    protected $casts = [
        'contractor_notified_at' => 'datetime',
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

    public function assessment()
    {
        return $this->belongsTo(ComponentAssessment::class, 'assessment_id');
    }

    public function getSeverityLabelAttribute(): string
    {
        return match($this->severity) {
            'low'    => 'Low',
            'medium' => 'Medium',
            'high'   => 'High',
            default  => $this->severity,
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'OPEN'                 => 'Open',
            'IN_PROGRESS'          => 'In Progress',
            'PENDING_VERIFICATION' => 'Pending Verification',
            'RESOLVED'             => 'Resolved',
            default                => $this->status,
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            'OPEN'        => 'bg-red-100 text-red-700',
            'IN_PROGRESS' => 'bg-amber-100 text-amber-700',
            'PENDING_VERIFICATION' => 'bg-blue-100 text-blue-700',
            'RESOLVED'    => 'bg-emerald-100 text-emerald-700',
            default       => 'bg-gray-100 text-gray-700',
        };
    }

    public function scopeVisibleTo($query, User $user)
    {
        return $query->whereHas('project', fn ($q) => $q->visibleTo($user));
    }
}
