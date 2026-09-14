<?php

namespace Database\Seeders;

use App\Models\ChecklistItem;
use Illuminate\Database\Seeder;

/**
 * Seeds Annex A's per-component question bank. Floor (A1_FLOOR) follows the
 * exact phrasing/order approved in the UI mockup (Scanned_20260720-1530.pdf),
 * including its one worked Guide example (Floor Levelness). The remaining
 * components are converted from CIS 7:2021's own "Requirements" columns into
 * the same plain-English, flat-list style; Guide content for those ships
 * empty and is expected to be filled in incrementally by admins — see
 * docs/superpowers/specs/2026-08-05-cis7-scoring-and-checklist-redesign-design.md §3.
 */
class ChecklistItemSeeder extends Seeder
{
    public function run(): void
    {
        ChecklistItem::query()->delete();

        foreach ($this->bank() as $componentCode => $items) {
            foreach ($items as $i => $item) {
                ChecklistItem::create(array_merge([
                    'applies_to'   => $componentCode,
                    'sort_order'   => $i + 1,
                    'method_tool'  => 'Visual',
                    'tolerance_text' => null,
                    'input_type'   => 'pass_fail',
                    'tolerance_max_mm' => null,
                    'guide_tools' => null,
                    'guide_procedure' => null,
                    'guide_result_thresholds' => null,
                ], $item));
            }
        }
    }

    private function bank(): array
    {
        return [
            'A1_FLOOR' => [
                ['defect_group' => 'Finishing', 'question_text' => 'No construction stains on the floor surface.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'Floor colour and tone are uniform.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'Floor surface is smooth and even (not rough or patchy).', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'No foreign materials on the floor surface.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'Floor finish texture is consistent.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'Paint finish (if applicable) is good without brush marks, blisters or peeling.', 'method_tool' => 'Visual'],
                [
                    'defect_group' => 'Alignment and Evenness', 'question_text' => 'Floor levelness is within the tolerated limit.',
                    'method_tool' => 'Spirit level 1.2m and steel wedge', 'tolerance_text' => '≤ 3 mm / 1.2 m',
                    'input_type' => 'numeric_with_tolerance', 'tolerance_max_mm' => 3.00,
                ],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Floor slope in wet areas is in the correct direction.', 'method_tool' => 'Spirit level 1.2m'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Joints are aligned with skirting or walls.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Joints between tiles are straight and aligned.', 'method_tool' => 'Visual'],
                [
                    'defect_group' => 'Alignment and Evenness', 'question_text' => 'Lippage between two tiles is within the allowable limit.',
                    'method_tool' => 'L-square (200 mm × 300 mm) and steel wedge', 'tolerance_text' => '≤ 1 mm',
                    'input_type' => 'numeric_with_tolerance', 'tolerance_max_mm' => 1.00,
                ],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'No protrusion or uneven spots that could cause tripping.', 'method_tool' => 'Visual & Physical'],
                ['defect_group' => 'Cracks and Damages', 'question_text' => 'No cracks or damages (cracked, chipped or scratched tiles).', 'method_tool' => 'Visual'],
                ['defect_group' => 'Cracks and Damages', 'question_text' => 'No unevenness or settlement on the floor.', 'method_tool' => 'Visual & Physical'],
                ['defect_group' => 'Hollowness/Delamination', 'question_text' => 'No loose or unstable floor panels.', 'method_tool' => 'Visual & Physical'],
                ['defect_group' => 'Hollowness/Delamination', 'question_text' => 'No hollow sound when tapped.', 'method_tool' => 'Tapping rod & Auditory (hearing)'],
                ['defect_group' => 'Hollowness/Delamination', 'question_text' => 'No delamination on the floor.', 'method_tool' => 'Visual & Physical'],
            ],

