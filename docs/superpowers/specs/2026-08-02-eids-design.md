# E-IDS v2 — Implementation Design Spec

**Date:** 2026-08-02  
**Project:** Electronic Inspection Defect System (E-IDS)  
**Institution:** Politeknik Merlimau Melaka — Civil Engineering Dept  
**Stack:** Laravel 13.8, PHP 8.3, SQLite, Tailwind CSS v4, Alpine.js, dompdf

---

## 1. Architecture

**Approach:** Pure server-side Blade + Alpine.js (no Livewire, no SPA).

- Laravel handles all routing, validation, business logic, and rendering.
- Alpine.js handles client-side interactivity: PASS/FAIL auto-compute, photo preview, modals, toast notifications.
- Tailwind CSS v4 (via `@tailwindcss/vite`) for styling — no custom CSS build step.
- `barryvdh/laravel-dompdf` for PDF generation.
- Photos stored at `storage/app/public/photos/`, served via storage symlink.

**Layout structure:**
- `layouts/app.blade.php` — authenticated shell with dark-green sidebar + topbar
- `layouts/auth.blade.php` — minimal split-panel layout for login only
- All authenticated views extend `layouts/app.blade.php`

**UI theme (matching mockups):**
- Sidebar: `#00342b` (deep forest green), white text, active item highlight
- Main content: white / `#f8fafc` background
- Cards: white with `shadow-sm`, `rounded-xl`
- Accent: `#10b981` (emerald) for PASS, progress, active states
- Danger: `#ef4444` (red) for FAIL, open defects
- Warning: `#f59e0b` (amber) for MODERATE rating, IN_PROGRESS status
- Font: Hanken Grotesk (Google Fonts CDN)
- Icons: Google Material Symbols Outlined (CDN)
- Language: Malay labels in UI (matching mockups), English in code

---

## 2. Authentication & Roles

### Auth
- Session-based login using Laravel's built-in `Auth` facade.
- Custom login controller — no Breeze/Jetstream.
- Login page: split layout (dark green branding panel left, white form right).
- "Remember me" checkbox, "Lupa Kata Laluan?" link (non-functional placeholder for v2).

### Roles
Four roles stored in `users.role` (enum):

| Role | Value |
|------|-------|
| Admin / Super Admin | `admin` |
| Ketua Pemeriksa (Lead Auditor) | `lead_auditor` |
| Inspektor (Inspector) | `inspector` |
| Penyelia Tapak (Site Supervisor) | `supervisor` |

### Role Middleware
`App\Http\Middleware\RoleMiddleware` — checks `auth()->user()->role` against allowed roles per route group.

### Permission Matrix

| Feature | admin | lead_auditor | inspector | supervisor |
|---------|-------|-------------|-----------|------------|
| Dashboard | ✅ | ✅ | ✅ | ✅ |
| Projects: view list/detail | ✅ | ✅ | ✅ | ✅ |
| Projects: create/edit/delete | ✅ | ✅ | ✅ | ❌ |
| Sample generation | ✅ | ✅ | ✅ | ❌ |
| Run inspection | ✅ | ✅ | ✅ | ❌ |
| View scores & summary | ✅ | ✅ | ✅ | ✅ |
| Defects: view | ✅ | ✅ | ✅ | ✅ |
| Defects: toggle/manage | ✅ | ✅ | ✅ | ❌ |
| PDF reports | ✅ | ✅ | ✅ | ✅ |
| Users management | ✅ | ❌ | ❌ | ❌ |
| Settings | ✅ | ❌ | ❌ | ❌ |

---

## 3. Database Schema

### `users` (extends Laravel default)
Added columns:
- `name` — varchar 255
- `role` — enum: `admin`, `lead_auditor`, `inspector`, `supervisor` (default: `inspector`)
- `employee_id` — varchar 50, nullable (e.g. `INSP-2024-001`)

### `projects`
- `id` bigint PK auto-increment
- `project_no` varchar 255 unique (e.g. `PRJ-2026-001`)
- `project_name` varchar 255
- `location` varchar 255 (added — seen in mockups)
- `developer_name` varchar 255
- `contractor_name` varchar 255
- `building_type` enum: `teres`, `semi_d`, `banglo`
- `total_units` integer default 1
- `floor_area_sqm` decimal(8,2) default 0.00
- `calculated_samples` integer default 1
- `overall_score` decimal(5,2) default 0.00
- `status` enum: `draf`, `dalam_pemeriksaan`, `selesai` default `dalam_pemeriksaan`
- `created_by` bigint FK → users.id nullable
- `timestamps`

### `project_samples`
- `id` bigint PK
- `project_id` bigint FK → projects.id ON DELETE CASCADE
- `sample_index` integer
- `location_name` varchar 255
- `timestamps`

