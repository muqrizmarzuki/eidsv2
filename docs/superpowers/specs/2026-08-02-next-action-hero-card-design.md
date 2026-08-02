# Next-Action Hero Card — Design Spec

**Date:** 2026-08-02
**Project:** Electronic Inspection Defect System (E-IDS v2)
**Stack:** Laravel, Blade + Alpine.js (no Livewire)

---

## 0. Problem

The project detail page (`projects/show.blade.php`) shows a one-line "next action" banner (from `Project::nextActionFor()`, added earlier) plus a separate 5-step pipeline stepper. Both are functionally correct but read as two disconnected, low-prominence widgets — a passive sentence to read, then a separate UI element to hunt through for the actual button. Neither guides the user toward a single obvious next click. The score page (`projects/score.blade.php`) has the stepper but no guidance banner at all.

---

## 1. Extended `Project::nextActionFor()` Return Shape

The method keeps its existing signature (`nextActionFor(User $user): array`) and its existing `icon`/`text` keys (so the Task 14 dashboard widget, which only reads those two, keeps working unchanged). It gains four new keys:

```php
[
    'icon'         => string,
    'text'         => string,
    'actionable'   => bool,           // does this state have a real next click?
    'route'        => string|null,   // route name for the button, null if not actionable
    'params'       => array,         // route params, e.g. ['project_id' => ..., 'status' => ...]
    'button_label' => string|null,   // button text, null if not actionable
]
```

**Per-branch mapping** (every existing branch, `actionable` + `route` + `button_label` added):

| Role | Condition | `actionable` | `route` | `button_label` |
|---|---|:---:|---|---|
| Admin / Lead Auditor | `!hasNamedLocations` | `true` | `projects.samples` | "Configure Samples" |
| Admin / Lead Auditor | `!inspectionStarted` | `false` | — | — |
| Admin / Lead Auditor | `openDefects > 0 \|\| pendingVerify > 0` | `false` | — | — |
| Admin / Lead Auditor | else (complete) | `true` | `reports.show` | "Generate PDF Report" |
| Inspector | `pendingVerify > 0` | `true` | `defects.index` (params: `project_id`, `status=PENDING_VERIFICATION`) | "Review Defects" |
| Inspector | `!inspectionStarted` | `true` | `projects.components` | "Start Inspecting Now" |
| Inspector | `!inspectionDone` | `true` | `projects.components` | "Continue Inspecting" |
| Inspector | else (complete) | `true` | `projects.score` | "View G-IDS Score" |
| Supervisor | `status === 'selesai'` | `true` | `reports.show` | "Download Report" |
| Supervisor | else (in progress) | `false` | — | — |
| default (unrecognized role) | — | `false` | — | — |

"Waiting" states (`actionable = false` with non-empty `text`) are a distinct third state from actionable — not merely "actionable with no button." This is what lets the UI render them with visibly muted styling instead of looking like a broken or missing call-to-action.

---

## 2. `<x-next-action-card>` Component

New file: `resources/views/components/next-action-card.blade.php`. Single prop: `:action="$nextAction"` (the array above).

Three render states:

1. **Actionable** (`actionable === true`): bold card using the existing `eids-primary` accent styling (matching the app's established hero-card visual language, e.g. the dashboard's "Average G-IDS Performance" card), showing the icon, a "YOUR NEXT STEP" eyebrow label, the headline `text`, and one primary `<a>` button styled as a call-to-action linking to `route($action['route'], $action['params'] ?? [])` with the `button_label` text.
2. **Waiting** (`actionable === false && $action['text'] !== ''`): muted grey/neutral card, a small clock-style icon, a "WAITING" eyebrow label, and the `text` — no button, no link.
3. **Empty** (`$action['text'] === ''`): renders nothing. Defensive only — every role that can reach `projects.show`/`projects.score` already has a defined branch in `nextActionFor()`, so this path shouldn't normally trigger, but it avoids an empty bordered box if it ever does.

---

## 3. Wiring

- **`resources/views/projects/show.blade.php`**: the existing banner `<div>` (added when `nextActionFor()` was first wired in) is replaced by `<x-next-action-card :action="$nextAction" />`. The stepper below it is unchanged, including its existing supervisor `@unless` guard.
- **`resources/views/projects/score.blade.php`**: net-new — add `$nextAction = $project->nextActionFor(auth()->user());` and the same `<x-next-action-card>` above the stepper (which already has its own supervisor guard from the earlier residual-gap fix).
- **`resources/views/dashboard.blade.php`**: unchanged. The Action Required widget stays a plain list — it's showing multiple projects at once, not a single-project guidance moment, so the hero treatment doesn't apply there.

---

## 4. Testing

- Extend `tests/Feature/ProjectNextActionTest.php`: for every branch in the table above, assert `actionable`, `route`, and `button_label` (not just `icon`/`text` as today).
- Extend the show-page and score-page feature tests: an actionable state renders a `<a href="...">` matching the expected route; a waiting state renders no anchor/button element at all — proving a "waiting" message can never be accidentally clicked into a dead link.

---

## 5. Out of Scope

- Dashboard widget styling (explicitly decided against, Section 3).
- Any change to the underlying business logic of *which* message/state applies to which role — this is purely a presentation-layer change built on the existing `nextActionFor()` decision logic.
