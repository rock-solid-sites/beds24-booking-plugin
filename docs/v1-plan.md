# V1 Plan — Beds24 Booking Plugin

**Status:** Living document. Updated between phases of work.
**Last updated:** 2026-05-15 (updated after Session 13 to mark cart and confirm handoff complete, resolve URL unknowns)
**Purpose:** Forward visibility into V1 scope and progress. Not a binding
schedule — work is sequenced one session at a time per project conventions.
Read this to orient against the V1 destination; read session handoffs for
the current state.

---

## What V1 is

V1 ships a working booking experience for the four Trip'N'Hostel properties.
The plugin owns discovery (search form, room results, cart accumulation) and
hands off to Beds24's iframe at the Confirm Booking button for transactions
(guest details, payment, booking creation). Full architectural reasoning in
`docs/architecture.md`.

V1 is "complete" when a guest can land on a property's booking page, search
by date, see live room availability and pricing, add one or more rooms to a
cart, and click Confirm Booking to land in a correctly pre-populated Beds24
iframe.

---

## Current state

- **Session:** 13 complete. Session 14 in planning.
- **Session 13 scope:** Confirm Booking button, URL construction, iframe
  handoff. All three architecture.md URL unknowns resolved by live
  browser testing against Beds24's booking3.php.
- **Most recent handoff:** `docs/session-handoff-13.md`
- **Repo HEAD at last update:** f5df22f

---

## Resolved foundations

Settled architectural decisions live in `docs/architecture.md`. This
document tracks V1 status against them, not the decisions themselves.

---

## Work map

The areas of work V1 covers. Not phase-ordered — sequencing is decided one
session at a time. Each area below is a destination, not a session.

### Frontend rendering

- **Search form.** Two date pickers, Search button, validation, minimum-stay
  subhead. No guest picker. Complete (Sessions 9–10). Dispatches live
  availability searches via REST route; results logged, card rendering pending.
- **Room results.** Cards rendered from API data plus WordPress-stored
  content. Available and unavailable states: complete (Session 11). Dorm
  vs. private rendering distinction: deferred (currently both use same
  card layout). Tag chips for amenities: deferred. Visual styling target:
  predecessor mockup (structural DOM in place; visual polish is per-property
  theme work).
- **Cart accumulator ("Your Stay").** Quantity controls, running total,
  and selected-state styling: complete (Session 12). Desktop layout
  placement TBD. Mobile placement decided: fixed bottom bar + slide-up
  drawer.
- **Confirm Booking handoff.** Complete (Session 13). URL construction
  confirmed; iframe loads correctly with pre-populated room selections
  and dates.

### Plugin internals

- **Block render callback.** Currently a placeholder. Replaced incrementally
  as the frontend pieces above are built.
- **Frontend JS for interactivity.** Search form submission, results
  fetching, cart state management, confirm-button URL construction.
  State managed via a plain-JS state store with subscribe/notify;
  no framework, no web components. See `docs/architecture.md`
  §"Frontend JS state management".
- **API client extensions.** Current client supports `get_properties()` and
  `get_offers()`. May need additions as frontend work surfaces gaps.
- **Property ID resolution.** Hardcoded helper function in V1 (returns
  `271142` for the Chill Zone implementation). Migrates to options-read when
  the Settings page lands (see "Anticipated, beyond V1 core" below).

### Content management

- **Room content storage — CPT.** Custom post type `beds24_room`: post title
  (room name), post content (description), featured image (photo), post meta
  `_beds24_room_id` (Beds24 room ID), custom taxonomy (additional amenities).
  CPT registration, Beds24 room ID meta box, and Chill Zone room seeding are
  Session 11 scope. Data model settled; see `docs/architecture.md`
  §"WordPress — content".
- **featureCodes mapping.** Built-in code-to-label table for Beds24's OTA
  vocabulary. See `docs/architecture.md` §"Design decisions".

### Styling

- **Class catalog.** First entries drafted in Session 9 for the search form;
  expands as each frontend area lands. Captured in `docs/styling-contract.md`
  §"CSS class catalog" as work proceeds.
- **theme.json reader.** Reads design tokens from the active theme and
  populates `--beds24-*` CSS variables on the plugin root element.
  Not strictly required for Chill Zone (Kadence; non-block-theme) but
  required for the three Twenty Twenty-Five property sites.
- **Iframe CSS generator.** Generates paste-ready CSS payload for Beds24's
  "Insert in HTML &lt;HEAD&gt; bottom" admin field. Plugin admin displays
  the generated string. Manual copy-paste workflow per
  `docs/styling-contract.md` Decision 5.

### Anticipated, beyond V1 core

These are known-needed but expected in later sessions or V1.x:

- **Plugin settings page.** Property ID configuration, refresh token
  setup/exchange UI, theme.json/admin token override mapping. Replaces
  the hardcoded property-ID helper function used in V1 core.
- **Iframe CSS staleness mitigation.** Detection-with-prompt UI per
  `docs/styling-contract.md` Known unknown 6. V1.x scope.

---

## Open questions

Things V1 hasn't resolved. Listed so future sessions encountering them know
they're real questions, not settled decisions.

Three URL unknowns previously listed here (booking endpoint, date parameter
format, ghost entries for unselected rooms) were resolved in Session 13 by
live browser testing. See `docs/architecture.md` §"Resolved unknowns —
verified Session 13".

- **Auto Actions on URL-prepopulated bookings.** Assumption is they fire
  identically; not tested. Required pre-rollout check, not a V1
  code-completion blocker.
- **Per-occupancy pricing edge case.** Current properties are flat-priced;
  edge case kicks in if any future property uses per-occupant pricing on
  private rooms. See `docs/architecture.md` §"The numAdults=1 decision".
- **Cart persistence scope.** Browser session only (decided); but whether
  to persist across page reloads within the session is open. See
  `docs/architecture.md` §"Cart data model".

---

## What V1 does NOT do

These constraints are permanent per the discovery-transaction boundary.
See `docs/architecture.md` §"What the plugin does not do" for the full
list and reasoning.

---

## Maintenance

This document is updated between phases of work — not after every session.
The update cadence is roughly: when a "work map" area completes or shifts
substantially, when an "open question" gets resolved, when a new
architectural question surfaces, or when V1's shape itself changes.

Updates are small: move items between sections; add brief notes; refresh
the "current state" header. The document stays short on purpose.
