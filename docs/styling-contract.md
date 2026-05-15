# Styling Contract — Beds24 Booking Plugin

**Status:** Draft for ratification
**Version:** 0.1.0 (pre-release)
**Last updated:** 2026-05-08
**Source documents:** `docs/architecture.md`, design-conversation responses 2026-05-08
**Owners:** Plugin project (canonical), design conversation (contributor)

---

## Purpose

This document defines the public styling surface of the Beds24 booking
plugin. It specifies what the plugin emits as DOM (with documented CSS
classes), what design tokens the plugin consumes from the active theme,
and how themes can override defaults via theme.json or plugin admin
settings.

The contract is one-way:

- The **plugin commits** to the documented DOM, the documented class
  set, the documented token consumption behavior, and the stability
  guarantees in this document.
- The **theme commits to nothing.** A theme can populate any subset of
  the documented tokens, target any subset of the documented classes,
  or do neither. The plugin's defaults must produce a presentable
  booking flow on a theme that has done nothing.

This document is the source of truth. If the plugin's implementation
diverges from the contract, fix the implementation or update the
contract. Themes can rely on what is documented here; they cannot rely
on undocumented internal classes or token names.

---

## Architectural decisions ratified

These decisions are settled by this document. They are foundational to
everything below. Future sessions revisit them only with explicit
intent.

### Decision 1 — theme.json consumption is the primary visual customization mechanism

The plugin reads design tokens from the active theme's `theme.json`
when present. This is the primary path for block themes.

**Rationale:** All four hostel properties will use block themes. For
the broader WP.org case, block themes are the current direction for
new WordPress builds. Reading from theme.json gives themes a
declarative, single-source-of-truth way to customize the plugin
without writing CSS or configuring plugin settings separately.

**Confidence:** High. Ratified in design conversation 2026-05-08
following plugin project's proposed layered approach.

### Decision 2 — Plugin admin settings are the fallback

When a theme does not define the relevant tokens (classic themes, hybrid
themes, themes that haven't adopted FSE), the plugin's admin UI lets
operators configure values manually. Settings are stored in `wp_options`
via CMB2.

**Rationale:** The plugin must be usable on themes that haven't adopted
theme.json. The admin settings panel is the path that makes the plugin
work on any WordPress site, not only block-theme sites. For WP.org
distribution, this is necessary.

**Confidence:** High. Ratified together with Decision 1.

### Decision 3 — CSS variables are the underlying transport

Both theme.json tokens and admin settings produce CSS custom properties
on the plugin's root element. All plugin styling references these
variables. The DOM does not know or care which source populated them.

**Variable namespace:** `--beds24-*`. Matches the `beds24-` class
prefix used throughout the plugin's emitted DOM. Replaces the
inconsistently-named `--booking-*` variables proposed in earlier
discussion.

**Rationale:** A single styling layer simplifies the plugin's CSS. The
plugin's stylesheet references variables; the variables get populated
from theme.json or admin settings depending on what's available;
themes can additionally override variables directly via their own CSS
if neither path is sufficient.

**Confidence:** High. Standard CSS-variable-based theming pattern.

### Decision 4 — The plugin renders structure; the theme renders character

Distinctive visual elements (badges, polaroid treatments, marquees,
display typography, decorative borders) are theme concerns. The plugin
renders the booking flow's structure with predictable, themeable DOM.
Themes layer character on top via CSS targeting the documented
classes.

**Rationale:** Property visual identity varies. A plugin that bakes in
a specific aesthetic forces every property to match that aesthetic or
fight the plugin. A plugin that exposes a styleable structure lets
each property apply its own design language.

**Confidence:** High. Aligns with `docs/architecture.md` design
principle 2 (the plugin handles discovery; Beds24 handles
transactions) and the predecessor project's design-language separation
of structure and treatment.

### Decision 5 — Iframe CSS is generated programmatically

For the Beds24 iframe (the transaction half of the booking flow), the
plugin generates a complete CSS payload from the configured tokens and
displays it in plugin admin for the operator to copy into Beds24's
"Insert in HTML \<HEAD\> bottom" field. The operator does not write CSS
by hand.

When tokens change (theme.json updates, admin settings change), the
generated CSS changes. The operator regenerates and re-pastes.

**Rationale:** The plugin lacks Beds24 admin API write access, and
the architecture's transaction-boundary principle (`docs/architecture.md`)
pushes back on the plugin having admin-write access regardless. The
copy-paste workflow is operationally simple and keeps Beds24 admin
under operator control. See `skills/beds24-property-rollout/references/property-setup.md` for the
field path and operational procedure.

**Confidence:** High. Confirmed in design conversation 2026-05-08.

**Known friction:** Operators will forget to regenerate and re-paste
when tokens change. This is the most common kind of post-rollout
customization and the friction will produce visual mismatches between
the on-site rendering and the iframe.

This is V1.x territory, not unbounded future work. The mitigation
mechanism is sketched in Known unknown 6 below. V1 ships without the
mitigation; the property setup documentation surfaces the dependency
manually until the tooling lands.

---

## Design tokens consumed

The plugin reads the following token roles from the active theme's
theme.json. If a token is not defined, the plugin falls back to its
default value (defined per-token below). Themes may define some,
all, or none of these tokens.

The plugin's admin settings UI lets operators configure these same
roles when theme.json doesn't define them. The two sources are
layered, not overlapping: theme.json is read first, and admin
settings supply values only for roles theme.json hasn't defined. A
role defined in theme.json is not overridable through admin
settings in V1. See Known unknown 2 for the reasoning and the
condition under which an override mechanism would be added.

### Color tokens

The plugin consumes colors by their semantic role, not by theme.json
slug. The plugin's settings UI maps theme palette slugs to roles. For
themes whose theme.json palette uses the same slugs the plugin looks
for, mapping is automatic.

| Role | Where used | Default value |
|---|---|---|
| `primary` | Confirm Booking button background, selected room highlight | `#2563eb` |
| `primary-text` | Text on primary-colored surfaces | `#ffffff` |
| `accent` | Price emphasis, "from" labels, badge highlights | `#f59e0b` |
| `surface` | Room card backgrounds, cart background | `#ffffff` |
| `surface-text` | Body text on surface backgrounds | `#1f2937` |
| `surface-muted` | Secondary text (descriptions, metadata) | `#6b7280` |
| `border` | Card borders, dividers, input borders | `#e5e7eb` |
| `success` | Confirmation states, "available" indicators | `#10b981` |
| `unavailable` | Sold-out states, disabled selections | `#9ca3af` |
| `error` | Text color for validation error messages | `#dc2626` |
| `error-bg` | Background of error message regions | `#fef2f2` |
| `error-border` | Border of error message regions | `#fecaca` |

**Theme.json slug mapping:**

When reading theme.json, the plugin first looks for a palette slug
matching the role name (e.g., `primary`, `accent`). If not found, the
plugin's admin UI prompts the operator to choose which theme color
fills each role. The mapping is stored in `wp_options` and persists
across plugin updates.

**Default values rationale:**

Defaults are visually plain — neutral palette, single accent — to
avoid clashing with the widest range of themes. They produce a
presentable booking flow on a stock theme without configuration.
Themes that want a distinctive look override the variables; themes
that don't can leave them as-is.

### Typography tokens

| Role | Where used | Default value |
|---|---|---|
| `font-family-body` | Room descriptions, form labels, body text | `system-ui, -apple-system, sans-serif` |
| `font-family-heading` | Room names, section headings | `system-ui, -apple-system, sans-serif` |
| `font-family-display` | Optional accent font for prices, "from" labels | (inherits `font-family-heading`) |
| `font-size-base` | Body text base size | `1rem` |
| `font-size-small` | Metadata, captions, tags | `0.875rem` |
| `font-size-large` | Room names, prominent prices | `1.25rem` |
| `font-weight-body` | Body text weight | `400` |
| `font-weight-heading` | Heading weight | `600` |
| `line-height-body` | Body text line height | `1.5` |
| `line-height-heading` | Heading line height | `1.2` |

**Theme.json mapping:**

theme.json's `typography.fontFamilies` array is read. The plugin
looks for slugs matching `body`, `heading`, and `display`. If not
present, the plugin uses theme.json's default font (the first entry
in `fontFamilies`) for all three roles. Sizes and weights are read
from theme.json's typography presets where available; otherwise
defaults apply.

