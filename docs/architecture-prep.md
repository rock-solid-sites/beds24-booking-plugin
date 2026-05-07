# Architecture Preparation

This document captures architectural thinking that has been done but
not yet recorded in `docs/architecture.md`. It exists because some
decisions and findings were established in conversation before the
architecture document existed, and they need to be durable until
that document absorbs them.

## How to use this document

Read this before:

- Drafting `docs/architecture.md` (Session 2's design flow target)
- Implementing any feature that depends on architectural decisions
- Making a new architectural decision (check whether the question is
  already addressed here)

When `docs/architecture.md` is drafted in Session 2, items marked
`[for architecture.md]` should be incorporated into that document.
Once incorporated, they can be removed from this file. When the file
is empty (or contains only items that don't belong in
`architecture.md`), it can be deleted.

## Decisions made in conversation, awaiting documentation

### [for architecture.md] Dorm pricing displays as per-bed-per-night

The Beds24 v2 API's `GET /inventory/rooms/offers` endpoint returns
total stay price for the requested adult count. For dorm rooms, this
is implicitly per-bed-for-the-stay because dorms charge per-bed and
`numAdults` is interpreted as bed count.

Verified arithmetic from the spike: for the 4-bed dorm with
numAdults=2 and a 2-night stay, the API returned €96 total. €96 / 2
beds / 2 nights = €24/bed/night. This matches the per-bed pricing
shown in Beds24's iframe for the same search.

Card display math: when rendering a dorm card, divide the offer's
total `price` by `numAdults` (passed in the offer query) and again by
night count to produce the per-bed-per-night figure. Display as
"from €X / bed / night."

For private rooms, the same calculation produces per-room-per-night
since `numAdults` doesn't change room price. Display as
"from €X / night."

Confidence: high. Verified by direct API call during the spike.

### [for architecture.md] Search uses numAdults=1 to avoid surprise pricing

Even though the search form collects only dates (no guest picker),
the API requires a `numAdults` parameter on
`GET /inventory/rooms/offers`. The plugin sends `numAdults=1`.

For private rooms with flat per-room pricing (the case for Chill
Zone, verified by the spike), this returns the same price as any
other adult count. For dorms, this returns per-bed pricing which is
exactly what the card displays.

Edge case to watch for: if a property has per-occupant pricing on
private rooms (e.g., €40 for 1 adult, €60 for 2), `numAdults=1`
displays €40/night but the user booking for 2 actually pays €60. The
"price went up at checkout" surprise should be avoided. Confirm flat
per-room pricing for each property during rollout. If a property has
per-occupant pricing, that property requires special handling
(possibly: send the larger expected occupancy, possibly: display
"from €X" and let users see the actual price in the iframe).

Confidence: moderate. Chill Zone's pricing is flat per-room (verified
by spike). Other properties unverified.

### [for architecture.md] Dorm UX uses per-card quantity input, not per-search numAdults

