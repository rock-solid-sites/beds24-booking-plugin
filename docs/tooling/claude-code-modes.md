# Claude Code Modes — Project Guide

**Read every session.** Short by design; use the reference doc for detail.

Upstream reference: `https://github.com/nklisch/claude-code-modes`
Config file: `.claude-mode.json` at repo root

---

Modes shape Claude Code's behavior to match the session's task. The mapping
below is the project's primary reference.

## Default invocation

```bash
claude-mode safe --base chill --context-pacing
```

Or with the project config loaded:

```bash
claude-mode rollout
```

`safe` (collaborative / minimal / narrow) is the conservative default — use it
when the session's scope isn't yet clear or before switching to a more specific
preset.

---

## Phase-to-mode mapping

The mapping below is the primary reason this document exists. Each row answers:
*for sessions with this focus, what mode fits best?*

| Phase | Mode | Axes | Notes |
|---|---|---|---|
| Architecture / design | `claude-mode architecture` | surgical / architect / narrow | Project preset. Adds `methodical` + `context-pacing` to the built-in `methodical` preset's axes. Step-by-step execution, architect-quality design, narrow scope. |
| V1 build (from scratch) | `claude-mode v1-build` | autonomous / architect / unrestricted | Project preset. Net-new code from a design doc — the canonical `create` case. Adds `context-pacing`. |
| Feature extension | `claude-mode feature` | autonomous / pragmatic / adjacent | Project preset. Extending existing plugin code without restructuring it. Matches existing patterns. Adds `context-pacing`. |
| Property rollouts | `claude-mode rollout` | collaborative / minimal / narrow | Project preset. Live property data — explains plan, makes smallest change, stays narrow. Adds `context-pacing`. |
| Bug fixes (deployed plugin, known issue) | `claude-mode bugfix` | collaborative / minimal / narrow | Project preset. Same posture as rollout. Use `debug` (next row) when the issue isn't yet understood. |
| Bug fixes (unknown root cause) | `claude-mode debug --base chill` | collaborative / pragmatic / narrow | Built-in preset. Investigation-first: understand before fixing. Narrows to confirmed root cause, then fixes. |
| Refactor | `claude-mode refactor --base chill --context-pacing` | autonomous / pragmatic / unrestricted | Built-in preset. Intentional structural cleanup — free to move files and reorganize. |
| Read-only investigation / adversarial review | `claude-mode review` | collaborative / architect / narrow | Project preset. No file modifications. Explains what it would do instead. Architect quality means thorough analysis. |
| Doc-only sessions | `claude-mode docs` | surgical / minimal / narrow | Project preset. Narrow scope, minimal output, step-by-step execution. |

### Notes on the mapping

**`architecture` vs. `methodical`:** The project's `architecture` preset is
`methodical` (surgical/architect/narrow) plus the `methodical` modifier and
`context-pacing`. The built-in `methodical` preset bundles the `methodical`
modifier already; the project version adds context-pacing on top and sets
chill base via `defaultBase` in the config.

**`bugfix` and `rollout` are the same config.** Two different phase names
point to the same axis values and modifiers. The distinction is semantic — it
communicates intent in the session handoff and invocation, not a different
behavioral mode.

**`debug` for unknown issues.** The `debug` preset uses pragmatic quality
(match existing patterns) rather than minimal (smallest change). This is
deliberate — when you're investigating, you may need to add temporary
instrumentation or test paths, which minimal quality would resist. Pragmatic
quality allows this while keeping the scope narrow.

**`explore` vs. `review`:** The built-in `explore` preset (collaborative /
architect / narrow) bundles readonly behavior — the upstream description says
"no file modifications." The project's `review` preset is the same composition
with the `readonly` modifier made explicit. For adversarial review of existing
code, `review` and `explore --base chill` are functionally equivalent; the
project preset name makes the intent clear in session logs.

---

## Operational notes

**Config file sets `defaultBase: "chill"`.** The `.claude-mode.json` at the
repo root sets the chill base for all invocations from this directory — both
built-in presets and project presets. You do not need to pass `--base chill`
explicitly when using the project presets.

**Print the assembled prompt before trusting it:**

```bash
claude-mode architecture --print
claude-mode v1-build --print
```

Useful for verifying that the right fragments assembled. Also confirms that
modifier names in the config resolved correctly.

**Passing flags to Claude Code:**

```bash
claude-mode v1-build -- --verbose
claude-mode v1-build -- --model sonnet
```

The `--` escape hatch passes remaining flags through to `claude`.

**Session start vs. mid-session mode changes:** Environment info (git status,
branch, platform) is captured once at launch. If you switch branches or stage
files mid-session, restart claude-mode. Switching modes mid-session requires
a restart.

**Sub-agents:** General-purpose agents spawned by Claude inherit the behavioral
tuning. Named specialists (Explore, Plan) do not — they have hardcoded prompts.
This is expected behavior; see the reference doc for detail.

---

## Preset names — project presets vs. built-in presets

| Project preset | Equivalent built-in invocation |
|---|---|
| `architecture` | `claude-mode methodical --base chill --context-pacing` |
| `v1-build` | `claude-mode create --base chill --context-pacing` |
| `feature` | `claude-mode extend --base chill --context-pacing` |
| `rollout` | `claude-mode safe --base chill --context-pacing` |
| `bugfix` | `claude-mode safe --base chill --context-pacing` |
| `review` | `claude-mode explore --base chill --readonly` |
| `docs` | `claude-mode --agency surgical --quality minimal --scope narrow --modifier methodical --base chill` |

The project presets exist so that the invocation in a session prompt is
self-documenting and doesn't require remembering the axis flags.
