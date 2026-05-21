---
title: "Template: Site-Building Session (VPS)"
tags: ["template", "vps", "design-doc"]
sources: []
contributors: ["unknown"]
created: 2026-05-21
updated: 2026-05-21
---


## Design Specification

### pre-session

- [ ] Read `tripn-sites` per-property handoff document
- [ ] Confirm VPS SSH access and the `claude-code` SSH user is set up
- [ ] Confirm property's WordPress site exists on the VPS (aapanel created site)

### theme setup

- [ ] Active theme identified from handoff (child theme or block theme slug)
- [ ] Theme installed and activated via WP-CLI: `wp theme activate <slug>`
- [ ] `theme.json` reviewed for color palette slugs
- [ ] Plugin's settings page updated: token role → theme slug mapping

### page creation

- [ ] Home page created with booking block
- [ ] Block editor: insert Beds24 Booking block
- [ ] Block configured: confirm property ID matches plugin settings
- [ ] Static front page set in Settings > Reading

### menu configuration

- [ ] Primary navigation menu created
- [ ] Menu items added per handoff
- [ ] Menu assigned to theme location

### plugin settings verification

- [ ] Plugin admin → property ID set correctly
- [ ] Plugin admin → API token exchange complete (if not already done)
- [ ] Sync Rooms run, rooms appearing as `beds24_room` posts

### iframe css

- [ ] Plugin admin → iframe CSS generator → copy CSS payload
- [ ] Beds24 admin → booking page → "Custom CSS" field → paste payload
- [ ] Verify: reload Beds24 booking page, design tokens visible

### end-to-end test

- [ ] Browse to WordPress site, search for dates
- [ ] Rooms appear with correct names, photos, prices
- [ ] Add room to cart, Confirm Booking → iframe loads correctly

### handoff

- [ ] Add session notes to `tripn-sites` per-property handoff
- [ ] Crosslink issue closed with result comment noting any deferred items

### vps tooling notes

- Only built-in `claude-mode` presets available on VPS (no `.claude-mode.json`)
- aapanel does not manage SSH users — use standard Linux commands
- `.user.ini` is immutable (aapanel behavior) — EPERM is expected, not a failure
- New filenames bypass OLS cache — use versioned names for any static assets