The current Beds24 iframe places a "Beds:" dropdown on each dorm card
allowing the user to pick how many beds to book. The new plugin
replaces this with a quantity input on dorm cards (private rooms have
no quantity input — they're booked one room at a time).

The user's flow:

1. Search by dates only
2. See all available rooms (dorms with per-bed price, private rooms
   with per-room price)
3. On a dorm card: pick a quantity (1, 2, 3, ... up to available
   beds). On a private room card: just click Add.
4. The "Your Stay" cart accumulates selections across multiple cards.
5. On Confirm Booking, the multi-room URL is constructed with the
   per-room adult count reflecting each card's quantity.

This was deliberated against three alternatives:

- One bed per booking (no quantity, user has to come back for more)
  — rejected as too restrictive for couples and groups.
- Restore the Beds24-style dropdown — rejected because it carries
  iframe UX baggage.
- Per-search guest picker — rejected because hostel inventory is
  heterogeneous and pre-filtering hides options the user might want.

The design principle "search filters by date only; capacity is
communicated per card and chosen per card" lands here.

Confidence: high on the principle. Moderate on the specific UI shape
(quantity input vs. +/- counter vs. some other interaction) — that's
a Session 2+ design decision.

### [for architecture.md] Why content lives in WordPress, not pulled from Beds24

The Beds24 v2 API returns no content for room descriptions or photos
when the property has used Beds24's native admin UI to enter that
content (the case for all four properties). This is because Beds24
operates two separate content systems:

1. **Native UI content** — entered via Beds24's "Description" and
   "Rooms Setup" admin pages. This is what the iframe-rendered
   booking page displays. It is not exposed through the v2 API.
2. **OTA/API channel content** — entered via Beds24's channel-specific
   content pages (used for Booking.com, Airbnb, etc.). This is
   returned by the API's `texts` field.

The properties have content only in system 1. The `texts` array on
both property and room responses is empty, even with
`includeTexts=en` requested. The `pictures` field doesn't exist in
the schema at all — the API has no path for room photo URLs.

This is not a workaround or a shortcoming we're routing around. It's
the correct division of labor:

- Beds24 is a property management system. It manages bookings,
  pricing, availability, payment processing.
- WordPress is a content management system. It manages descriptions,
  photos, marketing copy, brand presentation.

Each system is good at what it's for. The API gives us the data we
need from Beds24 (availability, pricing). The plugin's WordPress
admin gives operators the place to manage content (descriptions,
photos) using tools they already know.

Future requests to "sync content from Beds24 to keep it in one place"
should be answered by this principle: there is no single-source-of-
truth path that's better than what we have. The two systems have
complementary jobs.

Confidence: high. Verified by the spike's direct API calls.

### [for architecture.md] Transaction-boundary implications

The principle "the plugin handles discovery; Beds24 handles
transactions" has specific implications for what the plugin will and
will not contain. These constraints apply throughout the project's
lifetime:

- **No code that touches credit card data.** Card collection happens
  inside Beds24's iframe. The plugin never renders card form fields.
- **No `POST /bookings` calls.** Booking creation happens when the
  user submits Beds24's iframe form, not when the user clicks
  Confirm Booking on the WordPress side.
- **No payment gateway integration in the plugin.** The Beds24
  property's payment gateway is configured in Beds24 admin and
  remains untouched by the plugin.
- **No refund or cancellation flows.** Operators handle these in
  Beds24 admin as today.
- **No booking sync from Beds24.** Once a booking is created, its
  state is owned by Beds24. The plugin doesn't track bookings
  except possibly read-only display of recent activity in admin
  (out of scope for V1).

These follow from the principle but are worth being explicit about so
that future scope-up pressure ("can the plugin also handle X?")
encounters a clear answer.

Confidence: high. The decision was deliberate.

## Open design questions

These are questions that have been surfaced but not yet decided.
Each needs an answer before or during the relevant implementation.

### [for architecture.md when answered] featureCodes mapping approach

Beds24's API returns amenities as opaque OTA codes
(`PRIVATE_BATHROOM`, `BED_KING`, `AIR_CONDITIONING`, etc.) without
human-readable labels. The plugin needs to render amenity pills with
labels like "Private Bathroom" and "King Bed."

Two approaches were considered:

- **Mapping table in the plugin.** A code-to-label dictionary
  shipped with the plugin. Beds24 returns codes, plugin maps to
  labels for display. Operator doesn't manage the mapping; it's
  built in.
- **Labeled-string storage in WordPress, independent of Beds24.**
  Operator picks amenities from a list in plugin settings per room.
  The list is fully independent of what Beds24 returns. Operator
  has more control; potentially more setup work per room.

The Beds24 API returns codes consistently across properties, so a
mapping table can be shipped with the plugin and just work. But the
labeled-string approach gives more flexibility (custom labels,
groupings, additional amenities not in Beds24's vocabulary).

Decision deferred. Probably resolved in Session 2's architecture
work or whenever the room card component is designed.

### [for architecture.md when answered] Search form date persistence

Should the search form remember the user's previous dates between
visits to the page? Pro: better UX for users comparing options. Con:
state management complexity, and stale dates from days ago are not
useful.

Default position: do not persist dates. The search form starts
empty each visit. Reconsider if user research suggests otherwise.

### [for architecture.md when answered] Empty cart UX

What happens when the user clicks "Confirm Booking" with zero items
in their Your Stay cart? Defensive UI design question.

Default position: the Confirm Booking button is disabled when the
cart is empty. The button only enables when at least one item is
selected. This is the simplest correct behavior.

### [for architecture.md when answered] Mobile cart placement

The "Your Stay" cart accumulator works fine as a sidebar at desktop
widths. On mobile (390px and below), sidebar layouts don't work.
Options: bottom-fixed bar, slide-up drawer, separate page.

Decision deferred to mockup phase. Probably resolved when the
results page mockup is finalized in Session 2 or 3.

## Known unknowns from the spike

These are technical details we'll need to verify when implementation
gets close to them. Listed here so a future session knows to check
rather than assume.

### checkin parameter format requirements

The current widget URL uses `checkin=YYYYMMDD` (no separators). The
multi-room URL example from a real Beds24 booking session has both
`checkin=We+6+May+2026` (human-readable) and
`checkin_hide=2026-5-6` (machine-parseable). It's unclear whether:

- Sending only `checkin_hide` works
- Sending only `checkin` (in YYYYMMDD format) works
- Both must be sent together

Implementation: try `checkin_hide` alone first. Fall back to sending
both if needed. The spike verified the URL parameter scheme exists
but didn't test minimal-parameter variants.

### Whether ghost entries are required for unselected rooms

The example multi-room URL contained entries for rooms NOT in the
cart, with `sr1=1&naa1-1=0` (ghost selections). It's unclear whether
these are required for correct rendering of the cart in the iframe,
or whether sending only the active selections is sufficient.

Implementation: try sending only active selections first. If the
iframe renders incorrectly or the cart is incomplete, add ghost
entries for all rooms returned by the offers API for that search.

### Auto Action behavior on iframe-pre-populated bookings

Beds24's Auto Actions (which send confirmation emails, notifications,
etc.) fire on booking events. We assume they fire identically for
bookings created via Beds24's iframe regardless of whether the iframe
was loaded directly or with our pre-populated URL parameters.

Implementation: end-to-end test with a real booking on Chill Zone,
verify the operator and guest both receive the expected emails. This
is a required check before any rollout, not before V1 code completion.

## Items that don't belong in architecture.md

These are listed here because they came up in conversation and might
otherwise be lost, but they belong in other documents:

- **Beds24 admin UI navigation paths** (e.g., "SETTINGS → BOOKING
  ENGINE → PROPERTY BOOKING PAGE → LAYOUT [Layout 6]"). Belongs in
  a future `docs/skill/beds24-admin-paths.md` or similar reference.
- **`numadult=1` bug in predecessor's `booking-widget.js` line 329.**
  The predecessor's archive note records this. The successor fixes
  it by construction (per-room adult count is sent via the multi-room
  URL scheme).
- **The Beds24 wiki returns 403 to web fetches** (the spike hit this).
  Operationally useful for any future research session. Belongs in
  a future `docs/skill/gotchas.md`.
- **The Crosslink VS Code extension exists** as an alternative
  interface to the CLI. Mentioned in the upstream README. Worth
  considering during Session 2's adoption work but no urgency.

## Maintenance

This document is a living artifact. Items get added as new
architectural thinking happens in conversation; items get removed as
they land in their proper home (architecture.md, skill docs,
retrospective entries).

When this file is empty or contains only outdated items, delete it.
The deletion can be a one-line commit; the file's purpose is
explicitly transient.
