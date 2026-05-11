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

These answer recurring questions. Future sessions defer to these unless a
principle is explicitly revisited.

1. **Search filters by date only.** The search form is two date pickers
   and a Search button. No guest picker. Capacity is communicated per
   room card and chosen per card.

2. **The plugin handles discovery; Beds24 handles transactions.** The
   boundary is the Confirm Booking button. Everything before it is
   WordPress-rendered; everything after (guest details form, payment,
   booking creation) is Beds24-rendered inside an iframe.

3. **Content lives in WordPress.** Room descriptions, photos, and amenity
   labels are managed in the WordPress plugin admin. Beds24 is the
   property management backend; WordPress is the content management
   frontend.

---

## Project conventions

- **American spelling** throughout.
- **No time estimates** in plans or handoffs. Coding assistant work
  doesn't map cleanly to human-hour estimates.
- **Use Beds24 admin field names** when communicating about Beds24
  configuration (e.g., "Insert in HTML \<HEAD\> bottom", not `customhead`).
- **Design target:** Hostelworld-like density, not minimalist. The
  mockup at `docs/mockup.html` is the canonical visual reference for
  the booking-page DOM structure and layout — what the plugin emits.
  Layout and functionality carry across properties; visual presentation
  (colors, typography, accent treatments) is per-property and is
  captured in the tripn-sites repo's per-property handoff documents.
- **Fail loud during dev** — no graceful degradation fallbacks that hide
  bugs.
- **At session end:** add a retrospective entry if a failure mode was
  surfaced or a new rule was established. Use `### YYYY-MM-DD — Title`
  headers; append to `docs/retrospective.md`.

---

## One kind of work per session

Architecture, code, admin configuration through Chrome, WordPress admin
through MCP, code review, and refactor are different kinds of sessions
even when they share a Claude Code mode. Mixing two kinds surfaces
unrelated risks and forces context-switching mid-session.

**Exception:** when two kinds are tightly coupled and splitting them would
introduce coordination costs that outweigh the focus benefit.

---

## Architecture summary

The plugin owns:
- **Search form** — two date pickers, Search button, date validation
- **Room results** — one card per room, rendered from WordPress content
  plus live API data (availability, pricing)
- **Cart accumulator** ("Your Stay") — accumulates room selections;
  dorm beds use quantity input, private rooms use Add/Remove toggle
- **Confirm Booking button** — constructs a multi-room URL and opens
  Beds24's iframe

Beds24 owns (inside the iframe): guest details form, payment, booking
creation, confirmation emails, booking management.

**Multi-room URL pattern:**
```
https://beds24.com/booking3.php?propid={id}&checkin={date}&checkout={date}
  &sr1-{roomId}=N&naa1-1-{roomId}=N  [repeat per cart item]
```

**Data sources:**
- Beds24 v2 API — live availability and pricing (`GET /properties`,
  `GET /inventory/rooms/offers`)
- WordPress plugin admin — room descriptions, photos, amenity labels

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

- Process payments or store credit card data
- Call `POST /bookings` (Beds24's iframe creates bookings)
- Sync bookings out of Beds24
- Handle refunds, cancellations, or modifications
- Send confirmation emails (Beds24 Auto Actions handle this)

These constraints follow from the discovery-transaction boundary.
Future scope-up pressure defers to design principle 2.

---

## Active rules (summary)

The retrospective (`docs/retrospective.md`) maintains ~27 active process
rules. The most frequently relevant:

- **Measurements vs inferences** — verify inferences from prior sessions
  before building on them
- **Cheapest falsifying test first** — before complex proposals, find the
  simplest test that would falsify the premise
- **Mockup-first validation** — test the mockup against the live
  environment before designing a new approach
- **Read documents before browser state** — handoff docs are authoritative;
  browser tabs from prior sessions are leftover state

Full active rules: `references/retrospective-active-rules.md`

---

## References

When questions go beyond this summary:

| File | When to read |
|---|---|
| `references/architecture.md` | Full architectural reasoning, component specs, design decisions, known unknowns |
| `references/architecture-pivot-decision.md` | Why this project exists; what the predecessor project was; what ported forward |
| `references/retrospective-active-rules.md` | All active process rules with establishment dates |

For current project state, check `docs/session-handoff-{N}.md` (latest).
For API client details, use the `beds24-api-work` skill.
For property rollout, use the `beds24-property-rollout` skill.
