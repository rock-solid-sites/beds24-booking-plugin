# Architecture — Beds24 Booking Plugin

**Status:** Adopted (Session 4, 2026-05-07)
**Source documents:** `docs/architecture-prep.md`, `docs/architecture-pivot-decision.md`

---

## Overview

The plugin owns the discovery half of the booking experience: a
search form, room cards rendered from API data plus WordPress-stored
content, a cart accumulator for multi-room selections, and a Confirm
Booking button that hands off to Beds24's iframe for transactions.
Everything a guest does before clicking Confirm Booking happens in
WordPress-rendered UI. Everything after — the guest details form,
payment, booking creation — happens inside Beds24's iframe.

### Three design principles

These answer recurring questions. Future sessions that encounter them
defer to these answers unless a principle is explicitly revisited.

1. **Search filters by date only.** The search form is two date
   pickers and a Search button. No guest picker. Capacity is
   communicated per room card and chosen per card.
2. **The plugin handles discovery; Beds24 handles transactions.**
   The boundary is the Confirm Booking button. Future requests to
   "let the plugin do the form" or "let the plugin take payment"
   are answered by this principle.
3. **Content lives in WordPress.** Room descriptions, photos, and
   amenity labels are managed in the WordPress plugin admin. Beds24
   is the property management backend; WordPress is the content
   management frontend.

---

## The discovery-transaction boundary

