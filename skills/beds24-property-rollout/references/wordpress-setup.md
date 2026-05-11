# WordPress Setup — Plugin Stack and Build Environment

**Status:** Verified for Chill Zone staging
**Verified against:** SSH user and ACL setup (step 7) verified on
staging VPS 2026-05-11. MCP stack (steps 4–5) verified on Chill Zone
staging 2026-05-11 (Session 7).
**Companion document:** `references/property-setup.md` (Beds24-side
configuration for the same property)

---

## Purpose

This document records the WordPress-side setup needed for each
property site to be ready for AI-assisted build with Claude Code.
It is the WordPress parallel to `property-setup.md`, which covers
Beds24-side configuration. Together they form the complete
pre-build runbook for a property.

Setup happens once per property. Repeat for each new property at
rollout time.

---

## Stack overview

This is the verified stack for property sites in this project,
ratified during design conversation 2026-05-08:

- **Hosting:** VPS managed via aapanel
- **Admin / WordPress management:** aapanel WP Toolkit
- **WordPress version:** 6.9 or newer (Abilities API in core
  starting 6.9; required for the MCP integration described below)
- **PHP version:** 8.0 or newer (required by mcp-expose-abilities)
- **Parent theme:** Twenty Twenty-Five (`twentytwentyfive`)
- **Child theme:** custom per property, named `<property-slug>-child`
- **Block library:** core Gutenberg blocks only — no Kadence Blocks,
  Spectra/Ultimate Addons, GenerateBlocks, or other third-party
  block libraries
- **Forms:** Fluent Forms (free version)
- **Booking:** the project's custom Beds24 plugin (separate repo)
- **Caching:** LiteSpeed Cache plugin (default with OpenLiteSpeed
  via aapanel); handles page cache and Cloudflare integration
- **MCP layer:** Abilities API (core 6.9), MCP Adapter (plugin),
  mcp-expose-abilities (plugin)
- **Build agent access:** SSH from Claude Code, plus MCP via
  Application Password authentication

This stack was selected after surveying alternatives. The architectural
reasoning is in `docs/architecture.md` and the design conversation's
record. This document captures the operational steps to install it.

---

## Required setup, per property

### 1. WordPress install

Use aapanel's WP Toolkit to provision a fresh WordPress install for
the property. Standard procedure, no project-specific steps.

- WordPress version: confirm 6.9+ at installation. Fresh installs
  via aapanel will be on the current release; if the toolkit offers
  an older version, pick the newest available.
- PHP version: confirm 8.0+ in aapanel's PHP version selector. PHP
  7.4 is end-of-life and incompatible with the plugin stack below.
- Subdirectory installs are fine. The property sites are expected to
  live at subdirectories of the main domain (e.g.,
  `tripnhostel.com/property-name/`) — see the multi-property
  rollout docs for the directory structure.
- Each property gets its own WordPress install with its own database.
  Not WordPress Multisite. Five separate installs is simpler to
  maintain than one network with five sites.

### 2. Parent theme

Install and activate Twenty Twenty-Five.

WordPress 6.9 ships with Twenty Twenty-Five as the default theme; on
a fresh install it should already be active. If a different theme
is active, switch to Twenty Twenty-Five.

Do not install Twenty Twenty-Six, Twenty Twenty-Four, or other
parent theme variants for this project. Twenty Twenty-Five is the
ratified parent across all five property sites for consistency.

### 3. Child theme

The child theme is created by Claude Code during the build phase,
not during this setup phase. This document covers prerequisites; the
child theme itself is build output.

What this setup phase does: ensure the build agent has SSH access to
write to `wp-content/themes/` (covered in step 7 below).

### 4. MCP plugin stack

The MCP plugin stack lets Claude Code perform structured operations
on the WordPress site (creating pages, uploading media, configuring
menus, etc.) via Model Context Protocol rather than only via raw SSH.

The stack has three layers:

**Layer 1 — Abilities API.** Built into WordPress core starting in
6.9. No installation needed. The Abilities API is the framework that
defines what an "ability" is and how MCP-aware agents discover
abilities. Core 6.9 ships three read-only abilities
(`core/get-site-info`, `core/get-user-info`,
`core/get-environment-info`) — useful for site introspection but
insufficient for build operations.

The `WordPress/abilities-api` GitHub repository was archived in
February 2026 once the API merged into 6.9 core. The standalone
plugin remains available as a back-compat path for sites running
WordPress older than 6.9; this project standardizes on 6.9+ and
treats the API as a core feature, not a separate install.

