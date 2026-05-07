---
name: beds24-booking-plugin
description: "Configure, build, and iterate on the Beds24 Booking Plugin WordPress plugin. Use this skill whenever the task involves the plugin's PHP, JavaScript, CSS, WordPress admin UI, Beds24 v2 API integration, room card rendering, booking URL construction, or property setup. Triggers include: any mention of the plugin code, WordPress integration, Beds24 API calls, room discovery, booking URL, or property configuration for the plugin."
---

# Beds24 Booking Plugin — Skill Guide

Working discipline and project reference for the beds24-booking-plugin. Built
from hands-on patterns carried forward from the predecessor project and
extended as the new plugin takes shape.

The skill has two parts:

1. **Working discipline** — how to approach work in this project.
   Read this first. Every session.
2. **Project reference** — design target and architecture reference.
   Grows as the plugin is built.

---

## Part 1: Working discipline

These rules exist because violations have cost time repeatedly. The
`docs/retrospective.md` Active Rules section is the canonical list; this
section states the underlying principles. If a rule seems abstract, the
retrospective entries show the concrete failures that produced it.

### 1.1 Separate measurements from inferences

Every load-bearing claim in a prior session's plan, handoff, or
review is either a **measurement** (verified in-session by
inspecting code or measuring reality) or an **inference**
(conclusion drawn from earlier reasoning).

Inferences that gate the current scope must be re-verified before
they're built on. If a proposal says "we can't do X because Y," and
Y is an inference, test whether Y is still true *this session* before
accepting the scope limit.

The most expensive failures in this project came from sessions
inheriting unverified inferences and spending days on work that
solved a non-problem.

### 1.2 Run the cheapest falsifying test first

When a proposal's complexity feels disproportionate to the problem
described, identify the cheapest test that would falsify the
proposal's central premise, and run it before writing the proposal.

Applied to this project: if the Beds24 v2 API already returns the
data a feature needs, the cheapest falsifying test is a direct API
call before writing the feature. The API is a candidate data source,
not a verified one. The first question in any data-driven feature is:
"does the API actually return this?"

This rule also applies to architecture disputes, tooling claims, and
premise inheritance — if you can falsify it cheaply, do that before
proposing a fix.

### 1.3 Let the bug get smaller before the fix gets bigger

When successive rounds of review each surface information that makes
the problem look different, resist scoping up the fix. Usually the
problem is getting smaller, not larger. A fix that keeps growing
across diagnostic rounds is a sign the original framing was wrong,
not that more intervention is needed.

If round 1 called for a data-layer rewrite, round 2 called for a
new abstraction, and round 3 called for a full architectural change —
pause. Ask the question you haven't asked yet, rather than adding
another layer of architecture.

### 1.4 Verify before debugging

Before investigating why something doesn't work, confirm it's
actually running. This applies broadly:

- **Deployed plugin files:** Page source confirms correct JS/CSS
  versions are enqueued; WordPress admin confirms the plugin is active.
- **Saved admin values:** Reload the settings page and confirm
  persistence. WordPress options API has edge cases; custom tables
  have their own failure modes.
- **API responses:** Log the actual API response before diagnosing
  "the API doesn't return X."
- **Assumed context:** Uploaded docs and handoff notes, not leftover
  browser tabs from prior sessions. Tab state is not authoritative.

If the state you're debugging against isn't the state you think it
is, every downstream step is wasted. See `gotchas.md` for specific
failure modes (when established).

### 1.5 Plan the flow before coding the pieces

For multi-step flows (user flows, API call chains, composed
features), map the complete flow and identify architectural
constraints before implementing pieces. Testing components in
isolation and finding at integration time that they don't compose
is more expensive than upfront planning.

---

## At session end

- Add a retrospective entry if a failure mode was surfaced or a new
  rule was established. Use the template in `docs/retrospective.md`.
- Write or update a handoff note (`session-handoff-{N}.md`) with
  what was done, what's open, and what the next session should start
  with.

---

## Part 2: Project reference

### 2.1 Design target — Hostelworld density

The booking experience should feel like Hostelworld: dense,
information-rich, OTA-style. Not minimalist. See `docs/mockup.html`
for the approved design.

**Room card layout (desktop):**

```
┌─────────────────────────────────────────────┐
│ Room Name                                   │
├─────────┬───────────────────────────────────┤
│         │ Description                        │
│ Photo   │ [tag] [tag] [tag]                  │
│         │                                    │
├─────────┴───────────────────────────────────┤
│ from €XX / night   [qty] [total] [Book →]  │
└─────────────────────────────────────────────┘
```

**Room card layout (mobile):**

```
┌──────────────────────────────┐
│ Room Name                    │
├──────┬───────────────────────┤
│      │ Description            │
│Photo │                        │
├──────┴───────────────────────┤
│ [tag] [tag] [tag] [tag]      │
├──────────────────────────────┤
│ from €XX / night             │
│ [qty] [total] [Book →]       │
└──────────────────────────────┘
```

Breakpoints, responsive thresholds, and final layout measurements
are established as the plugin is built.

---

## Reference files

- `gotchas.md` — known pitfalls with solutions (when established)
