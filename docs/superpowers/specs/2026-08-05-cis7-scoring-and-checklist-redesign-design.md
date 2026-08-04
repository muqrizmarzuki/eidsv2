# CIS 7:2021-Compliant Checklist & Scoring Redesign — Design Spec

**Date:** 2026-08-05
**Project:** Electronic Inspection Defect System (E-IDS v2)
**Stack:** Laravel, MySQL, Blade + Alpine.js (no Livewire)
**Source standard:** CIS 7:2021 (CIDB Malaysia), Annex A/B/C + Tables 1-6

---

## 0. Context & Prior Work

Today's inspection form (`resources/views/projects/inspect.blade.php`, backed by
`ComponentAssessment`) applies the **same 5 generic checks** — Finishing, Hollow,
Levelling(mm), Joint/Gap(mm), Crack — to every one of the 8 architectural components
(A1_FLOOR...A8_EXT_WALL), via fixed columns on `ComponentAssessment`. Weightages live in
`config/eids.php` as a flat, single, code-owned list. M&E and External works are not
inspected item-by-item at all — they're fixed scores (`me_score = 2.00`,
`external_score = 11.80`).

Reading CIS 7:2021 in full (page 10, Annex A/B/C, and Tables 1-6) showed the standard is
far richer than what's implemented, and that several implemented numbers don't match the
standard at all (Table 2's architectural weightage breakdown sums to 100 with different
per-component values than `config/eids.php`'s 89; Table 1's External weightage for Category
A landed housing is 13%, not the hardcoded 11.80; the sample-count divisor is 70m² with a
30-700 clamp per Table 3, not the current uncapped ÷60).

This spec redesigns the checklist and scoring engine to be faithful to the standard:
real per-component/per-element question banks (Annex A/B/C), category-aware weightage and
sampling (Tables 1-6), and an inspection UI that matches an approved mockup
(`Scanned_20260720-1530.pdf`).

**Not addressed by prior specs** — this is a new subsystem; it does not conflict with the
role-visibility or hero-card specs already in this directory.

---

## 1. Building Category & Weightage Tables

Add `building_category` (enum `A`/`B`/`C`/`D`) to `Project`, set once at project creation.
It drives every weightage and sampling lookup below.

| Category | Description |
|---|---|
| A | Landed housing (Terrace, Semi-D, Bungalow) — today's only supported type |
| B | Stratified housing (flats, condos, apartments) |
| C | Public/Commercial/Industrial, without centralised cooling |
| D | Public/Commercial/Industrial, with centralised cooling |

New DB-backed, admin-editable tables (extends the existing Settings area with new CRUD
screens), replacing `config/eids.php`'s flat weightage list:

- **`weightage_overall`** (Table 1): `building_category → {architectural_pct, me_pct,
  external_pct}`. Sums to 100 per category. (Table 5 — "M&E weightage by category" — is the
  same `me_pct` column already in Table 1; it is **not** duplicated as a separate table.)
- **`weightage_architectural_elements`** (Table 2): `component_code → {name, group,
  breakdown_pct}`. This table becomes the single source of truth for component
  code/name/weightage, replacing `config/eids.php`'s `components` array entirely (today's
  code+name+weightage live together in config; splitting weightage into DB while leaving
  name/code in config would create two sources of truth for the same component list).
  Fixed regardless of category. Corrects the current config to match the standard exactly:
  Floor 18, Internal Wall 18, Ceiling 8, Door 8, Window 8, Internal Fixtures 8 (Internal
  finishes = 68), Roof 10, External Wall 10, Apron & Perimeter Drain 3, Car Park/Porch 3
  (External finishes = 26), Skim Coat/Prepacked Plaster 3, Wet-area Water-tightness Test 3
  (Material & functional test = 6). Total 100.
- **`weightage_locations`** (Table 4): `building_category → {principal_pct, service_pct,
  circulation_pct}`. Category A: 40/40/20. Used only for internal-finish components.

Admins can edit all three per category through a new Settings sub-screen. Seeders populate
the standard's published values as defaults.

---

## 2. Sample Count Formula (Table 3)

Replace the current single `sample_divisor = 60` (uncapped) with a category-aware,
clamped formula for **internal finishes** samples:

```
N = clamp(ceil(GFA ÷ divisor), min_samples, max_samples)
```

| Category | GFA divisor | Min samples | Max samples |
|---|---|---|---|
| A (Landed) | 70 m² | 30 | 700 |
| B (Stratified) | 70 m² | 30 | 600 |
| C (Commercial, no CCS) | 500 m² | 30 | 150 |
| D (Commercial, CCS) | 500 m² | 30 | 100 |

