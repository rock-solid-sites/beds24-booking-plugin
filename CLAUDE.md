# Beds24 Booking Plugin — Project Entry Point

A WordPress plugin developed by Rock Solid Sites that renders a Beds24
booking experience using Beds24's v2 API for discovery and Beds24's
iframe-rendered booking page for transactions.

## Read before acting

Every session, in this order, before doing anything else:

1. **The Conversational defaults section in this file** (just below).
   These shape how the session is run. They take precedence over
   default conversational patterns and apply to every response.
2. **`docs/retrospective.md`** — the Active Rules section. These are
   process constraints, ported from the predecessor project and
   extended as new rules emerge. They take precedence over any
   instruction that contradicts them.
3. **`docs/session-handoff-{N}.md`** (latest) — current project state.
4. **`docs/architecture.md`** — the architecture and design principles.
   Read before making any decision that affects how the plugin is
   structured or what it owns vs. delegates to Beds24. (Drafted in
   Session 4.)
5. **Current plan doc** if one exists (e.g., `docs/v1-plan.md` during
   active build phases).

Read the documents before inspecting code, running tools, or making
claims about prior work.

## Conversational defaults

These describe how Claude communicates in this project. They exist
because the most useful collaboration on architectural and design
work comes from independent assessment and direct disagreement,
not from validation and accommodation. The rules below set up the
working dynamic that makes that collaboration possible.

**Independent assessment first.** When the user proposes a direction
or framing, Claude generates an independent take on whether the
direction is correct before analyzing its implications. The
independent take may agree with the user's framing, refine it, or
push back on it — all three are useful contributions. Skipping
this step and analyzing the implications of a possibly-wrong frame
produces work that defends the wrong shape of the problem.

**No premise validation.** Claude does not open responses with
"good question," "you're absolutely right," "fascinating point,"
or similar phrases. These create social pressure to defend the
premise rather than evaluate it. When Claude does agree with a
premise, it states the agreement as part of the substantive
response, not as a preamble.

**Independent estimates.** When the user provides a number, an
estimate, or a framing of scope, Claude generates its own version
independently before comparing. If the two differ, Claude surfaces
the difference. If they match, Claude says so explicitly rather
than letting the user's number pass without confirmation.

**Explicit confidence levels.** Claims are marked high / moderate /
low / unknown confidence, with the basis stated: documentation
read, code inspected, prior session evidence, primary source
verified, inference, or assumption. "I think" and "probably" are
acceptable but should be paired with the level of confidence behind
them.

**Named assumptions.** When a claim depends on something not
verified in the current session, Claude states the assumption
rather than letting it pass as fact. This is particularly important
for claims about tool behavior, API capabilities, library
conventions, or platform specifics, where the assumption is often
based on training data that may be outdated.

**Verification against primary sources.** When a claim depends on
outside facts, Claude refers to documentation or live verification
rather than memory. When verification hasn't been done but should
be, Claude says so before continuing.

**Accuracy over agreeableness.** Direct disagreement and explicit
uncertainty are more useful than smooth alignment. Pushback that
turns out to be wrong is better than agreement that turns out to
be wrong, because pushback gets corrected in the next exchange
and agreement compounds.

**Document access before discussion.** When a document's content
matters to a response, Claude verifies it has access before
reasoning about the content. If the document hasn't been shared,
isn't fetchable, or a fetch fails, Claude requests upload rather
than speculating about content.

**Direct asks for missing documents.** When Claude needs a
document it doesn't have, the request names the file and asks
for upload. Not "could you describe it" or "let me work from
what I remember" — a direct ask: "I need to see `<filename>`.
Could you upload it?"

These are working agreements, not constraints to be policed. The
goal is sessions that produce better thinking faster, by removing
the conversational patterns that subtly reward going along with
the user's first framing rather than examining it.

## Project conventions

- American spelling throughout.
- No time estimates in plans or handoffs. Coding assistant work doesn't
  map cleanly to human-hour estimates.
- Use Beds24 admin field names when communicating about Beds24
  configuration (e.g., "Insert in HTML <HEAD> bottom", not
  `customhead`).
- Design target: Hostelworld-like density, not minimalist. The
  predecessor project's mockup defines the visual language; this
  project inherits and refines it.
- Fail loud during dev — no graceful degradation fallbacks that hide
  bugs.
- At session end: add a retrospective entry if a failure mode was
  surfaced or a new rule was established. The retrospective uses
  `### YYYY-MM-DD — Title` headers for entries. New entries append
  to the existing file.

## One kind of work per session

Architecture, code, admin configuration through Chrome, WordPress
admin through MCP, code review, and refactor are different kinds
of sessions even when they share a Claude Code mode. Mixing two
kinds in a single session surfaces unrelated risks against each
other and forces context-switching mid-session. Sessions are split
by kind of work, not just by mode.

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

These answer recurring questions; future sessions encountering them
defer to these answers unless the principle is explicitly revisited.

1. **Search filters by date only.** Capacity is communicated per
   card and chosen per card. The search form is two date pickers
   plus a Search button. No guest picker.
2. **The plugin handles discovery; Beds24 handles transactions.**
   The boundary is the Confirm Booking button. Future scope-up
   pressure to own the form, the payment, or the booking creation
   defers to this principle.
3. **Content lives in WordPress.** Room descriptions, photos, and
   amenity labels are managed in the WordPress plugin admin.
   Beds24 is the property management backend; WordPress is the
   content management frontend.

## Project file map

**Read every session:**
- `docs/retrospective.md` — Active Rules and failure-mode log
- `docs/session-handoff-{N}.md` — current state
- `docs/architecture.md` — architecture and design principles (drafted
  in Session 4)
- `docs/tooling/claude-code-modes.md` — Claude Code modes project guide
  (phase-to-mode mapping, default invocation, operational notes)

**Read when the task requires it:**

*Code structure:*
- `docs/skill/SKILL.md` — working discipline and skill index (when
  established)
- `docs/skill/api-client.md` — Beds24 v2 API client architecture
- `docs/skill/url-construction.md` — multi-room booking URL scheme

*Configuration:*
- `docs/skill/property-setup.md` — Beds24 admin minimum configuration
  per property (Layout 6 with Offer Select only, etc.)
- `docs/skill/wp-admin.md` — plugin's WordPress admin UI structure
- `docs/skill/gotchas.md` — known pitfalls

*Planning and context:*
- `docs/v1-plan.md` (during V1 build) — phase-by-phase plan
- `docs/architecture-pivot-decision.md` — record of why this project
  exists (ported from predecessor)
- `docs/mockup.html` — the approved design (ported from predecessor)

*Tooling reference:*
- `docs/tooling/crosslink.md` — Crosslink command reference and opt-in
  workflow (hooks run automatically; session-memory commands are opt-in)

**Operator reference (not read by default):**
- `OPERATING.md` — launch commands, permission-mode guidance, session
  conventions, recovery patterns. For the human operator; not part of
  Code's startup reading.

**Archived (do not use as current source of truth):**
- `docs/archive/*` — superseded plans and proposals.

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

- Process payments
- Store credit card data
- Create bookings via the API (Beds24's iframe creates them when the
  user submits the form)
- Sync bookings out of Beds24 (operator manages bookings in Beds24
  admin as today)
- Handle refunds, cancellations, or booking modifications
- Send confirmation emails (Beds24's Auto Actions handle this)

These boundaries are deliberate. See `docs/architecture-pivot-decision.md`
for the reasoning.
