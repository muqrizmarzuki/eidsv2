# Interface Design System: E-IDS v2

## Product Domain & Direction
- **Human**: Government inspectors (JKR, CIDB), private inspection firms, contractor QC engineers.
- **Context**: On-site field inspections on tablets/phones in outdoor ambient light & office desktop review.
- **Feel**: "The Precision Inspector's Ledger" — authoritative, dark emerald chrome, high-contrast data matrices, structured & audit-ready.

## Palette & Color Temperature
- `eids-primary` (`#00342b`): Deep Emerald Navy (Chrome, hero cards, primary action buttons)
- `eids-dark` (`#002720`): Deep Forest Navy (Button hover states, dark surface depth)
- `eids-accent` (`#059669`): Field Emerald (Active progress bars, wizard steps, links)
- `eids-light` (`#34d399`): Mint Glow (Hero score numbers, weightage highlights)
- `neutral-bg` (`#f9fafb`): Canvas background
- `neutral-surface` (`#ffffff`): Card container background
- `status-pass` (`#10b981` / `#d1fae5`): Emerald PASS badge
- `status-fail` (`#ef4444` / `#fee2e2`): Red FAIL badge
- `status-warning` (`#f59e0b` / `#fef3c7`): Amber Warning badge

## Hierarchy & Typography
- **Typeface**: `Hanken Grotesk, sans-serif`
- **Ratio**: ~1.25 scale (`11px` caption · `14px` body · `16px` title · `20px` headline · `30px+` display/score)
- **Weight Lever**: 700/800 bold for hero numbers & scores; 600 semi-bold for labels; 400 regular for body.
- **Tabular Numbers**: Applied to numeric inputs, GFA calculations, mm tolerances, and score tables.

## Spatial Grid & Touch Targets
- **Base Unit**: `4px` grid (`8px`, `12px`, `16px`, `24px`)
- **Density**: Compact workbench density (`p-4`, `p-6` cards; `py-3` table rows)
- **Touch Target Floor**: `44px` minimum height (`min-h-[44px]`) on interactive controls, toggles, and buttons.

## Depth Strategy
- Border-first layering (`border border-gray-100` / `border border-gray-200`)
- Subtle floating elevation for sticky action bars (`shadow-lg backdrop-blur-xs`)
- Soft backdrop blur for overlays (`bg-black/50 backdrop-blur-xs`)
