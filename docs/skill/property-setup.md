# Property Setup — Beds24 Admin Minimum Configuration

**Verified against:** Chill Zone (propid 271142), 2026-05-07  
**Status:** Confirmed working — booking page renders

This document records how to configure a Beds24 property's booking page
for use with the WordPress plugin. Repeat for each new property at
rollout time.

---

## Required configuration

### 1. Booking Engine — Configuration tab

**URL:** `control3.php?pagetype=bookingpage2`

- **Booking Page Version:** Responsive (not Adaptive)
- **Default Layout:** 6

These were already set correctly on Chill Zone before this session. If
not set on a new property, set them and click Save.

### 2. Booking Engine — Layout tab

**URL:** `control3.php?pagetype=bookingpagedesignlayout`

**Note:** The URL `bookingpagev2layout` does not load layout content in
the current Beds24 admin (control3). Use `bookingpagedesignlayout`.

Target state:

- **Offer section:** Offer Select — row 1 position 1, any width
- **All other sections:** no active modules (Property Top, Room Top,
  Room Bottom, Property Bottom all show only the "add module" dropdown)

**Default state on a fresh property:** Room Bottom likely has Room
Description 1, Room Picture, and Room Features active at row 1 position 1.
Remove these — set all three Position dropdowns to "not used" and Save.

**Verification after save:** Reload the page. Room Bottom section should
show only the "add module" dropdown with no module rows.

### 3. Booking Engine — Developer tab

**URL:** `control3.php?pagetype=bookingpagedesigndeveloper`

Field mapping (Beds24 label → textarea id):

| Beds24 label | `textarea` id |
|---|---|
| Insert in HTML \<HEAD\> top | `customheadtop` |
| Insert in HTML \<HEAD\> bottom | `customhead` |
| Insert in HTML \<BODY\> top | `custombodytop` |
| Insert in HTML \<BODY\> bottom | `custombody` |
| Confirmation Page Insert in HTML \<HEAD\> | `customheadconfirm` |
| Custom CSS | `bookingcss` |

**`customhead` (Insert in HTML \<HEAD\> bottom)** is the field the plugin
generates paste-ready content for. On a property being migrated from the
predecessor CSS+JS project, this field may already contain
`window.TNH_CONFIG` and the predecessor helper script. The plugin's
generated content replaces this field entirely — don't append.

**Important — fields that strip HTML tags on programmatic save:**  
`custombody` and `customheadconfirm` strip `<script>` and `<style>` tags
when saved via automation (setting `textarea.value` + clicking save). Only
`customhead` (with Unicode escapes for non-ASCII) and `bookingcss` (raw CSS,
no tags needed) are known to persist programmatic saves correctly. Paste
manually for any field that strips tags.

---

## API authentication (per-property)

Beds24 v2 uses a two-step auth flow. The Beds24 admin generates an **invite
code**, not a refresh token directly. The refresh token is obtained by
exchanging the invite code programmatically. This happens in Session 6
when the API client is built.

**To generate an invite code:**
1. Navigate to `control3.php?pagetype=apiv2`
2. Generate a new invite code for the property
3. Capture it immediately — store in password manager
4. The code is single-use and short-lived

The plugin stores the resulting refresh token in WordPress options (one
per property). Token rotation (30-day lifetime if used regularly) is
automatic once the API client is running.

---

## Verification — booking page renders

After configuring, open the public booking page:

```
https://beds24.com/booking2.php?propid={propid}
```

Confirm:
- Page loads without error
- Offer Select renders (date pickers, room cards with pricing)
- No console errors blocking page render
- Unavailable rooms show "Not available" (not hidden)

---

## Known state — Chill Zone (propid 271142)

- Layout 6 + Offer Select only: ✅ (configured 2026-05-07)
- Invite code generated: ✅ (captured in operator's password manager)
- `customhead` field: contains predecessor TNH_CONFIG (1,160 chars) — will be
  replaced by plugin-generated content in Session 6+
- "Your free trial has finished" banner visible in admin — operator to
  resolve; booking page currently still renders
- Booking page URL: `https://beds24.com/booking2.php?propid=271142`
