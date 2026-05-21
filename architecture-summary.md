---
title: "Architecture Summary"
tags: ["architecture", "context", "design-doc"]
sources: []
contributors: ["unknown"]
created: 2026-05-21
updated: 2026-05-21
---


## Design Specification

### the discovery-transaction boundary

The plugin owns discovery (search form, room results, cart accumulation) and
delegates transactions to Beds24's iframe at the Confirm Booking button.
Everything a guest does before clicking Confirm Booking happens in
WordPress-rendered UI. Guest details form, payment, and booking creation happen
inside Beds24's iframe.

### three design principles (settled, non-negotiable)

1. **Search filters by date only.** Two date pickers and a Search button. No
   guest picker. Capacity is shown per room card.
2. **The plugin handles discovery; Beds24 handles transactions.** The boundary
   is the Confirm Booking button. Requests to "let the plugin do the form" or
   "let the plugin take payment" are answered by this principle.
3. **Content lives in WordPress.** Room descriptions, photos, and amenity labels
   are managed in the WordPress plugin admin.

### system components

- **Search form:** Two date pickers, Search button, date validation.
- **Room results:** One card per room. Unavailable rooms show as unavailable,
  not hidden. Hostelworld-like density target.
- **Cart accumulator ("Your Stay"):** Accumulates selections. Dorm rooms: qty
  input. Private rooms: Add/Remove toggle. Confirm Booking button disabled when
  cart is empty.
- **Beds24 iframe:** Loads after Confirm Booking. URL pre-populated with room
  selections, dates, adult counts. Multi-room URL composes Beds24's native cart.

### what the plugin does not do (permanent constraints)

- No credit card data handling
- No `POST /bookings` API calls
- No payment gateway integration
- No refund or cancellation flows
- No booking sync from Beds24
- No confirmation emails (handled by Beds24 Auto Actions)

### data sources

- **Beds24 v2 API:** Availability, pricing, room metadata (live, per-search).
  Key endpoints: `GET /properties`, `GET /inventory/rooms/offers`.
- **WordPress custom post type (`beds24_room`):** Descriptions, photos, amenity
  labels. One post per Beds24 room, linked via `_beds24_room_id` post meta.

### multi-room url format

```
https://beds24.com/booking3.php?propid={id}
  &checkin_hide=YYYY-M-D&checkout_hide=YYYY-M-D
  &sr1-{roomId}=1&naa1-1-{roomId}=N  [repeated per cart item]
```
Non-zero-padded dates. `sr1=1` always; `naa1-1=N` is bed qty for dorms or 1 for
private rooms. Verified Session 13.

Source: `docs/architecture.md`

