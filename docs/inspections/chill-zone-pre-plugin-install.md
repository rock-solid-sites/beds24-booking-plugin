# Chill Zone — Pre-Plugin-Install Inspection

Captured during Session 8 (2026-05-11), before installing
beds24-booking-plugin v0.1.0. Reference point for Session 9 and
future property rollouts.

## Site

- Staging URL: `https://chillzone.astrongpresence.com`
- Site directory on VPS: `/www/wwwroot/chillzone.astrongpresence.com`
- Beds24 property ID: `271142`
- Beds24 owner ID: `141266`

## Booking page

- Post title: Book A Room
- Post ID: 109
- Slug: `book-a-room`
- URL: `https://chillzone.astrongpresence.com/book-a-room/`
- Post status: publish

## Active theme

- Name: Kadence
- Version: 1.4.5
- No child theme active.

## Booking page post_content (pre-transition)

```html
<!-- wp:kadence/header {"uniqueID":"109_e83a70-74","id":57} /-->

<!-- wp:kadence/advancedheading {"uniqueID":"109_0be232-0a","align":"center","padding":["sm","","",""],...,"htmlTag":"p"} -->
<p class="kt-adv-heading109_0be232-0a wp-block-kadence-advancedheading" ...>
  We offer our lowest nightly prices for bookings made directly on our website.
  Contact us for bigger discounts on weekly or monthly stays!
</p>
<!-- /wp:kadence/advancedheading -->

<!-- wp:kadence/advancedbtn {"uniqueID":"109_8c7a75-a6"} -->
<div class="wp-block-kadence-advancedbtn kb-buttons-wrap kb-btns109_8c7a75-a6">
  <!-- wp:kadence/singlebtn {"uniqueID":"109_f31792-df","text":"Email Us","link":"mailto:chillzone@tripnhostel.com",...} /-->
</div>
<!-- /wp:kadence/advancedbtn -->

<!-- wp:html -->
<div id="tnh-booking-root"></div>
<script>
window.TNH_WIDGET_CONFIG = {
  schemaVersion: 1,
  propertyId: "chillzone",
  ownerId: "141266",
  beds24PropId: "271142",
  colors: {
    primary: "#E7A35C",
    secondary: "#6DA17D",
    text: "#2D482D",
    border: "#EDF2F7"
  },
  fonts: { body: "Lexend" }
};
</script>
<script>
var s = document.createElement('script');
s.src = 'https://astrongpresence.com/booking-widget.js?v=' + Date.now();
document.head.appendChild(s);
</script>
<!-- /wp:html -->
```

## Active plugins (pre-install)

| Plugin | Status | Version |
|--------|--------|---------|
| admin-site-enhancements | active | 8.7.0 |
| beds24-online-booking | active | 2.0.30 |
| kadence-blocks | active | 3.6.7 |
| mcp-adapter | active | 0.5.0 |
| mcp-expose-abilities | active | 3.0.38 |
| spotlight-social-photo-feeds | active | 1.7.5 |
| kadence-starter-templates | active | 2.2.14 |

## External assets loaded on booking page

Confirmed from live page source (`https://chillzone.astrongpresence.com/book-a-room/`):

- **CSS:** `https://chillzone.astrongpresence.com/wp-content/plugins/beds24-online-booking/theme-files/beds24.css?ver=6.9.4`
  — enqueued globally by the `beds24-online-booking` plugin (handle: `beds24-css`)
- **JS:** `https://chillzone.astrongpresence.com/wp-content/plugins/beds24-online-booking/js/beds24-datepicker.js?ver=6.9.4`
  — enqueued globally by the `beds24-online-booking` plugin (handle: `beds24-datepicker-js`)
- **Dynamic JS:** `https://astrongpresence.com/booking-widget.js?v=<timestamp>`
  — loaded via inline script in the booking page's custom HTML block

## Theme-side hooks/enqueues touching the booking page

None. The theme's `functions.php` contains no booking-, beds24-, or tnh-related
enqueues, hooks, or filters. The booking UI is entirely self-contained in the
page's `<!-- wp:html -->` block and the `beds24-online-booking` plugin.

## Predecessor artifacts

### beds24-online-booking plugin (v2.0.30)

The official Beds24 WordPress plugin is active but is NOT the mechanism rendering
the booking UI on this page. Its role here appears to be:

