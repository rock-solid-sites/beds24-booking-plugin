# Claude Code Modes — Reference

A faithful summary of `nklisch/claude-code-modes` grounded in the fragment
files, not just the README. Read this when you need detail the project guide
doesn't carry. The project guide (`claude-code-modes.md`) is the daily
reference; this document answers "what does this value actually say?"

Upstream repository: `https://github.com/nklisch/claude-code-modes`

---

## What it is and why it exists

Claude Code ships with a single default system prompt — a compromise designed
to be safe and inoffensive across all use cases. That prompt tells Claude to
be minimal, ask before acting, and stay narrowly scoped. Those defaults are
appropriate for some tasks (surgical fixes to production code) and actively
harmful for others (building from scratch, refactoring, deep investigation).

`claude-mode` replaces the behavioral layer of Claude Code's system prompt
while preserving the tool instructions, security boundaries, and environment
detection that Claude Code needs to function. The behavioral layer is assembled
from three independent axes (agency, quality, scope) and an optional set of
modifiers. Named presets are shortcuts for common axis combinations.

The tool uses `claude --system-prompt-file` to swap in the assembled prompt.
It spawns Claude Code with direct TTY ownership — no intermediary process.

---

## Installation and updates

**Binary (no Bun required):**

```bash
curl -fsSL https://raw.githubusercontent.com/nklisch/claude-code-modes/main/install.sh | sh
```

Installs to `~/.local/bin/claude-mode`. Verifies SHA-256 checksum against
the release.

**From source (requires Bun):**

```bash
git clone https://github.com/nklisch/claude-code-modes.git
cd claude-code-modes
bun install
bun link
```

**Updating (binary install only):**

```bash
claude-mode update              # latest release
claude-mode update --check      # check without installing
claude-mode update 0.2.5        # pin a specific version
```

For source installs: `git pull && bun install`.

---

## The three-axis model

The behavioral layer is composed from three independent axes. Each axis is a
separate markdown fragment. Presets are named combinations of axis values.

### Agency — how much initiative?

**`autonomous`** — Full initiative. Makes architectural decisions, fixes
adjacent issues, chooses patterns without asking for approval. Picks an
approach and goes; reports reasoning after the fact rather than seeking
approval beforehand. When it needs information, it goes and gets it.

**`collaborative`** — Thinking partner. Explains plans before acting, presents
trade-offs with a recommendation but lets the user choose, surfaces concerns
as it goes, summarizes decisions after completing a piece of work.

**`surgical`** — Executes precisely what was asked. Nothing more, nothing less.
Does not fix adjacent issues, does not reorganize, does not refactor callers.
Asks for clarification if the request is ambiguous. Minimizes blast radius.

**`partner`** — Pair of equals with different specialties. Commits decisively
on execution choices (naming, structure, idiom, library use) — these are its
specialty. Defers to the user on direction choices (priorities, scope, what
counts as done). Keeps mental models in sync by stating its understanding
before non-trivial work. Flags assumptions and ambiguities rather than
guessing. Expects adversarial code reviews and guardrails as normal practice.

Note: the README's one-liner for `partner` ("pair-of-equals") undersells the
nuance. The fragment makes a meaningful distinction between execution choices
(commit decisively) and direction choices (surface and defer). This matters
when the user has strong product judgment and wants Claude to own craft while
the user owns priorities.

### Quality — what code standard?

**`architect`** — Write for years, not just today. Proper abstractions — if a
concept appears in multiple places, give it a name and a home. Cohesive modules
with clear boundaries. Error handling at meaningful boundaries (module edges,
I/O, user input, external APIs). Error types that carry useful context. Type
annotations for public interfaces. Comments that explain why, not what. When
making architectural decisions, explains reasoning and proposes alternatives.

**`pragmatic`** — Match existing patterns, improve incrementally. Follows
the patterns already established in the codebase — if the project uses factory
functions, use factory functions. Creates new abstractions only when there's
a clear, immediate benefit (three or more call sites, not a hypothetical).
Follows existing error handling patterns and documentation style. Direct and
practical in output: explains what changed and trade-offs, but concisely.

**`minimal`** — Smallest correct change. No refactoring, no abstractions
beyond what the task requires, no speculative improvements. Three similar
lines of code is better than a premature abstraction. Does not add error
handling for scenarios that can't happen. Extremely terse in output: leads
with the answer, skips filler, keeps responses short. If it can be said in
one sentence, doesn't use three.

