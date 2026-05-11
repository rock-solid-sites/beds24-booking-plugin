# Project Retrospective

A living log of failure modes, corrections, and process changes. Each session 
that identifies a non-trivial failure mode or adopts a new working rule adds 
an entry. Historical entries are append-only; the top summary is rewritten 
as needed to reflect current active rules.

## How to use this document

- **Read the Active Rules section before starting work.** These are process 
  constraints that have been learned the hard way.
- **Read the most recent 2-3 entries before writing a proposal.** Recent 
  failure modes are the most likely to still be relevant.
- **At the end of a session that surfaced a failure mode or adopted a rule, 
  add an entry.** Use the template at the bottom of this file.
- **Do not edit or delete past entries.** If a rule is superseded, add a new 
  entry that supersedes it and update the Active Rules section.

---

## Active Rules

These are the current rules extracted from accumulated entries. Each links 
to the entry that established or last revised it.

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
When a prior session documents a tool limitation alongside other failures 
(e.g., extension crash), retest the limitation independently before 
adopting it as a constraint. The limitation may have been a symptom of the 
co-occurring failure, not a standalone issue. *(Established: 2026-04-20)*

### Understand the data model before bulk operations
Before setting values across a multi-entity UI, confirm whether the UI 
presents a filtered view of shared data or isolated per-entity data. 
Applying changes to "all items" when only a subset belongs to the current 
entity causes cross-contamination that is tedious to reverse. 
*(Established: 2026-04-20)*

### Automate what you can verify, delegate what you can't see
When automation cannot inspect the result of an action (e.g., identifying 
image content from filenames), delegate that step to the human rather than 
guessing. A wrong automated guess creates more rework than a brief manual 
step. *(Established: 2026-04-20)*

### Verify saves before building on them
After any programmatic write to a Beds24 admin field, reload the page 
and confirm the value persisted before making further changes. Silent 
save failures (character limits, tag stripping) waste all subsequent 
work built on the assumption the save succeeded. *(Established: 2026-04-20)*

### Rewrite, don't patch
When making significant layout changes to CSS, start from the current 
clean file, make all edits, and deploy the complete file. Do not 
incrementally append patches to an inline field or use string 
replacement on large text blobs in browser textareas. Each iteration 
should produce a complete, self-consistent file. *(Established: 2026-04-20)*

### Read documents before browser state
At the start of a new session, read all uploaded handoff/project 
documents before inspecting browser tabs, running tools, or making 
claims about prior work. Browser tabs from previous sessions are 
leftover state, not authoritative context. Close Claude in Chrome 
tabs at the end of each session. *(Established: 2026-04-20)*

### Verify file accessibility before debugging
After uploading any JS/CSS file to the VPS, navigate to its URL and 
confirm a 200 response with correct content before testing 
functionality. If the file returns 404 or stale content, all 
subsequent debugging is wasted. *(Established: 2026-04-20)*

### Plan the flow before coding the pieces
When building a multi-step user flow (widget → iframe → checkout), 
map out the complete flow and identify architectural constraints 
before implementing individual pieces. Testing pieces in isolation 
then discovering they don't compose wastes more time than upfront 
flow planning. *(Established: 2026-04-20)*

### One observer, one guard
Never attach multiple MutationObservers to the same root with 
`subtree:true` when any callback modifies the DOM. Use a single 
observer with an `isModifying` guard flag. Two observers create 
infinite mutation loops that freeze the page. *(Established: 2026-04-20)*

### Verify the full deployment chain
After uploading files to the VPS, don't just verify the files are 
accessible — also verify that every reference to those files is 
updated. Check the Beds24 admin field, the WordPress HTML block, 
and the widget CONFIG. A correct file on the VPS is useless if the 
page still loads the old version. *(Established: 2026-04-20)*

### Hide measurable content with opacity, not display
When content inside an iframe needs to be hidden from the user but 
still rendered for measurement (height sync, getBoundingClientRect), 
use `opacity:0; position:absolute` instead of `display:none`. 
`display:none` prevents the browser from rendering content entirely, 
making all measurements return 0. *(Established: 2026-04-20)*

