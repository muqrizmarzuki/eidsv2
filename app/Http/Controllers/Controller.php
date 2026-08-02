<?php

namespace App\Http\Controllers;

use App\Models\Defect;
use App\Models\Project;

abstract class Controller
{
    protected function guardProjectVisible(Project $project): void
    {
        abort_unless(Project::visibleTo(auth()->user())->whereKey($project->id)->exists(), 403);
    }

    protected function guardDefectVisible(Defect $defect): void
    {
        abort_unless(Defect::visibleTo(auth()->user())->whereKey($defect->id)->exists(), 403);
    }
}
