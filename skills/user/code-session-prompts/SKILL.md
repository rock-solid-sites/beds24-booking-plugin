---
name: code-session-prompts
description: Use when drafting a prompt for a Claude Code session — selecting the mode preset, choosing the permission mode, structuring the prompt, and formatting it for the operator to paste. Covers Code sessions in both project repos (beds24-booking-plugin and tripn-sites) and Code sessions on the VPS for site building and infrastructure work. Activates whenever chat-side work produces a Code-facing prompt.
---

# Drafting Code Session Prompts

This skill captures how to draft a Code prompt for the project. It
covers Code sessions in the plugin repo, the tripn-sites repo, and
VPS-side work. It assumes the foundational project context skill is
also active.

Source documents (request when detail beyond this skill is needed):
- `docs/tooling/claude-code-modes.md` (plugin repo) — full
  phase-to-mode mapping for plugin work
- `OPERATING.md` (each repo) — operator-facing conventions,
  permission modes, recovery patterns
- `docs/tooling/ddev.md` (plugin repo) — local DDEV/WSL2 environment
  setup, mount mechanism, recovery patterns
- `references/environments.md` (this skill) — environment-specific
  paths and quirks for VPS and local DDEV; consult when drafting
  prompts for a specific environment

## Two repos, one VPS, one DDEV — four launch contexts

Code sessions run in one of four contexts:

1. **Plugin repo (local WSL2)** — `beds24-booking-plugin` cloned to
   `~/projects/beds24-booking-plugin` inside the operator's WSL2
   Ubuntu distro. Plugin development sessions launch from here.
   Project presets defined in `.claude-mode.json` at the repo root.

2. **Site-design repo (local Windows or VPS)** — `tripn-sites`
   cloned to the operator's local Windows machine for design
   conversations and documentation work, or cloned to the VPS for
   site-building work that needs the handoff documents. Project
   presets defined in the repo's own `.claude-mode.json`.

3. **VPS, outside any repo** — Code running on the VPS as the
   `claude-code` user via Remote-SSH for infrastructure or
   site-building work that doesn't need a repo. Only built-in
   `claude-mode` presets work (no project config available).

4. **DDEV local WordPress (local WSL2)** — Code running against
   the DDEV-hosted WordPress at `https://chillzone.ddev.site` (and
   potentially other property sites in the future). Plugin code
   lives in the plugin repo working tree and is mounted into the
   DDEV web container via a symlink plus a Docker Compose override;
   edits become immediately live on the local site. Used for plugin
   frontend development where iteration speed matters.

The launch block, mode selection, and any context-specific
considerations depend on which context the work runs in. For the
detailed paths, install methods, and quirks per environment, see
`references/environments.md`.

## When to develop locally vs. on the VPS

Plugin development and site building have different efficient
setups:

- **Site building** is *deliverable-is-development* — the theme
  files, page content, and block patterns being built are the
  artifacts that get deployed. Building directly on the VPS works
  well: no copy step, the test environment is the same as
  production.

- **Plugin development** is *development-is-upstream-of-deliverable*
  — the source repo (git-tracked) is upstream of the installed
  instance on any given site. Source files and active plugin files
  are different states; conflating them creates workflow friction
  (git working tree conflicts with active plugin directory, edits
  outside git lose version history). The clean separation is:
  develop locally in the git working tree, deploy as a separate
  step.

Default to **local DDEV development for plugin sessions** unless
there's a specific reason to work on the VPS. Default to
**VPS-direct for site building** unless the deliverable is
structurally portable (rare).

## Output format

Every drafted Code prompt comes in two code blocks, in this order:

1. **Launch block** — `cd` into the repo + the `claude-mode`
   invocation. This is what the operator runs in a new terminal
   to start the session.
2. **Prompt block** — the actual prompt for Code. Pasted as the
   first user message in the launched session.

Both blocks are fenced as code so the operator can copy them
without picking around markdown formatting.

If the prompt is a **continuation** of an active session (same
Code instance, follow-up turn), omit the launch block and mark the
prompt "continuation — paste into existing session."

### Code block conventions

Two rules apply when fencing the prompt block (and any code block
quoted inside it):

- **Prompt code blocks use four-backtick fences.** Drafted prompts
  frequently quote example commands, diffs, or other content inside
  their own triple-backtick fenced blocks. A surrounding
  triple-backtick fence terminates at the first inner triple-
  backtick, which silently splits the prompt block in two and
  breaks copy-paste. Four backticks on the outer fence prevents
  this. Use four backticks for the prompt block by default. The
  launch block rarely has inner triple-backticks, but using four
  backticks there too is fine for consistency.
