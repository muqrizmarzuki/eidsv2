# E-IDS v2 — User Manual & Client Guide
**Electronic Inspection Defect System (E-IDS v2)**
*Official Operational Manual for Admins, Site Inspectors, and Contractors*

> Updated 2026-08-06 to reflect the streamlined project lifecycle: a 2-step setup wizard, a single unified Case Ledger on the project dashboard (replacing two separate progress widgets), an explicit Notify Contractor / Notify Inspector confirmation cycle around defect handling, project sign-off opened up to Inspectors (not just Admin), a celebratory Score reveal animation, and a Sample Units Manifest with a Unit column. A few screenshots below predate this refresh and will be recaptured in a future pass — the described behavior is current even where a screenshot shows an earlier button label.
>
> Carried over from the 2026-08-05 CIS 7:2021-compliant scoring redesign: building categories, a real per-component question bank with Guide content, M&E (Annex B), External Works (Annex C) with multi-instance elements, and building-level architectural components (Roof/External Wall/Apron/Car Park).

![Inspector Portal sign-in screen](screenshots/01-login.png)
*The sign-in screen every role shares — the same login form routes Admin, Inspector, and Contractor to their own scoped view of the system.*

---

## 1. System Overview

E-IDS v2 is a specialized web-based building defect inspection and scoring platform for the Malaysian construction sector, implementing **CIS 7:2021** (CIDB's Construction Industry Standard) natively rather than approximating it. E-IDS replaces manual paper-based forms with an end-to-end digital workflow, run through a short setup wizard, a single project dashboard, and a defect notify cycle that keeps Inspector and Contractor in sync without either of them needing to phone the other:

```mermaid
flowchart TD
    A["Setup Wizard — Step 1 of 2<br/>Project Details<br/>(Building Category + Total GFA)"] --> B["Setup Wizard — Step 2 of 2<br/>Sample Setup<br/>(units + rooms, Table 3)"]
    B -->|"Save Project"| C["Project Dashboard<br/>(Case Ledger)"]
    C --> D["Component Inspection<br/>(Components Grid + dynamic checklist)"]
    D --> D2["Building External<br/>(Roof/Wall/Apron/Car Park)"]
    D --> D3["External Works<br/>(Annex C, multi-instance)"]
    D2 --> E["All Inspections Complete!<br/>modal → View E-IDS Score"]
    D3 --> E
    E --> F["Defect Notify Cycle<br/>Notify Contractor → repair → Notify Inspector → reinspect"]
    F --> G["Sign Off Project<br/>(Admin or Inspector)"]
    G --> H["Official Signed PDF Certificate"]
```

1. **Setup Wizard** — a focused 2-step flow (Project Details, then Sample Setup) with its own lightweight step indicator, separate from the project's ongoing lifecycle tracking. It ends with a **Save Project** button behind a confirmation modal, not a plain submit — clicking it tells you exactly what you're about to lock in before you do it.
2. **Project Dashboard** — every role who can open a project lands here first, always, regardless of what they just did. A single **Case Ledger** (see §3) replaces what used to be two separate progress widgets, and is the one place that answers "what phase is this case in, and whose turn is it?"
3. **Inspection** — the Components Grid, Building External sections, and External Works instances. Finishing the very last one doesn't silently drop you back to the grid — it pops an **"All Inspections Complete!"** modal with a direct link to the Score page.
4. **Score** — opens with a short one-time animation (the gauge sweeps in, the total counts up) the first time a project's score becomes available, then renders instantly on every visit after that.
5. **Defect Notify Cycle** — Inspector/Admin explicitly **Notify Contractor** about open defects (not just silent visibility); once repaired, the Contractor's **Mark Settled** action explicitly **Notifies the Inspector** for reinspection. Both are confirmation-modal actions, not one-click silent state changes.
6. **Sign Off** — once every defect is `RESOLVED`, either the **Admin or the Inspector** can sign the project off (this used to be Admin-only) and unlock the signed PDF certificate.

![Admin dashboard showing project totals, average E-IDS performance, and the Action Required widget](screenshots/02-dashboard.png)
*The Dashboard is the landing page for Admin and Inspector — project totals, the average score across every visible project, and an Action Required list of what still needs attention.*

---

## 2. User Roles & Permission Matrix

E-IDS v2 supports **three** roles. Each role's view of the system is scoped — Admin sees every project; Inspector and Contractor see only what they're individually assigned to.

```mermaid
graph TD
    subgraph Admin ["👑 System Admin"]
        A1["User Management & Roles"]
        A2["Weightage, Sampling & Question Bank Settings"]
        A3["Sees & Manages Every Project"]
        A4["Registers Projects, Assigns Inspector/Contractor"]
        A5["Signs Off Completion & Generates the Signed PDF"]
    end

    subgraph Inspector ["🔍 Field Inspector (Inspektor)"]
        B1["Sees Only Their Assigned Projects"]
        B2["Sample Setup, Component Inspection & External Works Checks"]
        B3["Verifies Contractor Repairs & Closes Defects"]
    end

    subgraph Contractor ["🔧 Contractor / Developer QC (Kontraktor)"]
        C1["My Defects Only — No Dashboard/Projects Access"]
        C2["Marks Repairs In Progress & Settled"]
        C3["Cannot Self-Certify as Resolved"]
    end

    Admin ~~~ Inspector ~~~ Contractor
```

### Who Each Role Actually Is

**👑 Admin.** The office-side owner of the whole portfolio. Registers projects, decides who inspects them and which contractor is on the hook for repairs, and holds the exclusive keys to system-wide configuration — user accounts, the CIS 7:2021 weightage/sampling tables, and the question bank's content and Guide material. An Admin can see and open every project in the system, and can step into any Inspector-facing screen (Sample Setup, Notify Contractor, Sign Off) if needed, but day-to-day inspection work is normally the Inspector's job.

**🔍 Inspector.** The field role. Sees only the projects they're assigned to (or unassigned ones open to any inspector), and owns everything that happens on site: naming sample locations, running the Components Grid checklist room by room, completing the Building External and External Works sections, uploading photo evidence, and reinspecting a Contractor's repair before confirming it resolved. As of this update, an Inspector can also **Sign Off** a project themselves once every defect is closed — they no longer have to hand that final step to an Admin.

**🔧 Contractor.** The narrowest, most focused role. A Contractor's login opens directly to **My Defects** — there's no Dashboard, no Projects list, no Reports link, because there's nothing for them to do there. Their whole job is: get notified about a defect, mark it in progress, mark it settled once fixed (which itself notifies the Inspector to come check), and repeat. They can never mark their own work as fully `RESOLVED` — that confirmation is reserved for someone who didn't do the repair.

### Detailed Permissions Table

| Action / Capability | Admin | Inspector | Contractor |
| :--- | :---: | :---: | :---: |
| **View Dashboard, Projects & Reports** | ✅ all | ✅ assigned | ❌ |
| **Download Official PDF Report** | ✅ all | ❌ (view score only) | ❌ |
| **Register & Edit Projects** | ✅ | ✅ | ❌ |
| **Assign the Inspector (`assigned_to`)** | ✅ | ❌ (locked to self) | ❌ |
| **Assign the Contractor (`assigned_contractor_id`)** | ✅ | ✅ | ❌ |
| **Configure Sample Locations & Optional Elements** | ✅ | ✅ | ❌ |
| **Perform Component Inspection Checklists** | ❌ (handed off to Inspector) | ✅ | ❌ |
| **Add/Remove External Works Instances** | ✅ | ✅ | ❌ |
| **Declare QP Certifications (Skim Coat, Water-tightness)** | ✅ | ✅ | ❌ |
| **Upload Photo Evidence (Spatie Media)** | ❌ | ✅ | ❌ |
| **View Defects Register / My Defects** | ✅ all | ✅ assigned projects | ✅ own-assigned project only |
| **Create / Manually Log a Defect** | ✅ | ✅ | ❌ |
| **Notify Contractor about Open Defects** (modal-confirmed) | ✅ | ✅ | n/a |
| **Mark Defect `IN_PROGRESS`** ("Start Repair") | ✅ | ✅ | ✅ |
| **Mark `IN_PROGRESS → PENDING_VERIFICATION`** ("Mark Settled" — notifies Inspector, modal-confirmed) | ✅ | ✅ | ✅ |
| **Confirm `RESOLVED` or Reject back to `IN_PROGRESS`** | ✅ | ✅ | ❌ |
| **Sign Off Project (once every defect is `RESOLVED`)** | ✅ | ✅ | ❌ |
| **Manage Users & Role Access** | ✅ | ❌ | ❌ |
| **Edit Weightage/Sampling Tables & Question Bank** | ✅ | ❌ | ❌ |
| **Delete Project (with Modal Confirm)** | ✅ | ❌ | ❌ |

**What "scoped" means in practice:** opening a project or defect you don't have visibility into — whether from a list or by typing the URL directly — returns a "Forbidden" page for every role except Admin. This applies uniformly across Projects, the Dashboard, Defects, and Reports.

![Projects list showing status, progress bar, and score per row](screenshots/03-projects-index.png)
*The Projects list — the same page renders different rows for each role: Admin sees every project, Inspector and Contractor only ever see rows they're assigned to.*

---

## 3. Operational Role Handoff & Project Lifecycle

E-IDS v2 enforces a clear phase handoff between project creation, site inspection, contractor repairs, and final certificate sign-off:

```mermaid
sequenceDiagram
    autonumber
    actor Admin as Admin
    actor Insp as Assigned Inspector
    actor Cont as Assigned Contractor

    Admin->>Admin: 1. Setup Wizard Step 1 — Project Details (Building Category A-D, Total Project GFA, assign Inspector & Contractor)
    Note over Admin: System auto-generates N sample units per Table 3, distributed across the project's units
    Admin->>Admin: 2. Setup Wizard Step 2 — Sample Setup: room names, location types, optional elements (Car Park, Apron/Drain)
    Admin->>Admin: 3. Clicks "Save Project" — confirms in modal — lands on the Project Dashboard
    Admin-->>Insp: Project now appears under Inspector's "My Assigned Projects"
    Insp->>Insp: 4. Inspector runs the Components Grid (dynamic checklist per component, Guide "?" content)
    Insp->>Insp: 4b. Inspector completes Building External (Roof/Wall/Apron/Car Park sections) and External Works (Annex C instances)
    Note over Insp: FAIL checks auto-spawn OPEN Defects with Spatie Media photos
    Insp->>Insp: 5. On the final inspection, an "All Inspections Complete!" modal offers a direct link to the E-IDS Score
    Insp->>Cont: 6. Inspector/Admin clicks "Notify Contractor" on the Dashboard — confirms in modal — defect(s) are flagged
    Cont->>Cont: 7. Contractor marks "Start Repair" (IN_PROGRESS), then "Mark Settled" — confirms in modal that this notifies the Inspector — (PENDING_VERIFICATION)
    Insp->>Insp: 8. Inspector re-inspects & either Confirms Resolved or Rejects back to IN_PROGRESS
    Note over Insp,Admin: System recalculates the Final E-IDS Score & Rating (GOOD/MODERATE/WEAK) continuously as checklists and defects resolve
    Admin->>Admin: 9. Once every defect is RESOLVED, Admin or Inspector clicks "Sign Off Project" — confirms in modal — then exports the Signed E-IDS PDF Certificate
```

![Project detail page with the Case Ledger showing all four phases, Building Category, and QP Declarations](screenshots/05-project-show.png)
*The project dashboard — the unified Case Ledger tracks Setup & Assignment, Field Inspection, Defect Rectification (with the Notify Contractor action once inspection is done and defects remain open), and Final Certificate (with Sign Off Project, now available to Admin or Inspector). Below it, a two-column layout splits the Sample Units Manifest (with a Unit column) from the Project Specifications and QP Declarations reference sidebar.*

---

## 4. Defect Lifecycle & Status Workflow

When a defect is discovered during site inspection, it follows a **4-stage** lifecycle:

```mermaid
stateDiagram-v2
    [*] --> OPEN: Checklist Item Marked FAIL (Inspector/Admin only)
    OPEN --> IN_PROGRESS: Contractor or Inspector/Admin starts repair
    IN_PROGRESS --> PENDING_VERIFICATION: Contractor marks "Settled"
    PENDING_VERIFICATION --> RESOLVED: Inspector/Admin confirms fix
    PENDING_VERIFICATION --> IN_PROGRESS: Inspector rejects — not actually fixed
    RESOLVED --> [*]
```

### Defect Status Breakdown

> [!IMPORTANT]
> **Who updates defect status?**
> The Contractor has their own login and updates status themselves for the repair steps (`IN_PROGRESS`, `PENDING_VERIFICATION`). The final `RESOLVED` confirmation — and the ability to reject a repair back to `IN_PROGRESS` — remains exclusively with the **Inspector** or **Admin**. A Contractor can never self-certify their own work as fully resolved.

1. **🔴 OPEN (Newly Discovered Defect)**
   - **Trigger**: Automatically created when any checklist question — in a room, a building-level section, or an External Works instance — is marked `FAIL`, or manually logged.
   - **Action**: Defect appears in the assigned Contractor's **My Defects** list and the office's **Defects Register**, with severity tag (`Low`, `Medium`, `High`), location, component code, and photo evidence via Spatie Media Library.

2. **🟡 IN_PROGRESS (Repair Underway)**
   - **Trigger**: The Contractor logs in and clicks **Start Repair** on the defect once they begin work on site. An Inspector or Admin can also set this manually if needed.
   - **Action**: Status updates in real time so the office knows a repair is genuinely underway, not just reported informally.

3. **🔵 PENDING_VERIFICATION (Awaiting Inspector Sign-off)**
   - **Trigger**: The Contractor clicks **Mark Settled** once they believe the repair is complete.
   - **Action**: The defect moves into a queue awaiting the Inspector's physical re-check. The Contractor sees a read-only "Awaiting Verification" badge and cannot advance it further themselves.

4. **🟢 RESOLVED (Verified & Closed)**
   - **Trigger**: The Inspector (or Admin) conducts a re-inspection of the specified location on site.
   - **Action**: Upon verifying the defect has been properly rectified to CIS 7:2021 standards, they click **Confirm Resolved**. If the repair is inadequate, they instead click **Reject – Not Fixed**, sending it back to `IN_PROGRESS` for the Contractor to redo.

![Defects Register showing defects across the lifecycle with their matching action buttons](screenshots/10-defects-register.png)
*The Defects Register — every state side by side, with the action button matching whoever's turn it is.*

---

## 5. Component Inspection — The Dynamic Checklist

This is the biggest change from earlier versions: there is **no fixed 5-point checklist** applied uniformly to every component. Each of the 8 architectural components (Floor, Internal Wall, Ceiling, Door, Window, Internal Fixtures, Roof, External Wall) has its **own** question bank straight out of CIS 7:2021's Annex A — Floor alone has 17 questions, other components have their own counts (see **§9. Managing the Question Bank**).

![Inspection checklist for Floor (A1_FLOOR) — 17 questions, unit reference, Guide column](screenshots/09-inspection-form.png)
*The inspection screen for one sample: which physical unit it's in, the room name, and a full checklist table — Question / Method-Tool / Limit / Guide / Result. Numeric questions (like Floor Levelness) take a measurement and auto-derive PASS/FAIL against the tolerance; everything else is a PASS/FAIL toggle.*

### The Guide "?" Icon

Any question can have optional **Guide content** — tools needed, numbered inspection steps, the CIS 7:2021 limit, result thresholds, and even inline reference photos. It only appears when an admin has filled it in for that specific question.

![Guide popup for Floor Levelness showing tools, numbered procedure, limit callout, and result thresholds](screenshots/09b-guide-modal.png)
*Tapping "?" opens this — formatted exactly as the admin authored it (bold text, lists, and images all render as shown).*

### Two Kinds of Architectural Components

Not every architectural component belongs to a room. CIS 7:2021's Table 3 splits sampling into two scopes:

- **Room-scoped** (Floor, Internal Wall, Ceiling, Door, Window, Internal Fixtures) — inspected at each of the project's sample room locations, shown in the main **Components Grid**.
- **Building-scoped** (Roof, External Wall, Apron & Perimeter Drain, Car Park) — sampled as building-level sections, not tied to any room. These live on their own **"Roof / Wall / Apron / Car Park"** page.

![Building External page — Roof shows 25 auto-generated sections, one marked FAIL](screenshots/13-building-external.png)
*Roof and External Wall sample counts scale with the project's unit count (50% of units, minimum 4 sections); Apron/Drain and Car Park use a fixed minimum of 2 sections. Every section is independently inspected and scored.*

---

## 6. M&E Fittings & External Works (Annex B & C)

### M&E Fittings (Annex B)

M&E Fittings get their own 6-question checklist, assessed at the **same room samples** as the architectural components (per Table 5) — you'll see an "M&E Fittings" tile alongside Floor/Wall/etc. in the Components Grid.

### External Works (Annex C)

External Works elements (Link-way/Shelter, External Drain, Roadwork, Footpath & Turfing, Fence & Gate, Playground, Court, Swimming Pool) live on their own page and now support **multiple instances of the same element** — e.g. a project with 3 separate playgrounds.

![External Works page — Link-way, External Drain, Roadwork, Footpath each with Add/Remove instance controls](screenshots/14-external-works.png)
*Click "Add [Element]" to generate another full instance (its own Table 6 sample sections); click the red "×" on an instance to remove it — this permanently deletes that instance's inspection data and renumbers the remaining instances. Infrastructure elements (Link-way, Drain, Roadwork, Footpath, Fence/Gate) default to 1 instance present; Facilities/Amenities (Playground, Court, Pool) default to none until added.*

### QP Declarations

Two items — **Skim Coat/Prepacked Plaster** and **Wet-area Water-tightness Test** — aren't inspected on-site at all; they're Qualified Person certifications. Attach the evidence document on the Project page's **QP Declarations** card (see §3's screenshot) to earn their weightage.

