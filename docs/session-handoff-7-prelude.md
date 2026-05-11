# Session 7 Prelude — MCP Setup for Chill Zone Staging

This handoff captures the state at the close of Session 6 follow-up
work and frames Session 7's MCP setup on the VPS.

Per OPERATING.md's session numbering convention, the recent work
(VPS SSH/user/ACL setup, tripn-sites repo creation, doc updates,
cross-repo reference cleanup, consolidation tail) belongs to Session
6 as follow-on commits, not a separate numbered session. Session 7
is the next full work session.

## State at session end

**Plugin repo (`beds24-booking-plugin`):** clean working tree at HEAD
`f7a2af4db4a476350133c40cbeb9033ba9479c39`. All Session 6 follow-up commits pushed
to `origin/main`.

**Site-design repo (`tripn-sites`):** clean working tree at HEAD
`bcc7662bcdf16512c683f51cc6eeb7cbb9811d4c`. Repo seeded with structure, Seaside
worked example, stub property folders for chill-zone / pink-bubble /
tripn-hostel, and project presets.

**VPS:** Ubuntu 22.04, aapanel-managed, hostname IT3812, IP
194.180.206.138, SSH port 5771.
- `claude-code` Linux user exists (uid 1003, primary group
  `claude-code` gid 1005, supplementary group `www` gid 1000)
- Password locked (`usermod -L`), SSH-key-only authentication
- SSH key reuses the operator's root authentication key
- ACLs applied: `setfacl -R -m u:claude-code:rwX` on each of the 14
  site directories under `/www/wwwroot/`, default ACL on the parent
  for new-site inheritance
- Code installed at `~/.local/bin/claude` (v2.1.138), PATH in
  `~/.bashrc`, authenticated on first run via SSH local port
  forwarding for OAuth callback
- `claude-mode` v0.2.13 installed

**Chat-side skills:** `beds24-booking-plugin-context`,
`code-session-prompts`, and new `skill-design-evidence` installed
and current.

## Session 7 scope

MCP setup on the VPS for Chill Zone staging. This is the prerequisite
to plugin activation + search form frontend work (that's Session 8 in
the revised numbering — what Session 6b originally called Session 7).

Specific items in scope:

1. **Generate a WordPress Application Password for Chill Zone.**
   Done in WP admin (Users → Edit your user → Application Passwords).
   Store the generated value in the plugin repo's `.env` as
   `WORDPRESS_APP_PASSWORD_CHILL_ZONE`.

2. **Seed the Beds24 refresh token via WP-CLI.** The refresh token
   is already in plugin repo `.env` as
   `BEDS24_REFRESH_TOKEN_CHILL_ZONE`. Use WP-CLI to set it as a
   WordPress option:

       wp option update beds24_booking_plugin_refresh_token_271142 "<token>"

   The property ID 271142 is Chill Zone's Beds24 propid.

3. **Install the MCP plugin stack per `wordpress-setup.md`.**
   Three pieces:
   - **Abilities API** — bundled in WordPress 6.9 core. Verify
     6.9 is installed on the Chill Zone staging site.
   - **MCP Adapter plugin** — installed from WP.org. Version-pinned.
     Auto-updates disabled.
   - **`mcp-expose-abilities` plugin** — installed from WP.org.
     Version-pinned. Auto-updates disabled.

   Both plugins activated for Chill Zone only at this stage, not the
   other properties.

4. **Verify MCP discovery.** The MCP endpoint should return
   approximately 69 abilities (3 core + 66 from
   `mcp-expose-abilities`). A diverging count means stop and
   investigate before proceeding — the count is a signal that the
   stack is correctly composed.

## Open questions

These don't block Session 7 but may surface during the work:

- **Cloning the plugin repo to the VPS:** the plugin repo is not
  currently on the VPS, only the site-design repo can be cloned
  there. MCP setup doesn't strictly require the plugin repo on the
  VPS, but if a Beds24 plugin install is needed (the plugin's own
  PHP files), the .zip or git clone needs to land somewhere.
  Decision deferred until the MCP plugin stack work surfaces it.

- **Install user for MCP plugins:** the MCP Adapter and
  `mcp-expose-abilities` install via WordPress admin or WP-CLI.
  Whether this runs as `claude-code` (with file ACLs) or as root
  depends on WP-CLI's behavior with aapanel's site ownership. Worth
  testing the `claude-code`-user path first, falling back to root if
  permission issues surface.

- **Exact ability count:** the "~69 abilities (3 core + 66 from
  mcp-expose-abilities)" comes from Session 6b's framing.
  `wordpress-setup.md` should be the authoritative source — verify
  the count there before treating divergence as a problem.

## Out of scope for Session 7

- Plugin activation, search form frontend, or any V1 build work
  (that's Session 8)
- The other three properties (only Chill Zone is the staging
  target; the other three remain "design pending" in tripn-sites)
- Site-design work (separate stream, separate repo)
- Any aapanel-side changes beyond what the MCP install requires
- Touching the rollout runbook for other properties

## Reference pointers

- **MCP setup runbook:**
  `skills/beds24-property-rollout/references/wordpress-setup.md`
- **Credentials location:** plugin repo `.env` (gitignored)
- **Refresh token source:** Chill Zone's Beds24 admin → API tokens
  (already extracted and stored in `.env`)
- **Chill Zone WordPress staging URL:**
  `https://chillzone.astrongpresence.com`
- **Chill Zone propid:** `271142`

## Session start checks

Before Session 7 begins, verify:

- Plugin repo at expected HEAD, clean working tree
- VPS SSH access works (`ssh tripn-vps` succeeds)
- Chill Zone WordPress staging site is reachable
- `.env` contains both `BEDS24_REFRESH_TOKEN_CHILL_ZONE` and
  `WORDPRESS_APP_PASSWORD_CHILL_ZONE` (the second will be generated
  during the session if not already present)
- WP-CLI works on the VPS for the Chill Zone site

## Conventions

Per the project skill: independent assessment first, named
assumptions, explicit confidence, flag plan deviations. The MCP
install touches a live staging site — `--permission-mode default`
applies (per-action approval) rather than `auto`.
