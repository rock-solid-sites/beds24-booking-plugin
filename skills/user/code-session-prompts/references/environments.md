# Environment-specific reference for code-session-prompts

The body of the `code-session-prompts` skill keeps the cross-cutting
prompt-drafting discipline — launch-block model, prompt-structure
conventions, self-check list. This file holds the environment-
specific reference material that matters only when drafting a
prompt for a particular environment.

Two environments today: the VPS, and the local DDEV/WSL2 setup.

## VPS environment notes

When drafting prompts for VPS-side work, a few things to keep in
mind that don't apply to local Code sessions:

- **Code on the VPS is installed at `~/.local/bin/claude` for the
  `claude-code` user**, with `PATH` configured in `~/.bashrc`.
  The install used Anthropic's native installer
  (`curl -fsSL https://claude.ai/install.sh | bash`). Per-user
  install — root's Code install is separate and not shared.
  Fresh terminals pick up the PATH automatically; VS Code
  Remote-SSH sessions may need `source ~/.bashrc` if `.bashrc`
  isn't auto-sourced.
- **`claude-mode` on the VPS is installed at
  `~/.local/bin/claude-mode` for the `claude-code` user**, via
  the upstream install script
  (`curl -fsSL https://raw.githubusercontent.com/nklisch/claude-code-modes/main/install.sh | sh`).
  Same per-user pattern as Code itself.
- **First-run authentication needed if Code isn't yet
  authenticated on the VPS.** The OAuth flow uses
  `localhost:35751` for the callback, which requires SSH local
  port forwarding from the operator's machine during the auth
  round-trip: `ssh -L 35751:localhost:35751 tripn-vps`. Once
  authenticated, credentials persist; only an issue on first run
  per VPS-user combination.
- **The `claude-code` Linux user owns site files via ACLs**, not
  via primary ownership. Files under `/www/wwwroot/` show as
  `www:www` but `claude-code` has rwX access via POSIX ACLs.
- **aapanel-specific file protections** — `.user.ini` files are
  `chattr +i` (immutable). EPERM errors on operations against
  them are expected aapanel behavior, not failures. Recovery if
  modification is needed: `chattr -i` (root only) to modify,
  `chattr +i` to restore.
- **Per-site `open_basedir` restriction.** aapanel configures a
  PHP `open_basedir` restriction per site, typically
  `/tmp/:/www/wwwroot/<site-directory>/`. Symlinks from outside
  these paths fail at PHP load time even though WP-CLI may
  report the plugin as registered. Plugin installs from external
  clones must be directory copies, not symlinks. The constraint
  is documented in `wordpress-setup.md` (plugin repo).
- **WP-CLI uses non-standard PHP path on aapanel.** PHP lives at
  `/www/server/php/83/bin/php` (or similar versioned path), not
  the system default. WP-CLI on this VPS needs `WP_CLI_PHP` set
  to that path in `~/.bashrc`. If `wp` commands fail with PHP
  errors, this is the first thing to check.

These are stable VPS-environment facts. When drafting prompts
that touch the VPS, the prompt doesn't need to repeat all of
them, but should not assume contrary facts.

## DDEV / WSL2 environment notes

The local plugin development environment runs on DDEV inside
WSL2 (Ubuntu 24.04). Full setup reference: `docs/tooling/ddev.md`
in the plugin repo. When drafting prompts that run against this
environment:

- **Plugin repo path:** `~/projects/beds24-booking-plugin` inside
  the WSL2 distro. The pre-migration Windows-side tree at
  `C:/Users/Dr. COMPUTER/Desktop/Development/beds24-booking-plugin`
  is stale; the WSL2 clone is canonical.
- **WordPress site path:** `~/projects/chillzone` inside WSL2.
  This is the DDEV project root.
- **Site URL:** `https://chillzone.ddev.site`. DDEV provides a
  trusted HTTPS certificate automatically (via `mkcert -install`
  one-time setup on the host).