---

## 7. E-IDS Scoring Framework (CIS 7:2021)

The total score is computed per CIS 7:2021's **Table 1**, which splits 100 points across three categories, weighted by the project's **Building Category** (A = Landed Housing, B = Stratified Housing, C/D = Commercial/Industrial):

$$S_{\text{total}} = S_{\text{arch}} + S_{\text{ME}} + S_{\text{external}}$$

| Category | Architectural | M&E | External |
| :--- | :---: | :---: | :---: |
| A — Landed Housing | 85% | 2% | 13% |
| B — Stratified Housing | 83% | 3% | 14% |
| C — Commercial/Industrial (no CCS) | 82% | 4% | 14% |
| D — Commercial/Industrial (with CCS) | 80% | 5% | 15% |

- **Architectural Subtotal**: each component's pass rate × its **Table 2** weightage (Floor 18%, Internal Wall 18%, Ceiling/Door/Window/Fixtures 8% each, Roof/External Wall 10% each, Apron/Drain and Car Park 3% each, Skim Coat and Water-tightness declarations 3% each). Internal-finish components are further weighted across sample locations by **Table 4** (Principal/Service/Circulation, also category-dependent). If an optional element (Car Park, Apron/Drain) doesn't exist on the project, its weightage is automatically redistributed across the rest — the project isn't penalized for lacking it.
- **M&E Subtotal**: a flat pass rate across every M&E Fittings answer, × the category's M&E %.
- **External Subtotal**: a flat pass rate pooled across every present External Works instance's answers, × the category's External %.