### `component_assessments`
- `id` bigint PK
- `project_id` bigint FK → projects.id ON DELETE CASCADE
- `sample_id` bigint FK → project_samples.id ON DELETE CASCADE
- `component_code` varchar 255 (e.g. `A1_FLOOR`)
- `component_name` varchar 255 (e.g. `Floor (Lantai)`)
- `weightage` decimal(5,2)
- `finishing_status` enum: PASS/FAIL default PASS
- `hollow_status` enum: PASS/FAIL default PASS
- `levelling_mm` decimal(5,2) nullable
- `levelling_status` enum: PASS/FAIL default PASS
- `joint_mm` decimal(5,2) nullable
- `joint_status` enum: PASS/FAIL default PASS
- `crack_status` enum: PASS/FAIL default PASS
- `overall_sample_status` enum: PASS/FAIL default PASS
- `photo_path` varchar 255 nullable
- `remarks` text nullable
- `timestamps`

### `defects`
- `id` bigint PK
- `project_id` bigint FK → projects.id ON DELETE CASCADE
- `assessment_id` bigint FK → component_assessments.id ON DELETE SET NULL nullable
- `component_name` varchar 255
- `location` varchar 255
- `defect_description` text
- `photo_path` varchar 255 nullable
- `severity` enum: `low`, `medium`, `high` default `medium` (added — supports "Tahap Kritikal" in mockup)
- `status` enum: `OPEN`, `IN_PROGRESS`, `RESOLVED` default `OPEN`
- `timestamps`

### Seeders
`DatabaseSeeder` creates:
- Admin user: `admin@eids.gov.my` / `password` / role: `admin`
- One inspector user for testing
- One sample project in `dalam_pemeriksaan` status with 3 samples

---

## 4. Business Logic

### Sample Calculation
```
N = max(1, ceil(GFA / 60))
```
Default location names cycle from: Living Room, Service Area, Passageway, Bedroom 1, Bedroom 2, Bedroom 3, Bathroom.

### Inspection Auto-rules (enforced server-side + previewed client-side via Alpine.js)
- `levelling_status` = PASS if `levelling_mm <= 3.0`, else FAIL
- `joint_status` = PASS if `joint_mm <= 1.0`, else FAIL
- `overall_sample_status` = PASS only if ALL 5 checks pass (finishing + hollow + levelling + joint + crack)