Stored alongside the weightage tables (new `sampling_rules` table keyed by
`building_category`), admin-editable the same way.

External-works sampling (Table 6: e.g. "10m length section, min 2 samples" for drains,
"1 location" for playground/court) is a separate, simpler mechanic — see §6.

---

## 3. Question Bank Schema (Annex A/B/C)

Replaces `ComponentAssessment`'s fixed 5 columns with a normalized, DB-backed, admin-editable
question bank:

- **`checklist_items`** — the question bank.
  - `applies_to` — component code (`A1_FLOOR`...`A8_EXT_WALL`), `ME_FITTING`, or an external
    element code (`EXT_DRAIN`, `EXT_POOL`, etc.)
  - `defect_group` — e.g. "Finishing", "Alignment and Evenness" (kept for authoring/filtering
    even though the inspection UI renders a flat numbered list, matching the mockup)
  - `sort_order`, `question_text` (plain-English, per the mockup's phrasing, not the
    standard's terser "Requirements" column)
  - `method_tool`, `tolerance_text` (e.g. "≤ 3 mm / 1.2 m", "-")
  - `input_type` — `pass_fail` or `numeric_with_tolerance`
  - Guide fields, **all nullable** — `guide_tools`, `guide_procedure` (ordered steps),
    `guide_result_thresholds`, `guide_photos` (file uploads). The "?" Guide icon renders
    in the UI only when a question has at least one guide field populated; guide content is
    authored incrementally per-question through the admin edit screen, not required upfront.
- **`assessment_answers`** — one row per `sample_id × checklist_item_id`: `result`
  (PASS/FAIL, or derived from `numeric_value` vs. `tolerance_text`), `numeric_value`,
  `photo_path`, `remarks`.
- `ComponentAssessment` becomes a thin parent (sample × component/element), holding
  `overall_status` and an `na` flag (see §5), grouping its `assessment_answers`.

Finish-type conditional applicability (e.g. "Falls in wet areas" only applies to
Cement-Screed/Tile/Vinyl floors, not Carpet) is **explicitly out of scope for v1** — all
questions for a component show regardless of finish material, and every question must be
answered PASS/FAIL (or a numeric value). There is no per-question N/A in v1 — the only N/A
concept in this spec is the whole-component/element N/A described in §5 (e.g. no Car Park
exists at all), set once at project setup, not per-question during inspection.

Seeders populate `checklist_items` from Annex A (8 components), Annex B (M&E), and Annex C
(8 external elements), using the phrasing style shown in the mockup PDF where available
(e.g. the 17-question A1_FLOOR set) and the standard's own "Requirements" column text
elsewhere.

---

## 4. Scoring Pipeline

1. **Per-question result**: PASS/FAIL (visual), or numeric value auto-compared to
   `tolerance_text` (same auto-derive behavior as today's Levelling/Joint fields).
2. **Per-component/element pass rate**: `passed ÷ applicable_questions × 100%`. Questions
   marked N/A are excluded from both numerator and denominator.
3. **Internal-finish components** (Floor, Wall, Ceiling, Door, Window, Fixtures): pass rate
   is further weighted across sample locations using Table 4's Principal/Service/Circulation
   split (varies by `building_category`), replacing today's flat average across samples.
4. **Architectural subtotal**: `Σ (component_pass_rate × component_breakdown_pct)`, using
   Table 2's weights. If a component is marked N/A at the project level (Car Park, Apron &
   Perimeter Drain — the only two architectural elements that can legitimately not exist),
   its weightage is **redistributed proportionally** across the remaining components so they
   still sum to 100% — a project without a car park is not penalized for lacking one.
5. **M&E subtotal**: same pass-rate approach across Annex B's 5 groups, assessed at the same
   sample locations as internal finishes (per Table 5's note that M&E sampling reuses the
   internal-finishes guideline — no separate M&E sample-count formula needed).
6. **External subtotal**: pass rate across only the Annex C elements present in the project
   (§6) — flat pass-rate across all applicable external questions; the standard gives no
   per-element weightage breakdown for External works (unlike Table 2), so no redistribution
   logic is needed here, only the N/A-exclusion from §4.2.
7. **QP declarations** (Skim Coat/Prepacked Plaster 3%, Wet-area Water-tightness Test 3%):
   binary earned/not-earned — a declaration checkbox plus a required document upload (the
   QP's evidence/test report). Earned weightage is added directly into the architectural
   subtotal via Table 2's "Material and functional test" rows; not part of the
   `checklist_items`/PASS-FAIL flow.
8. **Overall G-IDS score**:
   ```
   score = architectural_subtotal% × architectural_pct
         + me_subtotal%           × me_pct
         + external_subtotal%     × external_pct
   ```
   where `architectural_pct`/`me_pct`/`external_pct` come from Table 1 for the project's
   `building_category`.
9. **Rating** (GOOD/MODERATE/WEAK): applied to the final score using the existing
   configurable `baik`/`sederhana` thresholds — unchanged.

---

## 5. N/A Handling for Optional Components/Elements

Two architectural elements (Car Park/Porch, Apron & Perimeter Drain) and all 8 external
elements (§6) may legitimately not exist in a given project. These are marked N/A at the
**project setup** level (a checklist of "which elements exist," set once when the project's
samples are configured) — not per-question. An N/A component/element:

- Is excluded entirely from the inspection UI (no sample rows generated for it).
- Is excluded from its subtotal's pass-rate denominator (§4.2).
- For architectural elements only, has its Table 2 weightage redistributed (§4.4). External
  elements need no redistribution since they have no individual weightage to redistribute.

---

## 6. External Works Sampling (Table 6)

Unlike internal finishes, External works elements (Link-way/Shelter, External Drain,
Roadwork, Footpath & Turfing, Fence & Gate, Playground, Court, Swimming Pool) are **not**
sampled via the GFA-based formula in §2. Each gets its own sampling entity per Table 6's
guideline (e.g. "10m length section per sample, min 2 samples" for drains/roadwork; "1
location" for Playground/Court). These are modeled as their own sample rows (same shape as
`ProjectSample`, scoped to the external element) generated per the element's specific rule,
only for elements marked present (§5).

---

## 7. Inspection UI (Matching the Mockup)

Rebuilds `inspect.blade.php`'s per-component form to match `Scanned_20260720-1530.pdf`:

- Table columns: **No. / Inspection Question / Method-Tool / Limit / Guide(?) / Result**.
- Rows render as a flat numbered list per component (no visible defect-group dividers,
  though `defect_group` is retained in the schema for admin-side organization).
- `pass_fail` questions show PASS/FAIL buttons (as today). `numeric_with_tolerance`
  questions show a number input; PASS/FAIL is auto-derived and displayed, not
  manually chosen (matches today's Levelling/Joint behavior, generalized to any
  numeric question).
- The "?" Guide icon appears only when a question has guide content, opening a
  slide-over/modal with Tools, numbered Procedure steps, a Limit callout, Result
  thresholds, and example photos — all optional/nullable per §3.

---

## 8. Data Migration

Existing `ComponentAssessment` data in this environment is demo/seed data only (per recent
"Add a fully-completed demo project to the seeder" commit) — **no migration/backfill is
needed**. Migrations drop the 5 fixed columns and add the new schema directly; the seeder is
rewritten to populate `checklist_items` from the standard and generate a fresh demo project
against the new structure.

---

## 9. Implementation Phasing

Each phase ships a working, scoreable system; phases 2-4 build on phase 1's foundation.

1. **Foundation** — `building_category` field; Tables 1/2/4 schema + admin CRUD; Table 3
   sample formula; `checklist_items`/`assessment_answers` schema; Annex A question bank
   seeded for the 8 architectural components; inspection UI rebuilt to match the mockup;
   location-type weighting (Table 4); N/A + weightage redistribution (§4.4, §5) for Car
   Park/Apron; QP declarations (Skim Coat, Water-tightness). Architectural subtotal fully
   working end-to-end.
2. **M&E** — Annex B question bank seeded; M&E assessed at existing internal-finish sample
   locations (Table 5 note); fixed M&E score replaced with the calculated subtotal.
3. **External works** — project-setup toggle for which of the 8 Annex C elements exist;
   Table 6 sampling entities; Annex C question bank seeded; fixed External score replaced
   with the calculated subtotal.
4. **Report/UI polish** — PDF report and any dashboards updated to show the new detailed
   per-question/per-component breakdown and the final Table-1-weighted overall score.

Each phase gets its own implementation plan (via the `writing-plans` skill) once this design
is approved.

---

## 10. Open Items Deferred Beyond This Spec

- Finish-type conditional question filtering (§3) — explicitly deferred, not forgotten.
- Multi-tenancy / per-client isolation — unrelated, unchanged by this spec.
- Full guide content (procedure text + photos) authoring for all ~100+ questions — schema
  ships in phase 1; content is filled in incrementally by admins afterward, per-question.