### Rating Thresholds (admin-configurable, defaults shown)

> [!NOTE]
> - 🟢 **GOOD RATING**: Total score $\ge 85.00$ pts
> - 🟡 **MODERATE RATING**: Total score $\ge 70.00$ pts and $< 85.00$ pts
> - 🔴 **WEAK RATING**: Total score $< 70.00$ pts

![E-IDS Score page with the gauge, Architectural/M&E/External breakdown cards, and pass-rate table](screenshots/11-score-page.png)
*The Score page now shows real M&E and External subtotals (pass rate + max points, not fixed numbers) alongside the Architectural breakdown. Scroll down for the Detailed Findings section listing every failed question by component and location.*

---

## 8. Step-by-Step: Using E-IDS by Role

Each role below is written as a complete, self-contained walkthrough — start at the top of your role's section and follow it in order. Cross-references point to the earlier sections if you need the underlying "why."

### 8.1 Admin — Step by Step

**A. Register a new project**
1. Navigate to **Projects** → click **+ New Project**.
2. Enter Project Ref No., Project Name, Developer, Contractor, Building Type, and **CIS 7:2021 Building Category**. This is Step 1 of 2 in the setup wizard — a small "Step 1 of 2 — Project Details" indicator tracks your place, separate from the project's later lifecycle tracking.
3. Enter **Total Project GFA (m²)** — the whole project's combined floor area (all units together), not a single unit's size. The system auto-calculates sample units via Table 3's clamped formula ($N = \text{clamp}(\lceil \text{GFA} \div \text{divisor} \rceil, \text{min}, \text{max})$, divisor/min/max depend on Building Category).
4. Select the **Assigned Inspector** and **Assigned Contractor** from the dropdowns.
5. Click **Create Project & Initialize Samples**.

