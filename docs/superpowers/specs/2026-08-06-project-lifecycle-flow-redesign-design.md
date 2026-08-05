# Project Lifecycle Flow Redesign

## Overview

The project lifecycle — creation → sample setup → inspection → scoring → defect rectification → completion — already has most of its underlying logic in place (`nextActionFor()`, defect status machine, scoring service), but the UI around it has grown organically: two overlapping progress widgets on the dashboard, a heavyweight 4-step stepper shown during initial setup (before there's really anything to step through), a silent dead-end when defects block completion, and a score reveal with no ceremony. This redesign tightens the UX of that existing pipeline without changing its underlying data model beyond one new column.

**Goals:**
- Make the creation → setup flow feel like a short, self-contained wizard, separate from the full project lifecycle view.
- Consolidate the dashboard's two redundant progress indicators into one.
- Give the "all inspections done" and "score revealed" moments some payoff.
- Close the defect-notify loop: inspector/admin can explicitly flag open defects to the contractor, contractor can explicitly hand a fix back for reinspection, and once everything is resolved, either Admin or Inspector can sign the project off — with the UI clearly telling the inspector when that moment has arrived.

**Non-goals:**
- No email/SMS/push notifications — all "notify" actions are in-app, derived from existing state (defect status + one new timestamp column), no notification log table.
- No changes to the scoring formula, checklist structure, or PDF report content/template.
- No changes to Components Grid or per-component Inspect form UI beyond how their completion is signaled.
- No changes to the "Reject – Not Fixed" / "Confirm Resolved" actions already on the Defects Register — those stay as direct actions, not modal-gated.

## 1. Creation & Setup Flow

**Files:** `resources/views/projects/create.blade.php`, `resources/views/projects/samples.blade.php`, `app/Http/Controllers/ProjectController.php`, new `resources/views/components/setup-progress.blade.php`.

Today, `samples.blade.php` renders the full `<x-workflow-step step="2" :project="$project" />` — the 4-step routing slip (Project Details / Sample Setup / Components Grid / Inspection) plus the score seal. Showing "Components Grid" and "Inspection" as steps during initial setup — before the project has even been saved as ready — is premature; those belong to the project's ongoing lifecycle, not its one-time setup.

- **New component `<x-setup-progress :step="1|2" />`**: a minimal 2-step indicator — "Step 1 of 2 — Project Details" / "Step 2 of 2 — Sample Setup". No links, no lock/disabled states, no score seal. Purely orientation, not navigation.
  - Added to `create.blade.php` (step 1) — this page currently has no stepper at all.
  - Replaces `<x-workflow-step step="2" ...>` on `samples.blade.php` (step 2).
- **`samples.blade.php` action button:** unify the current role-split buttons ("Save Locations" for Admin, "Save & Continue to Grid" for Inspector) into a single **"Save Project"** button, for both roles. Clicking it opens a new informational confirmation modal (see "Notify/confirm modal styling" below) summarizing the outcome: *"{N} sample units configured. Saving will finalize project setup — the assigned inspector can begin the Components Grid inspection."* Confirming submits the existing form to `projects.samples.store` unchanged.
- **`ProjectController@storeSamples` redirect:** currently branches — `isAdmin()` → `projects.show`, otherwise → `projects.components`. This collapses to a single unconditional redirect to `projects.show` for every role. The Components Grid page itself is untouched; it's simply reached afterward from the dashboard rather than automatically.

## 2. Project Dashboard — Unified Lifecycle Stepper

**Files:** `resources/views/projects/show.blade.php`, `resources/views/components/workflow-step.blade.php` (retired/replaced), possibly renamed to `resources/views/components/lifecycle-ledger.blade.php`.

`show.blade.php` currently renders two progress widgets back-to-back: `<x-workflow-step step="1" ...>` (4-step routing slip + score seal) and a separate "Case Ledger" (Setup & Assignment / Field Inspection / Defect Rectification / Final Certificate). They cover almost the same ground at different granularity. These consolidate into **one** widget, keeping the Case Ledger's existing visual language (numbered chips, checkmark badges, dashed connectors between rows) since it already reads well — the routing-slip widget is retired.

The unified ledger's rows:

1. **Setup & Assignment** — `done` once project + samples exist. Meta: creator / assigned inspector. Link: "Configure Rooms" (if not yet done and user `canInspect()`).
2. **Field Inspection** — `active` while `0 < inspection_progress < 100`, `done` at 100%. Meta: progress %, checks assessed. Link: "Open Components Grid".
3. **Score & Certification** — unlocks (`active`) once inspection is 100%; shows the rating badge once scored. Link: "View E-IDS Score" → `projects.score`.
4. **Defect Rectification** — `attention` if `openDefects > 0` (includes `PENDING_VERIFICATION`), `done` if all resolved and at least one existed, `pending` otherwise. Meta: open / pending-verification / resolved counts. This is where the "Notify Contractor" action lives (see Section 5).
5. **Completed** — `done` once `status === 'selesai'`; otherwise shows the **"Sign Off Project"** action (Section 5) once eligible, or a locked state otherwise. Link once done: "Official PDF Certificate →".

Rows 3 and 4 are not strictly sequential — defects can surface and get worked on while inspection is still in progress on other samples, and scoring only reflects current assessments — so the ledger reflects state independently per row rather than enforcing a strict linear gate between them (row 5 is the only row gated on both 2, 3, and 4).

Everything else on the dashboard — Project Specifications, QP Declarations, Sample Units List, Danger Zone — is unchanged.

## 3. Inspection Completion — Modal, Not Auto-Redirect

**Files:** `app/Http/Controllers/InspectionController.php` (`storeAssessment`), `resources/views/projects/components.blade.php`.

Redirect behavior for `storeAssessment` is **unchanged**: saving the last component of a sample still redirects to `projects.components` with the existing "completed" flash message, regardless of whether other samples remain.

What's added: when this particular save brings `$project->fresh()->inspection_progress` to 100 (every sample now fully assessed), flash an additional session flag, e.g. `session()->flash('inspection_complete', true)`, alongside the normal success message.

On `components.blade.php`, an Alpine component checks for that flag on page load (via a Blade `@if(session('inspection_complete'))` bootstrapping an `x-data` open state) and fires a congratulatory modal — primary/emerald tone, not the red/amber danger style — titled **"All Inspections Complete!"** with a single CTA: **"View E-IDS Score →"**, linking to `projects.score`. The celebration animation (Section 4) plays there, on arrival — not before.

## 4. Score Page — Celebration Animation + Quiet PDF Button

**File:** `resources/views/projects/score.blade.php`.

**Animation (first-visit only):** Using the same one-time-celebration pattern already established in `workflow-step.blade.php` (a `localStorage` flag, there keyed `eids-score-celebrated-{project id}`), add a parallel key, e.g. `eids-score-animated-{project id}`. On first visit after scoring completes:
- The radial gauge's arcs sweep in from empty to their final sweep values over ~1–1.5s ease-out (animating the arc paths' effective end-angle, or equivalently a `stroke-dasharray`/`stroke-dashoffset` transition on each arc segment).
- The big score number (`{{ number_format($totalScore, 2) }}`) counts up from 0 to its final value in sync, via a small Alpine tween (`setInterval`/`requestAnimationFrame` stepping a bound value that the template renders).
- On any subsequent visit, both render fully at their final state immediately — no animation, no delay.

