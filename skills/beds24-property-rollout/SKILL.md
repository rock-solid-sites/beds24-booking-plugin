---
name: beds24-property-rollout
description: Use when configuring a Beds24 property for use with the WordPress plugin, including generating API invite codes, setting up Layout 6 with Offer Select only, configuring Developer tab fields, or troubleshooting Beds24 admin behavior during rollout. Also use when the user asks which Beds24 admin fields the plugin needs, what the admin field IDs are, how programmatic saves behave, or what the step-by-step sequence is to onboard a new property. Activate any time the conversation involves a Beds24 property rollout, even if the user just says "let's set up the next property" or "what do I need to configure in Beds24?"
---

# Beds24 Property Rollout

Repeat this procedure for each property being added to the plugin.
Verified against Chill Zone (propid 271142) on 2026-05-07.

**Full reference:** `references/property-setup.md`

---

## Required admin configuration

### Step 1 — Booking Engine: Configuration tab

**URL:** `control3.php?pagetype=bookingpage2`

- **Booking Page Version:** Responsive (not Adaptive)
- **Default Layout:** 6

Click Save. If already set correctly, skip.

### Step 2 — Booking Engine: Layout tab

**URL:** `control3.php?pagetype=bookingpagedesignlayout`

> Note: Use `bookingpagedesignlayout`, not `bookingpagev2layout` — the
> latter doesn't load layout content in the current control3 admin.

Target state:
- **Offer section:** Offer Select — row 1, position 1, any width
- **All other sections** (Property Top, Room Top, Room Bottom, Property
  Bottom): no active modules — show only the "add module" dropdown

On a fresh property, Room Bottom likely has Room Description 1, Room
Picture, and Room Features active. Set all three Position dropdowns to
"not used" and Save.

**Verify after save:** Reload the page. Room Bottom should show only
the "add module" dropdown with no module rows.

### Step 3 — Booking Engine: Developer tab

**URL:** `control3.php?pagetype=bookingpagedesigndeveloper`

The plugin generates paste-ready content for the "Insert in HTML \<HEAD\>
bottom" field. On properties being migrated from the predecessor project,
this field may already contain `window.TNH_CONFIG` and the predecessor
helper script — **replace the entire field, don't append**.

**Field ID mapping:**

| Beds24 label | `textarea` id |
|---|---|
| Insert in HTML \<HEAD\> top | `customheadtop` |
| Insert in HTML \<HEAD\> bottom | `customhead` |
| Insert in HTML \<BODY\> top | `custombodytop` |
| Insert in HTML \<BODY\> bottom | `custombody` |
| Confirmation Page Insert in HTML \<HEAD\> | `customheadconfirm` |
| Custom CSS | `bookingcss` |

Always refer to fields by their Beds24 UI label in conversation with the
operator — they can't see the `textarea` IDs.

---

## Programmatic save gotchas

When saving Beds24 admin fields via automation (setting `textarea.value`
+ clicking Save):

- **`customhead`** — accepts `<script>` content. Non-ASCII characters
  (emoji, accents) are silently stripped on save. Use JS Unicode escapes
  (`🛏`) for any non-ASCII in this field.
- **`bookingcss`** — accepts raw CSS (no tags needed). Character limit
  ~18-19K; silent save failure above this limit.
- **`custombody` and `customheadconfirm`** — strip `<script>` and
  `<style>` tags on programmatic save. These fields **must be pasted
  manually**. Generate paste-ready content; do not attempt to automate
  the save for these two fields.

After any programmatic save: reload the page and verify the value
persisted before proceeding. Silent save failures waste all downstream
work.

---

## API authentication setup (per property)

### Generate an invite code

1. Navigate to `control3.php?pagetype=apiv2`
2. Generate a new invite code for this property
3. Capture it immediately (store in password manager)
4. The code is single-use and short-lived (~24h)

**Required scopes:** `inventory:READ` and `properties:READ` minimum.

### Exchange invite code for refresh token

Use the plugin's API client:

```php
$client = new Beds24_API_Client( $property_id );
$result = $client->exchange_invite_code( $invite_code );
// Refresh token is now stored in wp_options
// Key: beds24_booking_plugin_refresh_token_{property_id}
```

Or via the plugin admin UI (once built) — it calls the same method.

**The exchange is GET, not POST.** The invite code goes in a request
header (`code:`), not in a body. See `beds24-api-work` skill for the
full auth flow.

---

## Verification

After configuration, open the public booking page:

```
https://beds24.com/booking2.php?propid={propid}
```

Confirm:
- Page loads without error
- Offer Select renders (date pickers, room cards with pricing)
- No console errors blocking page render
- Unavailable rooms show "Not available" (not hidden)

---

## Notes on "Your free trial has finished" banner

The Chill Zone Beds24 admin shows a "Your free trial has finished" banner.
Operator has confirmed this is a false alarm — the account is on a paid
plan and the booking page currently renders correctly. Ignore this banner
during rollout; it does not affect functionality.

---

## Known state — Chill Zone (propid 271142)

- Layout 6 + Offer Select only: ✅ (configured 2026-05-07)
- Invite code exchanged: ✅ (refresh token in `.env` and will be in
  `wp_options` once the plugin admin seeds it)
- `customhead` field: contains predecessor `TNH_CONFIG` (1,160 chars) —
  will be replaced by plugin-generated content in a future session
- Booking page URL: `https://beds24.com/booking2.php?propid=271142`

---

## References

| File | When to read |
|---|---|
| `references/property-setup.md` | Full setup doc with verification checklist and known state |
