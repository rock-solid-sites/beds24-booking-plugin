# Session 4 Handoff — 2026-05-07

## What this session did

Session 4 drafted `docs/architecture.md` — the architecture document
that gates V1 build. All content from `docs/architecture-prep.md` was
absorbed into the new document and the prep file was deleted. The four
open design questions were resolved; the three known unknowns were
named and flagged for implementation-time verification.

---

## What's in docs/architecture.md

The document has eleven sections:

1. **Overview** — one-paragraph summary, the three design principles
2. **The discovery-transaction boundary** — why Option 2 (plugin
   renders discovery, Beds24 renders transactions) was chosen over
   Option 1 (plugin wraps iframe) and Option 3 (custom payment)
3. **System components** — search form, room results (with ASCII
   layout sketches for desktop and mobile), cart accumulator, Beds24
   iframe
4. **Data sources** — Beds24 v2 API (availability, pricing, room
   metadata) and WordPress (descriptions, photos, amenity labels);
   why this division is correct, not a workaround
5. **Pricing display** — dorm per-bed arithmetic (verified by spike),
   private per-room, numAdults=1 decision and its per-occupancy
   edge case
6. **Multi-room cart and URL construction** — cart data model, URL
   parameter format, what the iframe renders from a multi-room URL
7. **Design decisions** — the four resolved open questions (see below)
8. **What the plugin does not do** — transaction boundary constraints,
   permanent
9. **Property setup dependency** — Layout 6 + Offer Select minimum
   configuration per property
10. **Known unknowns** — three implementation-time verification items
    (see below)
11. **Relationship to predecessor project** — what carries forward,
    what doesn't

---

## How the open questions were resolved

### featureCodes mapping approach

**Decision:** Built-in mapping table in the plugin for Beds24 OTA
feature codes, plus a per-room "additional amenities" free-text field
in WordPress for anything not in Beds24's vocabulary.

**Reasoning:** A mapping table covers standard amenities (the common
case) with zero operator configuration. The WordPress field provides
an escape hatch for custom amenities that don't have OTA codes.
The alternative (fully labeled-string storage in WordPress per room)
was rejected because it requires operators to configure every amenity,
including the ones that would have been automatic.

### Search form date persistence

**Decision:** Do not persist dates. Search form starts empty each
visit.

**Reasoning:** Stale dates from a prior visit mislead; the state
management cost is not justified by the UX benefit.

### Empty cart UX

**Decision:** Confirm Booking button is disabled when cart is empty.
Enables when at least one room is selected.

**Reasoning:** Simplest correct behavior; opening Beds24's iframe
with an empty URL is either broken or confusing.

### Mobile cart placement

**Decision:** Fixed bottom summary bar that expands into a slide-up
drawer when tapped.

**Reasoning:** Standard mobile shopping cart pattern on OTA sites
(Hostelworld, Booking.com). Keeps cart accessible without obscuring
room cards. Inline placement (below the room list) and separate page
(breaks flow) were rejected. Final UI shape — breakpoints, animation,
drawer height — is a later design pass.

---

## Known unknowns named

Three items from the feasibility spike are flagged in
`docs/architecture.md §Known unknowns — verify at implementation`:

1. **Date parameter format** — whether `checkin_hide=YYYY-M-D` alone
   suffices or both `checkin` and `checkin_hide` are required
2. **Ghost entries** — whether `sr1=1&naa1-1=0` entries for unselected
   rooms are required for correct multi-room cart rendering
3. **Auto Actions on prepopulated bookings** — whether Beds24 Auto
   Actions (confirmation emails, owner notifications) fire identically
   for iframe-prepopulated bookings vs. direct Beds24 page bookings

None of these are V1 code-completion blockers. Item 3 is a required
end-to-end test before the first property goes live.

---

## Crosslink /design evaluation

**Short version: not usable on this project for architecture work.**

Two issues:

1. **Windows/MSYS shell environment incompatibility.** The design.md
   command file uses `!`...`` inline shell patterns. One pattern is
   `ls .design/*.md 2>/dev/null`. On MSYS/bash, this returns a
   non-zero exit code when no files match the glob, even with
   `2>/dev/null`. The Skill harness captures the non-zero exit as a
   failure and the skill doesn't launch.

2. **Feature-doc template format doesn't fit architecture work.** Even
   if the shell issue were fixed, the design.md template produces a
   document structured as: Summary, Requirements (REQ-N), Acceptance
   Criteria (mechanically testable), Architecture, Open Questions,
   Out of Scope. This format suits feature-level design documents
   (components, APIs, workflows). It doesn't suit a project-level
   architecture document, which needs prose-form narrative about
   structure, boundaries, principles, and decisions.

**Recommendation:** Use `/design` for feature-level work (designing
an individual component, API surface, or workflow). For
project-level architecture documents, manual drafting from source
documents is the right approach. The session prompt's material
(settled decisions, open questions, known unknowns) maps directly to
architecture document sections without needing a design-flow
intermediary.

**Fix for the shell issue if someone wants to use /design in future:**
The `ls .design/*.md 2>/dev/null` pattern needs to be replaced with
something that handles no-match gracefully on MSYS. One option:
`find .design -name "*.md" 2>/dev/null`. This works on MSYS and
returns empty output (exit 0) when no files match.

---

## Repo state at session end

- Branch: `main`
- HEAD: `83176d5` (Draft architecture.md)
- Tag: `v0.0.4-architecture` on `83176d5`
- Remote: pushed and in sync with `origin/main`
- Working tree: clean

---

## Session 5 scope

**V1 build phase 1.** With the architecture document in place,
Session 5 can start building.

Starting point for Session 5:

1. **WordPress plugin scaffold.** Basic PHP plugin structure:
   plugin header, activation hook, version constant, autoloader.
   Registers a shortcode `[beds24_booking]` that renders the search
   form. Nothing functional yet — just the skeleton that can be
   activated in WordPress.

2. **Beds24 v2 API client.** A class (or namespace) that handles:
   - Authentication (refresh token → access token exchange, token
     caching in WordPress options)
   - `GET /properties` call returning room metadata
   - `GET /inventory/rooms/offers` call returning availability and
     pricing for given dates

Session 5 should use `claude-mode v1-build` (or `create --base chill
--context-pacing` equivalent). This is net-new code from an
architecture document — the canonical `create` case per the
phase-to-mode mapping.

Before implementation, Session 5 should read `docs/architecture.md`
(now the authoritative source) and `docs/architecture-pivot-decision.md`
(for the "why" behind the boundary decisions). The architecture-prep.md
is gone.
