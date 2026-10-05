<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
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
        'project_id', 'unit_id', 'assessment_id', 'component_name', 'location',
        'defect_description', 'rectification_notes', 'photo_path', 'severity', 'status',
        'contractor_notified_at',
    ];

    protected $casts = [
        'contractor_notified_at' => 'datetime',
    ];

    /**
     * Photos per record:
     * - 'photos': original defect evidence taken during inspection (Before)
     * - 'rectification_photos': repair proof uploaded by contractor/team (After)
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos');
        $this->addMediaCollection('rectification_photos');
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

    public function getLocalPhotoPathAttribute(): ?string
    {
        if ($this->hasMedia('photos')) {
            $media = $this->getFirstMedia('photos');
            if ($media && file_exists($media->getPath())) {
                return $media->getPath();
            }
        }

        if ($this->photo_path) {
            $path = storage_path('app/public/' . $this->photo_path);
            if (file_exists($path)) {
                return $path;
            }
            $publicPath = public_path('storage/' . $this->photo_path);
            if (file_exists($publicPath)) {
                return $publicPath;
            }
        }

        return null;
    }

    /**
     * Every photo on this defect as a browser-reachable URL, oldest first.
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

    /**
     * Every photo as a base64 data URI, for dompdf — which has no network access
     * and so cannot fetch an R2 URL. Big, but the forms downscale before upload.
     *
     * @return Collection<int, string>
     */
    public function getPhotosBase64Attribute(): Collection
    {
        if ($this->hasMedia('photos')) {
            return $this->getMedia('photos')
                ->map(fn (Media $media) => $this->mediaDataUri($media))
                ->filter()
                ->values();
        }

        return collect([$this->legacyPhotoDataUri()])->filter()->values();
    }

    public function getPhotoBase64Attribute(): ?string
    {
        return $this->photos_base64->first();
    }

    public function getBeforePhotosAttribute(): Collection
    {
        return $this->getMedia('photos');
    }

    public function getAfterPhotosAttribute(): Collection
    {
        return $this->getMedia('rectification_photos');
    }

    public function getRectificationPhotoUrlAttribute(): ?string
    {
        if ($this->hasMedia('rectification_photos')) {
            return $this->getFirstMediaUrl('rectification_photos');
        }
        return null;
    }

    public function getRectificationPhotoUrlsAttribute(): Collection
    {
        if ($this->hasMedia('rectification_photos')) {
            return $this->getMedia('rectification_photos')->map(fn (Media $media) => $media->getUrl())->values();
        }

        return collect();
    }

    public function getRectificationPhotosBase64Attribute(): Collection
    {
        if ($this->hasMedia('rectification_photos')) {
            return $this->getMedia('rectification_photos')
                ->map(fn (Media $media) => $this->mediaDataUri($media))
                ->filter()
                ->values();
        }

        return collect();
    }

    public function getHasRectificationAttribute(): bool
    {
        return $this->hasMedia('rectification_photos') || !empty($this->rectification_notes);
    }

    /** Reads one media item off its own disk (R2 in production), local copy as backstop. */
    private function mediaDataUri(Media $media): ?string
    {
        try {
            $contents = Storage::disk($media->disk)->get($media->getPathRelativeToRoot());
            if ($contents !== null) {
                return 'data:' . ($media->mime_type ?: 'image/jpeg') . ';base64,' . base64_encode($contents);
            }
        } catch (\Throwable $e) {
            // Fall through to the local copy, if this deployment has one.
        }

        $path = $media->getPath();

        return file_exists($path) ? $this->fileDataUri($path) : null;
    }

    /** Pre-media-library rows still carry a bare `photo_path` on some disk. */
    private function legacyPhotoDataUri(): ?string
    {
        if ($path = $this->local_photo_path) {
            return $this->fileDataUri($path);
        }

        if ($this->photo_path) {
            try {
                $disk = Storage::disk(config('filesystems.default'));
                if ($disk->exists($this->photo_path)) {
                    return 'data:' . $this->mimeForExtension($this->photo_path)
                        . ';base64,' . base64_encode($disk->get($this->photo_path));
                }
            } catch (\Throwable $e) {
                // No usable copy found; fall through to null.
            }
        }

        return null;
    }

    private function fileDataUri(string $path): ?string
    {
        if (! file_exists($path)) {
            return null;
        }

        return 'data:' . $this->mimeForExtension($path) . ';base64,' . base64_encode(file_get_contents($path));
    }

    private function mimeForExtension(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png'  => 'image/png',
            'webp' => 'image/webp',
            'gif'  => 'image/gif',
            default => 'image/jpeg',
        };
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function assessment()
    {
        return $this->belongsTo(ComponentAssessment::class, 'assessment_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
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
        if ($user->role === 'admin') {
            return $query;
        }

        return $query->whereHas('project', fn ($q) => $q->visibleTo($user));
    }
}
