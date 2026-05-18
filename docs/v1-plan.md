# Plugin Work Plan — Beds24 Booking Plugin

**Status:** Living document. Updated between phases of work.
**Last updated:** 2026-05-18 (Session 29: documentation cleanup; removed V1 milestone framing)
**Purpose:** Forward visibility into plugin scope and progress. Not a binding
schedule — work is sequenced one session at a time per project conventions.
Read this to orient against the plugin's current state; read session handoffs for
detail.

---

## Plugin scope

The plugin owns discovery (search form, room results, cart accumulation) and
hands off to Beds24's iframe at the Confirm Booking button for transactions
(guest details, payment, booking creation). Full architectural reasoning in
`docs/architecture.md`. Permanent constraints: `docs/architecture.md`
§"What the plugin does not do".

A guest lands on a property's booking page, searches by date, sees live room
availability and pricing, adds one or more rooms to a cart, and clicks Confirm
Booking to land in a correctly pre-populated Beds24 iframe.

---

## Current state

- **Most recent code sessions:** 28a + 28b complete (agent team).
- **Most recent handoff:** `docs/session-handoff-28.md`

---

## Resolved foundations

Settled architectural decisions live in `docs/architecture.md`. This document
tracks work status against them, not the decisions themselves.

---

## Work map

The areas of work the plugin covers. Not phase-ordered — sequencing is decided
one session at a time. Each area below is a destination, not a session.

### Frontend rendering

- **Search form.** Two date pickers, Search button, validation, minimum-stay
  subhead. No guest picker. Complete (Sessions 9–10). Dispatches live
  availability searches via REST route; results rendered as room cards.
- **Room results.** Cards rendered from API data plus WordPress-stored content.
  Available and unavailable states: complete (Session 11). Dorm vs. private
  rendering distinction: room type indicator bar complete (Session 28a);
  card body layout is currently identical for both types — assess whether
  further distinction is needed. Tag chips for amenities: complete (Session 12,
  DOM position updated Session 18). Visual styling target: predecessor mockup
  (structural DOM in place; visual polish is per-property theme work).
- **Cart accumulator ("Your Stay").** Quantity controls, running total, and
  selected-state styling: complete (Session 12). Sticky footer bar
  (desktop ≥768px) and per-item remove controls: complete (Session 14). Mobile
  bottom-bar-with-drawer: complete (Session 17).
- **Confirm Booking handoff.** Complete (Session 13). URL construction
  confirmed; three URL unknowns resolved. Inline iframe transition (room
  results + cart hide, iframe + back button reveal, back-to-rooms reset):
  complete (Session 15).

### Plugin internals

- **Block render callback.** Renders discovery UI (search form, room results,
  cart). Incrementally built as frontend pieces landed.
- **Frontend JS for interactivity.** Search form submission, results fetching,
  cart state management, confirm-button URL construction. State managed via a
  plain-JS state store with subscribe/notify; no framework, no web components.
  See `docs/architecture.md` §"Frontend JS state management".
- **API client extensions.** Current client supports `get_properties()` and
  `get_offers()`. May need additions as work surfaces gaps.
- **Property ID resolution.** Reads from `wp_options` via
  `beds24-property-config.php`. Multi-property settings page (Session 28b)
  replaced the prior hardcoded helper function.

### Content management

- **Room content storage — CPT.** Custom post type `beds24_room`: post title
  (room name), post content (description), featured image (photo), post meta
  `_beds24_room_id` (Beds24 room ID), custom taxonomy (additional amenities).
  CPT registration, Beds24 room ID meta box, and Chill Zone room seeding:
  complete (Session 11). Data model settled; see `docs/architecture.md`
  §"WordPress — content".
- **featureCodes mapping.** Built-in code-to-label table for Beds24's OTA
  vocabulary. See `docs/architecture.md` §"Design decisions".

### Styling

- **Class catalog.** Captured in `docs/styling-contract.md` §"CSS class
  catalog" as work proceeds. Updated through Session 28.
- **theme.json reader.** Complete (Session 20). Reads design tokens from the
  active theme via `wp_get_global_settings()` and maps them to plugin token
  roles. On non-block themes (Kadence), returns empty and the generator uses
  defaults.
- **Iframe CSS generator.** Complete (Session 16, extended Sessions 20–22, 24).
  Generates paste-ready CSS payload for Beds24's "Custom CSS" admin field.
  Plugin admin displays the generated string in a copyable textarea. Manual
  copy-paste workflow per `docs/styling-contract.md` Decision 5. Admin token
  settings: complete (Session 21) — operators configure token values for themes
  that don't provide them via theme.json; settings stored as individual
  `wp_options` entries; theme.json values take precedence.

### Plugin settings

- **Multi-property settings page.** Complete (Session 28b). Add/remove
  properties, invite code exchange UI, default property selector. Property
  list stored in `wp_options`. Migration seeds the Chill Zone entry from
  the existing refresh token on first load after update.

---

## Open questions

Things the plugin hasn't resolved. Listed so future sessions encountering them
know they're real questions, not settled decisions.

Three URL unknowns previously listed here (booking endpoint, date parameter
format, ghost entries for unselected rooms) were resolved in Session 13 by
live browser testing. See `docs/architecture.md` §"Resolved unknowns —
verified Session 13".

- **Auto Actions on URL-prepopulated bookings.** Assumption is they fire
  identically to manual bookings; not tested. Required pre-rollout check.
- **Per-occupancy pricing edge case.** Current properties are flat-priced;
  edge case kicks in if any future property uses per-occupant pricing on
  private rooms. See `docs/architecture.md` §"The numAdults=1 decision".
- **Cart persistence scope.** Browser session only (decided); but whether
  to persist across page reloads within the session is open. See
  `docs/architecture.md` §"Cart data model".
- **Block attribute for per-block property selection.** Settings page stores
  multiple properties; the block always renders the default. Per-block override
  attribute not yet implemented.

---

## Maintenance

This document is updated between phases of work — not after every session.
The update cadence is roughly: when a work map area completes or shifts
substantially, when an open question gets resolved, when a new architectural
question surfaces, or when the plugin's direction changes.

Updates are small: move items between sections; add brief notes; refresh the
current state header. The document stays short on purpose.
