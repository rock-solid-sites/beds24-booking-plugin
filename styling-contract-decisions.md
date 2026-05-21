---
title: "Styling Contract Decisions"
tags: ["architecture", "css", "design-doc"]
sources: []
contributors: ["unknown"]
created: 2026-05-21
updated: 2026-05-21
---


## Design Specification

### decision 1 — theme.json consumption is the primary visual customization mechanism

The plugin reads design tokens from the active theme's `theme.json` when present.
This is the primary path for block themes. For the broader WP.org case, block
themes are the current direction.

### decision 2 — plugin admin settings are the fallback

When a theme does not define the relevant tokens (classic themes, hybrid themes,
themes that haven't adopted FSE), the plugin's admin UI lets operators configure
values manually. Settings stored in `wp_options` via WordPress Settings API.
Key pattern: `beds24_token_{role}` (hyphens converted to underscores).

### decision 3 — css variables are the underlying transport

Both theme.json tokens and admin settings produce CSS custom properties on the
plugin's root element. **Variable namespace: `--beds24-*`.**

All plugin styling references these variables. The DOM does not know or care
which source populated them. Themes can additionally override variables directly
via their own CSS.

### decision 4 — the plugin renders structure; the theme renders character

Distinctive visual elements (badges, polaroid treatments, marquees, display
typography, decorative borders) are theme concerns. The plugin renders booking
flow structure with predictable, themeable DOM. Themes layer character on top.

### decision 5 — iframe css is generated programmatically

For the Beds24 iframe, the plugin generates a complete CSS payload from
configured tokens and displays it in plugin admin for the operator to copy into
Beds24's "Custom CSS" field. The operator does not write CSS by hand.

**Known friction:** Operators may forget to regenerate and re-paste when tokens
change. This is the most common post-rollout customization friction point.

### class naming convention

BEM with `beds24-` prefix. Examples:
- Block: `.beds24-room-card`
- Element: `.beds24-room-card__photo`
- Modifier: `.beds24-room-card--unavailable`

### token roles (color)

| Role | Default | Where used |
|---|---|---|
| `primary` | `#2563eb` | Confirm Booking button, selected highlight |
| `primary-text` | `#ffffff` | Text on primary surfaces |
| `accent` | `#f59e0b` | Price emphasis, badges |
| `surface` | `#ffffff` | Room card/cart backgrounds |
| `surface-text` | `#1f2937` | Body text |
| `surface-muted` | `#6b7280` | Descriptions, metadata |
| `border` | `#e5e7eb` | Card borders, dividers |
| `success` | `#10b981` | Available indicators |
| `unavailable` | `#9ca3af` | Sold-out states |
| `error` | `#dc2626` | Validation error text |
| `error-bg` | `#fef2f2` | Error region background |
| `error-border` | `#fecaca` | Error region border |

Source: `docs/styling-contract.md`