![New Project form with Building Category and Total Project GFA fields](screenshots/04-project-create.png)

**B. Finish sample setup and hand off**
1. You land on **Sample Setup** — Step 2 of 2 — with samples pre-distributed across the project's units (Unit #1, #2, ...) and pre-named by cycling through the default room list, per CIS 7:2021 §1.7's "distributed as uniformly as possible" requirement.
2. Adjust room names, location type (Principal/Service/Circulation — drives Table 4 weighting), and mark whether **Car Park** and **Apron & Perimeter Drain** exist on this project.
3. Click **Save Project**. A confirmation modal states how many sample units are configured and that saving finalizes setup — confirm to continue.
4. You land on the **Project Dashboard**, same as every other role does after this step. The assigned Inspector can now see the project under their "My Assigned Projects" and begin inspecting.

![Sample Setup — 150 samples distributed across 50 units, location type dropdowns, optional element toggles](screenshots/06-sample-setup.png)

**C. Monitor progress and intervene when needed**
1. Open the project anytime to see the **Case Ledger** — Setup & Assignment, Field Inspection, Defect Rectification, Final Certificate — each row shows exactly whose turn it is and what's blocking.
2. If inspection is complete but defects remain open, the Defect Rectification row shows a **Notify Contractor** button. Click it, confirm the modal (it tells you how many defects will be flagged and to which contractor), and the assigned Contractor sees them highlighted in their **My Defects** view.
3. You can perform any Inspector-facing action yourself if needed — configure samples, run the Components Grid, sign off — but day-to-day inspection is normally the Inspector's job.
4. Outside individual projects, use **Settings** to manage the question bank and Guide content (§9), the weightage/sampling tables (§10), and **Users** to manage accounts and roles.

