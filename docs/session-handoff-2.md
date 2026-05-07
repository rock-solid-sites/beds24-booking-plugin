# Session 2 Handoff — 2026-05-07

## What this session did

Session 2 was documentation work: Claude Code modes documented as an upstream
reference and a project guide; seven project presets written and validated in
`.claude-mode.json`; operator-facing content migrated from `CLAUDE.md` into a
new `OPERATING.md`; rationale and install-instruction content trimmed from all
tooling docs; stale Session-2 references updated to Session-3 throughout.

No architecture drafting was done. No Crosslink adoption was run. Both remain
deferred to Session 3.

---

## Files produced

| File | Purpose |
|---|---|
| `docs/tooling/claude-code-modes-reference.md` | Faithful upstream summary grounded in fragment content; Installation section omitted, upstream pointer added at end |
| `docs/tooling/claude-code-modes.md` | Project guide — phase-to-mode mapping, operational notes; read every session; "Why" rationale trimmed to one sentence |
| `.claude-mode.json` | Seven validated project presets; `defaultBase: "chill"` |
| `OPERATING.md` | Operator guide — launch commands, permission-mode table, session conventions, recovery patterns |

## Files updated

| File | Changes |
|---|---|
| `CLAUDE.md` | Modes docs reference updated; Tooling section trimmed to essentials; OPERATING.md added to file map; operator-facing content removed (numbered-sessions convention, predecessor project narrative, Repository section, Tooling adoption staging); Session-2 references updated to Session-3 |
| `docs/tooling/crosslink.md` | Modes mention updated; feature list → one-liner; "Why Crosslink" section removed; "Adoption sequence" → one sentence; "Risk and fallback" removed; Status header line removed; Session-2 references → Session-3 |

---

## `.claude-mode.json` — validated presets

All seven presets assembled successfully via `claude-mode <preset> --print`.

| Preset | Agency | Quality | Scope | Modifiers | Use for |
|---|---|---|---|---|---|
| `architecture` | surgical | architect | narrow | methodical, context-pacing | Architecture / design sessions |
| `v1-build` | autonomous | architect | unrestricted | context-pacing | V1 plugin build from design doc |
| `feature` | autonomous | pragmatic | adjacent | context-pacing | Feature extension on existing code |
| `rollout` | collaborative | minimal | narrow | context-pacing | Per-property Beds24 admin configuration |
| `bugfix` | collaborative | minimal | narrow | context-pacing | Fixes for known issues on deployed plugin |
| `review` | collaborative | architect | narrow | readonly | Adversarial review / read-only audits |
| `docs` | surgical | minimal | narrow | methodical | Doc-only sessions |

`defaultBase: "chill"` applies to all presets. Built-in presets invoked from
this directory also use the chill base unless `--base` is specified explicitly.

---

## Discrepancies between README and fragment content

1. **`explore` preset bundles `readonly`** — the README says "no file
   modifications" for `explore` but does not state that the `readonly`
   modifier is included. The `readonly` modifier fragment is the only thing
   that actually enforces no-file-write behavior; the collaborative/architect/narrow
   axes alone don't prevent writes. The project's `review` preset makes this
   explicit by listing `readonly` in the config.

2. **Session prompt named "methodical" as an agency axis value** — the session
   prompt's proposed phase-to-mode table said "methodical / architect / narrow"
   for the Architecture phase. "Methodical" is a modifier name and a preset name
   but not an agency axis value. The correct axis is **surgical** (the agency
   fragment file is `surgical.md`). The final mapping uses the correct axis
   value.

3. **`partner` agency nuance** — the README one-liner ("pair-of-equals") doesn't
   fully capture the fragment. The fragment makes a meaningful distinction:
   commit decisively on execution choices (craft), defer to user on direction
   choices (priorities, scope). The reference doc captures this.

4. **Preset-bundled modifiers** — the README doesn't explicitly document which
   modifiers are bundled into which presets. From reading the fragments and
   testing with `--print`, the bundling appears to be: `methodical` preset
   bundles the `methodical` modifier; `debug` bundles `debug`; `director`
   bundles `director` (which includes bold framing); `explore` bundles
   `readonly`. This is inferred, not documented.

---

## Was `methodical` the right mode for this session?

This session used the default Claude Code system prompt (not invoked via
`claude-mode`). The session was doc-only: reading upstream content, writing
two reference files, writing a config, updating two existing docs.

Per the phase-to-mode mapping produced this session, doc-only work maps to
`claude-mode docs` (surgical/minimal/narrow + methodical modifier). For this
particular session, `architecture` (surgical/architect/narrow + methodical +
context-pacing) would also have fit — the reference doc required careful
synthesis and attention to detail, which architect quality covers. Either
would have been appropriate. The session ran without a `claude-mode`
invocation and produced clean output, so the absence didn't cause a problem.