- **Code blocks contain only copyable content.** Inside any code
  block — launch block, prompt block, or any block quoted inside
  them — write only what the reader pastes. No `# what this line
  does` annotations, no `# alternatively, ...` asides, no inline
  commentary mixed with commands. The operator pastes the block
  verbatim; mixed-in commentary forces them to pick around it (or
  worse, paste commentary as a command). Explanation goes in
  prose around the block, not inside it.

## Launch block format

**Plugin repo (local WSL2):**

````bash
cd ~/projects/beds24-booking-plugin
claude-mode <preset> -- --permission-mode <mode>
````

**Site-design repo (local Windows):**

````bash
cd "C:/Users/Dr. COMPUTER/Desktop/Development/tripn-sites"
claude-mode <preset> -- --permission-mode <mode>
````

**VPS (no repo or in a VPS-cloned repo):**

````bash
claude-mode <built-in-preset> --base chill -- --permission-mode <mode>
````

No `cd` — Code launches from wherever the SSH session lands
(typically `/home/claude-code/`). Only built-in presets work
unless the VPS has a cloned repo with its own `.claude-mode.json`.

Each project repo's `.claude-mode.json` sets `defaultBase: "chill"`
already — no `--base chill` flag needed when launching from those
repo roots. When launching outside a project repo (the VPS case
above), pass `--base chill` explicitly if chill base is wanted.

**Argument ordering matters.** `claude-mode`'s own flags (like
`--base`, `--readonly`, `--modifier`, `--quality`, `--agency`) go
*before* the `--` separator, attached to the preset. Everything
*after* `--` is forwarded to `claude` verbatim. Putting `--base chill`
after `--` sends it to `claude`, which rejects it with
`unknown option '--base'`. Pattern to remember:

````
claude-mode <preset> [claude-mode-flags] -- [claude-flags]
````

## Mode selection

The plugin repo's phase-to-mode mapping is the primary reference
for plugin work. Quick lookup:

| Work type | Mode | Permission mode |
|---|---|---|
| Architecture / design | `architecture` | `auto` |
| V1 build (net-new, local) | `v1-build` | `bypassPermissions` |
| Feature extension (local) | `feature` | `bypassPermissions` |
| Refactor (local) | `refactor` | `bypassPermissions` |
| Documentation | `docs` | `auto` |
| Property rollout | `rollout` | `default` |
| Bug fix (known cause) | `bugfix` | `default` |
| Bug fix (unknown cause) | `debug --base chill` | `default` |
| Read-only review | `review` | `default` or `auto` |

`bypassPermissions` is the default for local plugin-build work
running in the WSL2 + DDEV environment: the working tree is
isolated from production, edits are high-volume, and per-edit
prompts add no safety the local isolation doesn't already provide.
For the same work types running on the VPS, fall back to `auto`
or `default` per posture — the VPS lacks the local isolation, so
per-action approval is justified there.

The site-design repo has its own `.claude-mode.json` with presets
suited to site-design work. When drafting a Code prompt for
site-design work, check the tripn-sites repo's `.claude-mode.json`
for current presets.

For VPS work without a repo, only built-in presets are available:
`create`, `extend`, `safe`, `refactor`, `explore`, `debug`,
`methodical`, `director`, `partner`, `none`. Map by posture rather
than by project-preset name. Built-in equivalents to common project
presets:

| Project preset | Built-in equivalent |
|---|---|
| `architecture` | `methodical` |
| `v1-build` | `create` |
| `feature` | `extend` |
| `rollout` / `bugfix` | `safe` |
| `docs` | `methodical` (closest) or compose with `--quality minimal` |
| `review` | `explore` |

Permission-mode rationale: `bypassPermissions` removes interrupt
friction in the trusted local environment; `auto` is the
middle-ground for low-risk work where some guardrails still help;
`default` keeps per-action approval for live system work or
anything touching property data or system configuration.

## Work that doesn't fit a row cleanly

When the work doesn't match the mapping, pick the closest row by
*posture* (collaborative vs. autonomous, minimal vs. pragmatic vs.
architect, narrow vs. adjacent vs. unrestricted) rather than by
nominal label. Surface the mismatch to the operator so a custom
mode can be considered if the work recurs.

Example: VPS infrastructure setup (Linux user creation, sshd
configuration) doesn't have a row in the plugin repo's mapping by
topic, but the posture is surgical/architect/narrow — careful
change to a working system, narrow scope, explain before acting.
`methodical` is the built-in preset that matches.

## Expected-state line

For prompts that direct Code to work on the local repo, include
an Expected-state line near the top, after the context section:

````
Expected HEAD: <commit hash>
Working tree: <state, e.g. "clean (`.claude/` untracked is expected)">
````

Code verifies this before starting. If state doesn't match, Code
halts and the operator updates the prompt before retrying. If
commits land between when the prompt was drafted and when it's
run, the operator updates the hash before pasting.

