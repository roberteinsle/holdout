# Design

The palette is based on German military RAL colours (vehicle olive, desert sand, signal orange). It reads as robust and calm, not alarmist. Olive and sand carry the interface; orange is used only for things that need attention.

Only the plain RAL / hex values are used: no military logos, insignia or names.

## Colour roles

| Role | Light mode | Dark mode |
|---|---|---|
| Page background | `#FFFFFF` | `#151619` |
| Cards | white with sand border `#BCAE8B` | `#242529` |
| Text | `#242529` | `#D3D8DB` |
| Muted text | `#615E59` | `#928F88` |
| Primary (buttons, links, navigation) | `#4A5342`, white text | `#A7AD6D`, dark text |
| OK / enough stock | `#616847` | `#A7AD6D` |
| Eat soon / low stock | `#FF860F`, always dark text | `#FF860F` |
| Missing / expired | `#D91E00` | `#FF2300` |

Never rely on colour alone: status is always shown with an icon and a label as well.

## Contrast

Normal text needs at least 4.5:1 (WCAG AA), large text and icons 3:1. White text on signal orange (2.4:1) is never used. Automated tests check the button colours and the muted text contrast.

## Implementation

- Filament panel: hand-built palettes in `app/Support/Palette.php`. Filament's `Color::hex()` keeps only the hue and would turn the muted olive into a bright yellow-green.
- Filled buttons: `app/Filament/Support/ButtonComponent.php` assigns the colour roles above instead of Filament's automatic choice.
- Guest pages (sign-in, registration, invitation): semantic colour tokens in `resources/css/app.css` (`bg-primary`, `text-fg-muted`, …) with a dark-mode variant.
- Avatars are rendered locally as SVG initials; no names are sent to third-party avatar services.
