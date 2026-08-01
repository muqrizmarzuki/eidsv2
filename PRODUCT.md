# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

Mixed primary audience across three deployment contexts — all share the same platform:

- **Government inspectors** (e.g. JKR, CIDB, local authority): conducting official site inspections on behalf of a regulatory body, responsible for issuing a formal G-IDS score and rating.
- **Private inspection firms**: hired by developers or purchasers for third-party defect inspections; produce reports as a deliverable to clients.
- **Developer / contractor QC teams**: internal quality control staff inspecting their own units before handover, using the system to track and close defects.

Roles within each deployment: `admin`, `lead_auditor`, `inspector`, `supervisor`. Inspectors are the field users; auditors and admins are the office-side reviewers.

Usage is both field (tablet / phone on-site, room by room) and office (desktop review, scoring, report generation) — both modes are core.

## Product Purpose

E-IDS (Electronic Inspection Defect System) digitises residential building defect inspections for the Malaysian construction sector. It replaces paper-based G-IDS inspection forms with a structured workflow: project registration → sample configuration → per-component assessment → defect tracking → scored report and PDF.

Success means an inspector can walk a unit, record every component result, and produce a signed-off G-IDS report without touching a paper form.

## Positioning

The only web system with G-IDS scoring built in natively — sample count formula (N = ceil(GFA ÷ divisor)), per-component pass rate → S_comp calculation, architectural subtotal + fixed M&E and external scores, and GOOD / MODERATE / WEAK rating thresholds are all live and configurable by admins. Competing tools are generic project management or spreadsheet workflows that leave scoring to the inspector.

## Operating Context

- Inspectors walk residential units (Terrace, Semi-D, Bungalow) and assess up to 8 architectural components (A1_FLOOR through A8_EXT_WALL) per sample unit.
- Each component check covers: finishing, hollow, levelling (mm), joint/gap (mm), crack status — auto-computed PASS/FAIL against configurable tolerances.
- A FAIL on any component auto-creates a defect record with severity and photo attachment.
- Defects cycle through OPEN → IN_PROGRESS → RESOLVED.
- At completion a G-IDS score is calculated; a formatted A4 PDF report is downloadable.
- Admins configure formula parameters and default sample locations via a settings UI; component codes and weightages are in config.

## Capabilities and Constraints

**Confirmed capabilities:**
- Project CRUD with status (Draft / In Inspection / Completed)
- Auto-generated sample slots with configurable default location names
- Component inspection grid with PASS/FAIL per sample × component cell
- Levelling (max 3 mm) and joint/gap (max 1 mm) numeric tolerances, configurable
- Auto-defect creation on FAIL; defect photo upload
- G-IDS score: S_arch + M&E fixed (2 pts) + External fixed (11.8 pts); configurable thresholds
- PDF report via dompdf
- Role-based access: admin, lead_auditor, inspector, supervisor
- Settings CRUD for 7 scalar parameters + default location list (DB-backed)
- Reports index listing all scored projects

**Constraints:**
- Docker-deployed (container `eidsv2`, port 2006); MySQL in `mysql-db` container
- No official logo or brand assets yet — working name E-IDS v2
- No mobile-native app; responsive web only (field use via browser on tablet/phone)
- Component codes (A1_FLOOR–A8_EXT_WALL) and weightages are config-file values, not DB-editable

**Undecided:**
- Whether component weightages will become DB-editable in a future release
- Multi-tenancy / per-client isolation (currently single-tenant)
- Offline / PWA capability for field use in low-connectivity sites

## Brand Commitments

Working name: **E-IDS v2** (Electronic Inspection Defect System). No official logo, wordmark, or brand guide exists yet. Current UI uses a dark green primary (`eids-primary`) with a lighter accent green (`eids-accent`). These are working defaults, not locked brand values.

## Evidence on Hand

No external testimonials, case studies, press, or certification assets. All product evidence is the live codebase itself.

## Product Principles

1. **Structured, not freeform** — every inspection follows the G-IDS framework; the system enforces completeness, not just captures notes.
2. **Field and office are equal citizens** — the UI must be operable on a tablet on a construction site and on a desktop in a report review meeting without mode-switching.
3. **Score integrity above convenience** — formula parameters are admin-configurable but audit-logged; the scoring calculation is never a black box.
4. **Report is the product** — the PDF report is the deliverable inspectors hand to clients or regulators; every screen before it is prep for that moment.
5. **Role clarity** — what an inspector can do vs. a lead auditor vs. an admin is explicit and enforced, not advisory.
