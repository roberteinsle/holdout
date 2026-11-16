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

## Logo and icons

The mark is a storage tin with battlements: supplies and a stronghold in one. Sand `#CFC4B2` on olive `#4A5342`, with signal orange only as a narrow label. The word "holdout" is set in Inter Display ExtraBold, converted to outlines, so no web font is needed.

All files are in `public/`:

| File | Use |
|---|---|
| `logo.svg`, `logo-dark.svg` | Mark with wordmark; top bar and the guest pages (dark text `#242529` / light text `#D3D8DB`) |
| `mark.svg`, `mark-dark.svg` | Mark alone without a background, for print or small spaces |
| `favicon.svg`, `favicon.ico` | Browser tab. `favicon.ico` holds 16, 32 and 48 px; the 16 px version leaves out the grooves (`favicon-16.svg`) |
| `icon.svg`, `icon-192.png`, `icon-512.png` | App icon with rounded corners |
| `icon-maskable.svg`, `icon-maskable-*.png`, `apple-touch-icon.png` | Full-bleed icon for Android masks and the iOS home screen (the system rounds the corners) |
| `manifest.json` | Web app manifest: name, colours and icons, for installing Holdout on a phone (#14) |

`resources/views/partials/head-icons.blade.php` adds the icons, the theme colour and the manifest to every page (Filament via a render hook, the guest layout via `@include`). The PNGs and the ICO are rendered from the SVGs; when the mark changes, render them again in the sizes above.

## Implementation

- Filament panel: hand-built palettes in `app/Support/Palette.php`. Filament's `Color::hex()` keeps only the hue and would turn the muted olive into a bright yellow-green.
- Filled buttons: `app/Filament/Support/ButtonComponent.php` assigns the colour roles above instead of Filament's automatic choice.
- Guest pages (household setup, "Who are you?"): semantic colour tokens in `resources/css/app.css` (`bg-primary`, `text-fg-muted`, …) with a dark-mode variant.
- Avatars are rendered locally as SVG initials; no names are sent to third-party avatar services.