**Omit the Expected-state line for sessions that don't touch the
repo.** VPS-side infrastructure work, WordPress site building on
property servers, local DDEV/WSL2 environment setup, and any
other session where Code isn't operating on the local repo has no
repo state to verify. The convention applies to repo work;
non-repo sessions skip it.

## Prompt structure

Standard sections, in order:

1. **Context** — what came before, what the operator wants, links
   to relevant prior work. Brief.
2. **Expected state** — HEAD, working tree, branch if not main.
   Repo work only.
3. **Scope** — what's in scope, what's explicitly out. The
   out-of-scope list prevents Code from expanding beyond the
   session's intent.
4. **Independent assessment** — instruct Code to evaluate the
   plan before executing. The project skill's "independent
   assessment first" default applies; the prompt reminds Code to
   push back if something is wrong.
5. **Plan** — numbered steps. Each step states what to do and
   what to report. Safety patterns (backups, validation commands,
   parallel sessions for dangerous steps) belong in the step that
   needs them.
6. **Failure modes** — what to watch for and what to do when each
   happens. Anticipates the known risks.
7. **Conventions reminder** — short pointer to project conventions
   (American spelling, no time estimates, fail loud, etc.). One
   line is enough; the project skill carries the detail.

## Handoff facts are inferences, not measurements

