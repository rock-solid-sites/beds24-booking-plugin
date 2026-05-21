---
title: "Dependencies"
tags: ["context", "infrastructure", "design-doc"]
sources: []
contributors: ["unknown"]
created: 2026-05-21
updated: 2026-05-21
---


## Design Specification

### beds24 v2 api

- **Auth:** Per-property refresh token, stored in WordPress options under
  `beds24_booking_plugin_refresh_token_{property_id}`. Token rotation is
  automatic (access token / refresh token pattern).
- **Endpoints used:** `GET /properties` (room metadata), `GET /inventory/rooms/offers`
  (live availability and pricing).
- **API does not provide:** Room descriptions, room photos, per-night price
  breakdowns, tax detail. These gaps are by design.
- **API base URL:** `https://api.beds24.com/v2/`

### beds24 iframe (booking3.php)

- **URL:** `https://beds24.com/booking3.php`
- **Required Beds24 admin config per property:** Layout 6 with Offer Select as
  the only active module.
- **URL parameters:** `propid`, `checkin_hide` (YYYY-M-D non-zero-padded),
  `checkout_hide`, `sr1-{roomId}=1`, `naa1-1-{roomId}=N`.
- **Multi-room:** Multiple room parameter sets in one URL produce a single
  multi-item cart in the iframe.
- **Auto Actions:** Beds24 Auto Actions handle confirmation emails and owner
  notifications. Plugin does not send emails.

### wordpress

- **Block editor:** The plugin is a Gutenberg block (`beds24-booking/booking-block`).
- **Custom post type:** `beds24_room` — one post per Beds24 room, linked via
  `_beds24_room_id` post meta.
- **Custom taxonomy:** `beds24_amenity` — for custom amenities without Beds24
  OTA feature codes.
- **Settings API:** Plugin admin settings stored in `wp_options` with key
  pattern `beds24_token_{role}`.
- **Transients:** Access tokens cached via WordPress transients with TTL.

### local development

- **DDEV** — local WordPress environment. Default URL: `chillzone.ddev.site`.
- No npm, no build step, no transpilation for the plugin's frontend JS.

### payment gateway

- Configured per property in Beds24 admin. The plugin does not interact with
  payment configuration at all.

### mcp expose abilities (evaluation pending)

- Three-plugin stack being evaluated for VPS property setup workflows.
- Status: install on DDEV Chillzone, run property setup sequence, assess value.
  Not yet adopted.