**Layer 2 — MCP Adapter.** The official WordPress core team plugin
that exposes the Abilities API to MCP-aware agents over HTTP.
Repository: `github.com/WordPress/mcp-adapter`. **Not available on
the WordPress.org plugin directory** — install from the GitHub
releases page only. Activate after install.

**Layer 3 — mcp-expose-abilities.** Community plugin that registers
66 core abilities (additional add-ons available, none of which are
needed for this project). Provides the content-management
abilities (`content/create-page`, `content/patch-post`,
`media/upload`, `menus/add-item`) that the build phase needs.
Repository: `github.com/bjornfix/mcp-expose-abilities`. **Not
available on the WordPress.org plugin directory** — install from
the GitHub releases page only. Activate after install.

Installation order matters: Abilities API must be available (it
will be, in 6.9 core), then MCP Adapter must be active before
mcp-expose-abilities can register against it.

#### Install method

Both MCP Adapter and mcp-expose-abilities are GitHub-only. The
install paths in order of preference:

1. **WP-CLI from a GitHub release URL:**

       wp plugin install https://github.com/WordPress/mcp-adapter/releases/latest/download/mcp-adapter.zip --activate
       wp plugin install https://github.com/bjornfix/mcp-expose-abilities/releases/latest/download/mcp-expose-abilities.zip --activate

   (Verify the exact asset filename on each release page before
   running; release ZIP naming can vary by upstream conventions.)

2. **WordPress admin upload:** download the release ZIP locally,
   then Plugins → Add New → Upload Plugin.

3. **Git clone into `wp-content/plugins/`:** clone the repo
   directly. Useful when running `composer install` is needed
   (MCP Adapter ships Composer dependencies and may require this
   step depending on the release).

For this project, WP-CLI install from release URL is the default.
Fall back to git clone + composer install if the release ZIP omits
vendored dependencies.

**Symlink caveat.** When installing a plugin from a separate
location on disk (e.g., a developer's clone outside the WordPress
site directory), symlinks can fail on VPSes with restrictive PHP
`open_basedir` settings. PHP resolves the symlink to its target
path before checking `open_basedir`; if the target is outside the
allowed paths, PHP refuses to load the file even though the symlink
itself is valid. The plugin may appear correctly registered (WP-CLI
sees it, activation succeeds) while front-end requests silently
fail. Directory copy is the safe default. See "VPS environment
notes" below for the constraint on the project's current VPS.

#### Version pinning

Disable auto-updates for both MCP Adapter and mcp-expose-abilities.
The build phase depends on stable plugin behavior; an auto-update
mid-project that introduces breaking changes would interrupt the
build.

To disable auto-updates per plugin: WordPress admin → Plugins →
locate the plugin → click "Disable auto-updates" in the plugin row
(toggle is per-plugin in modern WP admin).

After the project is complete, auto-updates can be re-enabled. During
build, manual updates only, after testing.

#### Add-ons not used

mcp-expose-abilities ships several optional add-ons (Filesystem,
Elementor, GeneratePress, Cloudflare, Rank Math, Wordfence, etc.).
None are needed for this project. The core 66 abilities cover all
required operations. Install only the core plugin.

Note on the Cloudflare add-on specifically: cache management for
this project is handled by LiteSpeed Cache plugin (default with
OpenLiteSpeed), which integrates with Cloudflare natively. The MCP
Cloudflare add-on would duplicate that responsibility and requires
storing Cloudflare API credentials in WordPress options. Skip it.

### 4a. MCP-public meta flag

A WordPress 6.9 default that's easy to miss: **abilities are not
exposed via MCP by default.** Every ability registered with the
Abilities API must carry a `meta.mcp.public = true` flag for the
MCP Adapter's default server to include it in discovery. This is
a deliberate safety control — abilities can be registered for
internal PHP/REST use without becoming AI-callable.

**What mcp-expose-abilities handles automatically:** the plugin
sets `meta.mcp.public = true` on its own 66 abilities at
registration time via the `mcp_expose_all_abilities()` filter.
After installing both stack plugins, the 66 mcp-expose-abilities
abilities should appear in MCP discovery.

**What requires explicit exposure:** the 3 abilities in WordPress
core (`core/get-site-info`, `core/get-user-info`,
`core/get-environment-info`) are registered without the meta flag
and will not appear in MCP discovery unless a bridge mu-plugin
adds it via the `wp_register_ability_args` filter.

