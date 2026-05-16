# Beds24 Booking Plugin — Operator Guide

This file is for the human operator running sessions. It is not part of
Claude Code's read-every-session list and does not appear in CLAUDE.md's
startup reading order. Content here describes how to launch and manage
sessions, not how Claude should behave within them.

---

## Local dev credentials (DDEV only)

WordPress admin — `chillzone.ddev.site`
- Username: `astrongpresencebiz_kixfumj4`
- Password: `DevSession23!`  ← reset 2026-05-16 (Session 23); update here when changed again

---

## Launch command pattern

```bash
cd ~/projects/beds24-booking-plugin
claude-mode <preset>
```

The `.claude-mode.json` at the repo root sets `defaultBase: "chill"` and
defines project presets. Built-in presets also use the chill base from
this directory. No `--base chill` flag needed.

**Passing flags to Claude Code** — use the `--` separator:

```bash
claude-mode v1-build -- --permission-mode auto
claude-mode rollout -- --permission-mode default
```

**Permission mode by session type:**

| Session type | `--permission-mode` | Why |
|---|---|---|
| Documentation, architecture | `auto` | Classifier-gated auto-accept; less interrupt for low-risk writes |
| V1 build, feature extension | `auto` | High-volume file creation; auto-accept reduces friction |
| Refactor | `auto` | Same rationale as build |
| Property rollouts | `default` | Per-action approval; live property data, deliberate |
| Bug fixes (deployed plugin) | `default` | Same as rollout |
| Read-only investigation | `default` or `auto` | Low stakes; either works |

**Per-session preset reference:** `docs/tooling/claude-code-modes.md`.

---

## VPS-side Code launch pattern

Claude Code sessions running on the VPS (via SSH) differ from the
local WSL2 pattern above. No `.claude-mode.json` is available unless
the tripn-sites repo is cloned on the VPS.

```bash
# From wherever the SSH session lands (typically /home/claude-code/)
claude-mode <built-in preset>
```

No `cd` to a project directory is required first. VPS sessions operate
on individual site directories via explicit paths.

Only built-in `claude-mode` presets are available. Approximate
equivalents to the project's custom presets:

| Plugin project preset | Built-in equivalent | Use for |
|---|---|---|
| `architecture` | `methodical` | Design and documentation |
| `v1-build` | `create` | Site build work |
| `rollout` / `bugfix` | `safe` | Live site changes |
| `review` | `explore` | Read-only investigation |

Pass `--base chill` explicitly on the VPS when the chill base is
wanted — no `.claude-mode.json` provides the default:

```bash
claude-mode methodical -- --base chill --permission-mode default
```

---

## Local development environment

Local development runs on DDEV inside WSL2. Full setup, canonical
paths, and migration notes: `docs/tooling/ddev.md`.

Key paths:
- Plugin repo: `~/projects/beds24-booking-plugin`
- Chillzone WordPress site: `~/projects/chillzone`
- Site URL: `https://chillzone.ddev.site`

The Windows-side paths (under `C:\Users\Dr. COMPUTER\Desktop\Development\`)
are stale after the WSL2 migration and should not be used.

Common operations:
- Start/stop DDEV: `ddev start` / `ddev stop` (from inside `~/projects/chillzone`)
- WP-CLI: `ddev wp <command>`
- Project status: `ddev describe`

---

## Session prompt conventions

Session prompts are pasted as the first user message. They live in session
files (`docs/session-{N}-prompt.md` by convention if tracked) or directly
in the chat.

**Session handoffs do not assert HEAD commit hashes in the body text.**
The "Session N+1 start checks" section at the bottom of each handoff
uses `git log --oneline -1` to verify HEAD at session start against
live state, not against a hash written at handoff time. This prevents
staleness when commits land between sessions.

**Expected-state lines** in session prompts are optional. When included,
they specify the HEAD commit hash and working tree state Code should
verify before starting:

```
Expected HEAD: b563bc1
Working tree: clean (`.claude/` untracked is expected)
```

If commits accumulate between when the session prompt was drafted and when
it is actually run, update the expected-state line to the new HEAD before
pasting. A stale commit hash causes a state-check halt that requires
re-reading and correcting the prompt before work begins. For this reason,
many prompts omit the expected-state line and rely on the start-check
pattern instead (`git log --oneline -1` and `git status` as Step 1).

**Continuation prompts.** When a Code session continues (same instance,
follow-up prompt), no launch command is needed — just paste the continuation
prompt. New sessions in a fresh terminal need the launch command. Drafted
prompts that are meant as continuations should be marked as such so the
operator knows not to look for a launch command.

**Operator-side material stays operator-side.** Personal preferences,
environment notes, and operator-side reasoning don't go in Code-facing
prompts. They live in OPERATING.md or the operator's local notes. Prompts
to Code contain only what Code needs to do the work.

**Operator saves files directly when practical.** For large content
(drafted documents, captured wiki pages, design specs), the operator saves
the file directly to the repo and the Code session commits it. Embedding
hundreds of lines of content inside a prompt for Code to write is
unnecessary friction.

---

## Project conventions (operator-facing)

**Session numbering.** Numbered sessions (`Session N`) are full work sessions
that meaningfully progress the project. Small follow-on commits — rule
additions, doc fixes, post-session cleanup — belong to the previous full
session and are not assigned their own number. The next numbered session
picks up from the highest number committed.

**Two-repo structure.** The project uses two repositories:
- `beds24-booking-plugin` (this repo) — plugin development and deployment
- `tripn-sites` — per-property site design artifacts (handoffs, child theme specs, content)

Plugin development work happens in this repo. Site-design work happens
in tripn-sites. The styling contract is the bridge between them —
the plugin emits structure; per-property themes emit visual presentation.
VPS-side site building uses tripn-sites' per-property handoffs as input;
the built WordPress sites are not in any repo.

**Predecessor project.** This plugin supersedes `TripN-Chill-Zone/booking-page`
(archived tag `archived-2026-05-07`). The pivot decision and reasoning are in
`docs/architecture-pivot-decision.md` (historical record, read-once). What
ported forward: retrospective rules, design language and mockup, Beds24 admin
learnings, Beds24 v2 API spike findings. What did not port: the CSS/JS/helper
files that styled Beds24's iframe, the GitHub Actions deployment chain, the
astrongpresence.com hosting.

**Repository.**
- Repo: `https://github.com/rock-solid-sites/beds24-booking-plugin`
- License: GPL-2.0-or-later (required for WP.org compatibility)
- Distribution: GitHub releases (manual ZIP or Git Updater). WP.org submission
  is a possible future step.

---

## Recovery patterns

**Code halts on a stop condition.** Read the halt report. Fix the root cause
(correct expected state, resolve the ambiguity, provide the missing input).
Then relaunch. Don't paste around the halt or ask Code to proceed past it —
halts are there because proceeding would produce bad output.

**Permission fatigue mid-session.** Press `Shift+Tab` to toggle auto-accept-edits
on, reducing per-edit prompts without restarting the session.

**Context filling up.** The `context-pacing` modifier (included in most project
presets) tells Code to pause cleanly at a natural stopping point rather than
rush. A stopping point from Code will say what's done and what remains. Session
handoff with `docs/session-handoff-N.md` picks up where it left off.