**PDF button:** the bottom action bar's "Generate Official E-IDS Certificate PDF →" currently renders as the page's largest, boldest CTA (`px-6 py-2.5`, `text-xs font-extrabold`, `shadow-md`, filled `bg-eids-primary`). It downgrades to a small, quiet button matching the weight of the "Official PDF Certificate →" link already used in the dashboard ledger's Completed row: a `print` icon + short label, no fill, text-sized like a secondary link rather than a hero action. It keeps its href/route unchanged (`reports.show`).

## 5. Defect-Notify Cycle & Sign-Off

**Files:** new migration adding `contractor_notified_at` to `defects`, `app/Models/Defect.php`, `app/Http/Controllers/DefectController.php` (`advanceStatus`), `resources/views/defects/index.blade.php`, `resources/views/projects/show.blade.php` (ledger row 4/5), `app/Models/Project.php` (`nextActionFor`), `app/Http/Controllers/ProjectController.php` (`markComplete`), `routes/web.php`.

### Schema

One new nullable column: `defects.contractor_notified_at` (timestamp). No new tables — "notify" stays derived from existing status plus this one flag, per the earlier decision to avoid building a notification log.

### Inspector/Admin → Contractor: "Notify Contractor"

On the dashboard ledger's **Defect Rectification** row, when inspection is 100% but `openDefects > 0`, show a **"Notify Contractor"** action — available to both Admin and Inspector (`canInspect()`), consistent with other ledger row actions. Clicking it opens a confirmation modal: *"{N} open defect(s) will be flagged to {contractor name} for correction."* Confirming sets `contractor_notified_at = now()` on every currently-un-notified `OPEN`/`IN_PROGRESS` defect on the project. The action is repeatable — running it again catches any newly-created defects that haven't been flagged yet.

