# DDEV Local Development Setup

The plugin's local development environment runs on DDEV inside WSL2.
This document captures the stack, the canonical paths, the project
setup, and the migration gotchas that are easy to re-encounter.

This replaces the Laragon setup used through Session 10. Laragon is
no longer part of the project's infrastructure.

## Stack

- **WSL2** with **Ubuntu 24.04 LTS**. Note: 24.04 specifically. An
  earlier attempt installed Ubuntu 26.04 (a pre-release at the time),
  which lacked packages DDEV's install scripts expect (`wslu`) and
  caused install friction. Use the LTS.
- **Docker CE inside WSL2** — not Docker Desktop. The DDEV Windows
  installer sets this up automatically when the "Docker CE" mode is
  selected.
- **DDEV** — installed both inside WSL2 (where commands run) and on
  the Windows side (a small binary that edits the Windows hosts file
  for `.ddev.site` domains).

## Canonical paths

All project work happens inside the WSL2 filesystem, not on the
Windows `C:\` drive. Files under `/mnt/c/...` are reachable from WSL2
but slow for live work — acceptable for one-time copies, not for the
working tree.

- Plugin git tree: `~/projects/beds24-booking-plugin`
- chillzone WordPress site: `~/projects/chillzone`
- DDEV global config: `~/.ddev`

The Windows-side plugin tree at
`C:\Users\Dr. COMPUTER\Desktop\Development\beds24-booking-plugin` is
stale after the migration and should not be used as the working copy.
The WSL2 clone is canonical.

## How the plugin is mounted into WordPress

The plugin is developed in `~/projects/beds24-booking-plugin/plugin/`
and needs to appear inside the WordPress install at
`~/projects/chillzone/wp-content/plugins/beds24-booking-plugin`. Two
pieces make this work:

1. **A symlink** in `wp-content/plugins/` pointing at the `plugin/`
   subdirectory of the repo:
   ```
   ln -s ~/projects/beds24-booking-plugin/plugin \
     ~/projects/chillzone/wp-content/plugins/beds24-booking-plugin
   ```
   The symlink target is the `plugin/` subdirectory, not the repo
   root. The main plugin file is `plugin/beds24-booking-plugin.php`.

2. **A Docker Compose override** at
   `~/projects/chillzone/.ddev/docker-compose.plugin-mount.yaml` that
   mounts `~/projects/beds24-booking-plugin` inside the DDEV web
   container. Without this, the symlink resolves on the WSL2 host but
   dangles inside the container, because the container can only see
   what DDEV mounts into it.

   The full file contents (as currently in use):
   ```yaml
   services:
     web:
       volumes:
         - /home/drcomputer/projects/beds24-booking-plugin:/home/drcomputer/projects/beds24-booking-plugin:ro
   ```

   Notes on this YAML:
   - The host path and container path match exactly. This is what
     lets the symlink in `wp-content/plugins/` resolve to the same
     absolute path inside the container as on the host.
   - The mount is read-only (`:ro`). The container reads plugin code
     from the host; it never writes back. Edits happen on the host
     (the canonical git tree) and are picked up immediately.
   - The host path is absolute, not a `~` expansion. Docker Compose
     does not expand `~`; if the host username changes the path must
     be updated here.

This override file is project infrastructure that is not committed to
the plugin repo (the plugin repo does not own DDEV config). If the
DDEV project is ever recreated or moved, this file must be
regenerated. It is the single most fragile, least-documented piece of
the setup — treat it as load-bearing.

## Initial setup from scratch

These steps recreate the environment on a fresh machine, or recover
from a lost WSL2 distro.

### Environment constraints (read first)

Three constraints surfaced during the Laragon → DDEV migration that
are easy to miss on a clean install.

**All tooling needs a native WSL2 install.** Crossing the
Windows/Linux boundary means installers, language toolchains, CLIs,
and editors must be reinstalled inside WSL2. DDEV and the repo
clone are only the start — the working environment for this project
also needs `claude-mode`, `bun`, and Claude Code, all of which were
installed natively after the migration. Anything previously running
under Windows that the project depends on belongs on the WSL2 side
now. The per-user install pattern lands everything in `~/.local/bin`
or `~/.bun`, and `~/.local/bin` must be on PATH in `~/.bashrc`.

Claude Code:
```
curl -fsSL https://claude.ai/install.sh | bash
```

bun (install `unzip` first if missing — see the next constraint):
```
curl -fsSL https://bun.sh/install | bash
```

claude-mode (from the nklisch/claude-code-modes upstream):
```
curl -fsSL https://raw.githubusercontent.com/nklisch/claude-code-modes/main/install.sh | sh
```

**A fresh Ubuntu 24.04 lacks utilities some installers assume.**
The `bun` installer requires `unzip`, which is not in the default
Ubuntu image:
```
sudo apt install unzip
```
Install missing utilities as the install scripts surface them. Do
not chase a long preventive checklist — fix them when they fail.

**WSL2 default memory is too low for this workload.** With DDEV's
containers running plus a Claude Code session plus other concurrent
heavy processes on an 8 GB Windows host, the WSL2 VM has been
observed to crash entirely — the full VM dies, killing every
process inside it. The fix is a `.wslconfig` at the Windows user
directory (`C:\Users\<you>\.wslconfig`):
```
[wsl2]
memory=5GB
swap=4GB
```
Restart WSL (`wsl --shutdown` from Windows, then reopen) for the
change to take effect. DDEV plus one Code session fits within
this ceiling. What does not fit is that plus additional heavy
processes — test runs from the parallel DeepSeek-TUI project
sharing the same WSL2 VM is the concrete example that triggered
the crashes. Run heavy parallel workloads in a separate WSL2
distribution or quiet down the others first.

### 1. WSL2 and Ubuntu

In an administrator PowerShell:
```
wsl --install -d Ubuntu-24.04
```
If this errors with `WSL_E_WSL_OPTIONAL_COMPONENT_REQUIRED`, run
`wsl.exe --install --no-distribution` first, reboot, then retry.

Reboot after install. On first launch, Ubuntu prompts for a Linux
username and password (independent of the Windows account; password
input is silent).

Then update packages:
```
sudo apt update && sudo apt upgrade -y
```

### 2. DDEV installer

Download the DDEV Windows installer from
`https://github.com/ddev/ddev/releases` (the
`ddev_windows_amd64_installer.vX.Y.Z.exe` asset). Run it, choose the
**Docker CE** mode, and enter the distro name (`Ubuntu-24.04`) when
prompted. The installer pushes DDEV and Docker CE into the distro.