### Scope — how far beyond the request?

**`unrestricted`** — Full freedom to create, reorganize, and restructure.
Creates new files, modules, and directories when they make the code better.
Reorganizes existing code when it improves structure. Moves functions to better
homes, splits oversized files, consolidates related logic. Creates infrastructure
(test suites, config files, utility modules) without being asked.

**`adjacent`** — Fix related issues in the neighborhood. Fixes broken imports,
failing tests, outdated type annotations in code it's touching. Prefers editing
existing files over creating new ones. Does not go on project-wide missions.
If a fix requires significant effort outside the immediate area, mentions it
rather than doing it silently.

**`narrow`** — Strictly what was requested. Does not create files unless
absolutely necessary. Does not modify code outside the direct scope. Does not
refactor, rename, or reorganize anything not directly required. If the request
requires more changes than expected, pauses and confirms scope before proceeding.

---

## The base layer

Each prompt assembly begins with a base — a directory with a `base.json`
manifest that declares fragment order and reserves `"axes"` and `"modifiers"`
as insertion points.

**Standard base** — Derived from upstream Claude Code's system prompt. Longer
(~11 fragment files). Includes the full Claude Code behavioral preamble.
Validated against Claude Code v2.1.121.

**Chill base** — Alternative base informed by Anthropic's emotion research
([Emotion Concepts and their Function in a Large Language Model](https://transformer-circuits.pub/2026/emotions/index.html)). Shorter
(~6 fragment files, ~65% of standard size). Calmer framing, no ALL-CAPS
emphasis, includes worked examples, and establishes a priority hierarchy. The
research found that Claude's internal confidence state affects output quality,
and that standard prompt framing can activate anxious/brooding states.

Invoke with `--base chill`, or set as default in `.claude-mode.json`:

```json
{ "defaultBase": "chill" }
```

Custom bases are also supported — see the Customization section.

---

## The modifier layer

Modifiers are behavioral overlays appended after the axis fragments. Multiple
modifiers can be combined. All modifier names below are the exact file names
in `prompts/modifiers/`.

| Modifier | What it adds |
|---|---|
| `bold` | Confidence framing: trust your instincts, lead with conviction, write idiomatic code without defensive over-engineering |
| `context-pacing` | Pause at natural stopping points rather than rushing as context fills up; partial-but-clean over complete-but-broken |
| `debug` | Investigation-first: understand the problem before reaching for fixes; trace data flow, cite file paths and line numbers, share findings even without a confirmed fix |
| `director` | Orchestration mode: load context, delegate to sub-agents, own the outcome; model-selection guidance (Opus for architecture, Sonnet for features, Haiku for lookups); verify agent output before accepting |
| `methodical` | Step-by-step: complete each step fully before the next; ask for clarification rather than guessing; when done, say so and stop |
| `readonly` | Read-only mode: no file creation, editing, moving, or deleting; no state-modifying commands; explains what it would do instead |
| `speak-plain` | Terse by default; trusts user to ask for more; DEEP token as opt-in for broad investigation with file-path citations |
| `tdd` | Test-driven by default: failing test first, minimum code to pass, refactor with tests green; tests are permanent fixtures, not throwaway scripts; explicit prototyping opt-out |

### The `bold` modifier and emotion research

The README has an unusually detailed explanation of `bold` grounded in
Anthropic's RLHF findings: post-training made Claude more hedging and
self-doubting. The `bold` modifier counters this by activating Claude's
confidence about its own capability — not by removing caution at system
boundaries, but by removing anxious defensiveness in routine craft decisions.

The `director` preset bundles `bold` framing in its design for the same
reason: a director who hedges is a bad director.

---

## Built-in presets

| Preset | Agency | Quality | Scope | Notes |
|---|---|---|---|---|
| `create` | autonomous | architect | unrestricted | Building from scratch |
| `extend` | autonomous | pragmatic | adjacent | Extending existing code |
| `safe` | collaborative | minimal | narrow | Surgical production changes |
| `refactor` | autonomous | pragmatic | unrestricted | Structural cleanup |
| `explore` | collaborative | architect | narrow | Read-only investigation |
| `debug` | collaborative | pragmatic | narrow | Root-cause investigation |
| `methodical` | surgical | architect | narrow | Step-by-step craftsmanship |
| `director` | collaborative | architect | unrestricted | Sub-agent orchestration |
| `partner` | partner | pragmatic | adjacent | Pair programming |
| `none` | — | — | — | Strip all behavioral instructions |

Four presets use the chill base by default: `debug`, `methodical`, `director`,
and `partner`. All others use the standard base unless `--base chill` is passed
or `defaultBase` is set in config.

`explore` bundles the `readonly` modifier — the README's description ("no file
modifications") is only possible because readonly is applied, even though the
README doesn't state this explicitly.

`methodical` bundles the `methodical` modifier. `debug` bundles the `debug`
modifier. `director` bundles the `director` modifier (which includes bold
framing).

---

## Customization

### Override a single axis from a preset

```bash
claude-mode create --quality pragmatic     # Architect structure, pragmatic code quality
claude-mode safe --scope adjacent          # Cautious, but fix nearby issues
```

### Compose from scratch

Defaults to collaborative/pragmatic/adjacent for unspecified axes:

```bash
claude-mode --agency autonomous --quality architect --scope narrow
```

### Add modifiers

```bash
claude-mode create --modifier bold
claude-mode create --context-pacing
claude-mode create --readonly
claude-mode create --modifier ./my-rules.md    # Custom file path
```

`--context-pacing` and `--readonly` are shorthand flags; other modifiers use
`--modifier <name-or-path>`.

### Append to the system prompt

```bash
claude-mode create --append-system-prompt "Use Rust, not TypeScript"
```

### Print the assembled prompt

```bash
claude-mode explore --print
```

### Config file — `.claude-mode.json`

Place in the project root (or `~/.config/claude-mode/config.json` for global
config). All `config` subcommands accept `--global` to target the global file.

```json
{
  "defaultBase": "chill",
  "defaultModifiers": ["team-rules"],
  "modifiers": {
    "team-rules": "./prompts/team-rules.md"
  },
  "axes": {
    "quality": {
      "team-standard": "./prompts/team-quality.md"
    }
  },
  "bases": {
    "my-base": "./path/to/base/dir"
  },
  "presets": {
    "team": {
      "agency": "collaborative",
      "quality": "team-standard",
      "scope": "adjacent",
      "modifiers": ["team-rules"]
    }
  }
}
```

- **`defaultBase`** — base applied to all invocations from this directory
- **`defaultModifiers`** — modifiers applied to every invocation
- **`modifiers`** — named modifiers referencing markdown files
- **`axes`** — custom axis values (override built-in axis fragment)
- **`bases`** — named bases referencing directories with `base.json` manifests
- **`presets`** — named presets; `agency`, `quality`, `scope` take exact
  axis-value names; `modifiers` takes an array of modifier names

Custom preset names must not collide with built-in preset names: `create`,
`extend`, `safe`, `refactor`, `explore`, `debug`, `methodical`, `director`,
`partner`, `none`.

CLI management:

```bash
claude-mode config init
claude-mode config add-preset architecture --agency surgical --quality architect --scope narrow
claude-mode config add-modifier context-pacing    # adds to defaultModifiers
claude-mode config show
```

---

## Sub-agent behavior

**General-purpose agents** (the default when Claude delegates work) inherit
the full `claude-mode` system prompt via Claude Code's fork mechanism. Axis
settings carry through.

**Named specialists** (Explore, Plan, etc.) have hardcoded system prompts and
run on their own models (Explore uses Haiku). They do not inherit behavioral
tuning.

**Custom agent definitions** (markdown files in `agents/` directories) use
whatever you write in the file body — they won't inherit axes. Include
behavioral instructions directly in custom specialist definitions if needed.

---

## Limitations

- **Environment info is static.** Git status, branch name, and platform info
  are captured once at launch and baked into the prompt. Branch switches or
  staged files during a session require a restart.
- **Named sub-agents ignore your prompt.** General-purpose agents inherit;
  named specialists do not (see above).
- **MCP server instructions are unaffected.** Claude Code delivers MCP
  instructions via message attachments, independent of the system prompt.
- **Preset name collisions.** Custom preset names in `.claude-mode.json` must
  not match any built-in preset name. Behavior for collisions is unspecified.
- **`--version` is standalone-only.** Combining it with other flags exits
  non-zero. Use `claude-mode -- --version` to pass `--version` to Claude
  Code itself.
