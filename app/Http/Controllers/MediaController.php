<?php

namespace App\Http\Controllers;

use App\Models\ComponentAssessment;
use App\Models\Defect;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Deletes a single photo. Assessments and the defects they raise hold their own
 * copies of an upload, so this removes exactly the one that was clicked and
 * leaves the mirrored copy on the other record alone.
 */
class MediaController extends Controller
{
    public function destroy(Request $request, Media $media)
    {
        abort_unless(in_array($media->collection_name, ['photos', 'rectification_photos'], true), 404);

        // Visibility is a property of the record the photo hangs off, not of the
        // media row — so authorise through the owner, never the media id.
        $owner = $media->model;

        match (true) {
            $owner instanceof ComponentAssessment => $this->guardProjectVisible($owner->project),
            $owner instanceof Defect              => $this->guardDefectVisible($owner),
            default                               => abort(404),
        };

        // Contractors can only delete rectification photos on defects that are not yet resolved
        if (auth()->user()->role === 'contractor') {
            abort_unless($owner instanceof Defect && $media->collection_name === 'rectification_photos', 403);
            abort_if($owner->status === 'RESOLVED', 403, 'Cannot delete photos from resolved defects.');
        }

        $media->delete();

        if ($request->expectsJson()) {
            return response()->noContent();
        }

        return back()->with('success', 'Photo deleted.');
    }
}
