---
name: design-review
description: >-
  Review a web app, Blade view, or component for visual design quality — layout, typography, spacing,
  colour, hierarchy, consistency, interaction patterns, zero emojis adherence, and responsive behaviour.
  Use when conducting design reviews, polishing UI components, reviewing visual layouts, or auditing
  Tailwind CSS styling.
---

# Visual Design Review & Quality Standards (Tailwind CSS & Blade)

Review a web application, Blade view, or UI component for visual design quality, aesthetic polish, component consistency, and responsiveness. This is not a functional or UX friction audit — it verifies that the design looks **clean, professional, coherent, and polished**.

---

## When to Use This Skill

- Before presenting a new view or feature to a client or stakeholder.
- When an interface "looks off" or feels unbalanced, cramped, or unpolished.
- After implementing a new Blade template or Alpine.js component.
- Auditing Tailwind CSS spacing, typography scales, colors, borders, and shadows.
- Enforcing the **Zero Emojis Directive** (verifying pure Heroicons SVGs and styled badge pills).

---

## Core Visual Review Criteria

### 1. Layout, Spacing & Alignment

| Check | Standard (Good) | Anti-Pattern (Bad) |
| :--- | :--- | :--- |
| **Consistent Spacing** | Uniform padding (`p-4`, `p-6`), gap scales (`gap-3`, `gap-4`, `gap-6`) across all cards and tables. | Mixed arbitrary margins (e.g. 13px, 27px) and inconsistent card padding. |
| **Alignment** | Clean vertical and horizontal alignment grids (`items-center`, `justify-between`). | Misaligned text baselines and ragged left edges. |
| **Breathing Room** | Generous whitespace framing containers; clear separation between inputs and labels. | Cramped text touching container borders or buttons crowded against inputs. |
| **Tabular Layouts** | Enclosed in rounded containers (`rounded-2xl border border-gray-100 overflow-x-auto`). | Tables overflowing viewport without scroll wrappers. |

---

### 2. Typography & Hierarchy

| Check | Standard (Good) | Anti-Pattern (Bad) |
| :--- | :--- | :--- |
| **Typeface & Weights** | Font family `font-outfit` or `font-sans`, regular for body, `font-bold` for labels, `font-black` for headers/metrics. | Uniform font weight throughout with zero visual hierarchy. |
| **Size Scale** | Clear scale (`text-[10px]` captions, `text-xs` table data, `text-sm` labels, `text-xl` titles). | Random non-standard font sizes. |
| **Line Height** | `leading-normal` or `leading-relaxed` for body text, `leading-tight` for titles. | Cramped text lines or excessive line spacing. |
| **Data Monospace** | Use `font-mono` for codes (`EMP-001`, `DRV-002`, reference numbers, currency values). | Standard proportional font for tabular IDs making alignment uneven. |

---

### 3. Color, Contrast & Zero Emojis Rule

| Check | Standard (Good) | Anti-Pattern (Bad) |
| :--- | :--- | :--- |
| **Zero Emojis Directive** | Strictly use **Heroicons SVGs** (`<svg>`) or styled Tailwind status badges (`bg-emerald-50 text-emerald-700`). | Using Unicode emojis (`🚀`, `✅`, `⏳`, `⏪`) in UI buttons, headings, or logs. |
| **Color Harmony** | Curated palette (slate/gray neutrals, `#F44336` primary accent, emerald for success, rose for deductions/errors). | Clashing bright rainbow colors with no coherent brand tone. |
| **Contrast Ratio** | WCAG AA compliant text contrast against card backgrounds (minimum 4.5:1). | Muted gray text (`text-gray-300`) on white backgrounds that is unreadable. |
| **Status Consistency** | Emerald for approved/released, Amber for pending, Rose for rejected/deductions. | Switching color meanings across different pages. |

---

### 4. Component Consistency & Visual Finish

| Check | Standard (Good) | Anti-Pattern (Bad) |
| :--- | :--- | :--- |
| **Buttons** | Uniform pill/rounded shapes (`rounded-xl`), consistent padding (`px-4 py-2.5`), hover states (`hover:bg-... transition-all`). | 5 different button radiuses, sizes, and hover styles. |
| **Inputs** | Uniform height, border styling (`border-gray-200 focus:border-[#F44336] rounded-xl text-xs font-bold`). | Mixed input heights, sharp borders, and default browser focus rings. |
| **Cards & Modals** | Consistent border radius (`rounded-2xl` or `rounded-3xl`), border stroke (`border-gray-100`), and subtle shadow (`shadow-sm` or `shadow-2xl`). | Mixing sharp corners with pill corners and inconsistent shadow depths. |
| **Interactive Feedback** | Smooth micro-transitions (`transition-all duration-150`), visible focus states, disabled styles (`opacity-50 cursor-not-allowed`). | Abrupt state changes with zero hover/focus feedback. |

---

### 5. Responsive Behavior

| Check | Standard (Good) | Anti-Pattern (Bad) |
| :--- | :--- | :--- |
| **Breakpoints** | Stacks gracefully on mobile/tablet (`flex-col sm:flex-row`, `grid-cols-1 md:grid-cols-3`). | Fixed desktop widths forcing horizontal body scrolling on mobile. |
| **Table Wrappers** | Wrap all data tables with `<div class="overflow-x-auto">`. | Unwrapped tables clipping on small screens. |
| **Touch Targets** | Minimum tap target of 40x40px for mobile buttons and filter pills. | Tiny, hard-to-tap icon links. |

---

## Design Findings Report Template

When performing a design review, generate findings in this structured format:

```markdown
# Visual Design Review: [Page / Component Name]
**Route**: `route('...')`  
**Blade View**: `resources/views/...`

## Overall Visual Impression
[1-2 sentences on visual polish, consistency, and alignment with design standards.]

## Key Observations

### 1. Strengths & Well-Executed Elements
- [Element 1]: [Why it looks polished]
- [Element 2]: [Clean alignment/typography]

### 2. Layout & Spacing Polish
- [Observation / Recommended Adjustment]

### 3. Typography & Hierarchy
- [Observation / Recommended Adjustment]

### 4. Color, Contrast & Zero Emojis Compliance
- [Verification of SVG icons and badge styling]

## Top 3 High-Impact Visual Fixes
1. [Highest visual impact improvement]
2. [Second improvement]
3. [Third improvement]
```