### Spacing tokens

The plugin uses theme.json's spacing scale directly via the standard
WordPress preset reference syntax (`var(--wp--preset--spacing--XX)`).
Internally, the plugin's CSS variables map to specific scale steps:

| Role | Used for | Default value |
|---|---|---|
| `space-xs` | Tag padding, tight inline gaps | `0.25rem` |
| `space-sm` | Card internal padding (mobile), small gaps | `0.5rem` |
| `space-md` | Card padding (desktop), section gaps (mobile) | `1rem` |
| `space-lg` | Section gaps (desktop), card-to-card spacing | `1.5rem` |
| `space-xl` | Major section breaks | `2rem` |

**Theme.json mapping:**

If theme.json defines `spacing.spacingScale`, the plugin maps its xs–xl
roles to the closest scale steps. If theme.json defines explicit
spacing presets with matching slugs (`xs`, `sm`, `md`, `lg`, `xl`), those
are used directly. Otherwise defaults apply.

**Why a five-step scale:**

A booking interface has limited spacing variety in practice. Five
roles cover the cases without over-specifying. A theme that wants
finer control overrides the variables directly via CSS.

### Layout tokens

| Role | Used for | Default value |
|---|---|---|
| `border-radius` | Cards, buttons, inputs | `0.5rem` |
| `border-radius-small` | Tags, small accents | `0.25rem` |
| `shadow-card` | Room card elevation | `0 1px 3px rgba(0,0,0,0.1)` |
| `shadow-floating` | Mobile cart bar, sticky elements | `0 -2px 8px rgba(0,0,0,0.1)` |
| `card-max-width` | Constraint on card-bearing container width | `none` (cards fill their container) |

**Theme.json mapping:**

Border radius is read from theme.json's `custom` section if a
`border-radius` key is defined. Shadows are not standardly defined in
theme.json; the plugin uses admin-settings values when configured,
falling back to the defaults above.

---

## CSS class contract

### Principles

The plugin emits DOM with classes following standard BEM conventions
with a namespace prefix.

**Namespace prefix:** `beds24-`. Every plugin-emitted block class
begins with this prefix to avoid collisions with theme classes.

**Naming convention:** `.beds24-<block>` for blocks (the major
conceptual units — `.beds24-room-card`, `.beds24-search-form`),
`.beds24-<block>__<element>` for elements within a block
(`.beds24-room-card__price`, `.beds24-search-form__submit`),
`.beds24-<block>--<modifier>` for state or variant modifiers on a
block (`.beds24-room-card--selected`),
`.beds24-<block>__<element>--<modifier>` for modifiers on elements
(`.beds24-room-card__tag--unavailable`).

The block portion is hyphen-separated when multi-word
(`room-card`, not `roomcard` or `room_card`). The element and
modifier separators (`__` and `--`) carry the BEM semantic and
are not used elsewhere in class names.

This is standard BEM with a project namespace prefix on each block.
Theme developers familiar with BEM should recognize the convention
without explanation.

**Examples of correct class names:**

- `.beds24-room-card` — block
- `.beds24-room-card__photo` — element of the room-card block
- `.beds24-room-card__price` — element of the room-card block
- `.beds24-room-card--selected` — block-level modifier (selected state)
- `.beds24-room-card__tag` — element
- `.beds24-room-card__tag--unavailable` — element-level modifier
- `.beds24-cart` — block
- `.beds24-cart__item` — element
- `.beds24-cart__confirm-button` — element (multi-word element name uses hyphen)

