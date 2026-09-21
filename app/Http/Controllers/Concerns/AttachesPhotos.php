<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\HasMedia;

/**
 * Single entry point for putting uploaded inspection photos into media
 * library — which, in production, means Cloudflare R2 via the `r2` disk
 * (MEDIA_DISK). Nothing else in the app should write photos to a local disk:
 * the app runs serverless, so anything under storage/ dies with the instance.
 */
trait AttachesPhotos
{
    /** Photos one record may carry. */
    private const MAX_PHOTOS = 10;

    /** Per-photo ceiling in kilobytes. The forms downscale to fit before uploading. */
    private const MAX_PHOTO_KB = 3072;

    /**
     * Rules for a `photos[]` input. Pass the record being added to so the cap
     * counts what it already holds — an upload that would take it past three is
     * rejected outright rather than quietly pushing the oldest evidence out.
     */
    private function photoRules(?HasMedia $owner = null): array
    {
        $free = max(0, self::MAX_PHOTOS - ($owner?->getMedia('photos')->count() ?? 0));

        return [
            'photos'   => ['nullable', 'array', "max:{$free}"],
            'photos.*' => ['image', 'max:' . self::MAX_PHOTO_KB],
        ];
    }

    private function photoMessages(): array
    {
        return [
            'photos.max'   => 'A maximum of ' . self::MAX_PHOTOS . ' photos is allowed. Delete one before adding another.',
            'photos.*.max' => 'Each photo must be 3 MB or smaller.',
            'photos.*.image' => 'Each file must be an image.',
        ];
    }

    /**
     * Attaches uploaded photos to the `photos` collection of every given model.
     *
     * A PHP upload lives in a single request-scoped temp file (/tmp/phpXXXX) and
     * media library *moves* it onto the media disk — it unlinks the source once
     * the copy lands. A second `addMedia()` on the same upload would then find
     * nothing there and throw `FileDoesNotExist`, which is exactly what happened
     * when a FAILed assessment tried to attach the same photo to its defect.
     * `preservingOriginal()` copies instead of moving, so the temp file survives
     * every attachment; PHP deletes it when the request ends.
     *
     * @param  array<int, UploadedFile>  $photos
     */
    private function attachPhotos(array $photos, HasMedia ...$models): void
    {
        foreach ($photos as $photo) {
            foreach ($models as $model) {
                $model
                    ->addMedia($photo)
                    ->preservingOriginal()
                    ->toMediaCollection('photos');
            }
        }
    }
}