Verify from the Ubuntu shell:
```
ddev version
docker ps
```
If `docker ps` returns a permission error, add the user to the docker
group and reopen the shell:
```
sudo usermod -aG docker $USER
```

### 3. Project setup

```
mkdir -p ~/projects/chillzone
cd ~/projects/chillzone
ddev config --project-type=wordpress --docroot=.
ddev start
ddev wp core download
```

Then clone the plugin and wire it in:
```
cd ~/projects
git clone https://github.com/rock-solid-sites/beds24-booking-plugin.git
ln -s ~/projects/beds24-booking-plugin/plugin \
  ~/projects/chillzone/wp-content/plugins/beds24-booking-plugin
```

Create the Docker Compose override so the container can see the
symlink target. The file lives at
`~/projects/chillzone/.ddev/docker-compose.plugin-mount.yaml` and
mounts `~/projects/beds24-booking-plugin` into the web container.
Then `ddev restart` and `ddev wp plugin activate beds24-booking-plugin`.

## Migrating an existing site (the Laragon migration, for reference)

The chillzone migration from Laragon surfaced several issues worth
recording, because any future site migration will hit the same ones.

### Database export must be UTF-8

Exporting via PowerShell's `>` redirect writes UTF-16 with a BOM,
which MySQL rejects on import (`ASCII '\0' appeared in the
statement`). Use mysqldump's `--result-file` flag, which writes UTF-8
directly:
```
mysqldump.exe -u root --result-file=C:\path\to\dump.sql database_name
```
Not `mysqldump ... > dump.sql`.

