# Tooling Decision: Crosslink as Workflow Engine

**Date:** 2026-05-07
**Status:** Adopted, pending Session 2 round-trip verification.
**Decision authority:** Project owner

This document records the decision to adopt Crosslink as the project's
primary workflow tool, and the reasoning that led there.

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

Three things made the adoption decision clean:

**Session continuity.** The predecessor project relied on
hand-written `session-handoff-N.md` files read at the start of each
session. That worked but produced friction: context-recovery prompts,
rule-count reconciliation, redundant document reads. Crosslink's
breadcrumbs and handoff notes are designed to remove that friction by
storing context in a structured database alongside the project.

**Behavioral discipline.** The predecessor project accumulated 27
active retrospective rules through trial and error. Each rule existed
as text that the assistant was supposed to read and apply. Crosslink's
behavioral hooks enforce rules at tool-call time — `work-check.py`
runs before every write/edit/bash, `post-edit-check.py` runs after
every edit, `pre-web-check.py` runs before web fetches. Hooks cannot
be forgotten.

**Workflow coverage.** Crosslink's `/design` and `kickoff` workflows
cover the same conceptual territory as standalone workflow plugins
like ed3d-plan-and-execute (codebase exploration before drafting,
gap analysis between design and current state, structured execution
with verification). Adopting Crosslink for session memory and getting
the workflow engine in the same package reduces the number of tools
the project depends on.

The decision was reached after reviewing both Crosslink's hooks
documentation and its design workflow guide directly. Confidence:
high that the tool covers what the project needs. Moderate that the
specific shape of the tool's interactions will fit smoothly without
adjustment — that's what Session 2's round-trip verifies.

## Adoption sequence

**Session 1 (scaffold):** No Crosslink. Files go in via manual git
operations. Nothing in the scaffold depends on Crosslink working.
This is deliberate — if Crosslink turns out to be a poor fit, the
project can proceed without it and the scaffolding work is not
wasted.

**Session 2 (adoption):** Run `crosslink init`, configure tracking
mode, customize `.crosslink/rules/` with project-specific norms
ported from the retrospective, and use `/design` to draft
`docs/architecture.md` (which doesn't exist at Session 1's end —
the `/design` round-trip both creates that document and tests
whether Crosslink's design flow fits the project). Outcome decides
whether Crosslink becomes the workflow engine or whether the
project falls back to manual workflow.

**Sessions 3+:** If adopted, Crosslink drives feature work. The
`/design` workflow produces design documents in `.design/`. The
`kickoff plan` and `kickoff run` commands drive implementation.
Behavioral hooks enforce retrospective rules at tool-call time.

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

For this project, Session 2's adoption work includes mapping the
existing retrospective rules to hook configuration where applicable.
Some rules (like "verify saves before building on them") map cleanly
to hook checks. Others (like "let the bug get smaller before the fix
gets bigger") are stance rules that live in CLAUDE.md and don't have
a hook equivalent.

The mapping is not exhaustive on first pass. It improves over time
as new rules emerge and as it becomes clear which rules need
machine enforcement vs. which work fine as text guidance.

## Risk and fallback

The risks of adopting Crosslink:

- **Single-maintainer dependency.** Crosslink is a low-popularity
  tool maintained by a small team or a single maintainer (the
  forecast-bio fork branched from another single-maintainer repo).
  If maintenance lapses, the project would need to fork and
  self-maintain.
- **Windows compatibility.** Crosslink installs via Cargo (Rust
  toolchain). Session 2's adoption work must confirm that it
  installs and runs on the project's Windows setup before the
  project commits to it.
- **Workflow rigidity.** Strong workflow tools can constrain
  exploration. If a session needs to do something the tool wasn't
  designed for, fighting the tool wastes more time than working
  without it.

The fallback if adoption doesn't go well: revert to manual workflow
with hand-written session handoffs and direct text-file management
of the retrospective. The scaffolding from Session 1 doesn't depend
on Crosslink, so falling back loses no scaffolding work.

A second-tier fallback is adopting `ed3d-plan-and-execute` as the
workflow engine instead. This was considered and set aside because
Crosslink covers the same workflow territory while also providing
session memory. If Crosslink fails to fit, ed3d remains an option.
