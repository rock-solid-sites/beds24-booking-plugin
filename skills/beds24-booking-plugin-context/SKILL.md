---
name: beds24-booking-plugin-context
description: Use at the start of any Claude Code session on the Beds24 booking plugin project (beds24-booking-plugin repo). Load this skill whenever the conversation involves plugin architecture, project conventions, design decisions, feature planning, project state, or questions about what the plugin does and why it's structured as it is. Also use when the user asks about the conversational working style for this project, what's been settled vs. open, or what the current session should focus on. Activate even if the user hasn't explicitly asked for context — this is the foundational project skill.
---

# Beds24 Booking Plugin — Project Context

A WordPress plugin that renders a Beds24 booking experience using the
Beds24 v2 API for discovery and Beds24's iframe for transactions.
Developer: Rock Solid Sites. Repo: `rock-solid-sites/beds24-booking-plugin`.

---

## Conversational defaults

These govern how to engage in this project. They take precedence over
default conversational patterns.

**Independent assessment first.** When the user proposes a direction or
framing, generate an independent take before analyzing its implications.
The independent take may agree, refine, or push back — all three are
useful. Skipping this step and analyzing the implications of a
possibly-wrong frame produces work that defends the wrong shape of the
problem.

**No premise validation.** Don't open with "good question," "you're
absolutely right," or similar. When you agree with a premise, state the
agreement as part of the substantive response, not as a preamble.

**Independent estimates.** When the user provides a number, an estimate,
or a scope framing, generate your own version independently before
comparing. If they differ, surface the difference. If they match, say so
explicitly.

**Explicit confidence levels.** Mark claims as high / moderate / low /
unknown, with the basis: documentation read, code inspected, prior session
evidence, primary source verified, inference, or assumption. "I think" and
"probably" are acceptable paired with a confidence level.

**Named assumptions.** When a claim depends on something not verified in
the current session, state the assumption rather than letting it pass as
fact. Critical for claims about API behavior, library conventions, or
platform specifics.

**Verification against primary sources.** When a claim depends on outside
facts, refer to documentation or live verification rather than memory. If
verification hasn't been done but should be, say so.

**Accuracy over agreeableness.** Direct disagreement and explicit
uncertainty are more useful than smooth alignment. Pushback that turns
out to be wrong is better than agreement that turns out to be wrong —
pushback gets corrected in the next exchange.

**Document access before discussion.** When a document's content matters
to a response, verify you have access before reasoning about the content.
If the document isn't accessible, ask for it by name: "I need to see
`<filename>`. Could you upload it?"

---

## Three design principles

1. **Search filters by date only.**
2. **The plugin handles discovery; Beds24 handles transactions.**
3. **Content lives in WordPress.**

Full statements and reasoning: `docs/architecture.md` §"Three design
principles".

---

## Project conventions

See `CLAUDE.md` §"Project conventions" and §"One kind of work per
session". Key points: American spelling, no time estimates, Beds24 admin
field names, Hostelworld-like density for booking pages, fail loud
during dev, retrospective entries at session end.

---

## Architecture

The plugin owns discovery (search form, room results, cart accumulation)
and hands off to Beds24's iframe at the Confirm Booking button for
transactions. Full component specs, data sources, and design decisions:
`docs/architecture.md`.

---

## Two-repo structure

The project uses two repositories with distinct scopes:

- **`beds24-booking-plugin`** (this repo) — the plugin: search form,
  room results, cart accumulation, Beds24 API integration, block
  registration. Plugin Code sessions live here.
- **`tripn-sites`** — per-property site design artifacts: handoff
  documents, child theme specs, content briefs. VPS-side build
  sessions use tripn-sites' per-property handoffs as input.

The styling contract bridges them: the plugin emits DOM structure;
per-property themes emit visual presentation (colors, typography,
accent treatments). Per-property design decisions live in tripn-sites,
not in this repo.

**Plugin Code sessions should not expand scope into site-design work.
That is tripn-sites' territory.** If a session starts pulling in
per-property visual decisions or child theme details, it has drifted
into the wrong repo's concerns.

---

## What the plugin does NOT do

No payment processing, no booking creation, no booking sync, no refunds,
no confirmation emails. These constraints follow from design principle 2.
Full list and reasoning: `docs/architecture.md` §"What the plugin does
not do".

---

## Active rules (summary)

The retrospective (`docs/retrospective.md`) maintains active process
rules. The most frequently relevant:

- **Measurements vs inferences** — verify inferences from prior sessions
  before building on them
- **Cheapest falsifying test first** — before complex proposals, find the
  simplest test that would falsify the premise
- **Mockup-first validation** — test the mockup against the live
  environment before designing a new approach
- **Read documents before browser state** — handoff docs are authoritative;
  browser tabs from prior sessions are leftover state

---

## References

For content beyond this skill's summary:

| Topic | File |
|---|---|
| Full architecture, component specs, design decisions | `docs/architecture.md` |
| Why this project exists, predecessor pivot | `docs/architecture-pivot-decision.md` |
| Active process rules | `docs/retrospective.md` §"Active Rules" |
| Current project state | `docs/session-handoff-{N}.md` (latest) |
| API client patterns | `beds24-api-work` skill |
| Property rollout | `beds24-property-rollout` skill |
