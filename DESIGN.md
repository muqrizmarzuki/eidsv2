---
name: E-IDS v2 Design System
description: Official design system and UI specifications for the Electronic Inspection Defect System (E-IDS v2)
colors:
  primary: "#00342b"
  primary-dark: "#002720"
  accent: "#059669"
  accent-light: "#34d399"
  neutral-bg: "#f9fafb"
  neutral-surface: "#ffffff"
  neutral-border: "#e5e7eb"
  neutral-text: "#111827"
  status-pass: "#10b981"
  status-fail: "#ef4444"
  status-warning: "#f59e0b"
typography:
  display:
    fontFamily: "Hanken Grotesk, sans-serif"
    fontSize: "1.875rem"
    fontWeight: 700
    lineHeight: 1.2
  headline:
    fontFamily: "Hanken Grotesk, sans-serif"
    fontSize: "1.25rem"
    fontWeight: 700
    lineHeight: 1.3
  body:
    fontFamily: "Hanken Grotesk, sans-serif"
    fontSize: "0.875rem"
    fontWeight: 400
    lineHeight: 1.5
  label:
    fontFamily: "Hanken Grotesk, sans-serif"
    fontSize: "0.75rem"
    fontWeight: 600
    lineHeight: 1.4
    letterSpacing: "0.05em"
rounded:
  sm: "8px"
  md: "12px"
  lg: "16px"
  full: "9999px"
spacing:
  sm: "8px"
  md: "16px"
  lg: "24px"
components:
  button-primary:
    backgroundColor: "{colors.primary}"
    textColor: "{colors.neutral-surface}"
    rounded: "{rounded.md}"
    padding: "10px 24px"
  button-primary-hover:
    backgroundColor: "{colors.primary-dark}"
---

# Design System: E-IDS v2

## Overview

**Creative North Star: "The Precision Inspector's Ledger"**

E-IDS v2 is a specialized, domain-authored industrial web application designed for on-site building defect inspectors, lead auditors, and regulatory bodies in Malaysia. The interface balances high-density data management in office settings with high-legibility, touch-friendly workflows on mobile tablets under bright ambient light on construction sites.

Key Characteristics:
- High contrast, dark emerald primary header surfaces (`#00342b`) paired with clean white cards (`#ffffff`).
- Strict 44px touch-target floor across all interactive inspection controls, radio toggles, and form buttons.
- Clear visual hierarchy with unambiguous status badges (`PASS`, `FAIL`, `PENDING`).
- Integrated step-by-step workflow indicators to guide inspectors across Project Setup, Component Matrix, Inspection, and Report phases.

## Colors

The color palette is derived from official engineering and construction inspection aesthetics, prioritizing high contrast and domain clarity.

### Primary
- **Deep Emerald Navy** (`#00342b`): Primary structural color used on sidebars, hero header cards, top bars, and primary CTAs.

### Secondary
- **Vivid Field Emerald** (`#059669`): Accent color used for progress bars, active step badges, and interactive highlights.
- **Mint Glow** (`#34d399`): Highlight accent for high-value metrics and hero score values.

### Neutral
- **Clean Canvas White** (`#ffffff`): Card containers, form containers, and modal dialog backgrounds.
- **Soft Warm Gray** (`#f9fafb`): Page background tint providing subtle separation from white cards.
- **Subtle Slate Border** (`#e5e7eb`): Container outlines and grid table borders.
- **Charcoal Heading** (`#111827`): Primary text color for high legibility.

### Named Rules
**The One Primary Rule.** `#00342b` is reserved for structural chrome and primary action buttons. Accent green `#059669` directs the user's attention to active progress and next steps.
**The High-Contrast Status Rule.** PASS status badges always use `#10b981` (emerald green text on `#d1fae5` background), and FAIL status badges use `#ef4444` (red text on `#fee2e2` background).

## Typography

**Display & Body Font:** Hanken Grotesk (with fallback to platform sans-serif)
**Icon System:** Material Symbols Outlined (with `.filled` utility class for active icons)

### Hierarchy
- **Display** (Bold, 30px / 1.875rem, line-height 1.2): Main project headlines and top-level score hero numbers.
- **Headline** (Bold, 20px / 1.25rem, line-height 1.3): Card titles and section headers.
- **Title** (Semi-bold, 16px / 1.0rem, line-height 1.4): Component names and field labels.
- **Body** (Regular / Medium, 14px / 0.875rem, line-height 1.5): Form input values, remarks, and table text.
- **Label** (Semi-bold / Bold, 12px / 0.75rem, tracking 0.05em, uppercase): Status badges, table headers, and metadata captions.

## Layout

- **Container Model**: Max width `max-w-7xl` for dashboards and tables; `max-w-3xl` for step-by-step wizard forms and inspect cards.
- **Grid System**: 2-column to 4-column responsive grid (`grid-cols-1 md:grid-cols-2 lg:grid-cols-4`).
- **Touch Ergonomics**: All field controls maintain a minimum height of `44px` (`min-h-[44px]`) to support reliable one-handed tablet use on construction sites.

## Elevation & Depth

Surfaces rely primarily on clean tonal layering and crisp 1px borders rather than heavy ambient shadows. Floating cards and sticky action bars use soft, directional shadows (`shadow-sm` and `shadow-md`) to lift interactive controls above scrolling content.

### Named Rules
**The Border-First Rule.** Card boundaries are established with a 1px border (`border-gray-100` or `border-gray-200`). Shadows are applied sparingly to sticky action bars and modal overlays.

## Shapes

- **Card Containers**: 16px radius (`rounded-2xl`).
- **Buttons & Input Controls**: 12px radius (`rounded-xl`).
- **Pills & Status Badges**: Full radius (`rounded-full`).

## Components

### Buttons
- **Primary CTA**: `bg-eids-primary hover:bg-eids-dark text-white font-bold rounded-xl px-6 py-2.5 min-h-[44px]`
- **Secondary CTA**: `bg-white border border-gray-200 text-gray-700 hover:bg-gray-50 font-bold rounded-xl px-4 py-2.5 min-h-[44px]`
- **Next Action Button**: Prominent `Save & Next →` button combining save operation with progression to the next component slot.

### Inspection Check Toggles
- **Pass/Fail Pill**: `min-h-[44px] min-w-[90px] px-4 py-2.5 border rounded-xl font-bold cursor-pointer transition flex items-center justify-center gap-1.5`

### Inspection Grid Cells
- **Grid Circle**: `w-10 h-10 rounded-full flex items-center justify-center font-bold min-h-[44px] min-w-[44px]`

## Do's and Don't's

### Do:
- **Do** ensure every interactive button and toggle meets the `44px` touch target height minimum.
- **Do** show explicit warning banners when new inspection forms initialize default PASS values.
- **Do** include step-by-step workflow progress indicators on all inspection screens.

### Don't:
- **Don't** use generic default blue/purple accent colors; stick to the approved `eids-primary` and `eids-accent` palette.
- **Don't** allow navigation links to skip saving form data without warning the user.
- **Don't** mix Malay and English labels in error notifications or dialog modals.
