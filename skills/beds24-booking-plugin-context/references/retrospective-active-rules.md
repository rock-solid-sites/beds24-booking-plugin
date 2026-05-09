# Active Rules — Beds24 Booking Plugin

Extracted from `docs/retrospective.md`. These are process constraints
learned from prior sessions. Full historical entries (with context and
concrete costs) are in the source file.

---

### Measurements vs inferences
When reading a prior session's plan or review, identify which claims are
measurements (tested evidence from code/DOM inspection) and which are
inferences (conclusions drawn from earlier sessions). Inferences that gate
the scope of current work must be verified in the current session before
being built upon. *(Established: 2026-04-19)*

### Cheapest falsifying test first
When a proposal's complexity feels disproportionate to the problem's
description, identify the cheapest test that would falsify the proposal's
central premise and run it before writing the proposal. If the premise
doesn't survive, the proposal isn't needed. *(Established: 2026-04-19)*

### Let the bug get smaller before the fix gets bigger
When successive rounds of review each surface new information that makes
the problem look different, resist the urge to scope up the fix. Usually
the problem is getting smaller, not larger. *(Established: 2026-04-19)*

### Retest inherited limitations
When a prior session documents a tool limitation alongside other failures,
retest the limitation independently before adopting it as a constraint. The
limitation may have been a symptom of the co-occurring failure, not a
standalone issue. *(Established: 2026-04-20)*

### Understand the data model before bulk operations
Before setting values across a multi-entity UI, confirm whether the UI
presents a filtered view of shared data or isolated per-entity data.
Applying changes to "all items" when only a subset belongs to the current
entity causes cross-contamination that is tedious to reverse.
*(Established: 2026-04-20)*

### Automate what you can verify, delegate what you can't see
When automation cannot inspect the result of an action (e.g., identifying
image content from filenames), delegate that step to the human rather than
guessing. *(Established: 2026-04-20)*

### Verify saves before building on them
After any programmatic write to a Beds24 admin field, reload the page
and confirm the value persisted before making further changes. Silent
save failures (character limits, tag stripping) waste all subsequent
work built on the assumption the save succeeded. *(Established: 2026-04-20)*

### Rewrite, don't patch
When making significant layout changes to CSS, start from the current
clean file, make all edits, and deploy the complete file. Do not
incrementally append patches to an inline field. *(Established: 2026-04-20)*

### Read documents before browser state
At the start of a new session, read all uploaded handoff/project
documents before inspecting browser tabs, running tools, or making
claims about prior work. Browser tabs from previous sessions are
leftover state, not authoritative context. *(Established: 2026-04-20)*

### Verify file accessibility before debugging
After uploading any JS/CSS file to the VPS, navigate to its URL and
confirm a 200 response with correct content before testing functionality.
*(Established: 2026-04-20)*

### Plan the flow before coding the pieces
When building a multi-step user flow, map out the complete flow and
identify architectural constraints before implementing individual pieces.
*(Established: 2026-04-20)*

### One observer, one guard
Never attach multiple MutationObservers to the same root with
`subtree:true` when any callback modifies the DOM. Use a single observer
with an `isModifying` guard flag. *(Established: 2026-04-20)*

### Verify the full deployment chain
After uploading files to the VPS, verify every reference to those files
is updated — admin fields, WordPress HTML blocks, widget CONFIG.
*(Established: 2026-04-20)*

### Hide measurable content with opacity, not display
Use `opacity:0; position:absolute` instead of `display:none` when content
inside an iframe needs to be hidden but still rendered for measurement.
*(Established: 2026-04-20)*

### Inject overrides via JS when CSS load order fails
When an external CSS file's rules are overridden by platform-generated
inline styles, move those overrides into a JS-injected `<style>` tag.
*(Established: 2026-04-20)*

### New filenames bypass server cache
When OpenLiteSpeed serves stale content despite purge attempts, the only
reliable workaround is uploading with a new versioned filename.
*(Established: 2026-04-20)*

### Test CSS against real DOM before deployment
Before extracting CSS from a mockup for production, verify the mockup's
DOM matches the live page. *(Established: 2026-04-20)*

### Use viewport resize for media query testing
CSS media queries respond to viewport width, not container width. Use
Chrome DevTools device mode or resize the actual browser window.
*(Established: 2026-04-20)*

### Use the platform's field names with the user
When referring to admin fields, use the names visible in the platform UI
(e.g., "Insert in HTML <HEAD> bottom") not internal field IDs.
*(Established: 2026-04-20)*

### Test the simple thing before building the complex thing
When a working reference implementation exists, test it against the real
environment before designing a more complex architecture.
*(Established: 2026-04-21)*

### Know your actual viewport
When an iframe embeds your page, your CSS viewport is the iframe's width,
not the user's screen width. *(Established: 2026-04-21)*

### Mockup-first validation
Before writing any proposal that would change layout or behavior covered by
a design mockup, test whether applying the mockup's CSS and JS directly to
the live environment produces the desired result. *(Established: 2026-04-19)*

### Show the design artifact, not just the plan
In adversarial review, request the design mockup as a first-round artifact
alongside the implementation plan. *(Established: 2026-04-19)*

### Use JS Unicode escapes for non-ASCII in Beds24 script fields
When embedding emoji or other non-ASCII characters in Beds24 admin fields
that contain `<script>` content, use JS Unicode escape sequences
(`🛏`) rather than literal characters. Beds24 silently strips
non-ASCII on AJAX save. *(Established: 2026-04-21)*

### Acceptance criteria must cover the user's first view
When defining acceptance criteria for a fix, include tests that start from
the user's entry state — fresh page load — not just the mid-interaction
state the fix targets. *(Established: 2026-04-24)*

### Adversarial review of the previous session opens each fix session
Sessions that follow a fix session begin by adversarially reviewing the
previous session's claimed outcomes. The previous session's confidence is
not evidence; measured behavior is. *(Established: 2026-04-24)*

### Fix session prompts include a contract section
Every fix-session prompt includes a one-paragraph contract statement: what
behavior the fix must satisfy from the user's perspective, expressed in
observable terms. *(Established: 2026-04-24)*