The plugin renders everything before the booking transaction; Beds24's
iframe renders everything during and after. The boundary is the Confirm
Booking button. This is Option 2 from the pivot decision document
(`docs/architecture-pivot-decision.md`), chosen for risk management
(payment processing stays in Beds24's battle-tested infrastructure),
operator workflow (content management moves to WordPress where operators
already work), and scope honesty (custom payment would introduce PCI,
Stripe, refund, and webhook scope the hostel use case doesn't require).

Multi-room support makes this boundary viable: Beds24's booking page
natively renders a multi-item cart when loaded with the right URL
parameters. The plugin composes the URL; Beds24 renders the cart.

---

## System components

### Search form

- Two date pickers: check-in and check-out
- One Search button
- No guest picker (see design principle 1)
- Date validation: check-out must be after check-in; no booking in
  the past
- On submit: fetches available rooms from the Beds24 v2 API for the
  selected dates

### Room results

- One card per room, rendered by the plugin from WordPress-stored
  content (description, photos, amenity labels) plus live data
  (availability, pricing) from the Beds24 v2 API
- Cards render even when availability is zero (unavailable rooms
  show as unavailable, not hidden — the guest sees the full
  inventory and its status)
- Design target: Hostelworld-like density. The approved mockup at
  `docs/mockup.html` defines the visual language

**Room card layout (desktop):**

```
┌─────────────────────────────────────────────┐
│ Room Name                                   │
├─────────┬───────────────────────────────────┤
│         │ Description                        │
│ Photo   │ [tag] [tag] [tag]                  │
│         │                                    │
├─────────┴───────────────────────────────────┤
│ from €XX / night   [qty] [total] [Book →]  │
└─────────────────────────────────────────────┘
```

**Room card layout (mobile):**

```
┌──────────────────────────────┐
│ Room Name                    │
├──────┬───────────────────────┤
│      │ Description            │
│Photo │                        │
├──────┴───────────────────────┤
│ [tag] [tag] [tag] [tag]      │
├──────────────────────────────┤
│ from €XX / night             │
│ [qty] [total] [Book →]       │
└──────────────────────────────┘
```

### Cart accumulator ("Your Stay")

- Accumulates room selections across multiple cards
- Dorm rooms: quantity input (1 to N available beds)
- Private rooms: single Add / Remove toggle (one room at a time)
- Shows selected items with per-item price and running total
- Confirm Booking button constructs the multi-room URL and opens
  Beds24's iframe

### Beds24 iframe

- Loads after Confirm Booking is clicked
- URL is constructed by the plugin with pre-populated room
  selections, dates, and adult counts
- Renders Beds24's guest details form, payment, and booking
  creation
- Confirmation and cancellation emails are handled by Beds24 Auto
  Actions (no plugin involvement)

---

## Data sources

### Beds24 v2 API — live data

The Beds24 v2 API provides all live data:

- **Availability** — which rooms have units available for the
  requested dates
- **Pricing** — total stay price per room for the requested dates
  and adult count
- **Room metadata** — room ID, name, occupancy limits, feature
  codes, room type

Authentication uses per-property refresh tokens stored in WordPress
options. Access tokens (24-hour lifetime) are obtained automatically
by exchanging the refresh token. Refresh token rotation (30-day
lifetime if used regularly) is handled automatically.

Key endpoints:

| Endpoint | Used for |
|---|---|
| `GET /properties` | Room metadata (names, IDs, occupancy, feature codes) |
| `GET /inventory/rooms/offers` | Live availability and total stay price |

The API does **not** provide:

- Room descriptions entered in Beds24's native admin UI
- Room photos
- Per-night price breakdowns or tax detail

These gaps are by design (see Content ownership section below).

### WordPress — content

The plugin's WordPress admin provides the content management layer:

- Room descriptions
- Room photos (media library)
- Amenity labels (see featureCodes decision below)

This is not a workaround. It is the correct division of labor:
Beds24 manages bookings and availability; WordPress manages
presentation and marketing copy.

**Room content storage — custom post type:**

Room content is stored as a custom post type (`beds24_room`), registered
by the plugin. Each Beds24 room maps to one post of this type. The fields
map as follows:

| Post field | Room content |
|---|---|
| Post title | Room name |
| Post content | Room description |
| Featured image | Primary room photo (via media library) |
| `_beds24_room_id` | Beds24 room ID (post meta); links API responses to WordPress content |

Additional amenities — custom amenities that have no Beds24 OTA feature
code (e.g., "Hot spring access," "Hammock terrace") — are stored as terms
in a custom taxonomy registered on `beds24_room`. Beds24 OTA featureCodes
are **not** stored here: they are resolved at render time by the plugin's
built-in mapping table and displayed as labels without WordPress storage.

Each property's WordPress install has its own set of `beds24_room` posts.
The plugin's render code queries by `_beds24_room_id` to match API offer
responses to the correct WordPress post content.

---

## Pricing display

### Arithmetic

The Beds24 v2 API's `GET /inventory/rooms/offers` returns a total
stay price for the requested `numAdults` count.

**Dorm rooms:** Total price / numAdults / nights = per-bed-per-night.
Beds24 treats `numAdults` as bed count for dorms. Display as
"from €X / bed / night."

**Private rooms:** The same arithmetic produces per-room-per-night
because private room pricing is flat (adding adults doesn't change
the price). Display as "from €X / night."

Verified arithmetic from the API spike: 4-bed dorm, numAdults=2,
2-night stay → €96 total. €96 / 2 / 2 = €24/bed/night. This matches
Beds24's iframe display for the same search. Confidence: high.

### The numAdults=1 decision

The plugin sends `numAdults=1` on all offers queries, even though the
search form collects no guest count.

Rationale: for properties with flat per-room private room pricing
(the case for all four current properties, verified by spike), any
adult count returns the same price. For dorms, `numAdults=1` returns
the per-bed price, which is exactly the card display.

**Per-occupancy pricing edge case:** If a property prices private
rooms per-occupant (e.g., €40 for 1 adult, €60 for 2), sending
`numAdults=1` shows €40/night when the guest will actually pay €60
at checkout. This creates a "price went up" surprise.

This edge case must be checked at rollout time for each property. If
a property has per-occupancy pricing on private rooms, it requires
special handling:
- Option A: send the expected occupancy count (but the search form
  has no guest picker, so this requires an assumption)
- Option B: display "from €X" to signal approximate pricing

Confidence in the numAdults=1 approach: moderate. Confirmed working
for current properties; untested on per-occupancy properties.

---

## Multi-room cart and URL construction

### Cart data model

The cart accumulates a list of selections. Each selection has:

- `roomId` — Beds24 room ID
- `quantity` — number of beds (dorms) or 1 (private rooms)
- `unitPrice` — per-bed-per-night or per-room-per-night at the time
  of selection

The cart persists for the duration of the browser session (page
reload clears it — no cross-session persistence; see design decision
below).

### URL construction

On Confirm Booking, the plugin constructs a Beds24 booking page URL
with one parameter group per cart item:

```
https://beds24.com/booking3.php
  ?propid={propertyId}
  &checkin_hide=YYYY-M-D
  &checkout_hide=YYYY-M-D
  &sr1-{roomId}=1
  &naa1-1-{roomId}=N      # N = beds selected (dorms) or 1 (privates)
  [&sr1-{roomId2}=1 ...]   # repeat for each cart item
```

Dates are non-zero-padded (e.g. `2026-8-1`, not `2026-08-01`).
The `propid` value comes from WordPress plugin settings. Room IDs
come from the offers API response. Full parameter semantics verified
in Session 13: see §"URL construction — confirmed parameter semantics".

### What the iframe renders

When loaded with a multi-room URL, Beds24's booking page renders:

- All selected rooms in a single cart
- One guest details form
- One payment step
- One booking confirmation

This is the same cart the native Beds24 UI shows when a guest adds
multiple rooms. The plugin's URL construction composes the same state
without the guest having to navigate Beds24's UI.

---

## Visual customization architecture

The plugin renders structural DOM with a stable, themeable class
contract. Visual presentation (colors, typography, spacing, accent
treatments) comes from the active theme via `theme.json` token
consumption (primary path) or plugin admin settings (fallback for
themes without theme.json). All styling is plumbed through CSS
custom properties under the `--beds24-*` namespace.

For full detail — token roles, default values, the class contract,
the iframe CSS generation workflow, and the architectural decisions
behind this system — see `docs/styling-contract.md`.

---

## Design decisions

These questions were open in `docs/architecture-prep.md`. Each is
resolved here. Future sessions treat these as settled.

### featureCodes mapping approach

**Decision:** Built-in mapping table in the plugin for Beds24 OTA
feature codes, plus a custom taxonomy on the `beds24_room` CPT for
amenities not in Beds24's vocabulary.

**Rationale:** Beds24's OTA feature code vocabulary is stable and
well-defined (`PRIVATE_BATHROOM`, `BED_KING`, `AIR_CONDITIONING`,
etc.). A shipping mapping table covers standard amenities with zero
operator configuration. The custom amenities taxonomy stores any
property-specific or custom amenities that don't have Beds24 codes
(e.g., "Hot spring access," "Hammock terrace") — operators add terms
via the WordPress admin; the terms are shared across rooms on the
same install, avoiding duplication and free-text typos.

The alternative — fully labeled-string storage in WordPress per room
— was rejected because it requires the operator to configure every
amenity by hand, including the ones that would have been automatic
with a mapping table. More setup, no benefit for standard amenities.

The mapping table ships with the plugin. It does not require a remote
fetch or update mechanism for V1; a plugin update is the correct
mechanism for adding new code mappings.

### Search form date persistence

**Decision:** Do not persist dates. The search form starts empty
each visit.

**Rationale:** Stale dates from a prior visit are actively misleading
— a user who searched for last month's dates and returns to the page
would see outdated availability results before noticing the dates are
wrong. The state management cost (setting, clearing on expiry,
handling back-navigation) is not justified by the UX benefit.

Revisit this if user feedback indicates it's a friction point.

### Empty cart UX

**Decision:** The Confirm Booking button is disabled when the cart
is empty. It enables when at least one room is selected.

**Rationale:** Clicking Confirm Booking with an empty cart would
either produce a broken Beds24 URL or a confusing empty-cart state
in Beds24's iframe. Disabling the button is the simplest correct
behavior: the user cannot proceed until they have something to book.

### Frontend JS state management

**Decision:** A small plain-JS state store with a subscribe/notify
mechanism. Render functions subscribe to state changes; event handlers
call set to update state. No framework, no web components.

**Rationale:**

- **No framework.** The booking flow is a single block's viewScript with
  a bounded scope: search form, room results, cart accumulator, and the
  Confirm Booking handoff. A framework introduces a build step, bundle
  weight, and learning curve the scope doesn't justify. WordPress's own
  guidance for block viewScripts favors vanilla JS; the ecosystem pattern
  for simple frontend interactivity is plain JS + DOM queries.

- **No web components.** Shadow DOM isolates styles, which fights the
  styling contract's design. The plugin's styling is built on CSS custom
  properties and BEM classes targeting plugin-emitted DOM; shadow DOM
  would require a separate styling channel for each component. The
  ES6 class syntax required for custom elements also conflicts with the
  view script's ES5-compatible syntax profile (no build step).

- **State store.** Cart state, search result state, and loading state
  need to be read by multiple render functions and written by multiple
  event handlers. A shared state object with explicit get/set and a
  subscribe/notify mechanism keeps render functions decoupled from each
  other while making state flow traceable. This is the minimal structure
  that avoids prop-drilling across DOM-manipulation functions without
  introducing a full framework.

The specific implementation (store structure, subscriber list, change
detection granularity) is settled in the session where the card
rendering and cart layers are built, once the concrete state fields
are known. This decision records the approach and the rejected
alternatives; it does not constrain the implementation shape.

---

### Mobile cart placement

**Decision:** A fixed bottom summary bar that expands into a
slide-up drawer when tapped.

The bar shows a persistent summary (e.g., "2 rooms · €144 total")
at the bottom of the viewport when the cart has at least one item.
Tapping the bar reveals a drawer with full cart detail and the
Confirm Booking button.

**Rationale:** This is the standard mobile shopping cart pattern
on OTA sites (Hostelworld, Booking.com). It keeps the cart
accessible without obscuring room cards. Inline placement below the
room list was rejected because it requires scrolling past all results
to see the cart. A separate page was rejected because it breaks the
browse-and-accumulate flow.

The final UI shape — breakpoints, drawer animation, drawer height,
bar design — is a later design pass. This decision establishes the
interaction pattern only.

---

## What the plugin does not do

These constraints are permanent — they follow from the
discovery-transaction boundary and should not be crossed in any
future scope extension without explicitly revisiting the boundary
decision.

- **No code that touches credit card data.** Card collection happens
  inside Beds24's iframe. The plugin never renders card form fields.
- **No `POST /bookings` calls.** Booking creation happens when the
  guest submits Beds24's iframe form. The plugin constructs a URL
  to pre-populate that form; it does not create bookings.
- **No payment gateway integration.** Each property's payment
  gateway is configured in Beds24 admin. The plugin does not
  interact with it.
- **No refund or cancellation flows.** Operators handle these in
  Beds24 admin as today.
- **No booking sync from Beds24.** Once created, a booking is owned
  by Beds24. The plugin does not track booking state beyond the
  pre-booking URL construction.
- **No confirmation emails.** Beds24 Auto Actions send these on
  booking creation. The plugin does not send emails.

---

## Property setup dependency

The plugin requires each Beds24 property to have its booking page
configured to Layout 6 with Offer Select as the only active module.
This is the minimum configuration needed for the plugin's URL-
constructed iframe to render the booking cart correctly.

The plugin generates the paste-ready content for the Beds24 admin
field "Insert in HTML \<HEAD\> bottom" — this field is used for any
iframe-side CSS overrides needed to align Beds24's rendered booking
form with the plugin's design language.

Payment gateway configuration remains in Beds24 admin, untouched by
the plugin.

---

## Resolved unknowns — verified Session 13

These were unknowns at the feasibility spike. All three were
resolved by live browser testing against Beds24's booking3.php
on 2026-05-15 (Session 13).

### 1. Date parameter format — RESOLVED

**Resolution:** `checkin_hide=YYYY-M-D` alone is sufficient.
The month and day must be non-zero-padded (e.g. `2026-8-1`, not
`2026-08-01`). The human-readable `checkin=` parameter is not
needed.

Verified: check-in 2026-08-01 → `checkin_hide=2026-8-1` produced
"Sa 1 Aug 2026" in the Beds24 booking page date strip, both inside
the plugin iframe and in a standalone tab.

The URL constructor in `view.js` (`formatCheckinHide()`) strips
leading zeros from month and day via `parseInt(..., 10)`.

### 2. Ghost entries for unselected rooms — RESOLVED

**Resolution:** Ghost entries are NOT required. Sending only the
rooms actually selected produces correct cart pre-population.

Verified: single-room URL (dorm only) and multi-room URL
(dorm + private) both rendered correctly in Beds24's booking page
without entries for unselected rooms. Beds24 shows all available
rooms in a browseable list regardless; the URL parameters control
which rooms have their quantity dropdowns pre-set.

The ghost entries observed in the feasibility spike (`sr1-{id}=1&
naa1-1-{id}=0`) were likely a side-effect of how the user's native
Beds24 session state was serialized into the URL at that time.

### 3. booking3.php vs booking2.php — RESOLVED

**Resolution:** `booking3.php` works. Not tested as an iframe
embedding issue — the page loaded correctly in both a standalone
tab and inside the plugin's inline iframe with no X-Frame-Options
or CSP errors.

---

## URL construction — confirmed parameter semantics

Verified Session 13 against live Beds24 booking page:

- `sr1-{roomId}=1` — always 1 regardless of bed count. Represents
  one room unit entry in the URL.
- `naa1-1-{roomId}=N` — controls the pre-selected quantity.
  For dorms: N = beds selected (e.g. `naa1-1-567219=2` → "2 Beds"
  dropdown pre-set). For private rooms: N = 1 always.

The plugin's URL constructor sends `sr1=1` and `naa1-1=quantity`
for every cart item.

---

## Remaining unknown — requires rollout test

### Auto Actions on URL-prepopulated bookings

Beds24 Auto Actions (confirmation emails, owner notifications, etc.)
fire on booking creation events. The assumption is that they fire
identically for bookings created via the plugin's URL-prepopulated
iframe versus bookings made directly on the Beds24 booking page.

This assumption has not been tested.

**Verify before rollout:** End-to-end test with a real booking on
the first rollout property. Confirm the operator and guest both
receive the expected Auto Action emails. This is a required check
before any property goes live, not a V1 code-completion blocker.

---

## Relationship to predecessor project

The predecessor project (Sessions 1–20) was a CSS+JS layer over
Beds24's iframe. Its retrospective rules (`docs/retrospective.md`),
approved mockup (`docs/mockup.html`), and accumulated learnings all
carry forward.

The rendering substrate changed — the new plugin renders room cards
in WordPress rather than styling Beds24's iframe — but the design
language, the density target, the color palette, and the UI
vocabulary from the predecessor mockup are the starting point for
the plugin's card design.

What does not carry forward: the predecessor's CSS files, the
JavaScript iframe helper, and the DOM-level selector maps. Those
artifacts describe elements that no longer exist in the new
architecture.
