# V1 Plan — Beds24 Booking Plugin

**Status:** Living document. Updated between phases of work.
**Last updated:** 2026-05-12 (drafted at start of Session 9)
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

- **Session:** 9 (V1 build phase opening)
- **In flight:** Search form V1 build. See `docs/session-handoff-9-pre.md`
  for inherited state and Session 9's specific scope.
- **Most recent handoff:** `docs/session-handoff-9-pre.md`
- **Repo HEAD at draft time:** `404f4d6`

---

## Resolved foundations

These decisions are settled. Future sessions defer to them unless
explicitly revisited.

- **Architecture:** discovery-transaction boundary at the Confirm Booking
  button. See `docs/architecture.md` and `docs/architecture-pivot-decision.md`.
- **Three design principles:** date-only search; plugin owns discovery,
  Beds24 owns transactions; content lives in WordPress. See
  `docs/architecture.md` §"Three design principles" and `CLAUDE.md`.
- **Per-property config sourcing:** WordPress options, propid-suffixed
  (e.g., `beds24_booking_plugin_refresh_token_{propid}`). Each WordPress
  install serves one property. See `docs/architecture.md` §"Data sources".
- **Styling contract:** plugin emits BEM DOM with `beds24-` namespace prefix;
  CSS custom properties under `--beds24-*`; low-specificity selectors with no
  `!important`. See `docs/styling-contract.md`.
- **Pricing model:** `numAdults=1` on all offers queries; "from €X / night"
  display; per-occupancy pricing flagged as known edge case for rollout. See
  `docs/architecture.md` §"Pricing display".
- **Multi-room cart composition:** single Beds24 URL with `sr1-` and `naa1-`
  parameter pairs per room. See `docs/architecture.md` §"Multi-room cart and
  URL construction".
- **Visual language:** ports forward from predecessor mockup
  (`docs/mockup.html`) — typography, palette, density. Plugin emits its own
  DOM so the mockup's CSS doesn't port directly; the visual decisions do.

---

## Work map

The areas of work V1 covers. Not phase-ordered — sequencing is decided one
session at a time. Each area below is a destination, not a session.

### Frontend rendering

- **Search form.** Two date pickers, Search button, validation, minimum-stay
  subhead. No guest picker. Currently in flight (Session 9).
- **Room results.** Cards rendered from API data plus WordPress-stored
  content. Available and unavailable states. Dorm vs. private rendering.
  Tag chips for amenities. Visual target: predecessor mockup.
- **Cart accumulator ("Your Stay").** Per-card quantity controls; running
  total; selected-state styling on cards in cart. Desktop layout placement
  TBD. Mobile placement decided: fixed bottom bar + slide-up drawer.
- **Confirm Booking handoff.** URL construction; transition to Beds24
  iframe with pre-populated cart.

### Plugin internals

- **Block render callback.** Currently a placeholder. Replaced incrementally
  as the frontend pieces above are built.
- **Frontend JS for interactivity.** Search form submission, results
  fetching, cart state management, confirm-button URL construction.
- **API client extensions.** Current client supports `get_properties()` and
  `get_offers()`. May need additions as frontend work surfaces gaps.
- **Property ID resolution.** Hardcoded helper function in V1 (returns
  `271142` for the Chill Zone implementation). Migrates to options-read when
  the Settings page lands (see "Anticipated, beyond V1 core" below).

### Content management

- **Room content storage.** Descriptions, photos, amenity overrides.
  Probably custom post type or post meta keyed by Beds24 room ID. Data model
  TBD.
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

- **`booking2.php` vs `booking3.php` endpoint.** `docs/architecture.md`
  specifies `booking3.php`; predecessor's `booking-widget.js` used
  `booking2.php`. To verify before the Confirm Booking handoff session.
- **Date parameter format for Beds24 URL.** Two formats observed in the
  spike (`checkin_hide=YYYY-MM-DD` vs. `checkin=DD+Mon+YYYY`). To verify
  by implementation. See `docs/architecture.md` Known unknown 1.
- **Ghost entries for unselected rooms in the booking URL.** May or may
  not be required. To verify by implementation. See `docs/architecture.md`
  Known unknown 2.
- **Auto Actions on URL-prepopulated bookings.** Assumption is they fire
  identically; not tested. Required pre-rollout check, not a V1
  code-completion blocker.
- **Per-occupancy pricing edge case.** Current properties are flat-priced;
  edge case kicks in if any future property uses per-occupant pricing on
  private rooms. See `docs/architecture.md` §"The numAdults=1 decision".
- **Room content data model.** Custom post type vs. post meta vs. options.
  Decision deferred until the content management work begins.
- **Cart persistence scope.** Browser session only (decided); but whether
  to persist across page reloads within the session is open. See
  `docs/architecture.md` §"Cart data model".

---

## What V1 does NOT do

These constraints are permanent per the discovery-transaction boundary:

- No code that touches credit card data
- No `POST /bookings` calls; no booking creation
- No payment gateway integration
- No refund or cancellation flows
- No booking sync from Beds24
- No confirmation emails

See `docs/architecture.md` §"What the plugin does not do" for the full list
and reasoning.

---

## Maintenance

This document is updated between phases of work — not after every session.
The update cadence is roughly: when a "work map" area completes or shifts
substantially, when an "open question" gets resolved, when a new
architectural question surfaces, or when V1's shape itself changes.

Updates are small: move items between sections; add brief notes; refresh
the "current state" header. The document stays short on purpose.
