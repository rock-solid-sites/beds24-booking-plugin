---
title: "VPS Site Building"
tags: ["vps", "deployment", "design-doc"]
sources: []
contributors: ["unknown"]
created: 2026-05-21
updated: 2026-05-21
---


## Design Specification

### two-repo structure

- **`beds24-booking-plugin`** (this repo) — the plugin: search form, room
  results, cart, API integration. Plugin Code sessions live here.
- **`tripn-sites`** — per-property site design artifacts: handoff documents,
  child theme specs, content briefs. VPS-side build sessions use tripn-sites'
  per-property handoffs as input.

**Plugin Code sessions should not expand scope into site-design work.** That is
tripn-sites' territory. If a session starts pulling in per-property visual
decisions or child theme details, it has drifted into the wrong repo.

### vps launch pattern

VPS sessions (via SSH) differ from the local WSL2 pattern:

```bash
# From wherever the SSH session lands (typically /home/claude-code/)
claude-mode <built-in preset>
```

No `.claude-mode.json` is available on VPS — use built-in presets only:

| Plugin project preset | Built-in equivalent | Use for |
|---|---|---|
| `architecture` | `methodical` | Design and documentation |
| `v1-build` | `create` | Site build work |
| `rollout` / `bugfix` | `safe` | Live site changes |
| `review` | `explore` | Read-only investigation |

Pass `--base chill` explicitly on VPS when the chill base is wanted.

### vps infrastructure notes

- **aapanel** manages PHP, OpenLiteSpeed, MySQL, WP Toolkit. It does NOT manage
  SSH users — that is standard Linux work (`useradd`, `authorized_keys`).
- **aapanel makes `.user.ini` immutable** (`chattr +i`). EPERM on `.user.ini`
  is expected behavior, not a filesystem failure.
- **OpenLiteSpeed caches static files aggressively.** New filenames bypass cache
  reliably; overwriting files does not. Use versioned filenames for any CSS/JS
  deployed to the VPS.
- **Character limit on `customhead`:** Beds24's "Insert in HTML <HEAD> bottom"
  field has an undocumented ~2,000-character server-side limit. Use "Custom CSS"
  (`bookingcss`) for generated CSS payloads (accepts 6,000+ characters).

### styling contract bridge

The plugin emits DOM structure; per-property themes emit visual presentation.
Per-property design decisions (colors, typography, accent treatments) live in
tripn-sites, not in this repo. The styling contract document (`docs/styling-
contract.md`) defines the interface between them.