### Auto Defect Creation
When `overall_sample_status = FAIL` on save, automatically insert a `Defect` record with status `OPEN`. If a defect already exists for this assessment, update it (don't duplicate).

### Score Calculation
```
P_comp = (passed_samples / total_assessed_samples) × 100
S_comp = P_comp × (weightage / 100)
S_architectural = sum of all S_comp (max 85)
Score_EIDS = S_architectural + 2.00 (M&E) + 13.00 (External Works)
```

Rating classification:
- ≥ 85%: BAIK (Good Pass) — green
- 70–84%: SEDERHANA (Moderate) — amber
- < 70%: LEMAH (Weak) — red

---

## 5. Component Registry (Static)

Defined in `InspectionController::getComponents()`:

| Code | Name (EN / MY) | Weightage |
|------|---------------|-----------|
| A1_FLOOR | Floor / Lantai | 18% |
| A2_WALL | Internal Wall / Dinding Dalam | 18% |
| A3_CEILING | Ceiling / Siling | 10% |
| A4_DOOR | Door / Pintu | 10% |
| A5_WINDOW | Window / Tingkap | 8% |
| A6_FIXTURES | Internal Fixtures | 5% |
| A7_ROOF | Roof / Bumbung | 10% |
| A8_EXT_WALL | External Wall / Dinding Luar | 10% |

**Total architectural weightage = 89.** The "85%" label refers to the architectural category's target ceiling in the overall building quality rubric. Score formula applies component weights literally: `S_comp = passRate × weightage`. Max architectural score when all samples pass all components = 89 points. M&E fixed at 2.00. External Works fixed at 11.80. Max achievable total ≈ 102.8 (by original G-IDS spec design — implement formula as written).

---

## 6. Controllers

| Controller | Responsibility |
|-----------|---------------|
| `AuthController` | Login, logout |
| `ProjectController` | CRUD for projects, sample generation view |
| `InspectionController` | Component selector, inspection form, store assessment, score pages |
| `DefectController` | Defect list, toggle status, manual create/edit/delete |
| `ReportController` | Report builder page, PDF download |
| `UserController` | CRUD for users (admin only) |
| `SettingController` | Read-only settings display (admin only) |

---

## 7. Route Map

```
GET  /                          → login view
POST /login                     → AuthController@login
POST /logout                    → AuthController@logout

GET  /dashboard                 → ProjectController@dashboard

GET  /projects                  → ProjectController@index
GET  /projects/create           → ProjectController@create
POST /projects                  → ProjectController@store
GET  /projects/{id}             → ProjectController@show
GET  /projects/{id}/edit        → ProjectController@edit
PUT  /projects/{id}             → ProjectController@update
DELETE /projects/{id}           → ProjectController@destroy

GET  /projects/{id}/samples     → ProjectController@samples
POST /projects/{id}/samples     → ProjectController@storeSamples

GET  /projects/{id}/components  → InspectionController@components
GET  /projects/{id}/inspect/{sampleId}   → InspectionController@inspect
POST /projects/{id}/inspect/{sampleId}   → InspectionController@storeAssessment
GET  /projects/{id}/score       → InspectionController@score
GET  /projects/{id}/summary     → InspectionController@summary

GET  /defects                   → DefectController@index
GET  /defects/create            → DefectController@create
POST /defects                   → DefectController@store
GET  /defects/{id}/edit         → DefectController@edit
PUT  /defects/{id}              → DefectController@update
DELETE /defects/{id}            → DefectController@destroy
POST /defects/{id}/toggle       → DefectController@toggleStatus

GET  /reports/{id}              → ReportController@show
GET  /reports/{id}/pdf          → ReportController@pdf

GET  /users                     → UserController@index
GET  /users/create              → UserController@create
POST /users                     → UserController@store
GET  /users/{id}/edit           → UserController@edit
PUT  /users/{id}                → UserController@update
DELETE /users/{id}              → UserController@destroy

GET  /settings                  → SettingController@index
```

---

## 8. UX Enhancements Over Mockups

1. **Flash toasts** — Alpine.js toast component, auto-dismiss in 3s. Triggered by Laravel session flash messages.
2. **Confirmation modals** — Before delete project, delete defect, delete user.
3. **Auto PASS/FAIL compute** — Alpine.js watches `levelling_mm` and `joint_mm` inputs, instantly flips status badges without server round-trip.
4. **Photo preview** — Instant client-side preview on file selection (FileReader API via Alpine.js).
5. **Progress breadcrumb** — Every inner page shows `Dashboard > Projek > Nama Projek > ...`
6. **Paginated lists** — Projects (15/page), Defects (10/page), Users (20/page).
7. **Search + filter** — Projects: by name/no/status. Defects: by project/component/status.
8. **Sticky sidebar** — Fixed position, scrolls content independently.
9. **Loading states** — Buttons show spinner + disabled state on form submit.
10. **Empty states** — Illustrated empty state when no projects / no defects exist yet.

---

## 9. PDF Report Structure (dompdf)

Sections (user-selectable checkboxes on report builder page):
1. Cover page — E-IDS logo, project name, reference no, date
2. Project information — developer, contractor, building type, units
3. Inspector information — name, ID, role, date of inspection
4. Formula explanation — G-IDS N = ceil(GFA/60) displayed
5. Component list — all components inspected with weightage
6. Component marks table — pass rate + mark per component
7. Defect list & photos — all OPEN defects with thumbnails
8. Recommendations — auto-generated based on FAIL components
9. Summary & sign-off — dual signature blocks (inspector + contractor)

---

## 10. File Structure (additions to Laravel skeleton)

```
app/
  Http/
    Controllers/
      AuthController.php
      ProjectController.php
      InspectionController.php
      DefectController.php
      ReportController.php
      UserController.php
      SettingController.php
    Middleware/
      RoleMiddleware.php
  Models/
    Project.php
    ProjectSample.php
    ComponentAssessment.php
    Defect.php
config/
  eids.php               ← formula constants
database/
  migrations/
    ..._add_role_to_users_table.php
    ..._create_projects_table.php
    ..._create_project_samples_table.php
    ..._create_component_assessments_table.php
    ..._create_defects_table.php
  seeders/
    DatabaseSeeder.php
resources/
  views/
    layouts/
      app.blade.php
      auth.blade.php
    components/
      sidebar.blade.php
      topbar.blade.php
      toast.blade.php
      modal-confirm.blade.php
      stat-card.blade.php
    auth/
      login.blade.php
    dashboard.blade.php
    projects/
      index.blade.php
      show.blade.php
      create.blade.php
      edit.blade.php
      samples.blade.php
      components.blade.php
      inspect.blade.php
      score.blade.php
      summary.blade.php
    defects/
      index.blade.php
      create.blade.php
      edit.blade.php
    reports/
      show.blade.php
      pdf.blade.php
    users/
      index.blade.php
      create.blade.php
      edit.blade.php
    settings/
      index.blade.php
routes/
  web.php
```
