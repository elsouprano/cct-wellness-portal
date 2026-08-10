# Landing Page Design Override

**Page:** `/` (welcome.blade.php)
**Pattern:** Enterprise Gateway / Storytelling Landing Page
**Style Base:** Organic Biophilic (with Academic Palette Override)

## Purpose
The landing page serves as the public entry point for the CCT Wellness Portal. Unlike the internal dashboards (which use a calming Sage Green), this page uses the institution's colors (Navy & Gold) to establish academic trust and authority, while retaining organic SVG shapes to maintain the wellness identity.

## Token Overrides
These CSS variables should be injected into the `:root` or `body` of the landing page specifically, overriding the global `app.css`.

| Role | Hex | CSS Variable |
|------|-----|--------------|
| Primary | `#1E3A5F` | `--color-primary` (CCT Navy) |
| On Primary | `#FFFFFF` | `--color-on-primary` |
| Secondary | `#2563EB` | `--color-secondary` |
| Accent/CTA | `#A16207` | `--color-accent` (Warm Gold) |
| Background | `#F8FAFC` | `--color-background` (Slate Off-White) |
| Foreground | `#0F172A` | `--color-foreground` |

## Typography Overrides
- **Heading Font:** `Lora` (Serif) for `h1` and `h2` to evoke academic prestige.
- **Body Font:** Keep global sans-serif (Inter/Nunito/System) for readability.

```css
@import url('https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap');
.font-lora { font-family: 'Lora', serif; }
```

## Structure
1. **Hero:** 
   - Lora heading.
   - Organic blob background elements (`fill-primary/5` or `fill-accent/10`).
   - Strong CTAs (Log In / Register).
2. **Value Prop Grid:** 
   - 3 columns.
   - Heroicons (SVG).
   - "Self-Discovery", "Guidance", "Campus Life".
3. **Footer:** Simple copyright line.