**Public vs. internal:**

Classes documented in the class catalog (below, when drafted) are
**public**: themes can target them with confidence that the plugin
will not rename or restructure them without a major version bump.

Classes that the plugin emits but does not document are **internal**:
the plugin reserves the right to rename, restructure, or remove them
between versions. Themes that target undocumented classes do so at
their own risk.

The plugin commits to:

- The documented classes existing on the documented DOM elements
- The documented classes carrying the documented semantic meaning
- New public classes being added in a forward-compatible way (existing
  classes continue to work)
- Removals or renames of public classes happening only in major
  version bumps, with a deprecation notice in the prior minor version

### Class catalog

The catalog is built incrementally as the plugin's frontend layers are
implemented. Entries added in Session 9 cover the search form. Room
card, cart, and mobile bar entries follow in later sessions.

---

#### Search form — `beds24-search-form` block (added Session 9)

The search form renders as a `<form>` element inside the block wrapper.
Its classes follow the BEM `beds24-search-form` block namespace.

| Class | Element | Semantics | State variants | Co-occurring classes |
|---|---|---|---|---|
| `beds24-search-form` | `<form>` | Root of the search form. Carries `data-property-id` and `data-min-stay` attributes consumed by the frontend JS. | — | — |
| `beds24-search-form__min-stay` | `<p>` | Minimum stay notice displayed above the date inputs (e.g. "Minimum stay: 2 nights"). Informational; not an interactive element. | — | — |
| `beds24-search-form__fields` | `<div>` | Flex container holding the two date field groups. Renders side-by-side on desktop (≥768px), stacked on mobile. | — | — |
| `beds24-search-form__field-group` | `<div>` | Wrapper for one label + input pair. Two instances: check-in and check-out. | — | — |
| `beds24-search-form__label` | `<label>` | Field label ("Check-in", "Check-out"). Associates with its input via `for` attribute. | — | — |
| `beds24-search-form__check-in` | `<input type="date">` | Check-in date input. Native browser date picker. `id="beds24-check-in"` in V1 (static; single-instance assumption). | — | — |
| `beds24-search-form__check-out` | `<input type="date">` | Check-out date input. Native browser date picker. `id="beds24-check-out"` in V1. | — | — |
| `beds24-search-form__error` | `<div>` | Validation error message region. Hidden via the HTML `hidden` attribute when no error is present. JS removes `hidden` to reveal, sets it to re-hide. Carries `role="alert"` and `aria-live="assertive"` for accessible error announcement. | Hidden by default; revealed by JS on validation failure. No CSS class variant — the `hidden` attribute is the toggle. | — |
| `beds24-search-form__submit` | `<button type="submit">` | Search Rooms submit button. Full-width. Primary color background. | `:disabled` state styled (unavailable color, `not-allowed` cursor) for future use when the button is disabled during API loading. | — |

**Block wrapper (not part of the `beds24-search-form` BEM block):**

| Class | Element | Semantics |
|---|---|---|
| `beds24-booking-flow` | `<div>` | Outermost block wrapper. The plugin's root element. CSS custom property (`--beds24-*`) defaults are defined here. Themes override by targeting this class with their own custom property assignments. |
| `wp-block-beds24-booking-flow` | `<div>` (same element) | WordPress-generated block identifier. Not part of the plugin's public class contract; do not rely on it for styling. |

---

---

#### Room results container — `beds24-room-results` (added Session 11)

The results container is a sibling of `.beds24-search-form` inside
`.beds24-booking-flow`. It is hidden (via the HTML `hidden` attribute) until
a search completes; JS removes `hidden` after rendering cards.

| Class | Element | Semantics | State variants |
|---|---|---|---|
| `beds24-room-results` | `<div>` | Container for all rendered room cards. Flex column layout, cards stacked vertically. Hidden by default; revealed by JS after card rendering. Carries `aria-live="polite"` so screen readers announce newly rendered cards. | Hidden by default; `hidden` attribute removed after rendering. |

---

#### Room card — `beds24-room-card` block (added Session 11)

One card per room in the search results. Renders for both available and
unavailable rooms. Unavailable rooms get the `--unavailable` modifier but
are not omitted.

| Class | Element | Semantics | State variants | Co-occurring classes |
|---|---|---|---|---|
| `beds24-room-card` | `<div>` | Card root. Carries `data-room-id` attribute with the Beds24 room ID for future JS targeting (cart accumulator). | `beds24-room-card--unavailable` when the room has no offers for the selected dates. | — |
| `beds24-room-card__name` | `<h3>` | Room name heading. Text content from the matching `beds24_room` post title. Falls back to "Room {roomId}" if no post is found (fail-loud fallback — the mismatch is also logged to console). | — | — |
| `beds24-room-card__body` | `<div>` | Flex container for photo + description. Desktop: photo and description side by side. Mobile (≤767px): stacked vertically. | — | — |
| `beds24-room-card__photo` | `<div>` | Photo column. Width: 140px desktop, 100% mobile. Rendered only when the room post has a featured image. | — | — |
| `beds24-room-card__content` | `<div>` | Description column. Flex-grows to fill remaining space. | — | — |
| `beds24-room-card__description` | `<p>` | Room description text. Content from the `beds24_room` post, trimmed to 40 words. | — | — |
| `beds24-room-card__offer` | `<div>` | Offer row at the card bottom. Contains price (available) or unavailable notice. Separated from the body by a top border. | — | — |
| `beds24-room-card__price` | `<p>` | Price display for available rooms. Format: "from €XX / night", where XX = total offer price / nights. | — | Present on available cards only. |
| `beds24-room-card__unavailable-notice` | `<p>` | "Not available for selected dates" text for unavailable rooms. | — | Present on unavailable cards only. |
| `beds24-room-card--unavailable` | modifier on `beds24-room-card` | Applied when the room has no offers for the selected dates (`room.offers` is absent or empty). Reduces opacity and mutes the room name color. | — | Co-occurs with `beds24-room-card`. |