### Inject overrides via JS when CSS load order fails
When an external CSS file's rules are overridden by platform-generated 
inline styles (e.g., Beds24's Style panel colors), move those overrides 
into a JS-injected `<style>` tag. JS-injected styles load last and 
reliably win specificity battles. *(Established: 2026-04-20)*

### New filenames bypass server cache
When OpenLiteSpeed (or similar server-side cache) serves stale content 
despite Cloudflare purges, `.htaccess` directives, and server restarts, 
the only reliable workaround is uploading with a new versioned filename. 
Never overwrite an existing file expecting the change to take effect. 
*(Established: 2026-04-20)*

### Test CSS against real DOM before deployment
When developing CSS via a local mockup, verify the mockup's DOM matches 
the live page before extracting CSS for deployment. A mockup built from 
documentation may miss elements, nesting, or classes that affect 
specificity and layout. *(Established: 2026-04-20)*

### Use viewport resize for media query testing
CSS media queries respond to viewport width, not container width. 
Simulating mobile by constraining a container's `max-width` via a 
dropdown does NOT trigger `@media` rules. Use Chrome DevTools device 
mode or resize the actual browser window. *(Established: 2026-04-20)*

### Use the platform's field names with the user
When referring to admin fields, use the names visible in the platform 
UI (e.g., "Insert in HTML <HEAD> bottom") not internal field IDs 
(e.g., `customhead`). The user can't see field IDs and can't act on 
instructions that use them. *(Established: 2026-04-20)*

### Test the simple thing before building the complex thing
When a working reference implementation exists (e.g., a mockup), test 
it against the real environment before designing a more complex 
architecture. If the simple version works, ship it. Complexity needs 
justification from a measured failure, not from an assumed one. 
*(Established: 2026-04-21)*

### Know your actual viewport
When an iframe embeds your page, your CSS viewport is the iframe's 
width, not the user's screen width. Identify the actual rendering 
width early and design for it. A "desktop" layout that never 
activates is wasted work. *(Established: 2026-04-21)*

### Mockup-first validation
Before writing any proposal that would change layout or behavior covered by 
a design mockup, test whether applying the mockup's CSS and JS directly to 
the live environment produces the desired result. Mockups are candidate 
implementations, not just visual references. *(Established: 2026-04-19)*

### Show the design artifact, not just the plan
In adversarial review, request the design mockup as a first-round 
artifact alongside the implementation plan. Reviewing a plan without the 
design target invites reviewers to speculate about architecture in a 
vacuum. *(Established: 2026-04-19)*

### Use JS Unicode escapes for non-ASCII in Beds24 script fields
When embedding emoji or other non-ASCII characters in Beds24 admin 
fields that contain `<script>` content (e.g., "Insert in HTML <HEAD> 
bottom"), use JS Unicode escape sequences (`\uD83D\uDECF`) rather than 
literal characters. Beds24 silently strips non-ASCII on AJAX save. 
Unicode escapes are pure ASCII, survive the save, and are evaluated 
as the correct character when the browser executes the script. 
*(Established: 2026-04-21)*

### Acceptance criteria must cover the user's first view
When defining acceptance criteria for a fix, include tests that 
start from the user's entry state — unselected, uninteracted-with, 
fresh page load — not just the mid-interaction state the fix 
targets. A fix that passes its criteria but leaves the page 
looking wrong on first view is not verified. 
*(Established: 2026-04-24)*

### Adversarial review of the previous session opens each fix session
Sessions that follow a fix session begin by adversarially reviewing 
the previous session's claimed outcomes. For each pass criterion 
the previous session claimed, attempt to falsify it before doing 
new work. The previous session's confidence is not evidence; 
measured behavior is. If a claimed pass survives adversarial 
re-examination, build on it. If it doesn't, surface the discrepancy 
before proceeding. *(Established: 2026-04-24)*

### Fix session prompts include a contract section
Every fix-session prompt includes a one-paragraph contract 
statement: what behavior the fix must satisfy from the user's 
perspective, expressed in observable terms (what they can see, 
do, or click). Acceptance criteria translate the contract into 
specific tests. Implementation details belong in the diagnostic, 
not the contract. The contract is what verification proves; the 
implementation is one path to that proof, but not the only one. 
*(Established: 2026-04-24)*

---

## Entry template

When adding a new entry, use this structure:

```
### YYYY-MM-DD — Short title

**Context:** What was being worked on, what the setup was.

**What happened:** The failure mode, narrated briefly. Include enough 
detail that a future session can recognize the same pattern.

**Root causes:** Numbered list. Be specific about mechanisms, not just 
outcomes.

**Rules established:** New active rules, or links to existing ones 
this entry reinforces.

**Concrete cost:** Time, rework, or other measurable impact. Optional 
but helpful for calibrating future decisions.

**Resolution:** What fixed it, where the fix lives.
```

---

## Entries

### 2026-04-20 — Beds24 navigation limitation was not standalone

**Context:** Session 3 documented that automated navigation between 
Beds24 admin pages causes 502 errors and session drops. This was 
recorded as a known limitation and carried forward as a constraint: 
"navigate manually, then let Claude in Chrome read/write on the 
current page."

**What happened:** Session 4 retested navigation at the user's 
suggestion. Automated navigation via JS `click()` and `form.submit()` 
worked without any 502 errors across multiple page transitions 
(Layout, Style, Content, Developer, Pictures, Room Descriptions). The 
limitation documented in Session 3 was not reproducible.

**Root causes:**
1. The Session 3 navigation failures occurred during the same session 
   where the Claude in Chrome extension entered a bad auth state 
   requiring full reinstall. The 502s were likely caused by the same 
   underlying issue (session corruption, server-side rate limiting 
   during extension instability), not by navigation itself.
2. The limitation was recorded as a standalone constraint rather than 
   being flagged as potentially correlated with the co-occurring 
   extension failure.

**Rules established:** *Retest inherited limitations* — when a prior 
session documents a tool limitation alongside other failures, retest 
the limitation independently before adopting it as a constraint.

**Concrete cost:** Would have required manual navigation for every 
admin page transition in Phase 2, adding friction to every step. 
Retesting took one click and saved dozens of manual navigations.

**Resolution:** Navigation confirmed working. Constraint removed from 
workflow. Handoff doc updated to reflect this.

---

### 2026-04-20 — Photo positions applied to all 53 files across all rooms

**Context:** Setting photo positions on the Beds24 Pictures page. Each 
room section displays all 53 uploaded files (shared pool), not just 
photos belonging to that room. Only photos with a position number 
(not "not used") display in that room's slider.

**What happened:** Automation set all 53 `picval` selects to sequential 
positions (1–53) and saved. This was done for 4 rooms before the user 
reported that every room was now showing all 53 photos. The intended 
behavior was to position only the photos belonging to each specific 
room (e.g., 5 for the Suite, 1 for the Dorm).

**Root causes:**
1. The automation could not see image thumbnails, so it could not 
   distinguish which photos belonged to which room.
2. The automation assumed "set all to positions" was correct without 
   understanding that the 53 files were a shared pool presented in 
   every room section, not 53 room-specific files.
3. No verification step was performed between the first room save and 
   proceeding to the remaining rooms. If the booking page had been 
   checked after the first room, the 53-photo problem would have been 
   caught immediately.

**Rules established:**
- *Understand the data model before bulk operations* — before setting 
  values across a multi-entity UI, confirm whether the UI presents a 
  filtered view or shared data.
- *Automate what you can verify, delegate what you can't see* — when 
  automation cannot inspect the result (image content), delegate to 
  the human.

**Concrete cost:** All 4 rooms had to be reset to "not used" (4 room 
loads + 4 saves), then the user had to manually identify and position 
photos per room. Approximately 10 minutes of rework.

**Resolution:** User manually set correct photo positions per room. 
Automation confirmed final state by reading `picval` selects per room.

---

### 2026-04-20 — Inline CSS field has undocumented character limit

**Context:** Building up the booking page CSS incrementally in Beds24's 
`bookingcss` inline field via Claude in Chrome. Each append appeared 
to save successfully (button clicked, no error).

**What happened:** After reaching ~18-19K characters, subsequent appends 
stopped persisting. The save appeared to succeed — the textarea showed 
the new content, the button clicked, no error message — but on page 
reload the field reverted to the pre-append state. This was only 
discovered after several rounds of "save, reload, check" revealed the 
length wasn't growing. Earlier in the session, a large batch 
replacement also silently failed for the same reason.

**Root causes:**
1. Beds24's server silently rejects or truncates saves above an 
   undocumented character limit (~18-19K) on the `bookingcss` field. 
   There is no client-side validation, maxlength attribute, or error 
   response.
2. The incremental append workflow meant the field grew gradually, and 
   the failure boundary was crossed without any signal. Each "save" 
   felt successful.
3. No verification step between save and next edit — a reload-and-check 
   after each save would have caught the failure immediately.

**Rules established:** *Verify saves before building on them* — after 
any programmatic write to a Beds24 admin field, reload the page and 
confirm the value persisted before making further changes. Silent save 
failures waste all subsequent work.

**Concrete cost:** Approximately 30 minutes of CSS work that didn't 
persist, plus debugging time to identify the cause. Led to the decision 
to move all CSS to the external file, which was the right architecture 
anyway but was forced by the limit rather than planned.

**Resolution:** All aesthetic/layout CSS moved to external file 
(`CSS-base-v2.css`) served via `&cssfile=` parameter. Inline field 
trimmed to 1,545 chars of critical CSS + variable overrides. External 
file has no character limit.

---

### 2026-04-20 — Beds24 strips script/style tags on programmatic save

**Context:** Attempting to save `<script>` content into the `custombody` 
field and `<style>` content into `customheadconfirm` via Claude in 
Chrome (setting textarea value + clicking save button).

**What happened:** Plain text ("test123") saved and persisted correctly. 
Content wrapped in `<script>` or `<style>` tags resulted in empty 
fields on reload. The save appeared to succeed — no error, the textarea 
showed the content — but the tags were stripped server-side.

**Root causes:**
1. Beds24's server-side form handler sanitizes HTML tags from certain 
   fields when submitted via the AJAX form mechanism. The admin UI's 
   own save path handles these tags correctly, but the programmatic 
   path (setting value + dispatching events + clicking submit) triggers 
   a different code path or lacks a flag that bypasses sanitization.
2. The failure was silent — no error response, no visual indication. 
   Only discovered by reload-and-check.

**Rules established:** Reinforces *verify saves before building on 
them*. Also: *know which fields accept HTML tags* — `bookingcss` 
accepts raw CSS (no tags needed), but `custombody` and 
`customheadconfirm` require HTML tags that get stripped 
programmatically. These fields must always be pasted manually by the 
user.

**Concrete cost:** Two rounds of writing + saving + discovering empty 
fields. Approximately 15 minutes. Ongoing cost: user must paste these 
fields manually for every property.

**Resolution:** Documented as a hard constraint. Paste-ready content 
provided to user as copyable text. Added to skill gotchas reference.

---

### 2026-04-20 — #b24scroller is the booking strip, not the room container

**Context:** The execution plan and earlier sessions referenced 
`#b24scroller` as the room card container. The hide/reveal JS was 
designed to observe this element for room node changes.

**What happened:** DOM inspection in Session 5 revealed that 
`#b24scroller` is the booking strip (date pickers, nights selector, 
Book button). The actual room container is `.b24fullcontainer-rooms`. 
The JS would have been watching the wrong element entirely.

**Root causes:**
1. The name "scroller" suggests a scrollable content area, which was 
   interpreted as the room listing. The actual booking strip is a form 
   area that happens to use this ID.
2. The selector was noted in the execution plan as "e.g., 
   `#b24scroller`" with a caveat to "inspect rendered HTML on staging 
   before writing" — but it was carried forward as assumed-correct in 
   subsequent planning.
3. No DOM inspection was performed in Sessions 3 or 4 to verify the 
   selector. It was an inference from Beds24 documentation, not a 
   measurement.

**Rules established:** Reinforces *measurements vs inferences* — the 
selector was an inference that was never verified. Also reinforces the 
skill's guidance to always read `dom-structure.md` before writing 
CSS/JS, which contains the verified selector map.

**Concrete cost:** Would have caused the hide/reveal JS to completely 
fail (observing wrong element, rooms never hidden/revealed). Caught 
before deployment by Session 5 DOM inspection.

**Resolution:** All selectors verified via Claude in Chrome DOM 
inspection. Complete DOM tree documented in 
`docs/skill/references/dom-structure.md`. Execution plan updated with 
correct selectors.

---

### 2026-04-20 — Incremental CSS patching created compounding layout issues

**Context:** Building the booking page design iteratively — starting 
with base styles, then adding layout fixes, then design changes, then 
reordering, each as appended CSS rules to the inline field.

**What happened:** By the end of the session, the CSS had multiple 
layers of rules that conflicted or partially overlapped: the original 
base styles, the "Session 5 design fixes," the "Session 5b layout 
reorder," the "offer section reorder," each targeting the same elements 
with different intents. Some `string.replace()` calls to update earlier 
rules failed silently due to whitespace mismatches, leaving both old 
and new rules active. The resulting page had elements styled by 
contradictory rules.

**Root causes:**
1. Appending CSS patches is additive — old rules aren't removed, 
   they're overridden (or not, depending on specificity). This creates 
   a growing stack of rules where the visual outcome depends on cascade 
   order.
2. Using `string.replace()` on large CSS strings in a browser textarea 
   is fragile. Whitespace, newlines, and encoding differences cause 
   silent match failures.
3. No "clean slate" step — each iteration built on the previous one 
   rather than rewriting from a known-good state.

**Rules established:** *Rewrite, don't patch* — when making significant 
layout changes, start from the current clean CSS file, make all edits, 
and upload the complete file. Do not incrementally append patches to an 
inline field. The external file architecture supports this: edit the 
file, increment the version, upload, test.

**Concrete cost:** Multiple rounds of CSS changes that appeared to save 
but didn't apply, or applied in combination with old conflicting rules. 
The user saw "nothing seems to have changed" at least once. Eventually 
required moving to the external file and starting from a clean base.

**Resolution:** All CSS consolidated into a single external file 
(`CSS-base-v2.css`). Future changes follow the CSS update protocol: 
edit file → increment version → upload → test.

---

### 2026-04-20 — New session hallucinated context from leftover browser tabs

**Context:** Session 5 left Claude in Chrome tabs open (booking page, 
Beds24 admin, WordPress site). The next session (Session 6) started 
with uploaded documents but also saw the open tabs via 
`tabs_context_mcp`.

**What happened:** Session 6 saw the tab showing the booking page 
embedded in an iframe on the WordPress site and immediately began 
diagnosing an "iframe double scroll issue on iOS" — inventing context 
about "previous session changes to the plugin/iframe config" that never 
happened. It made authoritative-sounding claims about what previous 
sessions had done based on inferring from tab URLs rather than reading 
the uploaded documents.

**Root causes:**
1. The new session treated browser tab state as authoritative context 
   about the project, rather than reading the uploaded handoff documents 
   first.
2. Tab URLs contained enough information (booking page URL with 
   `referer=wordpress`) for the session to construct a plausible but 
   incorrect narrative.
3. The session began acting before reading — it proposed investigation 
   and fixes before consulting any of the 7 uploaded documents.

**Rules established:** *Read documents before browser state* — at the 
start of a new session, read all uploaded handoff/project documents 
before inspecting browser tabs, running tools, or making claims about 
prior work. Browser tabs from previous sessions are leftover state, 
not authoritative context. Also: *close Claude in Chrome tabs at the 
end of each session* to prevent this confusion.

**Concrete cost:** Required the user to intervene and correct the 
session's assumptions, breaking the flow at the start of a new context 
window. Minor time cost but undermines trust.

**Resolution:** User corrected the session manually. Added note about 
this pattern to retrospective for future sessions.

---

### 2026-04-20 — Debugging functionality before verifying file deployment

**Context:** Uploading new versions of JS files to the VPS and updating 
Beds24/WordPress references. After each upload, immediately testing the 
booking page functionality.

**What happened:** Multiple rounds of debugging — inspecting DOM state, 
checking postMessage events, reading console errors, testing 
MutationObserver behavior — before discovering the JS file was returning 
404 (not yet uploaded) or serving stale content (cached by 
Cloudflare/LiteSpeed). In one case, a 404 response was cached even 
after the file was uploaded, persisting across refreshes.

**Root causes:**
1. No verification step between "upload file" and "test functionality." 
   The file URL was never checked for a 200 response.
2. Cloudflare/LiteSpeed caches 404 responses. Requesting a URL before 
   the file exists causes the 404 to persist even after upload.
3. The debugging instinct was to look at code logic rather than 
   confirming the code was even loaded.

**Rules established:** *Verify file accessibility before debugging* — 
navigate to the file URL, confirm 200 and correct content, before 
testing anything else. Also: never request a file URL before the file 
exists at that path.

**Concrete cost:** At least 20 minutes of debugging across multiple 
iterations that was entirely wasted because the file wasn't being served.

**Resolution:** Added file verification as step 1 in the deployment 
protocol. Documented in execution plan, SKILL.md, and gotchas.

---

### 2026-04-20 — Two MutationObservers created infinite DOM mutation loop

**Context:** The Beds24 iframe helper had two MutationObservers on 
`document.body` with `subtree:true`: one for height sync (which set 
`body.style.height`) and one for applying fixes (dorm buttons, Book 
button injection).

**What happened:** The page froze inside the iframe. The height observer 
set `body.style.height`, triggering the fix observer. The fix observer 
injected DOM elements, triggering the height observer. The height 
observer set `body.style.height` again. Infinite loop. No height 
messages reached the parent widget, so the loading spinner never cleared.

**Root causes:**
1. Two observers watching the same subtree where both callbacks modify 
   the DOM is inherently recursive.
2. The `setTimeout` debounce on each observer (150ms, 300ms) didn't 
   prevent the loop — it just slowed it to every ~200ms, still enough 
   to freeze the page.
3. No guard mechanism to prevent re-entry.

**Rules established:** *One observer, one guard* — use a single 
MutationObserver with an `isModifying` flag. Set the flag before DOM 
modifications, release after 500ms timeout. The observer callback 
checks the flag and skips if true.

**Concrete cost:** One full iteration (write, upload, test, debug) 
wasted. The "nothing loads" symptom was ambiguous — could have been 
many things.

**Resolution:** Merged into single observer with `isModifying` guard 
in iframe helper v12+.

---

### 2026-04-20 — Iterating on pieces without a flow plan

**Context:** Building the booking widget and iframe helper across 
multiple iterations. Each version addressed the most recent bug 
without re-examining the full user flow.

**What happened:** Thirteen versions of the iframe helper (v1–v13) 
and five versions of the widget (v1–v5) in a single session. Each 
fixed one thing and broke another: the floating bar fix introduced 
a scroll feedback loop; the body-height trimming fixed excess 
whitespace but clipped the last room; the per-room Book button 
injection worked manually but not via the observer. The user had to 
intervene and ask to step back and plan the full flow before 
continuing.

**Root causes:**
1. No upfront mapping of the complete booking flow (widget → iframe 
   → room selection → checkout → back button). Each piece was built 
   to solve the immediate problem without considering how it composed 
   with the other pieces.
2. The iframe height sync, floating bar, form breakout, dorm fix, and 
   Book button injection were all developed as independent features 
   rather than parts of a single coherent flow.
3. Testing was per-feature ("does the button appear?") rather than 
   per-flow ("can a guest go from search to checkout and back?").

**Rules established:** *Plan the flow before coding the pieces* — map 
the complete user flow, identify architectural constraints (cross-origin 
iframe limits, form submission behavior, back button semantics), and 
make key decisions (iframe vs new tab, floating vs static bar) before 
implementing. Testing pieces in isolation then discovering they don't 
compose wastes more time than upfront planning.

**Concrete cost:** 13 helper versions and 5 widget versions to arrive 
at a flow that could have been designed in 2-3 versions with upfront 
planning. The user's intervention ("We're testing too many things 
without having a solid plan") was the turning point.

**Resolution:** Stepped back, mapped the full flow, made the key 
architectural decision (iframe for display, `target="_top"` for 
checkout breakout), and rebuilt cleanly. The v10+ helper and v4+ 
widget reflect this planned approach.

---

### 2026-04-20 — WordPress Custom HTML block lost its div on edit

**Context:** Updating the WordPress Custom HTML block to point to a 
new widget JS version. The block should contain both 
`<div id="tnh-booking-root"></div>` and `<script src="...">`.

**What happened:** After updating the script URL, the page showed 
nothing. The div had been accidentally deleted, leaving only the 
script tag. The widget JS loaded but couldn't find its mount point, 
so it silently exited.

**Root causes:**
1. The Custom HTML block shows raw HTML in a textarea. When replacing 
   the script URL, it's easy to select-all and paste only the script 
   line, losing the div.
2. The widget JS fails silently when `#tnh-booking-root` is missing — 
   no error, no console message, just an empty page.
3. The deployment instructions sometimes mentioned only the script 
   tag without the div, reinforcing the mistake.

**Rules established:** Reinforces *verify saves before building on 
them*. Also: always provide both lines together in deployment 
instructions. Never give just the script tag.

**Concrete cost:** One debugging round to discover the missing div. 
Minor, but happened twice in the same session.

**Resolution:** All deployment instructions now include both lines. 
Added to gotchas document.

---

### 2026-04-20 — Helper stuck on v10 despite v13 being on VPS

**Context:** Session 6 created helper v13 and uploaded it to the VPS. 
The handoff notes stated v13 was deployed. Session 7 began by 
verifying deployment.

**What happened:** File verification confirmed v13 existed on the VPS 
with correct content. But inspecting the live Beds24 page revealed 
the "Insert in HTML <HEAD> bottom" field still pointed to v10. Three 
versions of improvements (v11, v12, v13) had never taken effect. 
Every bug attributed to "timing" or "observer issues" in Session 6 
was simply the old code running.

**Root causes:**
1. The deployment protocol verified file accessibility but not the 
   admin field reference. The file existed; the page didn't load it.
2. The Session 6 handoff stated v13 was deployed based on the file 
   being uploaded, not on verifying the admin field was updated.
3. No end-to-end verification: "file on VPS" ≠ "file loaded by page."

**Rules established:** *Verify the full deployment chain* — after 
uploading files, verify every reference is updated: Beds24 admin 
fields, WordPress HTML blocks, widget CONFIG. A file on the VPS is 
useless if the page loads the old version.

**Concrete cost:** Multiple issues from Session 6 (no Book buttons, 
bottom bar visible, page clipping) were "already fixed" in v13 code 
that never ran. Session 7 spent initial time re-diagnosing issues 
that were already resolved in undeployed code.

**Resolution:** Updated "Insert in HTML <HEAD> bottom" to v14. 
Expanded the deployment verification protocol to include admin field 
and WordPress block checks.

---

### 2026-04-20 — display:none iframe caused 18-second desktop loading delay

**Context:** The booking widget creates an iframe to load the Beds24 
booking page. While loading, the iframe was set to `display:none` to 
hide it behind a loading spinner.

**What happened:** On desktop, the loading spinner persisted for 18 
seconds. Diagnostic logging showed the helper inside the iframe 
reported `height=200` (the minimum floor) on every measurement cycle 
for the entire duration. The widget waited for height > 500 to reveal 
rooms — deadlock. Mobile was unaffected.

**Root causes:**
1. An iframe with `display:none` does not render its content. 
   `getBoundingClientRect()` returns 0 for all elements inside it.
2. The helper measured room container height as 0, added the 200px 
   floor, and reported 200. The widget never received height > 500.
3. The 8-second fallback set `display:block` but kept the height at 
   220px (200 + 20 padding). At 220px the rooms were squeezed and 
   measurements remained low.
4. Mobile browsers apparently handle hidden iframe rendering 
   differently, allowing content to be measured even when the parent 
   iframe has `display:none`.

**Rules established:** *Hide measurable content with opacity, not 
display* — use `opacity:0; position:absolute; height:1px` to keep 
content invisible but renderable and measurable.

**Concrete cost:** 18-second load time on desktop — likely causing 
guest abandonment on every visit.

**Resolution:** Changed widget v6 to use `opacity:0; position:absolute` 
instead of `display:none`. Loading time dropped to 2-3 seconds.

---

### 2026-04-20 — External CSS overrides lost to Beds24 Style panel inline styles

**Context:** CSS v3 changed `.datestay` background from orange to green 
using `background-color: var(--b24-color-secondary) !important`. The 
variable resolved correctly. The rule had `!important`.

**What happened:** Stay dates remained orange on the live page. The 
Beds24 Style panel generates inline `<style>` blocks with 
`.datestay { background-color: rgb(231, 163, 92) }` (no `!important`). 
These load after the external CSS file. Despite our rule having 
`!important` and theirs not, the color didn't change — investigation 
showed the external CSS was cross-origin and its rules couldn't be 
inspected via JS, making debugging difficult. The actual cause was 
likely cached stale CSS being served.

**Root causes:**
1. External CSS loaded cross-origin has unpredictable cache behavior 
   across Cloudflare and the browser.
2. Debugging CSS specificity across cross-origin stylesheets is 
   unreliable — `cssRules` throws SecurityError.
3. The Style panel's inline styles are a reliable override target 
   only from JS-injected styles that load last.

**Rules established:** *Inject overrides via JS when CSS load order 
fails* — for overrides that must beat platform-generated styles, 
inject via a JS `<style>` tag in the helper script. This loads last 
and is guaranteed to win.

**Concrete cost:** One round of CSS debugging and a failed deploy. 
Required moving the color overrides from external CSS to helper JS.

**Resolution:** Added Section 5 to helper v14: injects date strip 
color overrides via `document.createElement('style')`. Works 
reliably on both direct page and iframe.

---

### 2026-04-20 — OpenLiteSpeed cache immune to all standard purge methods

**Context:** Deploying updated CSS and helper JS files to the VPS. 
Files were overwritten with new content via aaPanel file manager.

**What happened:** Despite purging Cloudflare cache, enabling 
Cloudflare development mode, adding `.htaccess` CacheDisable 
directives, disabling the LiteSpeed WordPress plugin, and restarting 
the OpenLiteSpeed service — the server continued serving stale file 
content. A deleted file (`CSS-base-v4b.css`) remained accessible 
indefinitely. Multiple rounds of CSS/JS debugging were wasted 
because the live page was loading old code.

**Root causes:**
1. OpenLiteSpeed caches static files at the server level in 
   `/usr/local/lsws/cachedata/`, independent of Cloudflare, the WP 
   plugin, and `.htaccess` directives.
2. No SSH access to manually delete the cache data folder.
3. The aaPanel UI has no "purge OLS cache" button.
4. Overwriting a file does not invalidate its cached version.

**Rules established:** *New filenames bypass server cache* — the 
only reliable workaround is uploading with new versioned filenames.

**Concrete cost:** ~45 minutes across multiple upload-test-debug 
cycles where changes appeared not to work. Several CSS fixes were 
already correct but appeared broken because the old file was served.

**Resolution:** Adopted strict versioned filename policy (v5, v6, 
etc.). Every deployment uses a new filename. Added to CLAUDE.md 
and gotchas.

---

### 2026-04-20 — Media queries don't fire on container width changes

**Context:** Local mockup with a dropdown to simulate mobile by 
setting `max-width: 390px` on the container div. Media queries 
at `@media(max-width:767px)` controlled the mobile layout.

**What happened:** Selecting "Mobile (390px)" from the dropdown 
constrained the container but the mobile CSS rules never applied. 
Tags stayed next to the thumbnail, offer bar didn't rearrange. 
Multiple mockup iterations (v9 through v11) appeared broken, 
causing unnecessary CSS rewrites. When the user finally resized 
the actual browser window to 390px, the mobile layout worked.

**Root causes:**
1. CSS `@media(max-width)` queries check the viewport width, not 
   any container's width.
2. The mockup's dropdown changed `max-width` on a wrapper div — 
   the viewport stayed at desktop width.
3. Three mockup iterations were spent trying to "fix" mobile CSS 
   that was already correct, because testing used the wrong method.

**Rules established:** *Use viewport resize for media query testing* 
— use Chrome DevTools device mode or resize the browser window. 
Container width changes do not trigger media queries.

**Concrete cost:** Three wasted mockup iterations (v9, v10, v11) 
and ~30 minutes of CSS debugging chasing a testing artifact, not 
a real bug.

**Resolution:** User resized browser window and confirmed mobile 
layout was working. Noted in dev workflow instructions for future 
mockup work.

---

### 2026-04-20 — Mockup DOM diverged from live Beds24 DOM

**Context:** Developing room card CSS via a self-contained HTML 
mockup. The mockup's DOM was built from dom-structure.md and 
previous session notes, not from a live DOM snapshot.

**What happened:** CSS that worked in the mockup didn't match the 
live site. Specific issues: `:first-of-type` selectors failed 
because the live DOM has `.offer` as the first div child (before 
the slider row), which the mockup initially got wrong. Bootstrap 
column classes (`col-sm-6`) added `width:50%` and `float:left` 
on the live page that the mockup didn't account for until v4. 
The live `.container` class added its own `max-width` that 
constrained the layout differently.

**Root causes:**
1. The mockup was built from documentation, not a live DOM 
   extraction. DOM order, class combinations, and Bootstrap 
   interactions were approximated.
2. No verification step to compare mockup DOM against live DOM 
   before extracting CSS for deployment.
3. Multiple iterations were needed to discover and fix each 
   DOM discrepancy (`:first-of-type` → `:has()`, Bootstrap 
   column reset, container max-width override).

**Rules established:** *Test CSS against real DOM before deployment* 
— before extracting CSS from a mockup for production use, verify 
the mockup's DOM structure matches the live page.

**Concrete cost:** 4-5 mockup iterations (v1-v4) spent discovering 
and fixing DOM mismatches that could have been caught upfront.

**Resolution:** Switched to `:has()` selectors (stable regardless 
of DOM order), added global Bootstrap column reset, and forced 
`.container` width. These fixes make the CSS more robust against 
DOM variations.

---

### 2026-04-21 — Offer bar rebuild skipped the simplest candidate solution

**Context:** Session 11 implemented the offer-bar rebuild plan: create 
new `.tnh-offer-bar` markup, move Beds24's `.b24-multipricebox` into 
it, hide the original. When this produced persistent alignment and 
overlap bugs, a mirror-controls proposal was written (create our own 
`<select>`, sync values to Beds24's hidden select). When the mirror 
proposal's adversarial review raised further questions, a v2 proposal 
added widget-first layout redesign. Three progressively complex 
proposals across one session.

**What happened:** A separate test applied the existing mockup v13's 
CSS and JS directly to the live Beds24 iframe at 390px. It worked on 
3 of 4 rooms with one CSS fix (`[id^="selectors1-"] { flex: 1 
!important; min-width: 0 !important }`). The tag overlap was resolved. 
The Book button alignment was resolved. The mockup — which had existed 
since Session 10 — was a working solution the entire time.

The session never tested whether the mockup's approach worked on the 
live page. Instead it treated the rebuild plan as the task, and when 
the plan's approach failed, escalated to more complex architectures 
(mirror controls, optimistic rendering, widget-first layout redesign) 
rather than questioning whether the plan's premise was correct.

**Root causes:**
1. The rebuild plan's premise — that styling Beds24's elements in place 
   was a losing specificity war — was an inference from earlier sessions, 
   not a measurement. Session 11 inherited this premise without testing 
   it.
2. The mockup was treated as a visual reference ("match this output") 
   rather than a candidate implementation ("does this CSS work on the 
   live page?"). The cheapest falsifying test — applying the mockup 
   directly — was never run.
3. Each failure was diagnosed as requiring more architecture (move → 
   mirror → optimistic render → layout redesign) rather than less. The 
   problem was getting smaller (one missing CSS rule) while the proposed 
   fixes were getting larger.
4. The 698px iframe width was not discovered until a diagnostic test 
   spec was run in a real browser late in the session. This meant all 
   "desktop" CSS work was targeting a layout that no widget user ever 
   sees. The actual rendering context was never established upfront.

**Rules established:**
- *Test the simple thing before building the complex thing* — when a 
  working reference exists (mockup, prior version), test it against the 
  real environment before designing a more complex architecture. If the 
  simple version works, ship it.
  When a working reference exists (mockup, prior version), the test is to 
  apply it to the target environment and measure. Not to compare it visually, 
  not to describe what it shows, not to use it as inspiration for a new 
  implementation. Apply, measure, observe what breaks, fix the smallest gap.
- *Know your actual viewport* — when an iframe embeds your page, your 
  CSS viewport is the iframe's width, not the user's screen. Identify 
  the actual rendering width early and design for it.

**Concrete cost:** Six commits, three proposal documents, and one 
adversarial review cycle spent on architectures that were unnecessary. 
The working solution was a CSS port plus one rule. The session produced 
useful artifacts (the diagnostic test spec, the event-listener audit 
documentation, the retrospective analysis) but the core deliverable — 
a working offer bar — was not shipped.

**Resolution:** v3 implementation plan: port mockup v13 CSS to 
CSS-base.css, add the selectors1 fix, preserve existing helper JS 
Sections 3/4/6 (revert the rebuild). Total change: one CSS rewrite, 
zero JS architecture changes, one new CSS rule.


### 2026-04-19 — The rebuild that didn't need to happen

**Context:** The offer-bar rebuild plan (14 steps, Session 11 initial 
attempt), the mirror-controls proposal (v1), and the widget-first 
layout proposal (v2) were all written over multiple sessions to address 
two visible bugs: offer bar alignment and tag/thumbnail overlap.

**What happened:** Across eight rounds of adversarial review, each 
proposal grew more elaborate. Mirror controls proposed creating 
parallel DOM elements to escape specificity wars. Widget-first layout 
proposed redesigning the mobile CSS from scratch. Total proposed 
complexity: one DOM rebuild + mirror pattern + grid-at-all-widths + 
optimistic rendering + debounce lifecycle fix.

Then a diagnostic was run: apply the v13 mockup's existing CSS and JS 
directly to the live Beds24 iframe at 390px. It worked on 3 of 4 rooms 
with one CSS rule needing to be added (flex fix on `#selectors1-` 
wrapper). The dorm room needed its existing helper JS preserved. 
That's it.

**Root causes:**

1. **Premise inheritance without verification.** The rebuild plan 
   assumed "styling Beds24's elements in place can't win the 
   specificity war." This came from Session 3's CSS-only failure 
   history. Nobody re-tested the premise. It turned out to be false.

2. **Mockup categorized as reference, not implementation.** The v12/v13 
   mockup was built in a prior session but never validated against the 
   live DOM. Subsequent sessions treated it as a design target to reach 
   via new implementation work, rather than as an existing 
   implementation to port.

3. **Adversarial review inherited the frame.** Outside reviewers 
   (Gemini, DeepSeek, Z) critiqued the proposals from within the 
   proposals' frames. They didn't ask "does this complexity need to 
   exist?" because the brief told them not to re-litigate settled 
   decisions. The decision that most needed re-litigating was the one 
   that had been settled informally, not documented as settled.

4. **Complexity escalation in response to friction.** Each time a 
   proposal hit a CSS problem, the response was a more elaborate 
   architecture rather than a check of whether simpler approaches had 
   actually been ruled out.

**Rules established:**
- Mockup-first validation
- Measurements vs inferences
- Cheapest falsifying test first
- Let the bug get smaller before the fix gets bigger
- Show the design artifact, not just the plan

**Concrete cost:** Approximately 8 rounds of review, 3 proposal 
documents, ~500 lines of superseded CSS and JS. Recoverable through 
archive, but the rollout to properties 2-4 was delayed.

---

### 2026-04-21 — Beds24 strips non-ASCII characters from programmatic admin field saves

**Context:** Session 12 saved the Chill Zone `TNH_CONFIG` object — containing 
emoji icon characters (🛏, 🚿, etc.) in room tags — to the Beds24 "Insert in 
HTML <HEAD> bottom" field via Claude in Chrome (setting `textarea.value` + 
clicking save). The save appeared to succeed; length was 968 chars. After 
reload, the emoji had been replaced with `?`.

**What happened:** The first fix attempt set the emoji characters directly via 
`String.fromCodePoint()` in the JS tool, then saved. On reload, emoji were again 
`?` and the length had reverted from 990 to 968. Beds24 silently strips 
non-ASCII characters from `customhead` on server-side save.

The fix was to use JS Unicode escape sequences (`\uD83D\uDECF`) in the 
textarea value instead of the actual emoji characters. These are pure ASCII 
and survive the save intact. When the browser executes the `<script>` block, 
the JS engine evaluates `\uD83D\uDECF` as the emoji at runtime, so tags 
display correctly.

**Root causes:**
1. Beds24's AJAX form handler sanitizes or re-encodes non-ASCII characters 
   on save for `customhead`. The mechanism is server-side and silent — no 
   error, no truncation indicator.
2. The fix was obvious in hindsight but not documented, requiring two 
   save-reload cycles to diagnose.

**Rules established:** When embedding non-ASCII characters (emoji, accented 
characters, non-Latin scripts) in Beds24 script fields via programmatic save, 
use JS Unicode escape sequences (`\uD83D\uDECF`) rather than literal 
characters. They are functionally equivalent at JS runtime and survive the 
save encoding path without corruption.

**Concrete cost:** Two extra save-reload cycles. Low impact, but would recur 
on every new property rollout without documentation.

**Resolution:** TNH_CONFIG in Chill Zone `customhead` now uses JS Unicode 
escapes for all emoji. Tags render correctly. Gotchas doc updated.

**Resolution:** v3 plan (see `docs/v3-plan.md`) ports the v13 mockup 
verbatim with the `#selectors1-` fix and preserves the pre-rebuild 
helper JS for the dorm case.

---

### 2026-04-23 — Claims about third-party platform behavior must be verified in live browser

**Context:** Session 13 was debugging the dorm room's price not
updating when bed count changed, and made an authoritative claim
that "Beds24 does not reprice for naa changes, treating it as a
guest count field." The user correctly pushed back, noting that
Beds24 does treat each dorm bed as a separate unit and does reprice
on bed count changes.

**What happened:** The claim was reasoning from DOM symptoms (the
price didn't visibly change in the iframe when naa select changed),
not from measurement (no observation of what Beds24 actually did
behind the scenes). The wrong claim then shaped two subsequent
attempts at "fixing" a non-problem.

**Rule established:**

> Claims about how a third-party platform handles a specific DOM
> event (AJAX firing, field updating, repricing) must be treated
> as inferences until observed in a live browser. Do not state them
> as facts in plans, handoffs, or proposals.

**Verification pattern:** For Beds24-specific behavioral questions,
open DevTools Network panel, trigger the event in a live browser,
observe the request/response. For cross-property comparisons, pull
admin XML or screenshot admin pages from both properties.

This rule belongs to the broader "Separate measurements from
inferences" family (rule 1.1 in SKILL.md) but calls out the
third-party-platform case specifically because it recurs.

---

### 2026-04-23 — Trace visual order before deploying structural DOM changes

**Context:** Session 13's Attempt 4 on the Book button movement
removed the `.tnh-offer-row` wrapper and appended `totalEl` and
`btn` directly to `priceBox` (`.b24-multipricebox`). This was
intended to simplify the flex hierarchy. It introduced two
regressions: the `tnh-total-price` element moved to the wrong
visual position (next to the dropdown instead of near the Book
button), and the price stopped updating when qty changed.

**What happened:** The change in DOM position altered where the
price element landed in the flex visual order, and the new position
was outside the selector path `enhancePrices` used to find and
update it. Neither effect was checked before deploying.

**Rule established:**

> Before deploying a structural DOM change, trace through the full
> flex/grid visual order for all affected items and compare
> before/after. Also verify that any selectors downstream of the
> change still resolve to the intended elements. Especially on
> mobile where `order` values actively rearrange items.

This is related to rule 1.3 (let the bug get smaller before the fix
gets bigger) — Session 13's four-attempt escalation followed the
pattern of scoping up each attempt rather than diagnosing why the
bug actually happens.

**Concrete cost:** Two regressions shipped to main (commit
`67931a9`), plus the Book button bug still unresolved. Requires a
revert and a fresh diagnostic approach in the next session.

---

### 2026-04-24 — Cross-property comparison first, docs second

**Context:** Session 14 debugged the OCCUPANCY_EXCEEDS_MAX_PERSONS
errors on Chill Zone. The first instinct (mine, at the start of the
session) was to read Beds24 documentation about the error. The
user's response was "let's look at the working property first" —
which turned out to be the correct path.

**What happened:** By comparing Chill Zone's admin configs to
Trip'N'Hostel's (a working property in the same brand, with dorms
that don't error), we narrowed the difference to a single field
(Pricing Model: Per Occupancy Pricing vs Per Day Pricing) within a
few round-trips. Reading docs afterwards confirmed the fix. Reading
docs first would have taken longer and offered more dead-ends.

**Rule established:**

> When one property throws an error another doesn't, don't search
> third-party docs blindly. The cheapest diagnostic is side-by-side
> admin config comparison. Three fields' difference between working
> and non-working is usually the answer.
>
> Compare at two levels: the admin panel UI (what fields show) AND
> the channel-side API responses (what's actually being sent). The
> admin panel labels (rate names like "Standard Rate") are not the
> same as pricing type in the channel's XML response.

**Applicable outside Beds24:** Any situation where two instances
behave differently — two client configurations, two deployment
environments, two test data setups — benefits from this diagnostic
pattern. Compare first, theorize second.

**Concrete payoff:** Session 14 resolved a multi-session blocker
in approximately one round of investigation + documentation search,
versus Session 13's multiple-hour diagnostic dead-ends. Difference
is primarily the diagnostic approach.

**Also worth noting:** The cross-property comparison surfaced a
second issue (silent save failures on Daily Price Rules) that
wouldn't have been caught without the working-vs-non-working
contrast. The price math was off for Chill Zone's dorm even after
the Pricing Model change; the working property showed the math
should have worked; investigation revealed the "Price For" field
hadn't persisted its save.
---

### 2026-05-06 — Conversational defaults established

**Context:** Cross-conversation discussion that worked through whether
to migrate the predecessor booking-page project to a WordPress plugin,
and what scope that plugin should have. The conversation reached a
clean architectural decision (plugin handles discovery, Beds24's
iframe handles transactions, multi-room cart composes via URL
parameters) but reached it after several scope expansions that turned
out to be unnecessary.

**The pattern observed:** Each user prompt that introduced a new
architectural framing was analyzed at face value. "Should this be a
plugin?" was treated as a question about plugins, not as a question
about whether plugins were the right shape for the underlying
problem. "Should we use the API for the full flow?" was answered
with a Stripe integration estimate before checking whether the
property was already using Beds24's payment gateway (which could be
reused). "What about multi-room bookings?" produced three
architectural paths before the user pointed out that Beds24's
iframe already handles multi-room state natively.

The framings the user offered were genuine and worth analyzing, but
analyzing them at face value before independently evaluating whether
they were the right shape of the problem produced extra work. The
decision the conversation eventually reached was reachable from
earlier with shorter analysis if independent assessment had come
first.

**What this surfaces:** A working pattern where the user proposes a
direction and the assistant analyzes its implications can produce
good work, but it depends on the user's first framing being correct.
When the framing is partially correct, the analysis defends a
partially-correct shape of the problem. The cost is real but
hidden — the conversation feels productive because each step is
substantive, but the steps go through architectures that wouldn't
be picked from a fresh look.

The fix is making independent assessment a deliberate first step
on architectural questions, before the implications are analyzed.
This is a small change in conversational structure with
significant effect on outcome.

**Rule established:**

> Conversational defaults section added to CLAUDE.md. The full
> rule lives there and applies from session 1 forward. Summary:
> independent assessment first, no premise validation, independent
> estimates, explicit confidence levels, named assumptions,
> verification against primary sources, accuracy over agreeableness.

**Why this lives in the retrospective:** The rule itself is
forward-looking and lives in CLAUDE.md as part of the project's
operating context. This entry exists because the rule was
established in response to an observed pattern, and future
sessions that wonder why the rule exists or whether it's still
needed should be able to find the reasoning here.

The rule is not punitive. It describes how the collaboration works
best, based on what was observed when it didn't.

---

### 2026-05-07 — Verify both local and remote repo state, not just commit hashes

**Context:** Session 1a (predecessor archive) launched and stopped at
the first state check. Two discrepancies surfaced:

1. The working tree had unstaged deletions and untracked files. Five
   session handoff files had been deleted from `docs/` and copied to
   `docs/archive/`, but the move was never committed. Other untracked
   files were session-launch debris.
2. The local commit `31c28e1` existed locally but had not been pushed
   to origin. The branch was 1 commit ahead of `origin/main`.

The Session 1a prompt's repository assumptions section said: "the
current directory is the predecessor `booking-page` repository,
cloned and up to date with `main`. The most recent commit is
`31c28e1`." Both halves of this were partially true — the most
recent commit was correctly `31c28e1`, but "up to date with main"
was ambiguous. Local and remote had drifted.

**What this surfaces:** When planning a session against an external
repository's state, verifying the most recent commit hash is
necessary but not sufficient. A repository can match the expected
commit hash while still being:

- Out of sync with remote (commits not pushed, or remote ahead of
  local)
- Mid-housekeeping (uncommitted changes that should land before the
  session's work)
- Cluttered with untracked files that affect what `git add` would
  pick up

The Session 1a prompt's check passed on commit hash but the session
correctly halted on the broader state check. Halting was the right
behavior. The lesson is in the prompt-writing, not the execution.

**Rule established:**

> When a session prompt requires the working repository to be in a
> known state, the state check must verify all of:
> - The expected commit hash matches HEAD
> - The working tree is clean (no unstaged or staged changes)
> - There are no untracked files outside `.gitignore`'d paths, OR
>   if untracked files are expected, the prompt explicitly says so
> - Local and remote are in sync (HEAD matches origin/main, or the
>   prompt explicitly accommodates being ahead/behind)
>
> Each verification step is one git command and one assertion. The
> cost of adding all four is small; the cost of skipping one and
> halting partway through is larger.

**Concrete prompt template:**

```
### Step N — Confirm state

Run pwd and verify the output ends in <expected-directory>.
Run git status. Verify the output is exactly:
  "On branch main"
  "Your branch is up to date with 'origin/main'."
  "nothing to commit, working tree clean"
If anything differs, stop and report what was found.

Run git log --oneline -1. The most recent commit must be
<expected-hash>. If different, stop and report.
```

The literal-output match is intentional: it catches drift in any of
the dimensions above with one check.

**Why this lives in the retrospective:** Future sessions that
operate on existing repositories should reference this when writing
their state-check steps. The lesson generalizes: external state is
multi-dimensional, and partial verification is a source of session
halts that look like prompt failures but are really
incomplete-prompt failures.

---

### 2026-05-07 — PowerShell default encoding corrupted .gitignore silently

**Context:** Session 3 began with a `.gitignore` state check. Git
reported `.claude/` as untracked despite the `.gitignore` appearing to
contain a `.claude/` entry. The repo state didn't match the expected
"clean working tree."

**What happened:** The `.claude/` entry in `.gitignore` was written in
a prior session using PowerShell's default encoding (UTF-16 LE with
null bytes between each character: `.^@c^@l^@a^@u^@d^@e^@/`). Git's
pattern matching requires UTF-8 — the null-byte-padded entry matched
nothing. The file looked correct in any text editor that auto-detects
encoding, so the corruption was invisible until a `cat -A` hex dump.

**Root causes:**
1. PowerShell's default `Out-File` encoding is UTF-16 LE. Writing
   a gitignore line with `echo '.claude/' >> .gitignore` or
   `".claude/" | Out-File .gitignore -Append` from PowerShell produces
   UTF-16 LE output that git silently ignores.
2. No post-write verification step — git status after the write would
   have caught the mismatch immediately (`.claude/` would still show
   as untracked).

**Rules established:** When writing to configuration files from
PowerShell, always use `-Encoding utf8` or use the Read/Write/Edit
tools rather than PowerShell redirection operators. After writing to
`.gitignore`, verify the intended path is actually ignored:
`git check-ignore -v <path>` confirms whether the pattern matched.

**Concrete cost:** One diagnostic pass at session start to identify the
corruption. Low impact but would have silently admitted `.claude/`
commits had the session added untracked files without noticing.

**Resolution:** Truncated file at last clean UTF-8 line, rewrote the
corrupted entry in UTF-8 using Python (the system Python at
`/c/Python312/python`, not the store alias). Crosslink's init then
replaced the ad-hoc entry with a properly managed gitignore block.

---

### 2026-05-11 — aapanel makes .user.ini immutable

**Context:** VPS setup session — creating SSH users and configuring
POSIX ACLs for site file access on the staging server.

**What happened:** File operations targeting `.user.ini` files returned
`Operation not permitted` (EPERM) even when running as root. Initial
interpretation was a filesystem or permissions failure requiring
investigation.

**Root causes:**
1. aapanel applies `chattr +i` (immutable flag) to every site's
   `.user.ini` file to protect its `open_basedir` PHP restrictions
   from being overwritten.
2. The immutable flag blocks all writes, renames, and link operations
   on the file — including those by root. This is expected aapanel
   behavior, not a filesystem failure.

**Rules established:** Future Code sessions encountering EPERM on
`.user.ini` should recognize this pattern and move on rather than
treating it as a blocker. If a legitimate edit is ever needed:
`chattr -i <path>` to remove the immutable flag (root required), make
the change, then `chattr +i <path>` to restore. Do not leave
`.user.ini` files without the immutable flag after editing.

**Concrete cost:** Diagnostic time spent treating expected behavior as
an unexpected failure.

**Resolution:** Recognized as expected aapanel behavior. Proceeding
past `.user.ini` files without modification is correct.

---

### 2026-05-11 — Code's Bash tool has a static analyzer

**Context:** Running multi-step shell commands during SSH user and ACL
setup on the VPS.

**What happened:** Several commands were rejected before execution with
the message `Contains shell syntax (string) that cannot be statically
analyzed`. The rejection is a pre-execution check — the command does
not run.

**Root causes:**
1. Code's Bash tool runs commands through static analysis before
   executing. Known rejected constructs:
   - `$(...)` command substitution
   - Some `for`/glob control flow
   - The `time` keyword in some shell contexts
2. Rejection is a hard stop with no partial execution.

**Rules established:** When the analyzer rejects a script, flatten to
a sequence of plain commands. Don't fight the analyzer; restructure.
Trade-off: a flat sequence without `set -e` won't halt on first
failure, so verifying each step's output becomes more important.
Idempotent operations (like `setfacl`) are safer in this pattern
because re-running them is harmless. Measure performance outside the
script rather than wrapping commands with `time`.

**Concrete cost:** Commands had to be restructured mid-session.

**Resolution:** Restructured affected commands as plain sequences.
Recognized as expected tool behavior, not a session failure.

---

### 2026-05-11 — Flag plan deviations explicitly

**Context:** Executing the SSH/user/ACL setup plan on the VPS.

**What happened:** Code made technical refinements during execution
without explicitly flagging them as deviations from the plan:

1. Used `setfacl -m u:claude-code:rwX` (capital X) rather than
   lowercase `x`. Capital X means "execute only if directory or
   already executable" — avoids setting the execute bit on text/PHP
   files. An improvement, but not surfaced proactively.
2. Chose `usermod -L` (lock password) rather than generating a
   password and saving it to file. Sensible for SSH-key-only
   authentication, but the deviation wasn't flagged until operator
   feedback prompted it.

**Root causes:**
1. Deviation-flagging discipline was applied inconsistently —
   flagged for the `setfacl` case, missed for `usermod`.
2. The "technical refinement" vs. "deviation" distinction was
   being evaluated informally rather than uniformly applied.

**Rules established:** When Code's approach diverges from the prompt —
even when the deviation is an improvement — name the change, the
reason, and leave the call with the operator. The discipline is uniform
regardless of whether the deviation is a technical refinement (flag
choices, command alternatives) or a procedural one (sequencing, skipped
steps). Surface deviations; don't smuggle them in silently.

**Concrete cost:** Operator feedback loop required to surface the
`usermod` deviation. Small time cost but breaks the collaborative model
where deviations are transparent.

**Resolution:** Corrected via operator feedback during the session.
Added as an explicit process rule.

---

### 2026-05-11 — aapanel does not manage SSH users

**Context:** Planning SSH user setup for VPS access. The
`wordpress-setup.md` skill reference contained a claim that "aapanel's
user management can scope an SSH user to specific directories."

**What happened:** aapanel does not create or manage Linux SSH users —
this applies to both the free and paid versions, confirmed via aapanel
staff responses on their official forum. SSH user creation is standard
Linux work: `useradd`, `~/.ssh/authorized_keys`, optionally `AllowUsers`
in `sshd_config`. aapanel runs as root and manages WordPress sites as
the `www` user; that is the extent of its user management.

**Root causes:**
1. The `wordpress-setup.md` claim was written from research and
   inference, not verified against aapanel's actual behavior.
2. The claim was specific enough ("can scope an SSH user to specific
   directories") to read as authoritative, making it more likely to
   be acted on rather than questioned.

**Rules established:** aapanel provides hosting infrastructure (PHP,
OpenLiteSpeed, MySQL, WP Toolkit) and manages WordPress sites as `www`.
Linux user management — including SSH users — is OS-level work done
with standard Linux commands, not aapanel commands. Do not look to
aapanel docs for SSH user creation or file access scoping; use standard
Linux tooling.

**Concrete cost:** Incorrect documentation in `wordpress-setup.md`
that would have misled VPS setup work on future properties.

**Resolution:** `wordpress-setup.md` corrected as part of this session.

---

### 2026-05-11 — Verify cross-document references with grep before closing a session

When a session's work involves moving, renaming, or removing files that are referenced elsewhere, the session must end with grep verification across the affected repos before the closing commit, not as a follow-up task.

Two observations grounded this rule:

- The Session 6 consolidation moved files from `docs/skill/` to `skills/<name>/references/` and reported "all references updated," but four active references in `docs/styling-contract.md` survived and three file-level moves weren't committed at all.
- The Session 6 follow-up caught both gaps via an explicit grep step in the session prompt. The catches happened because verification was in scope, not because the original session was re-examined.

The pattern: a session that updates references in the files it has in hand will miss references in files it doesn't think to open. Grep across the affected repos surfaces them mechanically.

Practical shape:
- After the substantive work, run `grep -rn "<old-path>" --include="*.md" .` for any file paths that changed
- For cross-repo work, run grep in each affected repo
- Treat any surviving reference as either an in-scope fix or an explicit decision to leave it; don't let it slide silently

---

### 2026-05-11 — Acknowledge and decide on session scope drift, don't slide

When a session's actual work diverges materially from its named scope by mid-session, pause and decide explicitly whether to:

- Rename the session to reflect the actual scope and continue
- Hold the line on the original scope and defer the discovered work to its own session

Don't let scope drift silently. The implicit-acceptance pattern produces sessions that ostensibly accomplish one thing but actually did something else, which makes the session record harder to use as a reference.

The Session 6 follow-up that established this rule was a worked example: started intending MCP setup, found the surrounding documentation was inconsistent enough to make MCP setup risky, and spent the session making documentation reliable instead. That was the right call but the session's identity should reflect it.

---

### 2026-05-11 — Secrets in Code prompts: write up to the gate

**Context.** A prior session established that Code prompts should not
contain operator-fillable blanks (e.g., `{REFRESH_TOKEN_VALUE}`). The
substitution-just-before-paste pattern is fragile — easy to miss, easy
to paste literal, easy to leave broken state.

**Where this session surfaced friction.** Session 7's continuation
prompt needed the Beds24 refresh token value. Two bad options surfaced
first: (1) include the secret value in chat directly so the prompt is
complete, which puts a long-lived secret in chat history; (2) use a
placeholder marker the operator substitutes, which reintroduces the
fragility the no-blanks rule warns against.

**Rule.** When a Code prompt needs a secret value, write the prompt
only up to the gating action where the secret would appear, and stop
there. Do not write the gating command itself with a placeholder. Do
not write subsequent steps that depend on the gating action.

The operator then constructs the single gating command themselves, with
the secret substituted directly, and pastes that one command to Code.
Once Code reports back, a continuation prompt covers the remaining
steps.

**Why this works.** The secret never appears in chat (the operator
constructs and pastes the command directly to Code). There's no template
to substitute into and no marker to overlook — the operator writes the
single command from scratch using the secret. The handoff is one
command, not a multi-step prompt block requiring careful substitution.

**Distinguishing secrets from other values.** A value is a secret when
its disclosure in chat would meaningfully change the security posture
(i.e., would require rotation). API tokens, refresh tokens, passwords,
OAuth credentials, private keys — secrets. Configuration paths,
usernames, port numbers, file paths — not secrets; handle via the
normal no-blanks rule (request the value or write up to the point it
becomes available).