- **Services:** managed by DDEV — `ddev start`, `ddev stop`,
  `ddev restart` from inside the project directory. No GUI
  startup step. `ddev describe` shows project state and URLs;
  `ddev logs -s web` tails the web container's log.
- **WP-CLI:** invoke as `ddev wp <command>`. Runs inside the web
  container against the DDEV-managed MySQL. No full PHP path
  needed.
- **MySQL:** `ddev mysql` opens a shell to the database. DDEV
  manages the credentials internally; the operator does not
  hand-edit a `wp-config.php` database block.
- **Database snapshot/restore:** `ddev snapshot` captures,
  `ddev snapshot restore --latest` recovers. Use before any
  destructive DB operation.
- **Plugin mount mechanism.** The plugin in
  `~/projects/beds24-booking-plugin/plugin/` is mounted into the
  DDEV web container via two pieces working together: a symlink
  at `~/projects/chillzone/wp-content/plugins/beds24-booking-plugin`
  pointing into the plugin's `plugin/` subdirectory, plus a
  Docker Compose override at
  `~/projects/chillzone/.ddev/docker-compose.plugin-mount.yaml`
  that mounts the plugin tree into the container at the same
  absolute path. Without the override the symlink dangles inside
  the container even though it resolves on the host. The
  override file is project infrastructure not committed to the
  plugin repo; if the DDEV project is recreated, the override
  must be regenerated. Full contents and reasoning in
  `docs/tooling/ddev.md`.
- **Edits are immediately live.** The container-side mount is
  read-only (host is the canonical source, container reads),
  which means edits in the host filesystem appear instantly in
  the running WordPress with no copy step.
- **WSL2 tooling — per-user, native install inside the distro.**
  Crossing the Windows/Linux boundary means installers reinstall
  on the WSL2 side. The per-user pattern lands everything in
  `~/.local/bin` or `~/.bun`, and `~/.local/bin` must be on
  `PATH` in `~/.bashrc`.

  Claude Code:

  ```
  curl -fsSL https://claude.ai/install.sh | bash
  ```

  bun (install `unzip` first — `sudo apt install unzip` — since
  the bun installer expects it and a fresh Ubuntu 24.04 lacks
  it):

  ```
  curl -fsSL https://bun.sh/install | bash
  ```

  claude-mode (from the nklisch/claude-code-modes upstream):

  ```
  curl -fsSL https://raw.githubusercontent.com/nklisch/claude-code-modes/main/install.sh | sh
  ```

- **WSL2 memory ceiling.** Default WSL2 memory crashes the VM on
  this workload. Set `memory=5GB, swap=4GB` in
  `C:\Users\<you>\.wslconfig` and `wsl --shutdown` from Windows
  to apply. DDEV plus one Code session fits within that ceiling;
  heavy parallel processes (e.g. test runs from another project
  sharing the same WSL2 VM) do not. Background and reasoning in
  `docs/tooling/ddev.md` under "Environment constraints."
- **WP_DEBUG_DISPLAY refinement:** `WP_DEBUG=true,
  WP_DEBUG_LOG=true, WP_DEBUG_DISPLAY=false` is the working
  config for local dev. Errors log to
  `~/projects/chillzone/wp-content/debug.log` without breaking
  page rendering. The `fail loud during dev` project convention
  is satisfied via the log path.
- **Hosts file edits:** DDEV manages `.ddev.site` domain entries
  automatically via its Windows-side helper binary. No manual
  hosts-file editing needed for the standard project setup.
- **Editor:** VS Code with the WSL Remote extension, opened from
  the project directory with `code .`. The editor lives on the
  Windows side and remotes into WSL2; the working tree, git
  state, and shell are all on the WSL2 side.

These are stable DDEV-environment facts. When drafting prompts
that touch this environment, repeat only what's relevant to the
work being done — most prompts can rely on the environment being
in its known good state.
