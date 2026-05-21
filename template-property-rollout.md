---
title: "Template: Property Rollout"
tags: ["template", "rollout", "design-doc"]
sources: []
contributors: ["unknown"]
created: 2026-05-21
updated: 2026-05-21
---


## Design Specification

### pre-rollout

- [ ] Property has Beds24 Layout 6 configured with Offer Select as only active module
- [ ] Beds24 API invite code generated (MARKETPLACE > API > Generate invite code)
  - Scopes: `inventory` read, `properties` read
  - Note: single-use, expires in 24 hours
- [ ] WordPress plugin installed and activated on property site
- [ ] Plugin admin accessible (Settings > Beds24 Booking)

### plugin settings

- [ ] Property ID entered in plugin admin (Beds24 property ID number)
- [ ] Invite code exchanged: plugin admin → exchange invite code field
- [ ] `has_refresh_token()` confirms token stored
- [ ] Test API connection — run Sync Rooms, verify rooms appear as `beds24_room` posts

### room content (wordpress)

- [ ] All `beds24_room` posts reviewed — titles match Beds24 room names
- [ ] Room descriptions written or imported into post content
- [ ] Featured images set for each room
- [ ] Amenity tags assigned (custom taxonomy `beds24_amenity`)
- [ ] Room type overrides set where API value is wrong or missing
- [ ] Room display order (menu_order) set to desired sort sequence
- [ ] Unavailable rooms: positioning preference set

### iframe css

- [ ] Plugin admin → iframe CSS generator → copy generated CSS payload
- [ ] Beds24 admin → booking page settings → "Custom CSS" field
  - **Not** "Insert in HTML <HEAD> bottom" — that field has a ~2,000-character limit
- [ ] Paste CSS payload into Custom CSS field, save
- [ ] Reload Beds24 booking page, verify design tokens applied correctly
  - Check: primary button color, card backgrounds, text colors

### end-to-end booking test

- [ ] WordPress site → booking block renders correctly
- [ ] Search form: valid dates → rooms appear with availability and prices
- [ ] Search form: sold-out date range → unavailable rooms show as unavailable
- [ ] Add a dorm bed to cart, verify cart total
- [ ] Add a private room to cart, verify cart total
- [ ] Click Confirm Booking → Beds24 iframe loads with correct cart state
- [ ] Dates pre-populated correctly in iframe
- [ ] Room quantities pre-populated correctly

### auto actions verification (required before going live)

- [ ] Complete a real (or sandbox) booking via the plugin flow
- [ ] Guest receives confirmation email from Beds24 Auto Actions
- [ ] Property owner receives notification
- [ ] If Auto Actions do not fire: investigate Beds24 Auto Actions configuration

### post-rollout

- [ ] `docs/session-handoff-{N}.md` updated with rollout notes
- [ ] Crosslink issue closed with result comment

Source: `skills/beds24-property-rollout/references/property-setup.md` for full field paths

