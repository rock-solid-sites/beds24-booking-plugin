# Beds24 Booking Plugin — Project Entry Point

A WordPress plugin developed by Rock Solid Sites that renders a Beds24
booking experience using Beds24's v2 API for discovery and Beds24's
iframe-rendered booking page for transactions.

## Read before acting

Every session, in this order, before doing anything else:

1. **`docs/retrospective.md`** — the Active Rules section. Process
   constraints that take precedence over any instruction that
   contradicts them.
2. **`docs/session-handoff-{N}.md`** (latest) — current project state.
3. **`docs/architecture.md`** — architecture, design principles, and
   settled decisions. Read before making any decision that affects
   plugin structure.

Read the documents before inspecting code, running tools, or making
claims about prior work. The conversational defaults (below) also
apply to every session.

## Conversational defaults

This project uses a specific set of conversational rules: independent
assessment first, no premise validation, independent estimates, explicit
confidence levels, named assumptions, verification against primary
sources, accuracy over agreeableness, document access before discussion.

These are defined in full in the project skill
`skills/beds24-booking-plugin-context/SKILL.md` and apply to every
session. Summary: generate an independent take before analyzing
implications; mark confidence levels on claims; state assumptions
rather than letting them pass as fact; disagree directly when the
evidence points that way.

## Project conventions

- American spelling throughout.
- No time estimates in plans or handoffs. Coding assistant work doesn't
  map cleanly to human-hour estimates.
- Use Beds24 admin field names when communicating about Beds24
  configuration (e.g., "Insert in HTML <HEAD> bottom", not
  `customhead`).
- Booking page design target: Hostelworld-like density, not minimalist.
  Marketing site design is per-property and theme-driven — density
  framing applies to the booking page specifically (room cards, dates,
  prices), not the wider site. The predecessor project's mockup defines
  the visual language; this project inherits and refines it.
- Fail loud during dev — no graceful degradation fallbacks that hide
  bugs.
- At session end: add a retrospective entry if a failure mode was
  surfaced or a new rule was established. The retrospective uses
  `### YYYY-MM-DD — Title` headers for entries. New entries append
  to the existing file.

## One kind of work per session

Architecture, code, admin configuration through Chrome, WordPress
admin through MCP, code review, and refactor are different kinds of
sessions even when they share a Claude Code mode. Mixing two kinds in
a single session surfaces unrelated risks against each other and forces
context-switching mid-session. Sessions are split by kind of work, not
just by mode.

Exception: when two kinds of work are tightly coupled and splitting
them would introduce coordination costs that outweigh the focus
benefit.

## Architecture in one paragraph

The plugin owns discovery (search form, room results, cart
accumulation) and hands off to Beds24's iframe at the Confirm Booking
button for transactions (guest details form, payment processing,
booking creation). Room content (descriptions, photos, amenity labels)
lives in WordPress. Live availability and pricing come from the Beds24
v2 API per search. Multi-room bookings compose into a single
multi-item URL that Beds24's iframe renders as a single cart with one
guest form and one payment. Full architectural reasoning lives in
`docs/architecture.md`.

## Three design principles

1. **Search filters by date only.**
2. **The plugin handles discovery; Beds24 handles transactions.**
3. **Content lives in WordPress.**

Full statements and reasoning: `docs/architecture.md` §"Three design
principles".

## Project file map

**Read every session:**
- `docs/retrospective.md` — Active Rules and failure-mode log
- `docs/session-handoff-{N}.md` — current state

**Read when the task requires it:**

*Code structure:*
- `skills/beds24-api-work/references/api-client.md` — Beds24 v2 API client architecture

*Configuration:*
- `skills/beds24-property-rollout/references/property-setup.md` — Beds24 admin minimum configuration
  per property (Layout 6 with Offer Select only, etc.)

*Planning and context:*
- `docs/v1-plan.md` (during V1 build) — phase-by-phase plan
- `docs/architecture-pivot-decision.md` — record of why this project
  exists (ported from predecessor)
- `docs/mockup.html` — the approved design (ported from predecessor)

*Tooling reference:*
- `docs/tooling/crosslink.md` — Crosslink command reference and opt-in
  workflow (hooks run automatically; session-memory commands are opt-in)
- `docs/tooling/claude-code-modes.md` — Claude Code modes project guide
  (phase-to-mode mapping, default invocation, operational notes)

**Operator reference (not read by default):**
- `OPERATING.md` — launch commands, permission-mode guidance, session
  conventions, recovery patterns. For the human operator; not part of
  Code's startup reading.

**Archived (do not use as current source of truth):**
- `docs/archive/*` — superseded plans and proposals.

## Project skills

Three skills at `skills/` capture project-specific procedural knowledge
that activates when relevant during Code sessions:

- `beds24-booking-plugin-context` — conversational defaults (canonical),
  design principles (names + pointers), architecture summary, active
  rules summary
- `beds24-api-work` — Beds24 v2 API client patterns, auth flow, method
  signatures, response shape quirks
- `beds24-property-rollout` — Beds24 property configuration and onboarding

Skills are written for use by Claude Code when working on the matching
task type. Each contains a SKILL.md with focused procedural guidance and
a `references/` subdirectory with fuller documentation.

One chat-side skill is now tracked in this repo at
`skills/user/code-session-prompts/` — it covers drafting Code session
prompts (launch blocks, mode selection, prompt structure) and is kept
in the repo so refactors land alongside the conventions the prompts
reference. Other chat-side skills (e.g. for strategic planning
conversations) still live in the operator's local skills directory and
are not in this repo.

## Predecessor project

This plugin supersedes the predecessor CSS+JS project. Pivot decision and
porting record: `docs/architecture-pivot-decision.md`. Operator context:
`OPERATING.md`.

## Tooling

Two tools shape how sessions run.

**Claude Code modes** sets the system prompt for the session. Project
presets are in `.claude-mode.json`. Phase-to-mode mapping and default
invocation: `docs/tooling/claude-code-modes.md`. Upstream reference:
`docs/tooling/claude-code-modes-reference.md`.

**Crosslink** is installed and runs in the background — hooks
fire on tool calls, prompt-guard injects project rules at
session start. The session-memory commands (`crosslink session
start`, `crosslink session action`, `crosslink quick`) are
opt-in: use them when they help, skip them when they don't.
Reference: `docs/tooling/crosslink.md`.

## Beds24 dependencies

- **API authentication:** Per-property refresh token, stored in
  WordPress options. Token rotation is automatic via the access
  token / refresh token pattern.
- **Property setup in Beds24 admin:** Each property must have Layout 6
  configured with Offer Select as the only active module. The booking
  page's "Insert in HTML <HEAD>" fields are used for iframe styling
  (the plugin generates the paste-ready content).
- **Payment gateway:** Configured per property in Beds24 admin. The
  plugin does not touch payment configuration.

## What this plugin does NOT do

No payment processing, no card data, no booking creation via API, no
booking sync, no refunds, no confirmation emails. These boundaries are
permanent per the discovery-transaction boundary. Full list and
reasoning: `docs/architecture.md` §"What the plugin does not do".