**Note on cart controls:** Quantity inputs, running total, and the
Add/Remove action elements are documented in the cart-controls section below.
The `beds24-room-card__cart-btn` and `beds24-room-card__qty-control` classes
are public and stable.

---

#### Room card tag chips (added Session 12; DOM position updated Session 18)

Amenity chips render from the room's `beds24_amenity` taxonomy terms. Tags
are user-defined content — Beds24 featureCodes are **not** included here
(featureCodes are a future addition resolved at render time; taxonomy chips
are the only implemented chip source in V1).

The chip container is omitted entirely when a room has no assigned terms.
No empty container, no error.

**DOM position (updated Session 18):** `.beds24-room-card__tags` is a direct
child of `.beds24-room-card` (sibling of `.beds24-room-card__body`), rendered
between `__body` and `__offer`. This placement allows the chip row to span the
full card width on mobile. At desktop, CSS provides a `padding-left` indent
that aligns the chips under the description column.

| Class | Element | Semantics | State variants |
|---|---|---|---|
| `beds24-room-card__tags` | `<div>` | Chip container. Direct child of `.beds24-room-card`, between `__body` and `__offer`. Flex-row, wrapping. Present only when the room has at least one amenity term. At desktop, left-padding indented to align under the description column. At mobile, full card width. | — |
| `beds24-room-card__tag` | `<span>` | Individual chip. One per amenity term. Small text (0.75rem / 12px), subtle background (#f3f4f6), bordered, inline-flex. | — |

---

#### Room card cart controls (added Session 12)

Per-card controls for adding rooms to the cart. Two variants based on room type:
- **Dorm rooms** (`roomType: "bedInDormitory"`): quantity [−] [N] [+] widget.
- **Private rooms** (all other roomTypes): Add/Remove toggle button.

Controls are rendered only on available rooms. Unavailable cards have no controls.

| Class | Element | Semantics | State variants |
|---|---|---|---|
| `beds24-room-card__qty-control` | `<div>` | Dorm quantity widget wrapper. Carries `data-max-qty` attribute with the maximum selectable bed count (= `offer.unitsAvailable`). | — |
| `beds24-room-card__qty-btn` | `<button type="button">` | Shared class for decrement and increment buttons. Square, 28px. | `:disabled` state styled (opacity 0.4, not-allowed cursor). |
| `beds24-room-card__qty-btn--dec` | modifier on `beds24-room-card__qty-btn` | Decrement (−) button. Starts disabled (qty=0). | Disabled when qty ≤ 0. |
| `beds24-room-card__qty-btn--inc` | modifier on `beds24-room-card__qty-btn` | Increment (+) button. | Disabled when qty ≥ maxQty. |
| `beds24-room-card__qty-value` | `<span>` | Current quantity display. Text content updated by JS on state change. Carries `aria-live="polite"`. | — |
| `beds24-room-card__cart-btn` | `<button type="button">` | Private room Add/Remove toggle. Text toggles between "Add" and "Remove". Primary color background when in Add state. | `beds24-room-card__cart-btn--in-cart` when the room is in the cart. |
| `beds24-room-card__cart-btn--in-cart` | modifier on `beds24-room-card__cart-btn` | Applied when the private room is in the cart. Muted style; hover treatment signals destructive intent (error color). | Co-occurs with `beds24-room-card__cart-btn`. |

**Selected state on card root:**

| Class | Element | Semantics | State variants |
|---|---|---|---|
| `beds24-room-card--selected` | modifier on `beds24-room-card` | Applied when any quantity > 0 is in the cart for this room. Primary-color border highlight. | Co-occurs with `beds24-room-card`. Never co-occurs with `beds24-room-card--unavailable` (unavailable rooms cannot be added to cart). |

---

#### Cart region — `beds24-cart` block (added Session 12)

The cart region is a sibling of `.beds24-room-results` inside `.beds24-booking-flow`.
Hidden (HTML `hidden` attribute) when the cart is empty; revealed by JS when at
least one room is added.

The Confirm Booking button is deferred to the next session — it requires URL
construction logic not yet built. The cart region renders items and a running
total without it.

| Class | Element | Semantics | State variants |
|---|---|---|---|
| `beds24-cart` | `<div>` | Cart region root. Hidden by default; `hidden` attribute removed by JS when cart is non-empty. | Hidden by default; revealed by JS. |
| `beds24-cart__heading` | `<h2>` | "Your Stay" section heading. | — |
| `beds24-cart__list` | `<ul>` | List of selected room items. Populated by JS on state change. | — |
| `beds24-cart__item` | `<li>` | One row per selected room. Three columns: name, detail, per-night total. | — |
| `beds24-cart__item-name` | `<span>` | Room name. Truncates with ellipsis on overflow. | — |
| `beds24-cart__item-detail` | `<span>` | Dorms: "N beds × €X / night". Privates: "€X / night". | — |
| `beds24-cart__item-total` | `<span>` | Per-item running contribution: "€X / night". | — |
| `beds24-cart__footer` | `<div>` | Footer row: total label + total value. Space-between flex. | — |
| `beds24-cart__total-label` | `<span>` | "Total per night" label. | — |
| `beds24-cart__total` | `<span>` | Running per-night total across all cart items. Text content updated by JS. | — |

#### Cart item remove control (added Session 14)

A remove button appended to every `<li class="beds24-cart__item">`. Clicking it removes the room entirely from the cart (quantity → 0), which triggers all store subscribers: the card loses its selected state, the running total updates, and the bar hides if the cart is now empty.

For dorm rooms, remove clears the entire bed count (not minus-one). The dorm card's quantity widget resets to 0.

| Class | Element | Semantics | State variants |
|---|---|---|---|
| `beds24-cart__item-remove` | `<button type="button">` | Remove control (×) inside each cart item. Minimal — no background, no border, small muted ×. | Hover state applies error color to signal destructive intent. |

**`data-room-id` on `<li>`:** Each `.beds24-cart__item` carries a `data-room-id` attribute (added Session 14) so the document-level click delegator can identify which room to remove without walking up to a card element.

---

#### Cart sticky footer bar (added Session 14)

At ≥768px viewport width, `.beds24-cart` is positioned `fixed; bottom: 0; left: 0; right: 0` — a full-width bar anchored to the viewport bottom. At < 768px, the cart remains inline (mobile bottom-bar-with-drawer is a later session).

**No BEM modifier class.** The sticky layout is the only desktop cart layout in V1. A media query on the base `.beds24-cart` class distinguishes desktop from mobile naturally. When the mobile drawer session ships it will use its own modifier or block variant.

**Shadow:** The bar uses `--beds24-shadow-floating` (`0 -2px 8px rgba(0,0,0,0.1)`) — an upward-directed shadow to visually separate the bar from page content above it. This token is now defined in the token defaults on `.beds24-booking-flow`.

**Bottom padding:** JS adds `padding-bottom` equal to the bar's rendered height to `.beds24-booking-flow` when the cart is visible (measuring after render via `setTimeout(0)`), so no page content is hidden behind the fixed bar. Padding is cleared when the cart is hidden or at mobile widths.

**Internal layout (desktop):** Horizontal flex row: `[items list scrollable, flex-1] [footer: total label + value] [actions: Confirm Booking button]`. The cart heading ("Your Stay") is hidden. The per-item total column is hidden (running total is in the footer). Items list is horizontally scrollable if many items are added.

---

#### Cart confirm button and iframe (added Session 13; transition added Session 15)

The Confirm Booking button sits inside a `.beds24-cart__actions` wrapper at the
bottom of the cart region. Clicking it constructs the multi-room Beds24 URL from
cart state, hides the discovery UI (room results and cart bar), and loads the
Beds24 booking page in an inline iframe.

**Discovery ↔ transaction transition:**

- **Confirm Booking click:** `.beds24-room-results` and `.beds24-cart` receive the
  `hidden` attribute; `.beds24-booking-iframe-wrapper` has `hidden` removed;
  `iframe.src` is set to the constructed URL. The search form stays visible.
- **Back to rooms click:** `iframe.src` is set to `about:blank`; the wrapper
  receives `hidden`; cart state resets to empty (triggering `renderCart` and
  `syncCardControls` subscribers — cart hides, card selected states clear);
  `.beds24-room-results` has `hidden` removed if it contains rendered cards.

The iframe starts with **no `src` attribute** (not even `src=""`). In Chrome,
`src=""` resolves to the current page URL and loads it in the iframe. The `src`
attribute is only ever set by `openBookingIframe()`.

Height is fixed at 900px in V1.

**`[hidden]` override note:** The stylesheet includes an explicit rule:
`.beds24-cart[hidden], .beds24-room-results[hidden] { display: none !important; }`.
This is required because Chrome's UA stylesheet applies `[hidden] { display: none }`
without `!important`, so author `display` rules (e.g. `display: flex` on
`.beds24-cart` in the desktop media query) silently override it. Firefox uses
`!important` in its UA stylesheet and is unaffected. The rule ensures consistent
hide behavior across browsers.

| Class | Element | Semantics | State variants |
|---|---|---|---|
| `beds24-cart__actions` | `<div>` | Wrapper for the confirm button inside `.beds24-cart`. Provides bottom padding. | — |
| `beds24-cart__confirm-button` | `<button type="button">` | "Confirm Booking" primary CTA. Full-width, primary color background. | `disabled` when the cart is empty (HTML attribute; styled with unavailable color and `not-allowed` cursor). Enabled when at least one room is in the cart. |
| `beds24-booking-iframe-wrapper` | `<div>` | Wrapper for the Beds24 booking iframe nav strip and iframe. Sibling of `.beds24-cart`. Hidden by default; JS removes `hidden` on confirm, sets it again on back. | Hidden by default; toggled by JS. |
| `beds24-booking-iframe-nav` | `<div>` | Navigation strip above the iframe. Contains the "← Back to rooms" button. | — |
| `beds24-booking-iframe-nav__back` | `<button type="button">` | Back-to-rooms CTA. Minimal link-style button. Clicking resets cart, hides iframe wrapper, reveals room results. | Hover: text-decoration underline. |
| `beds24-booking-iframe` | `<iframe>` | Loads the Beds24 booking3.php page with pre-populated room selections, dates, and adult counts. `src` is set by JS at confirm time; cleared to `about:blank` on back. Fixed 900px height in V1. | — |

---

#### Mobile cart — bottom bar and slide-up drawer (added Session 17)

At viewports below 768px, the `.beds24-cart` becomes a fixed viewport-bottom bar
via a `@media (max-width: 767px)` block. The cart's DOM has two wrappers that change
behavior across breakpoints:

- **`.beds24-cart__drawer`** — at ≥768px: `display: contents` (transparent to the desktop
  flex layout). At <768px: a collapsible section that animates from `max-height: 0` to
  `max-height: 60vh`. DOM order: first child of `.beds24-cart`, so it appears above the bar
  when the cart expands upward from the viewport bottom.
- **`.beds24-cart__mobile-bar`** — at ≥768px: `display: contents` (transparent; its
  `.beds24-cart__actions` child participates as a desktop flex item). At <768px: a 56px
  flex row that forms the always-visible bar.

The backdrop is a sibling to `.beds24-cart` inside `.beds24-booking-flow`.

| Class | Element | Semantics | State variants |
|---|---|---|---|
| `beds24-cart__drawer` | `<div>` | Wrapper for heading, list, and footer. Transparent at desktop via `display: contents`; collapsible at mobile via `max-height` transition. | Collapsed by default at mobile; `max-height: 60vh; overflow-y: auto` when `.beds24-cart--drawer-open` is present on `.beds24-cart`. |
| `beds24-cart__mobile-bar` | `<div>` | Wrapper for mobile toggle and actions. Transparent at desktop via `display: contents`; 56px flex row at mobile. | — |
| `beds24-cart__mobile-toggle` | `<button type="button">` | Tap target occupying the left side of the mobile bar. Contains the summary span and chevron. `display: none` at desktop. `min-height: 44px` for WCAG touch target. | `aria-expanded="false"` → `"true"` when drawer is open. |
| `beds24-cart__mobile-summary` | `<span>` | Summary text inside the toggle. Format: "N room[s] · €X / night". Populated by the `syncMobileBar` JS subscriber on every cart state change. | — |
| `beds24-cart__mobile-chevron` | `<span>` | ▲ chevron icon inside the toggle. Rotates 180° via CSS transition when `.beds24-cart--drawer-open` is present. `display: inline-block; transition: transform 0.3s ease`. | Rotated 180° when drawer is open. |
| `beds24-cart--drawer-open` | modifier on `beds24-cart` | Applied by JS when the drawer is open. Drives `max-height: 60vh` on the drawer and 180° rotation on the chevron. Also controls `overflow-y: auto` on the drawer. | Applied/removed by `openDrawer()` / `closeDrawer()` in JS. |
| `beds24-cart-backdrop` | `<div>` | Semi-transparent overlay (`rgba(0,0,0,0.4)`) behind the drawer when open. Sibling of `.beds24-cart` inside `.beds24-booking-flow`. `position: fixed; inset: 0; z-index: 999` (below cart at 1000). `display: none !important` at ≥768px. | Hidden by default; `hidden` attribute removed when drawer opens, set when drawer closes. |

**Drawer content:** `.beds24-cart__heading`, `.beds24-cart__list` (with `.beds24-cart__item` rows at full width), `.beds24-cart__footer`. All standard classes from the cart block catalog apply within the drawer.

**Confirm Booking button at mobile:** The `.beds24-cart__confirm-button` lives inside `.beds24-cart__actions` which is inside `.beds24-cart__mobile-bar`. It is always visible in the mobile bar. At desktop, the same element participates in the flex bar row because `.beds24-cart__mobile-bar` is `display: contents`.

**Scroll lock:** When the drawer opens, `document.body.style.overflow = 'hidden'` is set to prevent body scrolling. Cleared when the drawer closes, when the Confirm Booking button is pressed, or when the Back to rooms button is pressed.

**Bottom padding:** `syncBottomPadding()` sets `document.body.style.paddingBottom` at both desktop and mobile. At mobile, padding is based on the mobile bar height only (not the drawer height), because the drawer's backdrop prevents interaction with hidden content.

---

#### Pending catalog sections

The following sections will be drafted when their frontend layers are built:

- **State modifier classes** — `--disabled`, `--loading` variants. (Added per block as implemented)

### Targeting guidance for themes

Themes that want to apply distinctive visual treatments (badges,
polaroid borders, custom accents) target the public classes via their
own stylesheet or via theme.json's `styles.blocks` declarations for
the `beds24/booking-flow` block.

The plugin's CSS specificity is intentionally low — single-class
selectors, no `!important` declarations, no nested specificity tricks
— so that theme CSS overrides cleanly without specificity battles.

See `docs/retrospective.md` rule "Inject overrides via JS when CSS
load order fails" for related context on specificity issues observed
in the predecessor project.

---

## Iframe CSS generation

For the iframe-rendered transaction half of the booking flow (guest
details form, payment, confirmation), the plugin generates a CSS
payload from the same token configuration that drives the on-site
rendering.

### Generation workflow

1. The plugin's admin UI exposes a "Beds24 admin setup" page.
2. This page displays a paste-ready CSS string, generated from the
   currently-configured tokens.
3. The operator copies the string and pastes it into Beds24's "Insert
   in HTML \<HEAD\> bottom" field for the property. (See
   `skills/beds24-property-rollout/references/property-setup.md`.)
