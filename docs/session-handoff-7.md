# Session 7 Handoff — 2026-05-11

## What this session did

Brought up the MCP plugin stack on Chill Zone staging. Installed WP-CLI,
upgraded MCP Adapter from v0.4.1 to v0.5.0, installed mcp-expose-abilities
3.0.38, generated an Application Password, seeded the Beds24 refresh token,
and verified 74 MCP abilities exposed end-to-end.

---

## VPS state at session end

**Chill Zone staging** (`https://chillzone.astrongpresence.com`,
propid 271142):

- **WordPress:** 6.9.4
- **WP-CLI:** 2.12.0, installed at `~/.local/bin/wp`; `WP_CLI_PHP`
  set to `/www/server/php/83/bin/php` in `~/.bashrc`
- **MCP Adapter:** v0.5.0 active, auto-updates off
- **mcp-expose-abilities:** 3.0.38 active, auto-updates off
- **MCP abilities verified:** 74 total — 3 `mcp-adapter/*` built-ins,
  68 from mcp-expose-abilities (across `content/*`, `plugins/*`,
  `menus/*`, `comments/*`, `users/*`, `media/*`, `system/*`,
  `options/*`, `widgets/*`, `meta/*`, `taxonomy/*`), and 3 `core/*`
- **Application Password:** issued for admin user
  `astrongpresencebiz_kixfumj4`; value held in operator's local `.env`
  as `WORDPRESS_APP_PASSWORD_CHILL_ZONE`
- **Beds24 refresh token:** seeded as WordPress option
  `beds24_booking_plugin_refresh_token_271142`

---

## Session 7 deviations from the prelude plan

All five deviations were flagged at the time they occurred.

1. **WP-CLI not present at session start.** The prelude said
   "verify WP-CLI works"; in practice it was absent. Installed
   to `~/.local/bin/wp` with `WP_CLI_PHP=/www/server/php/83/bin/php`
   in `~/.bashrc`.

2. **MCP Adapter pre-installed at v0.4.1.** Chill Zone had MCP Adapter
   from predecessor booking-page work. Upgraded to v0.5.0 via
   `wp plugin install --force` rather than a fresh install.

3. **MCP discovery is JSON-RPC POST, not GET.** The prelude described a
   simpler verification path; the actual flow requires `initialize` →
   capture `Mcp-Session-Id` → `tools/call` with session ID header.
   GET returns HTTP 405 (SSE not implemented in v0.5.0). Plan adjusted.

4. **`wp rest list` unavailable in WP-CLI 2.12.0.** Route enumeration
   done via `wp eval 'print_r(array_keys(rest_get_server()->get_routes()));'`
   instead.

5. **Auto-updates already disabled on both plugins.** The disable
   auto-updates step in the runbook was a no-op for this property.

**General principle for property rollouts:** a property's WordPress
install may have partial MCP state from prior work. Install commands
should be prepared to upgrade-in-place (`--force`) rather than assuming
a clean slate.

---

## Repo state at session end

- Branch: `main`
- HEAD: closeout session commits on top of `aace52b`
- Remote: pushed and in sync with `origin/main`
- Working tree: clean (`.claude/` untracked is expected and gitignored)

---

## Session 8 scope

**Plugin repo clone to VPS + Beds24 booking plugin install on Chill Zone.**

Before any frontend work:
1. Clone the `beds24-booking-plugin` repo to the VPS (or rsync the
   `plugin/` directory to `wp-content/plugins/beds24-booking-plugin/`
   on Chill Zone).
2. Activate the plugin via WP-CLI or WordPress MCP.
3. Verify the `beds24/booking-flow` Gutenberg block appears in the
   block inserter and the stub div renders on a test page.
4. Confirm a `Beds24_API_Client(271142)->get_offers(...)` call works
   inside WordPress (proves the class integrates with WP HTTP API and
   transients).

Then search form frontend work:
- Search form: two date pickers + Search button
- Wire Search button to `get_offers()` via AJAX handler
- Render room result cards from the offers response
- Room card layout per `docs/architecture.md` and `docs/mockup.html`

**Do not start URL construction or cart accumulation in Session 8.**
Those are blocked on the multi-room URL parameter empirical test (see
architecture Known Unknowns #1 and #2), which is a separate browser
session against the live Beds24 booking page.

---

## Conventions reaffirmed for Session 8

- **Permission mode `default`** for live-staging work (Chill Zone is
  a finished production site used as the booking-plugin test target).
  Per-action approval applies.
- **One identity end-to-end:** `claude-code` Linux user for SSH,
  existing WordPress admin `astrongpresencebiz_kixfumj4` for MCP,
  single Application Password. No additional service users.
