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

This document records how to use Crosslink as the project's workflow tool.

## What Crosslink is

Crosslink is a CLI issue tracker and workflow engine built for
AI-assisted development. Repository: `forecast-bio/crosslink`. MIT licensed.

## Adoption summary (Session 3)

`crosslink init` ran in Session 3. Hooks, commands, and rules deployed. Project
rules written to `.crosslink/rules/project.md`. Tracking mode set to "relaxed"
(solo project; strict team-gate is inappropriate here). Round-trip verification
(session end → session start) confirmed memory persists.

**Init quirk on Windows:** Running in Claude Code's non-interactive environment,
`crosslink init` deployed rules but skipped hooks on the first run. Required
`crosslink init --force --skip-signing --python-prefix "python"` to deploy
`.claude/hooks/`, `.claude/commands/`, `.claude/mcp/`, and `.claude/settings.json`.

**cpitd:** Auto-install failed because Windows redirects `python3` to the Store
alias. Install manually: `pip install cpitd` (using `/c/Python312/python`). The
`pre-web-check.py` hook degrades gracefully if cpitd is absent — it still runs
but skips the prompt-injection detection step.

**tracker_remote warning:** Each crosslink command prints a WARN about
`tracker_remote` not being configured. This is a multi-agent feature; benign for
solo use. Defaults to "origin" automatically.

## What Crosslink replaces

Crosslink is not additive to existing project workflow — it
substitutes for several manual practices:

- **Hand-written session handoffs** become `crosslink session end
  --notes "..."` with breadcrumbs accumulated during the session
- **Rule-count reconciliation** at session start becomes Crosslink's
  automatic context restoration
- **The retrospective as a text file** continues to exist but is
  paired with `.crosslink/rules/` that encode the same rules as
  hooks
- **Manual issue tracking** in `project-pipeline.md` becomes
  Crosslink's issue database with sub-issues and dependencies
- **Hand-written architecture documents** are paired with Crosslink's
  knowledge management for searchable retrieval by future sessions

The text-file artifacts that have served the predecessor project
well do not disappear — they continue to be authoritative for human
reading. Crosslink adds a structured layer underneath that makes the
artifacts machine-actionable.

## What Crosslink does not replace

- **Claude Code modes** continue to control the system prompt for each
  session. Crosslink and modes are orthogonal layers. The project's named
  presets live in `.claude-mode.json`; the phase-to-mode mapping and default
  invocation are documented in `docs/tooling/claude-code-modes.md`.
- **CLAUDE.md** continues to be the project entry point read at the
  start of every session, including the conversational defaults.
- **The retrospective as a text file** stays as the human-readable
  record of failure modes and accumulated learnings. Future sessions
  reading it gain context that hooks alone cannot convey.
- **Architectural decisions** (this file, the pivot decision, future
  ADRs) stay as text files. Crosslink stores them as searchable
  knowledge but the documents themselves are the source of truth.

## Per-session usage notes

When Crosslink is active, every session starts with:

```bash
crosslink session start
```

This restores breadcrumbs from the previous session and shows what
work was in flight. The session ends with:

```bash
crosslink session end --notes "Brief summary of session outcome"
```

During a session, breadcrumbs are recorded for significant decisions
or findings:

```bash
crosslink session action "Verified that GET /properties returns no picture URLs"
```

Issues are created and worked on through the standard CLI:

```bash
crosslink quick "Implement search form with date pickers" -p high -l feature
crosslink session start --issue 12
```

When working on a design-driven feature, the session starts with the
design workflow rather than a quick issue:

```bash
/design "Implement multi-room cart accumulator"
```

The agent explores, asks clarifying questions, drafts a design
document, validates it. Once accepted:

```bash
crosslink kickoff plan .design/multi-room-cart.md
crosslink kickoff run "implement multi-room cart" --doc .design/multi-room-cart.md
```

Detailed command reference is at
`forecast.bio/crosslink/reference/commands.html`.

## Hooks and rules customization

Crosslink installs hooks into `.claude/hooks/` automatically on
`crosslink init`. The five hooks:

| Hook | Fires when | Purpose |
|---|---|---|
| `session-start.py` | Session start/resume | Loads context, restores breadcrumbs |
| `prompt-guard.py` | Every prompt | Injects language-specific best practices |
| `work-check.py` | Before write/edit/bash | Enforces issue tracking, blocks unauthorized git mutations |
| `post-edit-check.py` | After file edits | Stub detection, linting, test reminders |
| `pre-web-check.py` | Before web fetch | Prompt-injection defense on external content |

Hook behavior is controlled by `.crosslink/hook-config.json`. Rules
live in `.crosslink/rules/` as markdown files that hooks consult.

Session 3's adoption work includes mapping the existing retrospective rules
to hook configuration. Some rules (like "verify saves before building on
them") map cleanly to hook checks. Others (like "let the bug get smaller
before the fix gets bigger") are stance rules that live in CLAUDE.md and
don't have a hook equivalent. The mapping improves over time.