**D. Sign off and export the certificate**
1. Once every defect on a project is `RESOLVED`, the Final Certificate row shows **Sign Off Project** (this is no longer Admin-exclusive — see §8.2 if the Inspector does it instead).
2. Click it, confirm the modal ("This marks the project as Completed and unlocks the official E-IDS certificate."), and the project's status becomes **Completed**.
3. Click **Generate Official PDF Report** — a small print-style link, not a big button — to view or print the formal signed E-IDS Inspection Certificate, which includes QP Declaration status and a Detailed Findings section listing every failed question.

![Formal E-IDS Inspection Certificate](screenshots/12-report-certificate.png)
*The signed certificate — only reachable once every checklist is complete and every defect is `RESOLVED`.*

### 8.2 Inspector — Step by Step

**A. Find your assigned work**
1. Log in — you land on the **Dashboard**, with an **Action Required** list of everything across your assigned projects that needs your attention right now.
2. Open a project from there, or from **Projects**, to reach its dashboard and Case Ledger.

**B. Run the Components Grid**
1. From the project dashboard, open **Components Grid** — each room-scoped component (plus M&E Fittings) gets its own tile per sample.
2. Click a tile to open its full checklist (question count varies by component — Floor has 17, others differ).
3. Answer each question PASS/FAIL, or enter a measurement for numeric questions (auto-derived against the CIS 7:2021 limit). Tap "?" for Guide content where available.
4. Upload photo evidence if a defect is found, then **Save & Next** to advance through the component's list, then the next component.
5. From the Grid's action bar, also complete **Roof / Wall / Apron / Car Park** (building-level sections) and **External Works** (Annex C instances) — both required before the project can be signed off.
6. When you submit the very last outstanding inspection on the project, an **"All Inspections Complete!"** modal appears with a **View E-IDS Score →** button — click it whenever you're ready, or dismiss it and keep working; nothing is lost either way.

