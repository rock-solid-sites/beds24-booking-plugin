---
title: "Project Conventions"
tags: ["process", "context", "design-doc"]
sources: []
contributors: ["unknown"]
created: 2026-05-21
updated: 2026-05-21
---


## Design Specification

### spelling and naming

- American spelling throughout.
- Beds24 admin field names use the name visible in the Beds24 admin UI:
  "Insert in HTML <HEAD> bottom" (not `customhead`), "Custom CSS" (not
  `bookingcss`), "Insert Custom HTML in Body" (not `custombody`).

### session rules

**One kind of work per session.** Architecture, code, admin configuration,
WordPress admin, code review, and refactor are different kinds of sessions.
Mixing two kinds surfaces unrelated risks and forces context-switching.
Exception: when two kinds are tightly coupled and splitting them would introduce
coordination costs that outweigh the focus benefit.

**At session end:** Add a retrospective entry if a failure mode was surfaced or
a new rule was established. New entries append to `docs/retrospective.md`.

**Session handoffs do not assert HEAD commit hashes in the body text.** The
"Session N+1 start checks" section uses `git log --oneline -1` to verify HEAD
at session start against live state, not against a hash written at handoff time.

### design target

Booking page design target: Hostelworld-like density, not minimalist. This
applies to room cards, dates, and prices — not to the wider marketing site.

### development stance

**Fail loud during dev** — no graceful degradation fallbacks that hide bugs.

**No time estimates** in plans or handoffs. Coding assistant work doesn't map
cleanly to human-hour estimates.

### startup reading order (every session)

1. `docs/retrospective.md` §Active Rules
2. `docs/session-handoff-{N}.md` (latest)
3. `docs/architecture.md` (when the task affects plugin structure)

### issue tracking (crosslink)

Tracking mode is strict. Create a Crosslink issue before any code change.
Use `crosslink quick "title" -p <priority> -l <label>` to create an issue
and start working on it in one step.

Comment discipline is required — add typed comments at key points during work:
plan, decision, observation, blocker, resolution, result.

