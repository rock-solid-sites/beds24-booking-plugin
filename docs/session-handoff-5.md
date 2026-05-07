# Session 5 Handoff — 2026-05-07

## What this session did

Two pieces of work, as planned:

1. **CLAUDE.md updates** — two new conversational-defaults rules, one new
   section (one kind of work per session), and five staleness fixes.

2. **Beds24 admin setup** — Chill Zone property configured to architecture
   spec; invite code generated and captured; property-setup.md created.

---

## CLAUDE.md changes (commit f9a73c7)

### Two new conversational-defaults rules

Added after "Accuracy over agreeableness," before the closing paragraph:

- **Document access before discussion:** Claude verifies it has a
  document before reasoning about its content; requests upload if not.
- **Direct asks for missing documents:** Named-file direct ask, not
  "let me work from memory."

### One new section

Added after "Project conventions," before "Architecture in one paragraph":

- **One kind of work per session:** Architecture, code, admin through
  Chrome, WP admin through MCP, review, and refactor are different session
  types. Split by kind of work, not just by mode. Exception for tightly
  coupled work.

### Staleness fixes

- Item 3 ("Read before acting"): "(or use crosslink session start once
  Crosslink is adopted in Session 3)" → "(use `crosslink session start`)"
- Item 4 ("Read before acting"): "Session 3" → "Session 4"; removed the
  "until then, see architecture-prep.md" clause (file deleted in Session 4)
- Item 5 ("Read before acting"): Removed entirely (architecture-prep.md
  reference); items 6-7 renumbered to 5-6
- Project file map — session handoff: "(or use crosslink session start
  once Crosslink is adopted)" → "(Crosslink-managed since Session 3)"
- Project file map — architecture.md: "when drafted in Session 3" →
  "(drafted in Session 4)"; removed architecture-prep.md line

---

## Beds24 admin setup — Chill Zone (propid 271142)

### Before state

- Layout 6: already selected ✅
- Room Bottom modules: Room Description 1, Room Picture, Room Features
  all active at row 1 position 1
- `customhead` field: contained predecessor TNH_CONFIG script (1,160
  chars) from the prior CSS+JS project

### After state

- Layout 6 + Offer Select as only active module: ✅
- Room Bottom: empty (all three modules removed and saved)
- `customhead` field: left unchanged — contains predecessor content;
  see below for reasoning

### Why customhead was not changed

The session prompt called for a placeholder HTML comment to "confirm the
field is writable." The field already has 1,160 chars of active predecessor
content. Writing a placeholder would overwrite active content from the
predecessor booking page, breaking it. The field is confirmed writable by
evidence (existing content persists and renders). Placeholder skipped;
reasoning recorded here.

When the WordPress plugin generates paste-ready content for this field,
it replaces the field entirely — the generator cannot assume the field is
empty.

### Invite code (API auth)

Beds24 v2 auth is a two-phase flow: admin generates an **invite code**;
the API client exchanges it for a refresh token programmatically. The admin
does not generate the refresh token directly. This is not captured in
architecture.md (which says "per-property refresh tokens" without
describing how they're obtained).

- Invite code: generated for Chill Zone, captured in operator's password
  manager (labeled "Beds24 Chill Zone — API invite code (not yet exchanged)")
- Invite codes are single-use and short-lived. If the code expires before
  Session 6, regenerate via `control3.php?pagetype=apiv2`

### Booking page verification

`https://beds24.com/booking2.php?propid=271142` renders cleanly:
- Date pickers functional
- Offer Select shows rooms with pricing (Double Room with Shared Bathroom:
  €36.00/night available; Deluxe King Suite: not available for today+2 nights)
- No console errors

### "Your free trial has finished" banner

Visible in Beds24 admin. Booking page still renders and rooms show
pricing, so the trial limitation does not appear to block basic booking
functionality. Operator should resolve this before Session 6 testing
involves live bookings.

---

## New artifacts

- `docs/skill/property-setup.md` — minimum configuration steps, field
  name→id mapping for Developer tab, auth flow notes, Chill Zone state.
  Read before configuring any future property.

---

## Repo state at session end

- Branch: `main`
- HEAD: `82c928b` (Document Beds24 property setup learnings)
- Remote: pushed and in sync with `origin/main`
- Working tree: clean

Commits this session:
- `f9a73c7` — Add document-access rules; fix Session 3/4 references in CLAUDE.md
- `82c928b` — Document Beds24 property setup learnings (Session 5)

---

## Session 6 scope

**V1 build phase 1: WordPress plugin scaffold + Beds24 v2 API client.**

Starting work in order:

### 1. Exchange invite code for refresh token

Before writing any plugin code, exchange the invite code stored in the
operator's password manager for a refresh token. This is a single API call:

```
POST https://beds24.com/api/v2/authentication/setup
Body: { "code": "<invite-code>" }
Response: { "refreshToken": "...", "expiresIn": ... }
```

Store the resulting refresh token in a `.env` file at the repo root:
```
BEDS24_REFRESH_TOKEN_CHILL_ZONE=<token>
```

Add `.env` to `.gitignore` if not already covered. Verify with
`git check-ignore -v .env`.

### 2. WordPress plugin scaffold

Basic PHP plugin structure:
- Plugin header (Plugin Name, Description, Version, Author, License)
- Activation hook
- Version constant
- Autoloader (PSR-4 or simple class map)
- Shortcode `[beds24_booking]` registered (renders empty div for now)
- Plugin activates without error in a local WordPress install

File structure suggestion:
```
beds24-booking-plugin.php     ← main plugin file
includes/
  class-plugin.php            ← bootstrap
  class-api-client.php        ← Beds24 v2 API
  class-shortcode.php         ← shortcode handler
```

### 3. Beds24 v2 API client

A class that handles:
- Refresh token → access token exchange (24-hour access tokens)
- Access token caching in WordPress transients (not options — transients
  expire automatically)
- `GET /properties` — room metadata (IDs, names, occupancy, featureCodes)
- `GET /inventory/rooms/offers` — availability and pricing for given
  check-in/check-out dates and numAdults=1

The API client should be testable standalone (e.g., via WP-CLI or a
small test script) before the shortcode wires it to a front-end.

### 4. Read before implementing

- `docs/architecture.md` sections 4 (Data sources) and 10 (Known unknowns)
- `docs/skill/property-setup.md` — auth flow details
- Beds24 v2 API docs (verify endpoint paths, request/response shapes)
  before writing the client; training-data assumptions about API shape
  should be verified against the live docs

Session 6 uses `claude-mode v1-build`.