**Bridge mu-plugin pattern** (drop into
`wp-content/mu-plugins/mcp-ability-bridge.php` if site/user/
environment introspection abilities are wanted, or as a fallback
if mcp-expose-abilities doesn't expose its own abilities):

    <?php
    /**
     * Plugin Name: MCP Ability Bridge
     * Description: Exposes core WordPress abilities via MCP.
     */
    add_filter(
        'wp_register_ability_args',
        static function ( array $args, string $ability_id ): array {
            // Skip MCP Adapter's own abilities (already exposed).
            if ( str_starts_with( $ability_id, 'mcp-adapter/' ) ) {
                return $args;
            }
            $args['meta']['mcp']['public'] = true;
            return $args;
        },
        10,
        2
    );

The mu-plugin path is recommended over a regular plugin so the
bridge can't be deactivated accidentally from the admin Plugins
screen. mu-plugins under `wp-content/mu-plugins/` load
unconditionally.

### 5. WP-CLI verification

WP-CLI is the WordPress command-line tool. aapanel installs include
it by default, but verify before relying on it.

Over SSH:

    wp --version

Should return `WP-CLI 2.x.x` or higher. If the command isn't found,
install WP-CLI per the official instructions
(https://wp-cli.org/#installing) before proceeding.

The build phase uses WP-CLI for operations not well-suited to MCP:
file/theme operations, plugin activation, options updates, bulk
queries. WP-CLI complements the MCP stack rather than replacing it.

**Note on `wp rest list`:** the command to enumerate REST routes
(`wp rest list`) is available in WP-CLI 2.13 and later. For WP-CLI
2.12 and earlier, enumerate routes via:

    wp eval 'print_r(array_keys(rest_get_server()->get_routes()));'

Chill Zone's VPS runs WP-CLI 2.12.0, installed at `~/.local/bin/wp`
with `WP_CLI_PHP=/www/server/php/83/bin/php` configured in `~/.bashrc`.

### 6. Forms plugin

Install Fluent Forms (free version, from the WordPress plugin
directory). Activate.

Configuration of specific forms happens during the build phase, not
this setup. This document only ensures the plugin is available.

### 7. SSH access for Claude Code

Claude Code needs SSH access to the VPS to write theme files,
create patterns, and execute WP-CLI commands. This is the gating
dependency for the build phase.

Setup is one-time and project-level (SSH credentials work across all
properties on the same VPS). **aapanel does not manage SSH users** —
SSH user creation is standard Linux work, not an aapanel operation.
Do not look for an aapanel UI for this; use the commands below.

#### Creating the SSH user

    # Create user (adjust uid/gid to first available on your VPS)
    useradd -m -u 1003 -g 1005 -G www claude-code
    # Lock password — SSH key auth only
    usermod -L claude-code
    # Create .ssh directory
    mkdir -p /home/claude-code/.ssh
    chmod 700 /home/claude-code/.ssh
    # Add authorized key (reuse existing key or generate a new one)
    cat /root/.ssh/authorized_keys > /home/claude-code/.ssh/authorized_keys
    chmod 600 /home/claude-code/.ssh/authorized_keys
    chown -R claude-code:claude-code /home/claude-code/.ssh

Verified configuration on staging VPS: user `claude-code`, uid 1003,
primary group `claude-code` (gid 1005), supplementary group `www`.
Password locked. SSH key reuses the root authentication key (same
human, two paths into the box — intentional).

Optional sshd hardening (in `/etc/ssh/sshd_config`):

    AllowUsers root claude-code
    PermitRootLogin prohibit-password

Reload sshd after editing: `systemctl reload sshd`.

#### Granting access to WordPress site files

aapanel uses group `www` (gid 1000) — not `www-data` (gid 33).
WordPress files under `/www/wwwroot/` are owned `www:www`. The
`claude-code` user is a member of `www`, but group write permissions
alone were not sufficient for all operations; POSIX ACLs are used
instead.

Apply per-site access ACL:

    setfacl -R -m u:claude-code:rwX /www/wwwroot/<site-dir>/

Apply default ACL on the parent so new sites inherit access
automatically:

    setfacl -d -m u:claude-code:rwX /www/wwwroot/

Capital `X` (not lowercase `x`) avoids setting the execute bit on
text and PHP files — execute is granted only to directories and
files already marked executable.

Note: `.user.ini` files inside each site will refuse ACL changes
with EPERM — this is expected. aapanel applies `chattr +i` to these
files. Skip them; no ACL change is needed on `.user.ini`.

#### Providing credentials to Claude Code

Provide the SSH host, username (`claude-code`), and private key path
via Claude Code's environment — SSH config file (`~/.ssh/config`) is
the recommended approach, or environment variables if the operator
prefers.

### 8. Application Password for MCP

The MCP Adapter uses WordPress Application Passwords for
authentication. Claude Code authenticates as a WordPress user via
this password.

1. WordPress admin → Users → select the user Claude Code will
   authenticate as. Create a dedicated user (e.g., username
   `claude-code-build`) with administrator role, rather than reusing
   a human user account.
2. On the user's profile page, scroll to "Application Passwords."
3. Generate a new application password with a clear name (e.g.,
   "Claude Code MCP").
4. Copy the generated password immediately — WordPress shows it once
   only.
5. Provide the password to Claude Code's MCP server configuration
   alongside the WordPress site URL.

After build completion, the application password should be revoked
or the user account should be disabled to remove the access path.

---

## Verification

After completing the steps above, verify the setup is working:

1. **WordPress accessible:** the property's WordPress admin loads at
   the expected URL.
2. **Twenty Twenty-Five active:** Appearance → Themes shows Twenty
   Twenty-Five as the active theme.
3. **MCP Adapter and mcp-expose-abilities active:** Plugins page
   shows both as active and auto-updates disabled.
4. **WP-CLI works:** `wp --info` over SSH returns version info and
   reads the WordPress install correctly.
5. **MCP discovery works — progressive check:**
   - The MCP endpoint speaks JSON-RPC 2.0 over HTTP via
     `POST /wp-json/mcp/mcp-adapter-default-server`. GET requests
     return HTTP 405 (SSE not implemented in MCP Adapter v0.5.0).
   - **Discovery flow:** send an `initialize` request → capture the
     `Mcp-Session-Id` value from the response header → send a
     `tools/call` request with `mcp-adapter-discover-abilities` as the
     tool name, including the session ID header. The response lists all
     exposed abilities.
   - **After MCP Adapter activated, before mcp-expose-abilities:**
     discovery returns only the 3 MCP Adapter built-ins
     (`mcp-adapter-discover-abilities`, `mcp-adapter-get-ability-info`,
     `mcp-adapter-execute-ability`).
   - **After mcp-expose-abilities activated:** discovery returns
     **74 abilities** (verified on Chill Zone staging, 2026-05-11):
     68 from mcp-expose-abilities (across `content/*`, `plugins/*`,
     `menus/*`, `comments/*`, `users/*`, `media/*`, `system/*`,
     `options/*`, `widgets/*`, `meta/*`, `taxonomy/*` namespaces),
     3 MCP Adapter built-ins (`mcp-adapter/*`), and 3 `core/*`
     abilities. The `core/*` abilities appeared without the bridge
     mu-plugin — load order was not an issue in practice.
   - The bridge mu-plugin (§4a) remains a documented contingency but
     is not the default path. If `core/*` abilities are missing after
     both plugins are active, install the bridge mu-plugin and
     re-check.
6. **SSH access works:** SSH from the build agent's environment
   reaches the VPS and can list `wp-content/themes/`.

If any verification fails, fix before proceeding to build. The
build phase assumes all six work.

---

## Cross-references

- `references/property-setup.md` — Beds24-side configuration. This
  document is the WordPress-side companion.
- `docs/architecture.md` — architectural reasoning for the stack
  decisions.
- The design-side handoff document for each property — describes
  what the build agent does with the configured stack. The handoff
  document references this setup runbook as a prerequisite.

---

## Known unknowns

### 1. mcp-expose-abilities maintainer risk

mcp-expose-abilities is a small project (community-driven, low star
count). Long-term maintenance is uncertain. Mitigations:

- Version-pinning during build (covered above) bounds project-time
  exposure.
- If the plugin is abandoned post-build, ongoing maintenance is small
  (manual edits to pages) and doesn't require the MCP stack. The
  stack is for *build*, not *operate*.
- If a future build is needed and mcp-expose-abilities is no longer
  viable, custom abilities can be registered against the Abilities
  API directly. ~5-10 abilities per build phase; Claude Code can
  write these.

This isn't a blocker but is worth knowing.

### 2. WordPress version availability

This document assumes WordPress 6.9. If aapanel's WP Toolkit defaults
to an older version on a future install, manual upgrade may be
needed before the Abilities API is available. Verify version on each
property setup.

### 3. PHP version availability on aapanel

aapanel supports multiple PHP versions side-by-side. The default may
be older than 8.0 on some installs. Verify and switch if needed
before installing mcp-expose-abilities.

### 4. Release ZIP asset naming

Both MCP Adapter and mcp-expose-abilities use GitHub Releases for
distribution. Asset filenames within a release may not always match
`<repo-name>.zip` (e.g., release tag variations, version suffixes).
Confirm the exact filename on each release page before running
`wp plugin install <url>`. If the release lacks vendored Composer
dependencies, fall back to git clone + `composer install`.

---

## VPS environment notes (current VPS)

The runbook above is written to be portable across VPSes running
aapanel + OpenLiteSpeed. The following constraints are specific to
the project's current VPS and may differ on other VPSes; verify on
first deployment to a new host.

### open_basedir

aapanel configures a per-site `open_basedir` PHP restriction in the
site's Nginx vhost config, typically scoped to:

    /tmp/:/www/wwwroot/<site-directory>/

Any PHP file the site needs to load must live within those paths.
Practical implications:

- Symlink-based plugin install (symlinking a directory from outside
  `/www/wwwroot/<site>/` into `wp-content/plugins/`) fails on the
  front end despite working in WP-CLI. PHP resolves the symlink
  before applying `open_basedir`, so the target's location is what
  matters. The plugin appears active in WP admin and WP-CLI; PHP
  silently fails to load it on front-end requests.
- Plugin updates from a separate clone must be applied as file
  copies, not symlink updates. Workflow: `git pull` in the clone,
  then copy changed files into the site's plugins directory.
- The constraint applies per-site. If multiple property sites need
  a shared plugin location, the location must be inside a path
  allowed by each site's `open_basedir` — most likely impossible
  without modifying aapanel's vhost generation.

The setting is managed by aapanel; the `claude-code` user cannot
directly read the Nginx vhost config. Modifying it (if ever needed)
requires root and aapanel awareness.

### Other constraints

Add new VPS-specific constraints to this section as they are
discovered.

---

## Document history

- **2026-05-08:** Initial draft from design conversation. Captures
  the stack decisions ratified during architecture/design discussion.
  Verification against actual property setup pending.
- **2026-05-11:** Step 7 (SSH access) rewritten based on verified
  staging VPS setup. Corrected false claim about aapanel SSH user
  management. Added concrete user creation commands, ACL approach
  (POSIX ACLs via setfacl, not group permissions), and aapanel-specific
  notes (www group gid 1000, .user.ini immutability).
- **2026-05-11:** MCP install path corrected — both MCP Adapter and
  mcp-expose-abilities are GitHub-only, not on WordPress.org plugin
  directory. Added §4a MCP-public meta flag section covering the
  6.9 default that abilities aren't MCP-exposed without explicit
  opt-in, with the bridge mu-plugin pattern. Verification step 5
  restructured as a progressive check. LiteSpeed Cache plugin added
  to the Stack overview as the canonical cache layer; Cloudflare MCP
  add-on explicitly skipped. Stable-tag for mcp-expose-abilities
  noted as 66 (not "~66"). Status line refined.
- **2026-05-11:** MCP stack verification completed on Chill Zone
  staging (Session 7). Verification step 5 updated: endpoint confirmed
  as JSON-RPC 2.0 POST (GET returns 405); discovery flow requires
  `initialize` → capture `Mcp-Session-Id` → `tools/call`. Verified
  ability count on Chill Zone is 74 (68 mcp-expose + 3 mcp-adapter + 3
  core); `core/*` abilities appeared without the bridge mu-plugin in
  practice. Bridge mu-plugin demoted to contingency. `wp rest list`
  availability note added to step 5 (WP-CLI verification). Status
  updated to verified for Chill Zone staging.
- **2026-05-12:** Added open_basedir constraint discovered during
  Session 8 plugin install. Symlink caveat added to §4 Install method
  (before Version pinning). New "VPS environment notes" section added
  with full detail on aapanel's per-site `open_basedir` restriction,
  its practical implications for plugin install workflows, and the
  copy-based update workflow required as a result.