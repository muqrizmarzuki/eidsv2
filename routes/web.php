<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BuildingExternalController;
use App\Http\Controllers\ChecklistItemController;
use App\Http\Controllers\DefectController;
use App\Http\Controllers\ExternalInspectionController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\InspectionController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\QpDeclarationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// ── Health check (public, no auth) ────────────────────────────────────────────
Route::get('/db-check', function () {
    return response()->json([
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
    ], 200);
});

// ── Auth (guest only) ─────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/',      [AuthController::class, 'showLogin'])->name('login');
    Route::get('/login', [AuthController::class, 'showLogin']);
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// ── Authenticated routes ──────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {

    // Dashboard — everyone except Contractor (their surface is My Defects only)
    Route::middleware('role:admin,inspector')->group(function () {
        Route::get('/dashboard', [ProjectController::class, 'dashboard'])->name('dashboard');
    });

    // Projects — static routes FIRST to avoid {project} swallowing them
    Route::middleware('role:admin,inspector')->group(function () {
        Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
        Route::post('/projects',       [ProjectController::class, 'store'])->name('projects.store');
    });

    Route::middleware('role:admin,inspector')->group(function () {
        Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');

        // Projects with {project} parameter
        Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');

        // Score & summary
        Route::get('/projects/{project}/score',   [InspectionController::class, 'score'])->name('projects.score');
        Route::get('/projects/{project}/summary', [InspectionController::class, 'summary'])->name('projects.summary');
    });

    Route::middleware('role:admin')->group(function () {
        Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    });

    Route::middleware('role:admin,inspector')->group(function () {
        Route::post('/projects/{project}/complete', [ProjectController::class, 'markComplete'])->name('projects.complete');
        Route::post('/projects/{project}/defects/notify-contractor', [DefectController::class, 'notifyContractor'])->name('projects.defects.notify-contractor');
    });

    Route::middleware('role:admin,inspector')->group(function () {
        Route::get('/projects/{project}/edit',  [ProjectController::class, 'edit'])->name('projects.edit');
        Route::put('/projects/{project}',       [ProjectController::class, 'update'])->name('projects.update');

        // Sample generation
        Route::get('/projects/{project}/samples',  [ProjectController::class, 'samples'])->name('projects.samples');
        Route::post('/projects/{project}/samples', [ProjectController::class, 'storeSamples'])->name('projects.samples.store');

        // Inspection workflow
        Route::get('/projects/{project}/components',                 [InspectionController::class, 'components'])->name('projects.components');
        Route::get('/projects/{project}/inspect/{sample}',           [InspectionController::class, 'inspect'])->name('projects.inspect');
        Route::post('/projects/{project}/inspect/{sample}',          [InspectionController::class, 'storeAssessment'])->name('projects.inspect.store');

        Route::post('/projects/{project}/qp-declarations', [QpDeclarationController::class, 'update'])->name('projects.qp-declarations.update');

        // External works (Annex C)
        Route::get('/projects/{project}/external',                       [ExternalInspectionController::class, 'elements'])->name('projects.external');
        Route::post('/projects/{project}/external/{elementCode}/add',                [ExternalInspectionController::class, 'addInstance'])->name('projects.external.add');
        Route::delete('/projects/{project}/external/{elementCode}/{instance}',       [ExternalInspectionController::class, 'removeInstance'])->name('projects.external.remove');
        Route::get('/projects/{project}/external/{sample}/inspect',      [ExternalInspectionController::class, 'inspect'])->name('projects.external.inspect');
        Route::post('/projects/{project}/external/{sample}/inspect',     [ExternalInspectionController::class, 'storeAssessment'])->name('projects.external.inspect.store');

        // Building-level architectural components (Table 3: Roof, External Wall, Apron/Drain, Car Park)
        Route::get('/projects/{project}/building-external',                  [BuildingExternalController::class, 'elements'])->name('projects.building-external');
        Route::get('/projects/{project}/building-external/{sample}/inspect', [BuildingExternalController::class, 'inspect'])->name('projects.building-external.inspect');
        Route::post('/projects/{project}/building-external/{sample}/inspect', [BuildingExternalController::class, 'storeAssessment'])->name('projects.building-external.inspect.store');
    });

    // Defects — static create route before parameterized routes. Index is open to
    // all 3 roles (Contractor's "My Defects" reuses it, scoped by Defect::visibleTo()).
    Route::get('/defects', [DefectController::class, 'index'])->name('defects.index');

    Route::middleware('role:admin,inspector')->group(function () {
        Route::get('/defects/create',           [DefectController::class, 'create'])->name('defects.create');
        Route::post('/defects',                 [DefectController::class, 'store'])->name('defects.store');
        Route::get('/defects/{defect}/edit',    [DefectController::class, 'edit'])->name('defects.edit');
        Route::put('/defects/{defect}',         [DefectController::class, 'update'])->name('defects.update');
        Route::delete('/defects/{defect}',      [DefectController::class, 'destroy'])->name('defects.destroy');
    });

    Route::middleware('role:admin,inspector,contractor')->group(function () {
        Route::post('/defects/{defect}/advance', [DefectController::class, 'advanceStatus'])->name('defects.advance');
    });

    // Reports — Contractor excluded (no Dashboard/Projects/Reports access)
    Route::middleware('role:admin,inspector')->group(function () {
        Route::get('/reports',               [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/{project}',     [ReportController::class, 'show'])->name('reports.show');
        Route::get('/reports/{project}/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
    });

    // Users & Settings — admin only
    Route::middleware('role:admin')->group(function () {
        Route::get('/users',             [UserController::class, 'index'])->name('users.index');
        Route::get('/users/create',      [UserController::class, 'create'])->name('users.create');
        Route::post('/users',            [UserController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}',      [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}',   [UserController::class, 'destroy'])->name('users.destroy');

        Route::get('/settings',  [SettingController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
        Route::post('/settings/weightage', [SettingController::class, 'updateWeightage'])->name('settings.weightage.update');

        Route::get('/checklist-items',                    [ChecklistItemController::class, 'index'])->name('checklist-items.index');
        Route::post('/checklist-items/upload-image',      [ChecklistItemController::class, 'uploadGuideImage'])->name('checklist-items.upload-image');
        Route::get('/checklist-items/{checklistItem}/edit', [ChecklistItemController::class, 'edit'])->name('checklist-items.edit');
        Route::put('/checklist-items/{checklistItem}',      [ChecklistItemController::class, 'update'])->name('checklist-items.update');
    });
});