4. When tokens change, the page displays the updated string. The
   operator re-copies and re-pastes.

The plugin does not write to Beds24 admin programmatically. The paste
step is manual.

### What the iframe CSS targets

The CSS targets Beds24's rendered iframe DOM, not the plugin's own DOM.
Selector targets are based on the predecessor project's accumulated
DOM knowledge.

The CSS sets the same `--beds24-*` variables on a root element inside
the iframe, then applies them to the iframe's elements via selectors
targeting Beds24's classes. This produces visual continuity between
the on-site rendering (search, room cards, cart) and the iframe
rendering (guest details, payment, confirmation).

### Stability of the iframe CSS

Beds24's iframe DOM is owned by Beds24 and may change without notice.
The plugin's iframe CSS is more fragile than the on-site styling
because of this — it depends on classes the plugin does not control.

**Mitigation:** The predecessor project's retrospective (rules "Verify
saves before building on them," "Test CSS against real DOM before
deployment") informs the iframe CSS workflow. When Beds24 updates the
iframe DOM, the plugin's iframe CSS may need updates; this is a
maintenance task, not an unrecoverable failure.

---

## Stability and versioning

### What is stable

- The token roles documented in this document
- The CSS variable names (`--beds24-*`) corresponding to those roles
- The default values for each token (changes here are visible to
  every consumer)
