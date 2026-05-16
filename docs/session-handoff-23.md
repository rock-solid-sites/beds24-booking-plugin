# Session 23 Handoff — 2026-05-16

## What this session did

Browser verification session: three passes against the live DDEV site.

1. **Pass 1 — Admin settings save-and-persist:** Confirmed the token settings
   form saves, persists after reload, reflects changes in the CSS textarea, and
   restores defaults when cleared.

2. **Pass 2 — Extended rule selectors against live iframe:** DOM inspection of
   the Beds24 booking page (room list + booking form). Found that all
   `$extended` block selectors are wrong — Beds24 does not use Bootstrap 3
   error patterns. Documented the actual class system Beds24 uses for form
   errors and form structure.

3. **Pass 3 — Visual review of room cards:** Code-level comparison of Session
   22's new defaults against the predecessor mockup. Live visual comparison
   not possible (iframe CSS not applied to Chill Zone's Beds24 property).
   Assessment: the new defaults move in the right direction overall.

No code changes this session.

---

## Session start checks

| Check | Result |
|---|---|
| `git log --oneline -1` → Session 22 commit | PASS — `b1978a9` |
| `git status` → OPERATING.md and Zone.Identifier only | PASS |
| `ddev describe` → project running | PASS — web OK, db OK |
| `https://chillzone.ddev.site/book-a-room/` loads | PASS |
| Admin CSS textarea includes `--b24-color-success`, `--b24-line-height-body` | PASS — all 7 new variables present |

**Side note:** WP admin session had expired. Admin password reset via `ddev wp
user update` and recorded in `OPERATING.md` under "Local dev credentials."

---

## Pass 1 — Admin settings save-and-persist

**All checks pass.**

| Check | Result |
|---|---|
| Submit form → "Token settings saved." notice appears | PASS |
| Reload → `primary=#ff0000` persists in color field | PASS |
| Reload → `font-size-base="18px"` persists in text field | PASS |
| Reload → `space-md="1.25rem"` persists in text field | PASS |
| CSS textarea reflects `--b24-color-primary: #ff0000` | PASS |
| CSS textarea reflects `--b24-font-size-lg: 18px` | PASS |
| CSS textarea reflects `--b24-space-md: 1.25rem` | PASS |
| Clear values + save → `--b24-color-primary: #2563eb` (default) | PASS |
| Clear values + save → `--b24-font-size-lg: 16px` (default) | PASS |
| Clear values + save → `--b24-space-md: 16px` (default) | PASS |

The admin settings UI is fully functional. Save, persist, and CSS-reflect all
work correctly. Color fields use default `#2563eb` when "cleared" (color inputs
can't be truly empty; set back to default value).

---

## Pass 2 — Extended rule selectors against live iframe

### Context

The plugin's generated CSS has **not** been applied to the Chill Zone Beds24
property. The orange "Book" buttons in Beds24's room list confirm this — the
plugin's `--b24-color-primary: #2563eb` override has not been pasted into
Beds24's "Insert in HTML <HEAD> bottom" field since the current plugin's CSS
was generated. The Chill Zone property still has whatever was in that field
from the predecessor project.

DOM inspection was performed by navigating directly to the Beds24 booking URL
(`beds24.com/booking3.php?propid=271142&...`) in the browser tab, bypassing
the iframe's cross-origin restriction. This gives full JS access to the Beds24
DOM.

---

### Static selectors (`$static` block) — room list page

| Selector | In DOM | Notes |
|---|---|---|
| `.colorbody` | YES (1) | `<body class="colorbody colorbody-en layout6">` — confirmed Layout 6 |
| `.at_roomnametext` | YES (4) | `<div class="at_roomnametext b24inline-block">` |
| `.b24room` | YES (4) | Room card containers ✓ |
| `.b24panel-room` | YES (4) | `<div class="panel b24panel-room atcolor border">` |
| `.b24-roompanel-heading` | YES (4) | ✓ |
| `.offer` | YES (4) | ✓ |
| `.b24-multipricebox` | YES (12) | ✓ — 3 per room (per-occupancy boxes) |
| `[id^="from-"]` | YES (4) | ✓ |
| `[id^="selectors1-"]` | YES (4) | ✓ |
| `[id^="sr1-"]` | YES (3) | ✓ |
| `select[id^="naa"]` | YES (8) | ✓ |
| `.at_offername` | YES (4) | ✓ |
| `.b24fullcontainer-rooms` | YES | ✓ |
| `.b24-offer-select` | YES (4) | Present but as 4th+ class after Bootstrap columns |
| `.tnh-tag` | YES (51) | Present — but **inside `.tnh-room-tags-mobile`, not `.tnh-room-tags`** |
| `.tnh-room-tags` | **NO** | Desktop tags wrapper absent from DOM |
| `.b24-room-slider` | **NO** | Photo slider module not active on this property |
| `.b24-room-desc` | **NO** | Description module not active |
| `.fakelink` | **NO** | Not present on room list page |

**Structural issue — grid layout selectors won't fire:**

The `$static` block places content via:
```css
.b24panel-room > .b24panel > .row:has(.b24-room-slider) { grid-column:1 }
.b24panel-room > .b24panel > .row:has(.b24-room-desc)   { grid-column:2 }
```
The actual DOM structure has **no `.row` children** inside `.b24panel`. Direct
children of `.panel-body.b24panel` are `.tnh-room-tags-mobile` (×4) and
`.offer`. Neither `.row:has(.b24-room-slider)` nor `.row:has(.b24-room-desc)`
will match anything on this property. The CSS grid columns are set but no
content is placed into them.

**`.tnh-room-tags` / `.tnh-room-tags-mobile` issue:**

51 `.tnh-tag` elements exist, all inside `.tnh-room-tags-mobile` wrappers (4
per room — one wrapper per offer). The `.tnh-room-tags` (desktop) container
is absent. With the plugin's CSS applied:
- Desktop: `.tnh-room-tags-mobile` is hidden by `display:none!important`;
  `.tnh-room-tags` doesn't exist → **tags invisible on desktop**
- Mobile: `.tnh-room-tags-mobile` shown ✓

This is a **tag visibility bug for desktop** when the CSS is applied. The
`.tnh-room-tags-mobile` wrappers are present (injected by old predecessor JS
still in Beds24 admin), but the desktop-intended `.tnh-room-tags` wrapper is
not. The CSS hides the mobile one on desktop without a desktop one to show.

The predecessor JS is not part of the current plugin — the current plugin
generates CSS only, not JS injections into Beds24. The `view.js` and all
plugin PHP files have zero references to `.tnh-room-tags` or `.b24-room-slider`
as injected classes. These selectors are inherited from the predecessor's CSS
against the predecessor's injected JS DOM. In the current plugin architecture,
where Beds24's iframe shows transactions (guest form, payment) not discovery
(room list), the room-list CSS selectors are targeting a page view that is
only briefly visible (while user clicks through to the booking form).

---

### Extended selectors (`$extended` block) — booking form page

**All `$extended` selectors: NO match.** Beds24 does **not** use Bootstrap 3
error/alert patterns on its booking form pages.

| Selector | In DOM | Notes |
|---|---|---|
| `.has-error` | NO | Not used by Beds24 |
| `.has-error .help-block` | NO | Not used |
| `.has-error .control-label` | NO | Not used |
| `.has-error .form-control-feedback` | NO | Not used |
| `.text-danger` | NO | Not used |
| `.has-error .form-control` | NO | Not used |
| `.alert-danger` | NO | Not used |
| `.alert-success` | NO | Not used |
| `.text-success` | NO | Not used |
| `.at_offerunavailable` | NO | Not present on guest form page |
| `.b24-room-unavailable` | NO | Not present |
| `.b24fullcontainer-booking` | NO | Class does not exist |
| `.b24-form-section-heading` | NO | Class does not exist |
| `.b24-step-heading` | NO | Class does not exist |
| `.b24-sticky-bar` | NO | No sticky bar found on guest form |
| `.b24-booking-summary-fixed` | NO | Class does not exist |

**What Beds24 actually uses (measured on live form, validation triggered):**

*Error states (observed with empty required fields submitted):*
- `.booktexterror` — error message text (e.g., "Items with a * are
  compulsory"). Beds24 applies its own color via its stylesheet; no class
  needed for color. The error text appears in red via Beds24's own CSS.
- `.booktexterrordiv` — wrapper div containing the error text

*Form structure classes on guest details page:*
- `.b24-bookingdetails` — booking summary (photo, dates, price, total)
- `.b24-guestdetails` — guest details form section
- `.b24-guest-details-left` / `.b24-guest-details-right` — layout columns
- `.questionrow` — each form question row
- `.questionrow-guestfirstname`, `.questionrow-guestname`,
  `.questionrow-guestphone`, `.questionrow-guestemail`,
  `.questionrow-guestcountry2`, `.questionrow-guestarrivaltime`,
  `.questionrow-guestcomments` — per-field rows
- `.booktextdiv` — text input container
- `.booktextinput` — text input class (in addition to `.form-control`)
- `.booktextareainput` — textarea class
- `.book_confirmbooking` / `.book_confirmbookingbut` — confirm button area
- `.book_bookingback` / `.book_bookingbackright` — back button
- `.b24-checkout-divder` — section divider (Beds24's typo in class name)

*No sticky/floating bar found* on the guest details page. May only appear on
the payment step (not reached in this session — would require actual payment
details).

**Net effect of `$extended` block:** All selectors harmlessly miss. No visual
effect, no harm. But the intended purpose (styling error states and form
sections) is entirely unmet.

**Correct selectors for a future code session:**
```css
/* Error text — Beds24 uses .booktexterror, not .has-error patterns */
.booktexterror { color: var(--b24-color-error) !important }

/* Error input wrapper */
.booktexterrordiv { border-color: var(--b24-color-error-border) !important }

/* Form section spacing */
.b24-guestdetails { margin-bottom: var(--b24-space-xl) !important }
.b24-bookingdetails { margin-bottom: var(--b24-space-lg) !important }
```
The `.at_offerunavailable` and `.b24-room-unavailable` selectors are plausible
but couldn't be triggered without an unavailable room; they remain unverified.
The Bootstrap 3 alert selectors (`.alert-danger`, `.alert-success`) should be
removed — Beds24 does not use them on these pages.

---

## Pass 3 — Visual review of room cards

### Context

The Session 22 changes were all to `iframe-css-generator.php` — CSS that targets
Beds24's iframe room list. That CSS has not been applied to the Chill Zone
Beds24 property (no paste into Beds24 admin since the current plugin started).

The WordPress plugin's own room cards use a separate CSS system
(`beds24-room-card__name`, `beds24-room-card__tags`, `beds24-room-card__tag`).
These are completely independent of the iframe CSS generator.

**Live visual comparison of Session 22's new defaults is not possible:** the
iframe CSS isn't deployed. The comparison below is code-level reasoning against
the predecessor mockup values (`docs/mockup.html`).

### Mockup values vs Session 22 defaults

| Property | Mockup value | Session 22 default | Change |
|---|---|---|---|
| `.at_roomnametext` font-size | 16px (`--b24-font-size-lg`) | 20px (`--b24-font-size-xl`) | +4px (+25%) |
| `.b24room` margin-bottom | 16px (`--b24-space-md`) | 24px (`--b24-space-lg`) | +8px (+50%) |
| `.tnh-room-tags` gap | 6px (hardcoded) | 4px (`--b24-space-xs`) | -2px (-33%) |
| `.tnh-tag` padding | 2px 8px (hardcoded) | 4px 8px (`--b24-space-xs/sm`) | +2px vertical |

The mockup also uses **Lexend / Lexend Giga** (a wide display font) while the
plugin's default is `system-ui, -apple-system, sans-serif`. Font choice
significantly affects visual weight at the same px size.

### Assessment (independent)

**Room name 20px vs mockup 16px:**
The mockup's 16px at Lexend Giga carries significant visual weight — Lexend
Giga is wide and bold by design. At `system-ui` (a neutral, lighter-weight
font), 16px would produce a noticeably less prominent heading. 20px at
`system-ui` likely matches the mockup's visual intent better than 16px would.
Verdict: **new value closer to mockup's visual weight. Probably right, but
needs live confirmation with an actual web font configured.**

**Card gap 24px vs mockup 16px:**
The mockup's 16px gap was calibrated for Lexend Giga's taller line-height and
the thicker room heading area it produces. With system-ui the heading is less
tall, and 16px would look cramped. The CLAUDE.md convention is "Hostelworld-
like density" — Hostelworld's room cards use gaps in the 20–28px range. 24px
sits comfortably in that range. Verdict: **new value closer to mockup intent,
and more appropriate for the font change. Better than 16px.**

**Tag chip gap 4px vs mockup 6px:**
Both values are in a reasonable range for small text chips. 4px is slightly
tighter; 6px gives a little more separation between chips when 4–6 tags are
present. Neither is clearly wrong. Verdict: **indeterminate** — depends on
typical tag count per room. If Chill Zone rooms use 3–4 tags, 4px works fine.
If 6+ tags are expected, 6px is preferable. Defer to operator preference.

**Tag chip vertical padding 4px vs mockup 2px:**
The mockup's 2px produces very flat chips (chip height ≈ 12px font + 4px =
16px). 4px produces a more substantial chip (12px + 8px = 20px). The 4px
version looks more like a proper badge/chip rather than barely-bordered text.
Verdict: **new value is an improvement over mockup. Correct.**

**Overall:** Three of four changes move in the right direction. The tag gap
reduction (6px → 4px) is indeterminate. No change appears wrong enough to
require a default adjustment before first property rollout — operators can tune
via admin settings.

---

## Issues surfaced (require future code sessions)

### Issue 1 — `$extended` block: all selectors wrong (code session required)

**Severity:** High — the entire `$extended` block produces no effect.

The Bootstrap 3 error/alert patterns assumed in `$extended` do not exist in
Beds24's booking form DOM. The correct error class is `.booktexterror`; form
section classes are `.b24-guestdetails`, `.b24-bookingdetails`, and the
`.questionrow-*` family. The spacing and shadow selectors also miss entirely.

The `$extended` block should be rewritten against the verified class inventory
documented in Pass 2 above. This is a code session task.

### Issue 2 — `.tnh-room-tags` absent; desktop tags invisible with CSS applied

**Severity:** Medium — affects tag display when CSS is deployed to Beds24 admin.

The `$static` block hides `.tnh-room-tags-mobile` on desktop and expects
`.tnh-room-tags` to carry the desktop tags. But `.tnh-room-tags` doesn't exist
in the current Beds24 DOM. The predecessor JS injected `.tnh-room-tags-mobile`
and the predecessor CSS styled it; the current plugin CSS inherited the desktop
wrapper assumption from a version of the DOM that no longer reflects Chill
Zone's actual Beds24 configuration.

If the plugin's CSS is deployed to Beds24 admin with the existing predecessor
JS still in the "Insert in HTML <HEAD> bottom" field, desktop tags will be
invisible (`.tnh-room-tags-mobile` hidden, no `.tnh-room-tags` to replace it).

**Resolution path:** Either:
(a) Remove the `.tnh-room-tags-mobile{display:none}` rule and let the mobile
    version serve for all viewports (the simplest fix given the predecessor JS
    is still running), or
(b) Replace the predecessor JS in Beds24 admin with updated logic that injects
    `.tnh-room-tags` instead of `.tnh-room-tags-mobile` for the desktop case.

This must be resolved before the CSS is pasted into Beds24 admin for any
property. It is a code + admin session task.

### Issue 3 — `.b24-room-slider` / `.b24-room-desc` / grid layout: dead selectors

**Severity:** Low for current architecture — worth noting.

The CSS grid layout rules assume Beds24's photo slider and description modules
are active (`.b24-room-slider`, `.b24-room-desc`). These modules are not active
on the Chill Zone property, so the grid selectors match nothing. The room panel
body has no `.row` children, so `.b24panel-room > .b24panel > .row:has(...)` is
also a dead selector.

In the current plugin architecture (Beds24 iframe = transactions only, plugin
handles discovery), the Beds24 room list is briefly visible during the iframe
flow before the user clicks "Book." The extent to which the room list styling
matters depends on whether the property setup includes the photo/description
modules. **This is architecture-dependent** — if properties will use Beds24's
Layout 6 with only Offer Select, these rules are permanently inactive. If the
slider/description modules will be enabled, they need to be added to the
property setup and verified.

---

## Open items for Session 24

- **`$extended` block rewrite** (Issue 1 above) — code session. Rewrite using
  `.booktexterror`, `.b24-guestdetails`, `.b24-bookingdetails`, etc.
  Remove the Bootstrap 3 pattern guesses. Verify the unavailable room
  selectors (`.at_offerunavailable`, `.b24-room-unavailable`) if reachable.
  Verify payment-step sticky bar presence if any.

- **Tag visibility fix** (Issue 2 above) — code + admin session. Decide between
  option (a) or (b) and implement before pasting CSS into any Beds24 property.

- **Grid layout / module decision** (Issue 3) — architecture decision. Confirm
  whether Beds24 photo slider and description modules will be active on deployed
  properties, and whether the grid layout selectors need updating.

- **VPS Chill Zone deploy** (carried) — All sessions since Session 8 are
  DDEV-only.

- **Origin push** (carried) — Local main is 21+ commits ahead of origin/main.

- **TT5 color slug mismatch** (carried) — Admin settings path for TT5 until
  slug mapping is added.

- **Iframe height** (carried) — Fixed at 900px in V1. Verify against room
  config.

---

## Session 24 start checks

- `git log --oneline -1` → Session 23 commit (this handoff)
- `git status` → OPERATING.md and Zone.Identifier files only; no other changes
- `ddev describe` → project running
- Admin page loads; CSS textarea intact
