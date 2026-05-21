---
title: "Template: Security Review Session"
tags: ["template", "security", "design-doc"]
sources: []
contributors: ["unknown"]
created: 2026-05-21
updated: 2026-05-21
---


## Design Specification

### ajax handler audit

- [ ] List all `wp_ajax_` and `wp_ajax_nopriv_` registrations
- [ ] For each handler, verify:
  - Nonce verification (`check_ajax_referer()` or `wp_verify_nonce()`)
  - Capability check (`current_user_can('manage_options')` or appropriate cap)
  - All inputs sanitized before use
  - No unintended nopriv handlers (handlers that run unauthenticated)
- [ ] Handlers to check: room sync trigger, enabled toggle, reorder, name change acceptance

### api token handling

- [ ] Refresh token stored in `wp_options` under correct key pattern
- [ ] Access token stored in transients (not wp_options) with correct TTL
- [ ] Tokens not exposed in: error messages, PHP error logs, rendered HTML, REST API responses
- [ ] No tokens in JavaScript source (all token operations are server-side)
- [ ] Rotation logic correct: refresh token used within 30-day window

### url construction review

- [ ] Room IDs: `encodeURIComponent()` applied before URL inclusion
- [ ] Dates: validated format before URL inclusion (check-in before check-out, not in past)
- [ ] Property ID: validated as integer, not user-supplied string
- [ ] Iframe src: constructed server-side or from validated PHP values, not raw user input
- [ ] No open redirect: the Beds24 domain is hardcoded, not configurable

### input validation paths

- [ ] Search form dates: client-side validation (JS) AND server-side validation (PHP) before API call
- [ ] Admin settings fields:
  - Property ID: validated as positive integer
  - Token exchange field: invite code format checked before API call
- [ ] Room edit screen:
  - Room type override: allowlist check against known enum values (already in meta box save)
- [ ] Admin AJAX submissions: all inputs sanitized and validated on server side

### output escaping

- [ ] All PHP-rendered content uses `esc_html()`, `esc_attr()`, `esc_url()` as appropriate
- [ ] No unescaped `$_POST`, `$_GET`, or `$_REQUEST` in template output
- [ ] JavaScript that inserts dynamic content uses `textContent` not `innerHTML`

### session notes

Document each finding:
- `crosslink issue comment <id> "Finding: [description]" --kind observation`
- For confirmed vulnerabilities: create sub-issue, mark priority, add to security epic
- For false positives: document the reason in a comment, do not create issues

Source: Epic #4 Security review sub-issues