![Inspection checklist for Floor (A1_FLOOR) — 17 questions, unit reference, Guide column](screenshots/09-inspection-form.png)

**C. Work the defect cycle**
1. Any FAIL you record auto-creates an `OPEN` defect. Once inspection is done and defects remain open, go to the project dashboard and click **Notify Contractor** so the assigned Contractor sees them flagged.
2. When the Contractor marks a defect **Mark Settled**, it moves to `PENDING_VERIFICATION` and you'll see it surfaced both on your Dashboard's Action Required list and in the **Defects Register** (filter to `Pending Verification`).
3. Re-inspect the location on site, then click **Confirm Resolved** if the fix holds up, or **Reject – Not Fixed** to send it back to `IN_PROGRESS` for another attempt.

**D. Review the score and sign off**
1. Once every checklist — rooms, M&E, building-level sections, and External Works instances — is inspected, open **E-IDS Score** to review the final score, rating, and full breakdown. The first time you open it for a project, the gauge sweeps in and the total score counts up; after that it just renders instantly.
2. If any defect is still `OPEN`, `IN_PROGRESS`, or `PENDING_VERIFICATION`, the score page and PDF report stay locked with an explanatory message — work the defect cycle above until none remain.
3. Once every defect is `RESOLVED`, return to the project dashboard and click **Sign Off Project** yourself — confirm the modal, and the project is marked Completed. You don't need to hand this back to an Admin.