- The class naming convention (standard BEM with `beds24-` namespace prefix)
- Documented public classes (when the catalog is drafted)
- The behavior of the theme.json reader (which slugs are looked for,
  the fallback order)

### What is internal and may change

- Internal CSS classes not documented in the class catalog
- The exact CSS rules the plugin uses to style its own DOM
- The internal structure of the plugin's CSS (file organization,
  custom properties beyond `--beds24-*`, internal mixins)
- The plugin's admin UI implementation details

### Versioning

The plugin follows semantic versioning. The styling contract is
versioned with the plugin.

- **Major version bump:** breaking changes to public classes, token
  roles, default values, or the variable namespace.
- **Minor version bump:** additions to public classes, new optional
  tokens, new default-value tokens. Existing consumers are not broken.
- **Patch version bump:** internal changes, default-value
  refinements that don't change semantic meaning.

**Deprecation policy:** Public classes or tokens marked for removal in
a future major version are documented as deprecated in the prior minor
version. Themes have at least one minor-version cycle to migrate.

---

## Known unknowns

These are open questions that need resolution at implementation. They
are listed here so future sessions encountering them can find the
context.

### 1. theme.json palette slug conventions

Whether to look only for the role-name slugs (`primary`, `accent`,
etc.) or also accept common alternative slugs (`brand-primary`,
`color-primary`, etc.) is not yet decided. The current plan is to look
for role-name slugs first and fall back to admin-configured mapping.
A list of common alternative slugs that are auto-recognized could
reduce per-property configuration but adds complexity to the reader.

