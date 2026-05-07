# Session 3 Handoff — 2026-05-07

## What this session did

Session 3 adopted Crosslink as the project's workflow engine. `crosslink init`
ran, hooks and rules deployed, project-specific rules written, round-trip
verification (session end → session start) confirmed memory persists.
Architecture drafting was deferred to Session 4 per the original plan.

---

## What was adopted

**Crosslink 0.8.0.** CLI issue tracker and session memory engine.

- `.crosslink/` initialized: `hook-config.json`, 35 rule files in `rules/`,
  `.gitignore` for machine-local state
- `.claude/settings.json` deployed: Claude Code hook configuration (5 hooks +
  heartbeat + crosslink_config)
- `.claude/commands/` deployed: 14 slash command files
- `.claude/mcp/` deployed: 3 MCP server files (agent-prompt-server, knowledge,
  safe-fetch)
- `.mcp.json` at repo root: project-level MCP server registration
- `.crosslink/rules/project.md`: project-specific rules (Beds24 field naming,
  verify-before-debugging, measurements vs. inferences for third-party behavior)
- Tracking mode: `relaxed` (solo project; strict team-gate not appropriate)

---

## Database commit-vs-ignore decision

**Gitignored.** Following crosslink upstream's default.

The session prompt's default was "commit the database." But the override condition
applies: `crosslink init` itself generates a `.gitignore` that gitignores
`issues.db`. Upstream recommendation followed.

Crosslink's inner `.crosslink/.gitignore` does not gitignore `issues.db` —
crosslink supports committing it in team setups. But for this project, the
machine-local default is simpler and avoids binary diffs in the history.

If this decision causes problems (e.g., loss of issue history after a machine
switch), revisit and switch to committing.

---

## Tracking mode chosen at init

`relaxed`. Changed from crosslink's default `strict` in `hook-config.json`.

The strict mode gates `git commit` behind an open issue. For a solo developer
doing infrastructure and documentation work, that gate adds friction without
value. Relaxed mode makes crosslink available for session memory and issue
tracking without blocking edits.

---

## Init quirks and surprises

1. **Non-interactive mode skipped hooks on first run.** Running `crosslink init`
   in Claude Code's non-interactive environment deployed rules but not hooks,
   commands, or `.claude/settings.json`. Required a second run with `--force
   --skip-signing --python-prefix "python"` to complete deployment. (Documented
   in `crosslink.md` adoption summary.)

2. **cpitd auto-install fails on Windows.** The `python3` command resolves to
   the Microsoft Store alias on Windows, not the real Python at
   `/c/Python312/python`. `cpitd` couldn't be auto-installed. The
   `pre-web-check.py` hook degrades gracefully without it. Install manually if
   prompt-injection detection is needed: `pip install cpitd` (using
   `/c/Python312/python`).

3. **tracker_remote WARN on every command.** Benign. Crosslink defaults to
   "origin" when `tracker_remote` isn't configured. Multi-agent feature; ignore.

4. **`.gitignore` had a corrupted `.claude/` entry.** The entry from a prior
   session was written as UTF-16 LE (null bytes between each character), making
   it invisible to git. Fixed by truncating to the last clean line and rewriting
   in UTF-8 before running `crosslink init`.

---

## Rules format

Clear from the installed files — markdown prose injected into agent prompts.
No YAML schema to learn. `project.md` is the standard location for project
rules. Crosslink's `prompt-guard.py` hook injects the relevant rules before
each prompt.

No upstream consultation required. The deployed `global.md` and `rigor.md`
files showed the format immediately.

---

## Common operations verified

All four operations from the session prompt's verification checklist passed:

| Operation | Result |
|---|---|
| `crosslink session start` | Session #1 started; no prior breadcrumbs (first run) |
| `crosslink session action "..."` | Breadcrumb recorded |
| `crosslink quick "..." -p high -l infrastructure` | Issue #1 created |
| `crosslink session end --notes "..."` then `session start` | Handoff notes restored; issue #1 visible |

---

## Repo state at session end

- Branch: `main`
- HEAD: `b6cbd03` (Update crosslink.md to reflect adopted status)
- Tag: `v0.0.3-crosslink` on `b6cbd03`
- Remote: pushed and in sync with `origin/main`
- Working tree: clean

---

## Session 4 scope

Three items:

1. **`docs/architecture.md` drafting.** Use `crosslink session start` at the
   top of the session, then draft from `docs/architecture-prep.md` and
   `docs/architecture-pivot-decision.md`. The `/design` flow in Crosslink is
   available (`.claude/commands/design.md` is deployed) if it fits the task.

2. **`docs/v1-plan.md`.** Once the architecture doc is drafted, the V1 plan
   follows. No plan exists yet.

3. **cpitd installation (optional).** If prompt-injection protection is wanted,
   run `pip install cpitd` using `/c/Python312/python`. Low priority.

Session 4 should start with `claude-mode architecture` per the phase-to-mode
mapping.