            'A2_WALL' => [
                ['defect_group' => 'Finishing', 'question_text' => 'No construction stains on the wall surface.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'Paintwork is good, with no drips, brush marks, pin holes, blistering or peeling.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'Wall colour and tone are uniform.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'No rough or patchy surface from touch-up work.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'Wall finish texture is consistent.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Wall surface is even, within tolerance.', 'method_tool' => 'Spirit level 1.2m & steel wedge', 'tolerance_text' => '≤ 3 mm / 1.2 m', 'input_type' => 'numeric_with_tolerance', 'tolerance_max_mm' => 3.00],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Wall is vertical, within tolerance.', 'method_tool' => 'Spirit level 1.2m & steel wedge', 'tolerance_text' => '≤ 3 mm / 1.2 m', 'input_type' => 'numeric_with_tolerance', 'tolerance_max_mm' => 3.00],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Walls meet at a right angle at corners.', 'method_tool' => 'L-square (200 mm × 300 mm) & steel wedge', 'tolerance_text' => '≤ 4 mm / 300 mm', 'input_type' => 'numeric_with_tolerance', 'tolerance_max_mm' => 4.00],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Joints are aligned between tiles.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Lippage between two tiles is within the allowable limit.', 'method_tool' => 'L-square (200 mm × 300 mm) and steel wedge', 'tolerance_text' => '≤ 1 mm', 'input_type' => 'numeric_with_tolerance', 'tolerance_max_mm' => 1.00],
                ['defect_group' => 'Cracks and Damages', 'question_text' => 'No visible damages, dents, scratches or corrosion on the wall.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Cracks and Damages', 'question_text' => 'No warpage detected on tiled or timber-panel walls.', 'method_tool' => 'Visual & Physical'],
                ['defect_group' => 'Hollowness/Delamination', 'question_text' => 'No hollow sound when tapped.', 'method_tool' => 'Tapping rod & Auditory (hearing)'],
                ['defect_group' => 'Hollowness/Delamination', 'question_text' => 'No sign of delamination.', 'method_tool' => 'Visual & Physical'],
                ['defect_group' => 'Jointing', 'question_text' => 'Edges are straight, aligned and consistent.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Jointing', 'question_text' => 'Joints are not visible where they should be concealed.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Jointing', 'question_text' => 'Grout joints are of consistent size and properly filled.', 'method_tool' => 'Visual'],
            ],

            'A3_CEILING' => [
                ['defect_group' => 'Finishing', 'question_text' => 'No construction stain marks (e.g. leakages, excess paint or plaster).', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'Ceiling colour tone is consistent.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'Paintwork is good (opacity, no brush marks, pin holes, blistering).', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'Ceiling surface is smooth, even, not wavy and not sagging.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Ceiling panels are level with each other.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Ceiling edges are straight and aligned.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Ceiling grid is straight and aligned.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Ceiling panels do not warp and are laid neatly into the grid.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Cracks and Damages', 'question_text' => 'No damages (spalling, chipped panels, cracks).', 'method_tool' => 'Visual'],
                ['defect_group' => 'Cracks and Damages', 'question_text' => 'No sign of corrosion on ceiling grids or metal panels.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Roughness and Patchiness', 'question_text' => 'No rough surface on finished concrete.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Roughness and Patchiness', 'question_text' => 'Cold joints or formwork joints are ground smooth.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Roughness and Patchiness', 'question_text' => 'Touch-up work shows no rough or patchy surface.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Jointing', 'question_text' => 'Joints between ceiling and wall are neat and consistent.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Jointing', 'question_text' => 'Joints between ceiling tee and panel are neat and consistent.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Jointing', 'question_text' => 'Access opening joints are neat and of consistent width.', 'method_tool' => 'Visual'],
            ],

            'A4_DOOR' => [
                ['defect_group' => 'Joints and Gaps', 'question_text' => 'Gap between the bottom of the door panel and the finished floor is consistent and within tolerance.', 'method_tool' => 'Steel wedge', 'tolerance_text' => '≤ 5 mm', 'input_type' => 'numeric_with_tolerance', 'tolerance_max_mm' => 5.00],
                ['defect_group' => 'Joints and Gaps', 'question_text' => 'No visible gaps between the door frame and the wall.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Joints and Gaps', 'question_text' => 'Joints between door frame and wall are neat, internally and externally.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Joints and Gaps', 'question_text' => 'Gap between the door panel and frame is consistent and within tolerance.', 'method_tool' => 'Steel gauge', 'tolerance_text' => '≤ 5 mm', 'input_type' => 'numeric_with_tolerance', 'tolerance_max_mm' => 5.00],
                ['defect_group' => 'Joints and Gaps', 'question_text' => 'No visible gaps at door panel and frame joints.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Door is aligned and level with the opening and surrounding wall.', 'method_tool' => 'Visual & spirit level 1.2m'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Double-panel doors are flush with each other.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Door frame and panel are flush.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Door panel and frame corners are maintained at right angles.', 'method_tool' => 'L-square (200 mm × 300 mm)'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'No rattling sound when the door is closed.', 'method_tool' => 'Physical & Auditory (hearing)'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'No stain marks or visible damages on the door.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'Door panel is not sagging or warped.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'Door joints and nail holes are filled, sanded and well painted.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'No additional timber strips added for site adjustment.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'Glazing is clean and evenly sealed with gasket.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'No sign of corrosion.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'Paintwork is good, including the top and bottom of the door panel.', 'method_tool' => 'Visual & Angle Mirror'],
                ['defect_group' => 'Functionality', 'question_text' => 'Door opens, closes and locks with ease.', 'method_tool' => 'Physical'],
                ['defect_group' => 'Functionality', 'question_text' => 'No squeaky sound during opening and closing.', 'method_tool' => 'Physical & Auditory (hearing)'],
                ['defect_group' => 'Functionality', 'question_text' => 'Lockset is functional.', 'method_tool' => 'Physical'],
                ['defect_group' => 'Accessories', 'question_text' => 'Accessories fit well with no construction stains or corrosion.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Accessories', 'question_text' => 'No missing or defective accessories (lockset, hinges, door closer, etc.).', 'method_tool' => 'Visual'],
                ['defect_group' => 'Accessories', 'question_text' => 'Screws are not over-tightened and have no defective heads.', 'method_tool' => 'Visual'],
            ],

            'A5_WINDOW' => [
                ['defect_group' => 'Joints and Gaps', 'question_text' => 'No visible gaps between the window frame and the wall.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Joints and Gaps', 'question_text' => 'Joints between window frame and wall are neat, internally and externally.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Joints and Gaps', 'question_text' => 'No visible gaps at window panel and frame joints.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Joints and Gaps', 'question_text' => 'Gap between window panel and frame is consistent.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Window is aligned and level with the opening and surrounding wall.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Window panel and frame corners are maintained at right angles.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'No stain marks or visible damages on the window.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'Louvered window glass panels are of the correct length.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'Glazing is clean and evenly sealed with sealant or gasket.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'No sign of corrosion.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'No patchy paintwork from touch-up work.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Functionality', 'question_text' => 'Window opens, closes and locks with ease.', 'method_tool' => 'Physical'],
                ['defect_group' => 'Functionality', 'question_text' => 'No squeaky sound during opening and closing.', 'method_tool' => 'Physical & Auditory (hearing)'],
                ['defect_group' => 'Functionality', 'question_text' => 'No sign of leakage.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Accessories', 'question_text' => 'Lock sets fit well and are aligned.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Accessories', 'question_text' => 'Accessories fit well with no construction stains or corrosion.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Accessories', 'question_text' => 'No missing or defective accessories.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Accessories', 'question_text' => 'Screws are not over-tightened and have no defective heads.', 'method_tool' => 'Visual'],
            ],

            'A6_FIXTURES' => [
                ['defect_group' => 'Joints and Gaps', 'question_text' => 'Gaps and joints are consistent and neat.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Joints and Gaps', 'question_text' => 'Welding joints are ground smooth or flushed.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Fixture is level and in alignment.', 'method_tool' => 'Visual & Spirit Level 1.2m'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'No stain marks or visible damages on the fixture.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'Paintwork is good.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Functionality', 'question_text' => 'Fixture is functional, secured and safe.', 'method_tool' => 'Visual & Physical'],
                ['defect_group' => 'Accessories', 'question_text' => 'No missing or defective accessories (screws, bottle trap, hose, handles, hinges).', 'method_tool' => 'Visual'],
                ['defect_group' => 'Accessories', 'question_text' => 'Accessories fit well with no stains.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Accessories', 'question_text' => 'No sign of corrosion.', 'method_tool' => 'Visual'],
            ],

            'A7_ROOF' => [
                ['defect_group' => 'Finishing', 'question_text' => 'No construction stain marks or rust.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'No rough surface on flat roof finishes.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'Paintwork is good.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'Colour tone is consistent.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Roof is even and level, with no tripping hazard.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Roof falls in the right direction.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Roof tiles are aligned (pitched roof).', 'method_tool' => 'Visual'],
                ['defect_group' => 'Cracks and Damages', 'question_text' => 'No visible damages (cracks, chippings, stripping, sharp protrusions).', 'method_tool' => 'Visual'],
                ['defect_group' => 'Construction', 'question_text' => 'No sign of leakage.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Construction', 'question_text' => 'Any protrusion is properly dressed.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Construction', 'question_text' => 'No sign of clogging or ponding.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Construction', 'question_text' => 'Openings are sealed to prevent pest invasion.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Construction', 'question_text' => 'RWDP inlet is lower than the surrounding gutter invert level.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Construction', 'question_text' => 'Gutter and RWDP inlet are covered to prevent blockage where practical.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Construction', 'question_text' => 'Fixtures (e.g. solar cell roof) are neatly and securely installed.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Jointing', 'question_text' => 'Joint width is consistent and neat.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Jointing', 'question_text' => 'Laps at joints and vertical abutment details are proper.', 'method_tool' => 'Visual'],
            ],

            'A8_EXT_WALL' => [
                ['defect_group' => 'Finishing', 'question_text' => 'No stain marks (mortar, paint, drips) on the wall.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'Colour tone is consistent, good paintwork, no efflorescence or fading.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'No rough or patchy surface.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'No sign of corrosion (cladding).', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'Surface is free from peeling, blister and chalkiness (paint).', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'Finish texture is consistent (architectural coating).', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Wall is aligned and not wavy.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Edges are straight and aligned.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Surface is even.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Cracks and Damages', 'question_text' => 'No visible damages, dents or scratches.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Construction', 'question_text' => 'Weep holes are provided as specified.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Construction', 'question_text' => 'Fixtures (e.g. aluminium strip, glass holder) are neatly and securely installed.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Jointing', 'question_text' => 'Marking is consistent and neat.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Jointing', 'question_text' => 'Joints are aligned between tiles and consistent in size.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Jointing', 'question_text' => 'Gaps around openings are properly sealed.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Jointing', 'question_text' => 'Joints are of regular width as specified.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Jointing', 'question_text' => 'Sealant material is compatible with the cladding.', 'method_tool' => 'Visual'],
            ],

            'A9_APRON_DRAIN' => [
                ['defect_group' => 'Finishing', 'question_text' => 'No stain marks on the apron/drain surface.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'No rough or patchy surface.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'No sign of corrosion.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Edge is straight and even.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Cracks and Damages', 'question_text' => 'No visible damages or defects.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Cracks and Damages', 'question_text' => 'Structure is intact with no settlement.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Fall/Gradient', 'question_text' => 'Free flowing with no water ponding.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Joints and Gaps', 'question_text' => 'Joint width is consistent and neat.', 'method_tool' => 'Visual'],
            ],

            'ME_FITTING' => [
                ['defect_group' => 'Joints and Gaps', 'question_text' => 'Gaps and joints on the M&E fitting are consistent and neat.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'The M&E fitting is aligned, levelled and straight.', 'method_tool' => 'Visual & Spirit level'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'No stain marks or visible damages/defects on the M&E fitting.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Functionality and Safety', 'question_text' => 'The M&E fitting is functional, secured and safe.', 'method_tool' => 'Visual & Physical'],
                ['defect_group' => 'Accessories Defects', 'question_text' => 'No missing or defective accessories (e.g. screw at power point/switches/floor trap, screw cap, floor trap filter).', 'method_tool' => 'Visual'],
                ['defect_group' => 'Accessories Defects', 'question_text' => 'Accessories fit well with no stains.', 'method_tool' => 'Visual'],
            ],

            'A10_CAR_PARK' => [
                ['defect_group' => 'Finishing', 'question_text' => 'No stain marks.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'Colour is consistent and paintwork is good.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'No rough or patchy surface.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Surface is even.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Falls in the right direction.', 'method_tool' => 'Spirit Level 1.2m'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Edge is straight.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'No visible damages or defects.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'No missing or defective accessories.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'No sign of corrosion.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Functionality', 'question_text' => 'Securely fixed, functional and safe.', 'method_tool' => 'Visual & Physical'],
                ['defect_group' => 'Joints and Gaps', 'question_text' => 'Joint width is consistent and neat.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Joints and Gaps', 'question_text' => 'No visible gaps for M&E fittings.', 'method_tool' => 'Visual'],
            ],

            'EXT_LINKWAY' => [
                ['defect_group' => 'Finishing', 'question_text' => 'No stain marks on the floor, column, ceiling or roof covering.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'Colour is consistent and paintwork is good.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'No rough or patchy surface.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Surface is even.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Edge is straight.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'No visible damages or defects.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'No missing or defective accessories.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'No sign of corrosion.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Functionality', 'question_text' => 'Securely fixed, functional and safe.', 'method_tool' => 'Visual & Physical'],
                ['defect_group' => 'Joints and Gaps', 'question_text' => 'Joint width is consistent and neat.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Joints and Gaps', 'question_text' => 'No visible gaps for M&E fittings.', 'method_tool' => 'Visual'],
            ],

            'EXT_DRAIN' => [
                ['defect_group' => 'Drain', 'question_text' => 'No stain marks on the drain.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Drain', 'question_text' => 'No rough or patchy surface.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Drain', 'question_text' => 'No patchiness or brush marks on the drain grating.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Drain', 'question_text' => 'No sign of corrosion on the drain grating.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Drain', 'question_text' => 'Drain grating is properly painted.', 'method_tool' => 'Visual & Physical'],
                ['defect_group' => 'Drain', 'question_text' => 'Finishes are even, level, aligned and consistent.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Drain Cover / Inspection Chamber', 'question_text' => 'Level, and does not warp or rock.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Drain Cover / Inspection Chamber', 'question_text' => 'Cover is level with the frame.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Drain Cover / Inspection Chamber', 'question_text' => 'No visible cracks or damages.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Drain Cover / Inspection Chamber', 'question_text' => 'Fixtures installed are safe, secured and functional.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Drain Cover / Inspection Chamber', 'question_text' => 'Free flowing with no water ponding or siltation.', 'method_tool' => 'Visual & Physical'],
                ['defect_group' => 'Drain Cover / Inspection Chamber', 'question_text' => 'Drain grating is safely and securely fixed and functional.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Drain Cover / Inspection Chamber', 'question_text' => 'Joint width is consistent and neat.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Drain Cover / Inspection Chamber', 'question_text' => 'Gap between drain covers is within the allowable limit.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Drain Cover / Inspection Chamber', 'question_text' => 'Gap between sides of the drain is within the allowable limit.', 'method_tool' => 'Steel measuring tape', 'tolerance_text' => '5-10 mm'],
                ['defect_group' => 'Drain Cover / Inspection Chamber', 'question_text' => 'Inspection chambers are level with surroundings, within tolerance for protrusion.', 'method_tool' => 'Steel measuring tape', 'tolerance_text' => '5-10 mm'],
                ['defect_group' => 'Drain Cover / Inspection Chamber', 'question_text' => 'Inspection chamber covers are seated flush and properly sealed.', 'method_tool' => 'Visual & Steel measuring tape', 'tolerance_text' => '≤ 20 mm protrusion'],
            ],

            'EXT_ROADWORK' => [
                ['defect_group' => 'Finishing', 'question_text' => 'No stain marks.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'Colour is consistent and paintwork is good.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'No rough or patchy surface.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Surface is even.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Edge is straight.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'No water ponding.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'No visible damages or defects.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'No missing or defective accessories (signage, lighting).', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'No sign of corrosion.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Functionality', 'question_text' => 'Securely fixed, functional and safe.', 'method_tool' => 'Visual & Physical'],
                ['defect_group' => 'Joints and Gaps', 'question_text' => 'Joint width is consistent and neat.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Joints and Gaps', 'question_text' => 'No visible gaps for M&E fittings.', 'method_tool' => 'Visual'],
            ],

            'EXT_FOOTPATH' => [
                ['defect_group' => 'Finishing', 'question_text' => 'No stain marks.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'Colour is consistent and paintwork is good.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'No rough or patchy surface.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Surface is even.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Edge is straight.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'No visible damages or defects.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'No missing or defective accessories (benches, signage, railing).', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'No sign of corrosion.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'No depression or bald patches on the turfing.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'Turfing is done evenly with no dead grass or weeds.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Functionality', 'question_text' => 'Securely fixed, functional and safe.', 'method_tool' => 'Visual & Physical'],
                ['defect_group' => 'Joints and Gaps', 'question_text' => 'Joint width is consistent and neat.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Joints and Gaps', 'question_text' => 'No visible gaps for M&E fittings.', 'method_tool' => 'Visual'],
            ],

            'EXT_FENCE_GATE' => [
                ['defect_group' => 'Finishing', 'question_text' => 'No stain marks.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'Colour is consistent and paintwork is good.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'No rough or patchy surface.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Surface is even.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Edge is straight.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Piers and gate are vertical, perpendicular and straight; the gate is parallel and aligned.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'No visible damages or defects.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'No missing or defective accessories.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'No sign of corrosion.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Functionality', 'question_text' => 'Securely fixed, functional and safe.', 'method_tool' => 'Visual & Physical'],
                ['defect_group' => 'Joints and Gaps', 'question_text' => 'Joint width is consistent and neat.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Joints and Gaps', 'question_text' => 'No visible gaps for M&E fittings.', 'method_tool' => 'Visual'],
            ],

            'EXT_PLAYGROUND' => [
                ['defect_group' => 'Finishing', 'question_text' => 'No stain marks.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'Colour is consistent and paintwork is good.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'No rough or patchy surface.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Surface is even.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Edge is straight.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Water flows freely with no ponding or siltation.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'No visible damages or defects.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'No missing or defective accessories.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'No sign of corrosion.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Functionality', 'question_text' => 'Securely fixed, functional and safe.', 'method_tool' => 'Visual & Physical'],
                ['defect_group' => 'Joints and Gaps', 'question_text' => 'Joint width is consistent and neat.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Joints and Gaps', 'question_text' => 'No visible gaps for M&E fittings.', 'method_tool' => 'Visual'],
            ],

            'EXT_COURT' => [
                ['defect_group' => 'Finishing', 'question_text' => 'No stain marks.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'Colour is consistent and paintwork is good.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'No rough or patchy surface.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Surface is even.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Edge is straight.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'No water ponding.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'No visible damages or defects.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'No missing or defective accessories (net post, fencing, benches).', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'No sign of corrosion.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Functionality', 'question_text' => 'Securely fixed, functional and safe.', 'method_tool' => 'Visual & Physical'],
                ['defect_group' => 'Joints and Gaps', 'question_text' => 'Joint width is consistent and neat.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Joints and Gaps', 'question_text' => 'No visible gaps for M&E fittings.', 'method_tool' => 'Visual'],
            ],

            'EXT_POOL' => [
                ['defect_group' => 'Finishing', 'question_text' => 'No stain marks.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'Colour is consistent and paintwork is good.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Finishing', 'question_text' => 'No rough or patchy surface.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Surface is even.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Alignment and Evenness', 'question_text' => 'Edge is straight.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'No visible damages or defects (overflow drain, pool deck, ladder, railing).', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'No missing or defective accessories.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'No sign of delamination.', 'method_tool' => 'Visual & Physical'],
                ['defect_group' => 'Materials and Damages', 'question_text' => 'No sign of corrosion.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Functionality', 'question_text' => 'No chockage/blockage.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Functionality', 'question_text' => 'Securely fixed, functional and safe.', 'method_tool' => 'Visual & Physical'],
                ['defect_group' => 'Joints and Gaps', 'question_text' => 'Joint width is consistent and neat.', 'method_tool' => 'Visual'],
                ['defect_group' => 'Joints and Gaps', 'question_text' => 'No visible gaps for M&E fittings.', 'method_tool' => 'Visual'],
            ],
        ];
    }
}