A pattern that has surfaced repeatedly: session-handoff documents
assert environmental facts ("Code is installed at X," "WP-CLI
works," "the MCP stack is at version Y") that were true for the
*writing* session's context but not necessarily for the *reading*
session's. When the reading session relies on these without
verifying, the assumption costs real session time to unwind.

**Treat handoff facts as inherited claims to be verified, not
established measurements.** When drafting a prompt that depends
on inherited state, include explicit start-checks that verify
each load-bearing inherited fact before acting on it. The
verify-before-trusting preamble used in `session-handoff-N.md`
documents from Session 7 onward is the convention; the prompt
inherits this discipline.

Worked instances across the project so far:
- "Code installed at `~/.local/bin/claude`" — true for root, not
  for `claude-code` user (Session 7)
- "claude-mode v0.2.13 installed" — true for root, not for
  `claude-code` user (Session 7)
- "WP-CLI works on the VPS" — not installed (Session 7)
- "MCP Adapter is at v0.5.0" — was actually pre-installed at
  v0.4.1, upgraded during Session 7
- "Predecessor was iframe styled by CSS/JS" — actually a JS
  widget + side-installed Beds24 plugin (Session 8)

The pattern is general: handoff documents and preludes are
summaries written under time pressure. They don't replace
measurement.

## Secrets in Code prompts — write up to the gate

When a Code prompt needs a secret value (API token, refresh
token, password, OAuth credential, anything whose disclosure
would require rotation), do NOT include the value in the prompt,
and do NOT use an operator-fillable placeholder like
`{TOKEN_VALUE}` or `PASTE_HERE`. Both patterns create real costs:
direct inclusion puts the secret in chat history; placeholder
patterns reintroduce the fragility the no-blanks rule warns
against.

**The pattern: write the prompt only up to the gating action
where the secret would appear, and stop.** Do not write the
gating command itself with a placeholder. Do not write subsequent
steps that depend on the gating action.

The operator then constructs the single gating command
themselves, with the secret substituted directly, and pastes that
one command to Code. Once Code reports back, a continuation
prompt covers the remaining steps.

Why this works:
- The secret never appears in chat (operator constructs and
  pastes the command directly to Code)
- No template to substitute into; no marker to overlook
- The handoff is one command, not a multi-step prompt block
  requiring careful substitution

Distinguishing secrets from other values: a value is a secret
when its disclosure in chat would meaningfully change the
security posture (i.e., would require rotation). API tokens,
refresh tokens, passwords, OAuth credentials, private keys —
secrets. Configuration paths, usernames, port numbers, file paths
— not secrets; handle via the normal no-blanks rule (request the
value before drafting, or write only up to the point it becomes
available).

For non-secret values, the no-blanks rule still applies: don't
write prompts with operator-fillable blanks. Request the value
before drafting, or write only up to the point the value becomes
available (then continue after the operator provides it).

## What stays out of Code prompts

Per OPERATING.md: operator-side material stays operator-side.
Don't include personal preferences, environment notes (the
operator's machine details, paths the operator knows), or
operator-side reasoning in a Code-facing prompt. Code gets only
what it needs to do the work.

If the chat-side draft contains operator-facing context that
explains the choice of approach, keep that in chat as preamble
around the prompt block, not inside the prompt block.

## Continuation vs. new session

New session: launch block + prompt block.
Continuation in active session: prompt block only, marked
"continuation."

Don't bundle a continuation into a launch block — it confuses the
operator about whether to open a new terminal.

## Large content placement

When the work involves substantial content (drafted documents,
captured wiki pages, design specs), the operator saves the file
directly to the repo and the Code session commits it. Don't embed
hundreds of lines of content inside a Code prompt for Code to
write to disk — it's friction.

The prompt then says "read `<path>` and commit with message
`<message>`" rather than reproducing the content.

## Recovery awareness

The operator's recovery patterns (from OPERATING.md):
- Halts have reasons; the operator fixes the root cause and
  relaunches
- `Shift+Tab` toggles auto-accept mid-session if permission
  fatigue sets in
- `context-pacing` (in most project presets) lets Code pause
  cleanly at natural stopping points

When drafting prompts, structure work so a clean stopping point
exists between major steps. Sessions that can't be paused mid-way
(everything-or-revert) are fragile; sessions with clean stopping
points compose better with `context-pacing`.

## Code's Bash tool — known constraints

Code's Bash tool runs commands through a static analyzer before
executing. The analyzer rejects commands it can't reason about,
which surfaces as `Contains shell syntax (string) that cannot be
statically analyzed` errors.

Known rejected constructs:
- `$(...)` command substitution
- Some `for`/glob control flow constructs

When drafting prompts that involve loops or substitution, expect
Code may need to flatten to plain sequences of commands. Don't
specify exact loop syntax in the prompt; describe what needs to
happen and let Code pick the shape that passes the analyzer.

The `time` keyword has also surfaced as problematic in some
contexts (treated as not a reserved word). When timing matters,
measure outside the script rather than wrapping commands with
`time`.

## Deviation flagging discipline

When Code's approach diverges from what the prompt specified —
even when the deviation is an improvement — Code names the
change, the reason, and leaves the call with the operator. The
principle applies uniformly: technical refinements (choosing a
better flag or command) and procedural deviations (skipping a
step the prompt described) both get surfaced.

Examples from past sessions:

- **`X` vs `x` in `setfacl`** — Code spotted that `rwX` (capital
  X) was better than `rwx` (which would set execute bit on every
  file). Flagged explicitly. Working as intended.

- **`usermod -L` vs generated password** — Code locked the
  password instead of generating one as the prompt specified.
  Missed initially; corrected via operator feedback.

- **WP_DEBUG_DISPLAY refinement** — `fail loud during dev`
  convention pointed at `WP_DEBUG_DISPLAY=true`, which broke
  page rendering by outputting notices before `<!DOCTYPE html>`.
  Code corrected to `WP_DEBUG=true, WP_DEBUG_LOG=true,
  WP_DEBUG_DISPLAY=false` — errors log to `debug.log`, not to
  browser output. The fail-loud intent is preserved via the log
  path. The deviation was reasoned correctly without needing a
  separate rule for it.

When drafting prompts, include a line in the conventions reminder
section telling Code to flag deviations explicitly. The pattern
is established; reinforce it.

## Common deprecation noise to expect

Plugins running on WordPress 6.7+ surface
`_load_textdomain_just_in_time` notices when they call translation
functions before the `init` action. Kadence Blocks and similar
plugins do this. These notices appear in `debug.log` and (if
`WP_DEBUG_DISPLAY=true`) in rendered output, but they are
unrelated to the work being done in the session — third-party
plugin issue, not a project bug. Mention this in failure modes
only if the prompt has Code inspecting `debug.log`; otherwise
it's noise to ignore.

## Environment-specific reference

VPS-side environment notes (paths, auth pattern, aapanel quirks,
WP-CLI PHP path) and local DDEV/WSL2 environment notes (paths,
mount mechanism, tooling install) live in
`references/environments.md`. When drafting a prompt for one of
these environments, consult that file for the load-bearing facts.
The prompt body rarely needs to repeat the full set; surface only
the ones that gate the work or override a default assumption.

## Self-check before handing the prompt to the operator

- Launch block uses the right preset and permission mode?
- Launch block puts `claude-mode` flags before `--` and `claude`
  flags after `--`?
- Expected-state line present (for repo-work sessions only)?
- Scope explicit, including out-of-scope?
- Independent assessment instruction present?
- Inherited handoff facts marked as start-checks for Code to
  verify, not asserted as established truth?
- Any secret values? If yes, prompt ends at the gating action,
  not past it?
- Failure modes anticipated for the dangerous steps?
- No operator-side material inside the prompt block?
- Both blocks fenced as code blocks?
- **Prompt block uses a four-backtick outer fence** so any
  inner triple-backtick examples don't terminate it early?
- **Code blocks contain only copyable content** — no `#`
  annotations, no inline commentary, no asides mixed in with
  commands?
- Continuation prompts marked as such?

If any answer is no, fix before handing off.
