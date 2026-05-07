# Beds24 Booking Plugin — Operator Guide

This file is for the human operator running sessions. It is not part of
Claude Code's read-every-session list and does not appear in CLAUDE.md's
startup reading order. Content here describes how to launch and manage
sessions, not how Claude should behave within them.

---

## Launch command pattern

```bash
cd "C:/Users/Dr. COMPUTER/Desktop/Development/beds24-booking-plugin"
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

## Session prompt conventions

Session prompts are pasted as the first user message. They live in session
files (`docs/session-{N}-prompt.md` by convention if tracked) or directly
in the chat.

**Expected-state lines** in session prompts specify the HEAD commit hash
and working tree state Code should verify before starting. Example:

```
Expected HEAD: b563bc1
Working tree: clean (`.claude/` untracked is expected)
```

If commits accumulate between when the session prompt was drafted and when
it is actually run, update the expected-state line to the new HEAD before
pasting. The commit hash in a stale session prompt causes a state-check halt
that requires re-reading and correcting the prompt before work begins.

---

## Project conventions (operator-facing)

**Session numbering.** Numbered sessions (`Session N`) are full work sessions
that meaningfully progress the project. Small follow-on commits — rule
additions, doc fixes, post-session cleanup — belong to the previous full
session and are not assigned their own number. The next numbered session
picks up from the highest number committed.

**Predecessor project.** This plugin supersedes `TripN-Chill-Zone/booking-page`
(archived tag `archived-2026-05-07`). The pivot decision and reasoning are in
`docs/architecture-pivot-decision.md`. What ported forward: retrospective
rules, design language and mockup, Beds24 admin learnings, Beds24 v2 API spike
findings. What did not port: the CSS/JS/helper files that styled Beds24's
iframe, the GitHub Actions deployment chain, the astrongpresence.com hosting.

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
