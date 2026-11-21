# CCT Wellness Portal — Master Design System (Direction A: Refined Institutional)

> **Source of Truth**: `resources/css/app.css` (`:root`) mapped in `tailwind.config.js`.
> Applies to the City College of Tagaytay Guidance & Counseling Services Student Individual Inventory Management System.

## 1. Color Tokens

| Role | CSS Variable | Hex | Tailwind Token |
| :--- | :--- | :--- | :--- |
| Brand Primary | `--color-primary` | `#7F1D1D` | `primary` |
| Brand Hover | `--color-primary-hover` | `#631515` | `primary-hover` |
| Brand Subtle | `--color-primary-subtle` | `#FBF4F4` | `primary-subtle` |
| On Primary | `--color-on-primary` | `#FFFFFF` | `on-primary` |
| Canvas | `--color-background` | `#FAF8F5` | `background` |
| Surface | `--color-surface` | `#FFFFFF` | `surface` |
| Surface Subtle | `--color-surface-subtle` | `#F5F2EC` | `surface-subtle` |
| Primary Ink | `--color-foreground` | `#1C1917` | `foreground` |
| Secondary Ink | `--color-muted-foreground` | `#57534E` | `muted-foreground` |
| Border | `--color-border` | `#E5DFD5` | `border` |
| Strong Border | `--color-border-strong` | `#CFC7BA` | `border-strong` |
| Success | `--color-success` / `subtle` / `border` / `foreground` | `#1F6F43` / `#ECF6F0` / `#B7DFC7` / `#165332` | `success*` |
| Warning | `--color-warning` / `subtle` / `border` / `foreground` | `#B45309` / `#FEF7EC` / `#F2D29B` / `#7C3705` | `warning*` |
| Info | `--color-info` / `subtle` / `border` / `foreground` | `#1E4E79` / `#EFF5FA` / `#BDD5EA` / `#163A5B` | `info*` |
| Destructive | `--color-destructive` / `subtle` / `border` / `foreground` | `#B91C1C` / `#FDF2F2` / `#F5C2C2` / `#991B1B` | `destructive*` |

## 2. Typography
- **Body**: `DM Sans` (`font-sans`), `16px` (`1rem`) root size, unitless `line-height: 1.5`, `text-wrap: pretty`.
- **Headings**: `Plus Jakarta Sans` (`font-heading`), weights `600/700/800`, `text-wrap: balance`.

## 3. Spacing, Radius & Elevation
- **Spacing**: `--space-xs` (`0.25rem`) through `--space-3xl` (`4rem`).
- **Radius**: `rounded-token-sm` (`0.375rem`), `rounded-token-md` (`0.625rem`), `rounded-token-lg` (`0.875rem`).
- **Shadows**: Crisp neutral elevation (`shadow-2xs`, `shadow-xs`, `shadow-sm`, `shadow-md`, `shadow-lg`, `shadow-xl`); never glowy shadows, gradients, or glassmorphism (`backdrop-blur`).

## 4. Shared Components
- Buttons: `<x-primary-button>`, `<x-secondary-button>`, `<x-danger-button>` (`min-h-[44px]`, `cursor-pointer`, loading spinner, no layout shift).
- Inputs: `<x-text-input>`, `<x-input-label>`, `<x-input-error role="alert">`.
- Badges: `<x-badge variant="neutral|primary|success|warning|danger|info" icon="...">` — always pair severity/status with icon + text.
- Overlays: `<x-modal>`, `<x-confirm-action-modal>`, `<x-logout-modal>`, `<x-toast role="status">`.
- Data Views: `<x-data-table>` + `<x-pagination>` (server and client modes).
- Icons: `<x-icon>` (Heroicons SVG paths only; zero emojis).

## 5. Accessibility (WCAG 2.2 AA)
- Text contrast ≥ 4.5:1; UI boundaries ≥ 3:1; Forced Colors Mode supported.
- Global `:focus-visible` outline (`2px solid var(--color-ring)`, `outline-offset: 2px`).
- Skip-to-content link (`.skip-link` -> `#main-content`), semantic landmarks, `44px` minimum touch targets, `prefers-reduced-motion` honored.
