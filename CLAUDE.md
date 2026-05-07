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
3. **`docs/session-handoff-{N}.md`** (latest) — current project state
   (or use `crosslink session start` once Crosslink is adopted in
   Session 2).
4. **`docs/architecture.md`** — the architecture and design principles.
   Read before making any decision that affects how the plugin is
   structured or what it owns vs. delegates to Beds24. (Drafted in
   Session 2; until then, see `docs/architecture-prep.md` and
   `docs/architecture-pivot-decision.md`.)
5. **`docs/architecture-prep.md`** if it exists and is non-empty.
   Captures architectural thinking that has been done but not yet
   landed in `architecture.md`. Read before any session that involves
   architectural decisions or builds features.
6. **`docs/tooling/crosslink.md`** — Crosslink workflow and commands.
7. **Current plan doc** if one exists (e.g., `docs/v1-plan.md` during
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
- Numbered sessions are full work sessions that progress the project.
  Small post-session commits (rule additions, doc fixes) belong to
  the previous full session and are not numbered as their own
  session.

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
- `docs/session-handoff-{N}.md` — current state (or use `crosslink
  session start` once Crosslink is adopted)
- `docs/architecture.md` — architecture and design principles (when
  drafted in Session 2)
- `docs/architecture-prep.md` — pre-architecture-doc captured thinking
  (until architecture.md absorbs it)
- `docs/tooling/crosslink.md` — Crosslink workflow and commands
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

**Archived (do not use as current source of truth):**
- `docs/archive/*` — superseded plans and proposals.

## Predecessor project

This plugin supersedes the booking-page CSS+JS project at
`https://github.com/TripN-Chill-Zone/booking-page`. The pivot decision
and reasoning are recorded in `docs/architecture-pivot-decision.md`.

What ported forward: the retrospective rules, the design language and
mockup, the Beds24 admin learnings, the Beds24 v2 API spike findings.

What did not port forward: the CSS, JS, and helper files that styled
Beds24's iframe; the GitHub Actions deployment chain; the
astrongpresence.com hosting dependency. The new architecture renders
its own DOM and ships via WordPress plugin installation.

## Tooling

This project uses two tools that shape how sessions run:

**Claude Code modes** sets the system prompt for the session. The project
has named presets in `.claude-mode.json`; see `docs/tooling/claude-code-modes.md`
for the phase-to-mode mapping and default invocation. The upstream reference
is `docs/tooling/claude-code-modes-reference.md`. Repository: `nklisch/claude-code-modes`.

**Crosslink** is the project's session memory and workflow engine.
Adoption is staged: Session 1 (this scaffold) does not use Crosslink;
Session 2 runs the adoption process and verifies fit; Sessions 3+ use
Crosslink as the primary workflow tool. Full reasoning, command
reference, and usage notes live in `docs/tooling/crosslink.md`. The
repository is `forecast-bio/crosslink`.

Future sessions read both tooling documents at session start so the
in-session conversation knows what tools are in use without having to
discover them.

## Repository

- **Repo:** `https://github.com/rock-solid-sites/beds24-booking-plugin`
- **License:** GPL-2.0-or-later (required for WordPress plugin
  ecosystem compatibility, including potential WP.org submission)
- **Distribution:** GitHub releases (manual ZIP install or Git
  Updater for automated updates). WP.org submission is a possible
  future step.

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
