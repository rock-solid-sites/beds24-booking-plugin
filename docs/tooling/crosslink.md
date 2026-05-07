# Tooling Decision: Crosslink as Workflow Engine

**Date:** 2026-05-07
**Status:** Adopted; Session 3 runs the round-trip verification.
**Decision authority:** Project owner

This document records the adoption of Crosslink as the project's primary
workflow tool and how to use it.

## What Crosslink is

Crosslink is a CLI issue tracker and workflow engine built specifically
for AI-assisted development. It provides:

- **Persistent session memory** through handoff notes and breadcrumbs
  that survive context compression
- **Behavioral hooks** that run on every Claude Code action and enforce
  project rules without manual prompting
- **Design document workflow** (`/design`) that produces validated,
  codebase-grounded design documents
- **Implementation orchestration** (`crosslink kickoff plan` and
  `kickoff run`) that drives single-agent or multi-agent
  implementation against design documents
- **Knowledge management** with searchable design and reference
  documents
- **Multi-agent coordination** through distributed locking via git
- **Drift detection** that increases reminder frequency when an
  agent's behavior diverges from project norms

Repository: `forecast-bio/crosslink` (forked from
`dollspace-gay/chainlink`). Active development. MIT licensed.

## Why Crosslink

Crosslink covers session memory and workflow in one tool: breadcrumbs replace
hand-written handoff files, behavioral hooks enforce retrospective rules at
tool-call time, and `/design` + `kickoff` drive design-driven implementation.
Adoption reasoning is in the commit history if needed.

## Adoption sequence

Adoption runs in Session 3: `crosslink init`, rules configuration from the
retrospective, and the `/design` round-trip to draft `docs/architecture.md`.
Sessions 3+ use Crosslink as the primary workflow tool.

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