**Contractor-facing banner:** a derived count — defects where `contractor_notified_at IS NOT NULL` and status is `OPEN` or `IN_PROGRESS`, across the contractor's assigned projects — surfaces as a banner (e.g. on `defects.index` and/or their next-action area): *"{N} defect(s) flagged for your action."*

### Contractor → Inspector: "Mark Settled"

The existing "Mark Settled" button on `defects/index.blade.php` (submits `IN_PROGRESS → PENDING_VERIFICATION` via `defects.advance`) becomes modal-gated instead of an instant submit. Confirmation copy: *"This will notify the inspector that this defect is ready for reinspection."* Confirming submits the same existing request — no schema change needed, since `PENDING_VERIFICATION` already is the "awaiting reinspection" signal that `nextActionFor()` reads.

The "Confirm Resolved" / "Reject – Not Fixed" actions the inspector then uses to clear a `PENDING_VERIFICATION` defect are **unchanged** — they stay direct, unmodaled.

### Sign-Off: Inspector or Admin, once everything is clean

Once inspection is 100% **and** every defect is `RESOLVED` (zero remaining in `OPEN`/`IN_PROGRESS`/`PENDING_VERIFICATION`), the ledger's **Completed** row shows a **"Sign Off Project"** button available to **both Admin and Inspector**. This replaces today's Admin-only "Mark as Completed" gate:
- `routes/web.php`: `POST /projects/{project}/complete` middleware changes from `role:admin` to `role:admin,inspector`.
- `ProjectController@markComplete`: logic is unchanged (still requires `inspection_progress >= 100 && openDefects === 0`); only the route-level role restriction loosens.
- `show.blade.php`: `$canMarkComplete = auth()->user()->canInspect()` (was `isAdmin()`).

### Bug fix bundled into this section

`Project::nextActionFor()`'s Inspector branch currently returns *"Inspection complete — notify your Admin."* the moment `inspection_progress >= 100`, checking only `$pendingVerify` — not `$openDefects` (which includes plain `OPEN`/`IN_PROGRESS` defects that haven't even reached the contractor yet). This means an inspector could see a "you're done" message while unaddressed defects sit open. As part of this change, that final branch becomes conditional on `$openDefects === 0 && $pendingVerify === 0`, and its copy changes to **"All defects resolved — ready to sign off."**, pointing at the new sign-off action. The existing `$pendingVerify > 0` branch above it already takes priority when reinspection is pending, so this only fixes the case where defects are open but nothing has reached `PENDING_VERIFICATION` yet.

## Modal styling note

Two new modal "tones" are introduced across this design (setup-save confirmation, notify-contractor confirmation, inspection-complete celebration) that aren't destructive actions and shouldn't use the existing `modal-confirm` component's danger/warning (red/amber, `delete_forever`/`warning` icon) styling. These get a new informational variant — same structural pattern (`x-on:open-confirm.window`, backdrop, transition), swapped to primary/emerald tones (`bg-eids-primary/10` icon chip, `check_circle` or `info` icon, `bg-eids-primary` confirm button) — either as a `tone="info"` prop on the existing `modal-confirm` component, or a sibling `modal-notify` component, whichever keeps the diff smaller once in the implementation plan.

## Testing Considerations

- Feature tests already exist for completion gating (`ProjectCompletionTest`) and next-action logic (`ProjectNextActionTest`) — both need updates to reflect Inspector-eligible sign-off and the corrected `nextActionFor` branch condition.
- New coverage needed: `storeSamples` redirect is role-independent; `storeAssessment` flashes `inspection_complete` only on the truly-last submission; `contractor_notified_at` is set only on the intended defect subset and is idempotent/repeatable; the loosened `projects.complete` route accepts Inspector requests and still rejects Contractor.