### Plugin files must be on disk before the DB import is usable

The imported database has a list of active plugins. If those plugin
files are not present in `wp-content/plugins/` when WordPress next
loads, it fatals — a blank admin page with no visible error. Copy all
active plugins' files from the old install before or immediately
after the DB import. In the chillzone migration, seven third-party
plugins were copied from Laragon; `beds24-booking-plugin` itself was
handled separately via the symlink.

### Table prefix case sensitivity

The Laragon install used a mixed-case table prefix (`rhbKfRVV9_`).
Two problems followed:

1. `wp-config.php` in the fresh DDEV install defaulted to `wp_`. The
   `$table_prefix` variable must be set to match the imported
   database. Note this is the `$table_prefix` *variable*, not a
   `define('table_prefix', ...)` constant — `wp config set
   table_prefix` creates the constant by default, which WordPress
   ignores. Edit `wp-config.php` directly to set the variable.

2. Even with the prefix string correct, MySQL on Linux is
   case-sensitive about identifiers in a way the Windows MySQL was
   not. `usermeta` keys (capabilities, user_level) and the
   `user_roles` options key had been stored with mixed case.
   WordPress could not find the admin role — login succeeded but the
   user had no capabilities. The shape of the fix was two
   `LOWER()` updates against the prefix-bearing rows in those
   two tables — illustratively:
   ```sql
   -- Illustrative only — actual WHERE clauses not recovered.
   -- Two UPDATEs were run, one per table, restricted to the rows
   -- whose key/name began with the (mixed-case) prefix.
   UPDATE <prefix>usermeta
     SET meta_key = LOWER(meta_key)
     WHERE meta_key LIKE '<MixedCasePrefix>%';
   UPDATE <prefix>options
     SET option_name = LOWER(option_name)
     WHERE option_name LIKE '<MixedCasePrefix>%';
   ```
   Treat the above as a sketch of intent, not a recipe to paste.
   Before running similar fixes on any future migration, inspect
   the actual stored values first (`SELECT DISTINCT meta_key FROM
   ...usermeta WHERE meta_key LIKE 'mix%'`) and narrow the WHERE
   clause to exactly the affected rows.

### URL search-replace

After import, WordPress's stored URLs still point at the old domain:
```
ddev wp search-replace 'http://chillzone.test' 'https://chillzone.ddev.site'
ddev wp cache flush
```

### Other artifacts

The active theme (Kadence) was not part of the plugins copy and had
to be copied separately. The `wp-content/uploads` directory must be
copied for the media library to work. After everything is in place,
`ddev wp rewrite flush` if permalinks 404.

## Common operations

- Start / stop: `ddev start`, `ddev stop` (from inside the project dir)
- WP-CLI: `ddev wp <command>` — no full-path invocation needed, unlike
  the Laragon setup
- Shell into the web container: `ddev ssh`
- Project status and URLs: `ddev describe`
- Logs: `ddev logs -s web`
- Database snapshot: `ddev snapshot`
- Editing files: VS Code with the WSL extension, opened from the
  project directory with `code .`

## What changed from the Laragon setup

For anyone cross-referencing older session handoffs:

- WP-CLI was previously invoked with a full path
  (`/c/laragon/bin/php/.../php.exe /c/laragon/bin/wp.phar`). Under
  DDEV it is just `ddev wp`.
- The site was `http://chillzone.test`; it is now
  `https://chillzone.ddev.site` (DDEV provides a trusted HTTPS cert).
- The plugin was previously live via a Windows junction; it is now
  live via a Linux symlink plus the Docker Compose mount override.
- Laragon's terminal popup bug — the original reason for the
  migration — is gone. There is no equivalent friction in the DDEV
  workflow.