---

## OPERATING.md — migration decisions

Four items moved from `CLAUDE.md` to `OPERATING.md`:

| Item | Framing rationale |
|---|---|
| Numbered-sessions convention | Operator decision about numbering. Code doesn't assign session numbers. |
| Predecessor project section | Historical narrative. Code needs the project purpose, not the porting record. Brief pointer left in CLAUDE.md. |
| Repository section (URL, license, distribution) | Code uses git; it doesn't need the repo URL or license details to do its work. |
| Tooling section's adoption staging and upstream repo URLs | Process history. Code needs to know the tools and where their docs are, not the adoption timeline. |

`OPERATING.md` also adds content that was never in `CLAUDE.md`:
- Launch command pattern with `--permission-mode` table per session type
- Session prompt conventions (expected-state lines, how to update them)
- Recovery patterns (halt behavior, permission fatigue, context pacing)

### Borderline cases

**"Predecessor project" section.** The pivot decision and "what ported forward"
are useful context for Code when making architectural decisions (avoid
reintroducing predecessor patterns). Resolution: migrated the narrative,
left a 3-line pointer in CLAUDE.md pointing to `docs/architecture-pivot-decision.md`
and `OPERATING.md`. The pivot-decision doc itself stays in Code's "Read when
task requires it" section of the file map.

**Tooling section.** Tool names and doc paths stay (Code needs to know where
its guides are); adoption staging and upstream repo URLs migrate (Code doesn't
need process history or upstream URLs). The trimmed section is 6 lines vs.
the original 14.

**"No time estimates" convention.** Borderline — could be seen as operator
process. Kept in CLAUDE.md because it shapes Code's output in plans and
handoffs. Code is the thing that writes plans and handoffs; this tells Code
what not to produce.

### Structural notes

Nothing in the migration broke CLAUDE.md's coherence. The document flows
from "Read before acting" through behavioral instructions to project constraints.
No dangling headers. The "Predecessor project" section survived as a 3-line
pointer — not a dangling header, meaningful pointer.

OPERATING.md body: 74 non-blank lines (100 total with blanks).

---

## Documentation trim pass

Cuts made to existing files across the trim passes. Files produced in this
session (reference doc, project guide) had their rationale trimmed during
drafting; those trims are part of how those files turned out, not a separate
pass.

| File | What was removed |
|---|---|
| `crosslink.md` | Seven-bullet feature list → one-liner; "Why Crosslink" section removed; "Adoption sequence" → one sentence; "Risk and fallback" section removed; Status header line removed; Session-2 references → Session-3 |
| `CLAUDE.md` | Three stale "Session 2" references updated to "Session 3" (mechanical only) |

### Rationale-overlap check (OPERATING.md vs. claude-code-modes.md)

No duplicate reasoning found. OPERATING.md's permission-mode table answers
"auto or default?" (operator decision). claude-code-modes.md's phase table
answers "which preset?" (Code's working mode). Different questions, no overlap
to resolve.

### Post-Session-3 cleanup flags

`crosslink.md` still has content that will want revisiting once Crosslink is
actually adopted:
- **"What Crosslink replaces"** — lists manual practice substitutions. If
  adoption reveals exceptions, the list updates. If adoption goes well, it
  stays as-is.
- **"Adoption sequence"** — one sentence; will want updating once Session 3
  completes.
- **Document title** ("Tooling Decision: Crosslink as Workflow Engine") — still
  decision-framing. After adoption, rename candidate: "Crosslink — Workflow
  Engine Reference."

---

## Repo state at session end

- Branch: `main`
- HEAD: `246ea11` (Further trim crosslink.md: remove feature list, Why section, Status line)
- Tag: `v0.0.2-modes` (on `b563bc1`, per project convention small post-session
  commits belong to the previous session's tag)
- Remote: pushed and in sync with `origin/main`
- Working tree: clean (`.claude/` is gitignored)

---

## Session 3 scope

Three items remain:

1. **Crosslink adoption.** Run `crosslink init`, verify Windows compatibility,
   configure `.crosslink/rules/` with retrospective rules, run the round-trip
   verification. If adoption fails, document why and fall back to manual workflow.
   See `docs/tooling/crosslink.md` for the adoption plan.

2. **`docs/architecture.md` drafting.** Use the `/design` workflow (if Crosslink
   adopted) or draft manually from `docs/architecture-prep.md` and
   `docs/architecture-pivot-decision.md`. The architecture document was originally
   planned for Session 2; modes documentation was sufficient scope for one session
   and it moved to Session 3.

3. **`docs/v1-plan.md`.** Once the architecture doc is drafted, the V1 plan
   follows. No plan exists yet.

Session 3 should start with `claude-mode architecture` and verify Crosslink
installs on Windows before anything else.