**Verify at implementation:** when the plugin's theme.json reader is
built (Session 7+), survey the four properties' theme.json files and
record which slugs they actually use. If they consistently use
non-standard slugs, expand the auto-recognition list. If they're
inconsistent, leave the fallback as admin-configured.

### 2. Default-value override mechanism

When a theme defines theme.json tokens for some roles but not others,
the plugin uses defaults for the unset roles. Whether the admin
settings panel should also let operators override the defaults
explicitly (even when theme.json sets them) is not decided.

**Argument for:** operators may want to override a theme.json value
without editing the theme.

**Argument against:** introduces three layers of token sources
(theme.json, admin settings, defaults) which is harder to reason
about than two.

**Verify at implementation:** start with admin settings as fallback
only (no override of theme.json values). If operators report needing
to override, add an explicit "override theme.json" toggle then.

### 3. Iframe CSS stability under Beds24 updates

The plugin's iframe CSS depends on Beds24's iframe DOM, which Beds24
may change without notice. The plugin has no way to detect such
changes proactively.

**Verify at rollout:** the rollout plan for each property should
include an end-to-end test of the iframe rendering. If Beds24 updates
the iframe DOM between rollouts, the iframe CSS may need updates.

### 4. Display token usage in V1

The `font-family-display` token is documented but its actual use in
V1 is limited (currently planned only for "from" labels and prominent
prices). If V1 doesn't use a display font distinctly from the heading
font, the token may be dropped from V1 and reintroduced when needed.

**Verify at implementation:** Session 7+ decides whether to use the
display token. If not used, mark as "reserved for future use" rather
than removing — the doc references will accumulate slowly.

### 5. Mobile cart styling tokens

The mobile cart's bottom bar and slide-up drawer (per
`docs/architecture.md`) may need their own token roles (e.g.,
`mobile-bar-height`, `drawer-max-height`) that aren't currently
listed. These are layout values that may not have analogues in
theme.json and may need to be admin-settings-only.

**Verify at implementation:** when the mobile cart is built (Session
8+ likely), determine whether its layout tokens need to be exposed in
this contract or remain internal.

### 6. Iframe CSS staleness mitigation (V1.x scope)

Decision 5 above describes a manual copy-paste workflow for the
iframe CSS. This works for initial property setup but produces a
predictable friction point: when an operator changes design tokens
post-rollout (the most common kind of post-rollout customization),
the on-site rendering updates immediately while the iframe CSS goes
stale until the operator manually regenerates and re-pastes.

V1 ships with the manual workflow only. V1.x adds a mitigation
mechanism. The design conversation has thought through the UX shape
in advance so the plugin project doesn't need to design it from
scratch:

**Approach considered (recommended):** automatic detection with
prominent re-paste prompt. The plugin tracks two timestamps — when
tokens last changed, and when the operator last confirmed pasting
the iframe CSS. When the first is more recent than the second,
plugin admin displays a prominent banner on relevant pages with the
regenerated CSS and a copy button. The operator copies, pastes into
Beds24's "Insert in HTML \<HEAD\> bottom" field, and confirms the
paste in plugin admin to dismiss the banner.

**Approaches considered and rejected:**

- *Banner without copy affordance:* relies on operators noticing
  the banner and navigating to find the CSS. Adds friction without
  reducing it.
- *Email notification to operator:* introduces SMTP dependency on
  the WordPress install. WP email reliability is uneven; some sites
  silently drop transactional email. Not robust enough to trust as
  the primary signal.
- *Automatic API push to Beds24 admin:* would require Beds24 admin
  API write access the plugin doesn't have, and would conflict with
  the architecture's transaction-boundary principle (the plugin
  doesn't write to Beds24 admin).

**Verify at implementation:** when V1.x lands the mitigation
(Session 9+ likely), implement the automatic-detection-with-prompt
approach. The exact banner placement, dismiss/confirm UX, and
timestamp storage are plugin-project decisions; the design
conversation has only specified the shape.

**Property setup documentation in V1:** until the mitigation lands,
`skills/beds24-property-rollout/references/property-setup.md` must surface the dependency
explicitly. Specifically: a section noting that any change to design
tokens (theme.json updates, plugin admin settings changes) requires
regenerating and re-pasting the iframe CSS, and where the
regenerated CSS is found in plugin admin.

---

## Cross-references

This document interacts with:

- **`docs/architecture.md`** — the architectural reasoning behind the
  plugin's structure, including the discovery-transaction boundary
  that determines what the plugin renders vs. what Beds24 renders.
  The styling contract applies to the plugin-rendered half; the
  iframe CSS section bridges to the Beds24-rendered half.
- **`skills/beds24-property-rollout/references/property-setup.md`** — the operational procedure for
  configuring each Beds24 property. Includes the copy-paste step for
  the iframe CSS that this contract describes generating.
- **`docs/architecture-pivot-decision.md`** — historical context for
  why the plugin exists and why the discovery-transaction split was
  chosen. Useful for understanding the constraints this contract
  operates within.