![E-IDS Score page with the gauge, Architectural/M&E/External breakdown cards, and pass-rate table](screenshots/11-score-page.png)

### 8.3 Contractor — Step by Step

**A. Log in and find your defects**
1. Log in — you land directly on **My Defects**, scoped to the project(s) you're assigned to. There's no Dashboard, Projects list, or Reports link for your role; this list is your whole job.
2. Defects the Inspector has explicitly flagged via **Notify Contractor** show up in a banner at the top ("N defect(s) flagged for your action") — start there, though the full list below also shows everything on your projects regardless of notification state.

**B. Work each defect through to settlement**
1. Click **Start Repair** on an `OPEN` defect once work begins on site — it moves to `IN_PROGRESS`.
2. Once the repair is physically done, click **Mark Settled**. A confirmation modal tells you this will notify the Inspector that the defect is ready for reinspection — confirm it, and the defect moves to `PENDING_VERIFICATION`.
3. At that point you'll see a read-only "Awaiting Verification" badge — there's nothing more for you to do on that defect until the Inspector reinspects.
4. If the Inspector rejects the repair, it comes back to you as `IN_PROGRESS` — repeat from step 2 once you've fixed it properly. You can never mark your own repair as fully `RESOLVED`; that confirmation always comes from the Inspector or Admin.

![Defects Register showing defects across the lifecycle with their matching action buttons](screenshots/10-defects-register.png)
*Contractors see this same lifecycle from their scoped "My Defects" view — the action button always matches whoever's turn it is.*

---

## 9. Admin — Managing the Question Bank & Guide Content

**Settings → Question Bank** lists every architectural component's checklist. Click a component tab to see its questions; click **Edit** on any question to change its text, defect group, method/tool, tolerance, or input type.

![Question Bank — Floor (A1_FLOOR) with 17 questions, showing which have Guide content](screenshots/16-checklist-items-index.png)