1. Providing `beds24.css` (datepicker/form styling) enqueued globally.
2. Providing `beds24-datepicker.js` enqueued globally.
3. Holding legacy Beds24 configuration options (see below).

The booking page does NOT use any shortcode from this plugin.

### Beds24 WP options (set by beds24-online-booking plugin)

```
beds24_ownerid       141266
beds24_propid        271142
beds24_height        1600
beds24_width         800
beds24_numdisplayed  -1
beds24_hidecalendar  -1
beds24_hideheader    -1
beds24_hidefooter    -1
beds24_advancedays   -1
beds24_numnight      2
beds24_numadult      1
beds24_layout        0
beds24_target        window
beds24_color         #dddddd
beds24_bgcolor       #444444
beds24_padding       10
beds24_referer       wordpress
beds24_domain        https://www.beds24.com
```

### Custom HTML block (predecessor widget approach)

The actual booking UI is loaded via a custom HTML block that:
1. Creates a mount point: `<div id="tnh-booking-root"></div>`
2. Injects a config object `window.TNH_WIDGET_CONFIG` with property/owner IDs and
   theming values.
3. Dynamically loads `booking-widget.js` from `astrongpresence.com`.

This is the predecessor project's JavaScript widget approach — not a Beds24 iframe
directly. The widget presumably communicates with Beds24's API internally.

### Beds24 plugin refresh token (new plugin — seeded in Session 6/7)

```
Option: beds24_booking_plugin_refresh_token_271142
Status: present (verified via WP-CLI eval)
```

## VPS environment — open_basedir constraint

**Important for future plugin installs on this VPS.**

PHP-FPM serves chillzone under a per-site `open_basedir` restriction:
```
/tmp/:/www/wwwroot/chillzone.astrongpresence.com/
```

PHP resolves symlinks before checking `open_basedir`. A symlink from
`wp-content/plugins/beds24-booking-plugin` → `/home/claude-code/...` was blocked
because the resolved real path is outside the allowed paths. The plugin silently
failed to load on front-end requests while appearing active in WP-CLI (which runs
as `claude-code` user, not subject to the same restriction).

**Consequence:** Plugin must be copied (not symlinked) into the site's plugin
directory. For active development, the workflow is: edit files in the repo at
`/home/claude-code/beds24-booking-plugin/plugin/`, then copy changed files to
`/www/wwwroot/chillzone.astrongpresence.com/wp-content/plugins/beds24-booking-plugin/`.

The open_basedir restriction is configured in the Nginx per-site vhost config
(managed by aaPanel), not in php.ini or php-fpm.conf.

## Deviation from session plan expectations

### 1. Predecessor implementation

The session plan described the predecessor as "Beds24 iframe styled by predecessor
CSS/JS." The actual setup is different: the predecessor uses a custom JavaScript
widget loaded from `astrongpresence.com`, not a Beds24 iframe. Key differences:

- No Beds24 iframe `src` attribute on this page.
- The `tnh-booking-root` mount point is a custom widget host.
- The `beds24-online-booking` plugin assets load globally (not per-page), but the
  plugin's shortcode is not used on the booking page.

This is flagged per stop-condition rules but is not a hard stop. Beds24 integration
is intact (property ID, refresh token, API options all present). The swap path is
unaffected: replace the `<!-- wp:html -->` block with the new plugin's block.

### 2. Plugin install method

The plan recommended symlinking the cloned repo's plugin directory into
`wp-content/plugins/`. The symlink was created but the block did not render on
the front end. Root cause: per-site `open_basedir` restriction (see above). The
symlink was replaced with a copy. Plugin files are now at:
`/www/wwwroot/chillzone.astrongpresence.com/wp-content/plugins/beds24-booking-plugin/`
owned by `claude-code` with `o+r` permissions (readable by `www` user).

## Transition plan (for reference)

- Remove: the `<!-- wp:html -->` block containing `tnh-booking-root` and the two
  inline scripts.
- Add: `<!-- wp:beds24/booking-flow /-->` block.
- Retain: Kadence header, advanced heading, and button blocks (above the booking
  widget area).
- The `beds24-online-booking` plugin can be deactivated/removed separately once
  the new plugin's CSS/JS replaces its assets (out of scope for Session 8).

## Screenshot reference

No screenshot captured in this session (no browser tool available from VPS context).
The live rendered page is at `https://chillzone.astrongpresence.com/book-a-room/`.