- **The design conversation's per-property design system docs**
  (when established) — each property's theme.json values are the
  design conversation's deliverable; this contract specifies what
  those values feed into.

---

## Sections to be drafted by plugin project

These sections of this document require plugin-implementation context
that the design conversation does not have:

1. **CSS class catalog** (in "CSS class contract" above) — the actual
   list of public classes, what DOM they appear on, and what they
   represent. Drafted at Session 7+ when the plugin's frontend
   rendering is built.
2. **Default value refinements** — the default token values in this
   draft are reasonable starting points but may want adjustment based
   on plugin team aesthetic preferences and WP.org distribution
   considerations.
3. **Specific theme.json slug list** for auto-recognition (per Known
   unknown 1).
4. **Versioning specifics** — exact policy on minor vs. patch bumps,
   deprecation notice format, changelog conventions for contract
   changes.

The design conversation has fully drafted the sections it has standing
to define: token roles, the contract framework, the cross-cutting
principles, the iframe CSS workflow.

---

## Document history

- **2026-05-08:** Initial draft for ratification. Token roles,
  contract framework, architectural decisions, and known unknowns
  drafted by design conversation. CSS class catalog and some defaults
  marked as to-be-drafted by plugin project.
- **2026-05-08 (rev 1):** Three corrections following plugin-project
  review. Body text in "Design tokens consumed" intro corrected to
  match Known Unknown 2 (theme.json values not overridable through
  admin settings in V1). Class naming convention corrected to
  standard BEM with `beds24-` namespace prefix (was incorrectly
  using `.beds24` as a single root class). Iframe CSS staleness
  mitigation lifted from "future tooling" to V1.x scope as Known
  Unknown 6, with worked-through UX shape.
- **2026-05-12 (Session 9):** Search form class catalog drafted.
  Three error-state color tokens (`error`, `error-bg`, `error-border`)
  promoted from a temporary catalog note to the canonical Color tokens
  table.
- **2026-05-15 (Session 11):** Room results container (`beds24-room-results`)
  and room card block (`beds24-room-card`) class catalog drafted. Tag chips
  and cart controls reserved in catalog notes, not yet implemented.
- **2026-05-15 (Session 12):** Cart accumulator added. Tag chip classes
  (`beds24-room-card__tags`, `beds24-room-card__tag`) drafted — taxonomy
  terms only, featureCodes deferred. Cart control classes
  (`beds24-room-card__qty-control`, `beds24-room-card__qty-btn`,
  `beds24-room-card__cart-btn`, `beds24-room-card--selected`) drafted.
  Cart region block (`beds24-cart`) drafted. Chip note corrected to remove
  featureCodes reference (taxonomy-only in V1).
- **2026-05-15 (Session 13):** Confirm Booking button and iframe classes
  added (`beds24-cart__actions`, `beds24-cart__confirm-button`,
  `beds24-booking-iframe-wrapper`, `beds24-booking-iframe`). Cart confirm
  button marked as implemented (was pending). Iframe height fixed at 900px
  in V1.
- **2026-05-15 (Session 14):** Cart item remove control (`beds24-cart__item-remove`)
  added. `data-room-id` attribute added to `.beds24-cart__item`. Sticky
  footer bar behavior documented (media-query approach, no BEM modifier).
  `--beds24-shadow-floating` token promoted from contract-only to implemented
  in CSS defaults. Checkout date min-tracking wired in JS.
- **2026-05-15 (Session 17):** Mobile cart bottom bar and slide-up drawer implemented.
  New classes: `beds24-cart__drawer`, `beds24-cart__mobile-bar`, `beds24-cart__mobile-toggle`,
  `beds24-cart__mobile-summary`, `beds24-cart__mobile-chevron`, `beds24-cart--drawer-open`,
  `beds24-cart-backdrop`. DOM restructure: `.beds24-cart__actions` moved inside
  `.beds24-cart__mobile-bar`; `.beds24-cart__heading`, `__list`, `__footer` wrapped in
  `.beds24-cart__drawer`. Both wrappers use `display: contents` at ≥768px to preserve
  the existing desktop flex layout without structural changes. "Pending catalog sections"
  note for mobile cart classes removed (now documented above).
- **2026-05-15 (Session 18):** Room card styling applied against the predecessor mockup's
  visual language. Changes: card hover shadow lift (`transition: box-shadow 0.2s ease`,
  `0 4px 12px` on hover); name padding tightened (0.75rem top, xs bottom); body padding
  reduced to `sm md` (8×16px) with gap reduced to `sm` (8px); photo resized to 120×90px
  desktop / 90×68px mobile; description gets `-webkit-line-clamp: 2` on desktop,
  unclamped on mobile; tag chips restyled (inline-flex, #f3f4f6 background, border,
  0.75rem / 500-weight text); tags container moved from inside `__content` to be a sibling
  of `__body` (structural DOM change in `view.js`) with `padding-left` indent at desktop
  to align under the description column; mobile layout changed from stacked (column)
  to compact side-by-side (row) at 90×68px thumbnail; price label changed to muted/
  small/normal-weight to match "from €X / night" label treatment in mockup.
- **2026-05-15 (Session 16):** Iframe CSS generator implemented per Decision 5.
  `plugin/includes/iframe-css-generator.php` — `beds24_iframe_css_defaults()`
  returns default token values; `beds24_generate_iframe_css( $tokens )` returns
  the complete CSS string. Default values use px units for iframe predictability.
  Five internal defaults (prefixed `_`) added for values with no public contract
  token: `_page-bg`, `_shadow-hover`, `_transition`, `_tag-bg`, `_tag-border`.
  Plugin admin menu registered (`plugin/includes/beds24-admin-page.php`) — top-level
  "Beds24 Booking" menu with "Property Setup" submenu page displaying the generated
  CSS in a copyable textarea. CSS-base.css source located at `docs/reference/CSS-base.css`
  (not `docs/CSS-base.css` as referenced in v1-plan — path difference only, content
  unchanged).
