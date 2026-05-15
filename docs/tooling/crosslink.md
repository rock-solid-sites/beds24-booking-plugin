# Crosslink — Workflow Engine

**Adopted:** 2026-05-07
**Status:** Opt-in from Session 7 forward (see below)

## Status: Opt-in tooling

Crosslink was adopted in Session 3 and evaluated in Session 7's
post-Code-session investigation. The evaluation found:
- Session memory (breadcrumbs, last-handoff) works but largely
  duplicates the project's `docs/session-handoff-{N}.md` files
- The prompt-guard hook genuinely injects project rules and
  fires automatically — this is the clearest ongoing value
- The /design workflow is unsuitable for project-level
  architecture (Windows/MSYS bug + scope mismatch)
- The issue tracker has been misused as a session log; feature
  tracking hasn't started

**Decision:** Keep the installation; drop the per-session
ceremony from CLAUDE.md. Use Crosslink commands opt-in when
they're useful (e.g., `crosslink quick` for real feature
issues during V1 build, `crosslink session last-handoff` if
context compression makes the text handoff hard to find).

The hooks continue to run without ceremony. This document is
preserved as reference for opt-in usage.

---

## Adoption notes (Session 3, Windows)

`crosslink init` ran in Session 3. Hooks, commands, and rules deployed.
Project rules written to `.crosslink/rules/project.md`. Tracking mode
set to "relaxed" (solo project).

**Init quirk on Windows:** Running in Claude Code's non-interactive
environment, `crosslink init` deployed rules but skipped hooks on the
first run. Required `crosslink init --force --skip-signing --python-prefix
"python"` to deploy `.claude/hooks/`, `.claude/commands/`, `.claude/mcp/`,
and `.claude/settings.json`.

**cpitd:** Auto-install failed because Windows redirects `python3` to the
Store alias. Install manually: `pip install cpitd` (using
`/c/Python312/python`). The `pre-web-check.py` hook degrades gracefully if
cpitd is absent — it still runs but skips prompt-injection detection.

**tracker_remote warning:** Each crosslink command prints a WARN about
`tracker_remote` not being configured. This is a multi-agent feature;
benign for solo use.

## What Crosslink does not replace

- **Claude Code modes** continue to control the system prompt for each
  session. Crosslink and modes are orthogonal layers. The project's named
  presets live in `.claude-mode.json`; the phase-to-mode mapping is in
  `docs/tooling/claude-code-modes.md`.
- **CLAUDE.md** continues to be the project entry point read at the
  start of every session, including the conversational defaults.
- **The retrospective as a text file** stays as the human-readable
  record of failure modes and accumulated learnings. Future sessions
  reading it gain context that hooks alone cannot convey.
- **Architectural decisions** stay as text files; Crosslink's knowledge
  store is supplemental, not authoritative.

## Hook mapping note

Some retrospective rules map to hook checks (e.g., "verify saves before
building on them" → work-check). Stance rules (e.g., "let the bug get
smaller before the fix gets bigger") live in CLAUDE.md only — there is
no hook equivalent for judgment calls.

Hook behavior: `.crosslink/hook-config.json`. Rules: `.crosslink/rules/`.

For command reference and full documentation:
https://github.com/forecast-bio/crosslink