Each question also has an optional **Guide** section — a rich-text editor (bold, lists, links, and inline images, all rendered exactly as authored) for Tools Needed, Inspection Procedure, and Result Thresholds. Leave it blank to keep the "?" icon hidden for that question.

![Question edit screen with the rich-text Guide editor](screenshots/17-checklist-item-edit.png)

---

## 10. Admin — Weightage & Sampling Settings

**Settings** also exposes CIS 7:2021's reference tables directly, editable per Building Category:

![System Settings — rating thresholds, default locations, and the Table 1 overall weightage editor](screenshots/15-settings-weightage.png)

- **Table 1** — overall Architectural/M&E/External % split by Building Category.
- **Table 4** — Principal/Service/Circulation location weighting by Building Category.
- **Table 3** — sample count formula (GFA divisor, min/max samples) by Building Category.
- **Table 2** — architectural component weightage (Floor, Wall, Ceiling, ... including the two QP declaration rows).

Changes apply immediately to scoring on every project — there's no need to re-create existing projects.

---

## 11. Demo / Testing Accounts

Running `docker exec -w /var/www eidsv2 php artisan migrate:fresh --seed` loads a ready-to-test dataset: **3 projects and 5 accounts covering all 3 roles**, sized to genuinely exercise Table 3's sample-count formula rather than a token handful of rooms.

| Role | Account | Password | Scope |
| :--- | :--- | :--- | :--- |
| Admin | `admin@eids.gov.my` | `password` | Sees everything; created all three projects |
| Inspector | `inspector.a@eids.gov.my` | `password` | Assigned to Project 1 and Project 3 |
| Inspector | `inspector.b@eids.gov.my` | `password` | Assigned to Project 2 only |
| Contractor | `contractor.a@eids.gov.my` | `password` | Assigned to Project 1 only — lands on **My Defects** |
| Contractor | `contractor.b@eids.gov.my` | `password` | Assigned to Project 2 and Project 3 — lands on **My Defects** |

**Project 1 — "Taman Merlimau Perdana" (PRJ-2026-001):** Category A, 50 units, Total GFA 10,500 m² → **150 samples** (Table 3's formula in action at real scale). The first 4 samples (all Unit #1: Living Room, Service Area, Passageway, Bedroom 1) carry a hand-built defect narrative — one in each of the four lifecycle states — with the remaining 146 samples fully passing. Also seeded: a Roof section FAIL, an External Drain instance FAIL, and **2 playgrounds** (one demonstrating the multi-instance External Works feature). Score: **95.68 — GOOD**.

| Defect | Location / Component | Status | What to test |
| :--- | :--- | :--- | :--- |
| 1 | Living Room / Internal Wall | `OPEN` | Contractor A sees **Start Repair** |
| 2 | Living Room / Window | `IN_PROGRESS` | Contractor A sees **Mark Settled** |
| 3 | Service Area / Floor | `PENDING_VERIFICATION` | Inspector A sees **Confirm Resolved** / **Reject**; Contractor A sees a read-only "Awaiting Verification" badge |
| 4 | Roof - Section 1 | `RESOLVED` | Inspector A sees **Reopen** |

**Project 2 — "Desa Aman Villa" (PRJ-2026-002):** Category A, 20 units, Total GFA 2,600 m² → 38 samples. **No Car Park** on this project — a live example of Table 2 weightage redistribution. Minimal inspection progress (one `OPEN` defect only) proves Contractor B / Inspector B never see Project 1's data. Score: **87.74 — GOOD**.

**Project 3 — "Kota Laksamana Heights" (PRJ-2026-003):** Category A, 12 units, Total GFA 2,160 m² → 31 samples. The only seeded project that's **fully signed off** (status `Completed`) — every checklist (rooms, M&E, building sections, External Works, including a Playground/Court/Pool) 100% inspected, both defects `RESOLVED`, both QP declarations attached. Use this one to see the unlocked certification-seal button, the fully-stamped Case Ledger, and the actual **Official PDF Certificate**. Score: **99.53 — GOOD**.
