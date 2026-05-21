---
title: "Active Rules"
tags: ["process", "rules", "design-doc"]
sources: []
contributors: ["unknown"]
created: 2026-05-21
updated: 2026-05-21
---


## Design Specification

### rule summary

**Measurements vs inferences** — Identify which claims from prior sessions are
measurements vs inferences. Inferences that gate current work must be verified
this session.

**Cheapest falsifying test first** — When a proposal's complexity feels
disproportionate, find the cheapest test that would falsify the central premise
and run it first.

**Let the bug get smaller before the fix gets bigger** — When successive rounds
surface new information, resist scoping up the fix. The problem is usually
getting smaller.

**Retest inherited limitations** — When prior sessions document a tool limitation
alongside other failures, retest it independently before adopting as a constraint.

**Understand the data model before bulk operations** — Before setting values across
a multi-entity UI, confirm whether the UI presents a filtered view of shared data
or isolated per-entity data.

**Automate what you can verify, delegate what you can't see** — When automation
cannot inspect the result, delegate that step to the human.

**Verify saves before building on them** — After any programmatic write to a Beds24
admin field, reload and confirm the value persisted.

**Read documents before browser state** — At session start, read all handoff
documents before inspecting browser tabs or running tools.

**Verify file accessibility before debugging** — Confirm 200 response with correct
content before testing functionality.

**Plan the flow before coding the pieces** — Map the complete user flow before
implementing individual pieces.

**Verify the full deployment chain** — After uploading files, verify every
reference is updated (admin fields, WordPress blocks, widget config).

**Test CSS against real DOM before deployment** — Verify the mockup DOM matches
the live page before extracting CSS.

**Use viewport resize for media query testing** — CSS media queries respond to
viewport width, not container width.

**Use the platform's field names with the user** — Use Beds24 admin UI names
(e.g., "Insert in HTML <HEAD> bottom"), not internal field IDs.

**Test the simple thing before building the complex thing** — When a working
reference exists, test it against the real environment first.

**Mockup-first validation** — Test whether applying the mockup's CSS/JS directly
produces the desired result before proposing a more complex approach.

**Claims about third-party platform behavior must be verified in live browser** —
Treat them as inferences until observed in DevTools.

**Trace visual order before deploying structural DOM changes** — Before deploying
a structural DOM change, trace through the full flex/grid visual order.

**Cross-property comparison first, docs second** — When one property throws an
error another doesn't, compare admin configs side-by-side first.

**Verify cross-document references with grep before closing a session** — When
files are moved or renamed, end with `grep -rn` across affected repos.

**customhead character limit** — Use "Custom CSS" (`bookingcss`) for generated
CSS payloads, not "Insert in HTML <HEAD> bottom" (`customhead`). The latter has
an undocumented ~2,000-character server-side limit.

**Acknowledge and decide on session scope drift, don't slide** — When actual
work diverges from named scope, pause and explicitly decide to rename or defer.

**Secrets in Code prompts: write up to the gate** — Write the prompt only up to
the gating action where the secret would appear, and stop.

**Flag plan deviations explicitly** — When Code's approach diverges from the
prompt, name the change and reason, and leave the call with the operator.

