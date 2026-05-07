<!-- Project-Specific Rules -->
# Beds24 Booking Plugin — Project Rules

## Project Context

WordPress plugin (PHP + JavaScript) for Beds24 hostel booking. The plugin owns
discovery (search form, room results, cart accumulation) and delegates
transactions to Beds24's iframe at the Confirm Booking boundary.

Solo developer project. Tracking mode is relaxed — use crosslink when it helps,
not as a blocking gate. The canonical project entry point is `CLAUDE.md`.
Architectural decisions live in `docs/architecture.md` (drafted Session 4+).

---

## Beds24 Admin Field Names

When referring to Beds24 admin configuration fields in explanations or
instructions to the user, always use the name visible in the Beds24 admin UI —
never the internal field ID:

| Use this (UI name) | Not this (internal ID) |
|---|---|
| "Insert in HTML &lt;HEAD&gt; bottom" | customhead |
| "Booking Page CSS" | bookingcss |
| "Insert Custom HTML in Body" | custombody |
| "Insert in HTML &lt;HEAD&gt; confirm" | customheadconfirm |

The user interacts with the Beds24 admin UI. Internal field IDs are invisible to
them and produce instructions they can't follow.

---

## Verify Before Debugging

Before diagnosing any functionality issue in the deployed plugin or Beds24 admin:

1. **Deployed plugin files**: Confirm the correct PHP/JS/CSS versions are active.
   Check page source for enqueued filenames. If a file URL is involved, request it
   and confirm a 200 response with expected content before testing anything.
2. **Saved admin values**: After any programmatic or manual write to a Beds24 admin
   field, reload the settings page and confirm the value persisted. Silent save
   failures (character limits, tag stripping, encoding issues) are common.
3. **API responses**: Log the actual Beds24 v2 API response before diagnosing
   "the API doesn't return X." The API is a candidate data source, not a verified one.

If the state being debugged isn't the state assumed, every downstream step is wasted.

---

## Measurements vs. Inferences for Third-Party Behavior

Claims about Beds24 platform behavior — API responses, admin field limits, AJAX
save paths, iframe DOM selectors, repricing on field changes, character limits —
are **inferences until verified in a live browser or via a real API call**. Do
not state them as facts in plans, handoffs, or proposals.

Verification pattern:
- Open DevTools Network panel, trigger the event in a live browser, observe the
  request and response
- For cross-property discrepancies, compare admin configs side-by-side before
  theorizing about root causes
- For API behavior, run a real call and log the response before designing around it

This is the most-violated rule in the project retrospective. The cost of getting
it wrong is designing around phantom limitations or ignoring real ones.
