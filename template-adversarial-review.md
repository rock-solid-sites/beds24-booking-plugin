---
title: "Template: Adversarial Code Review"
tags: ["template", "process", "design-doc"]
sources: []
contributors: ["unknown"]
created: 2026-05-21
updated: 2026-05-21
---


## Design Specification

### 1. identify scope

- Which file or module? (e.g., `plugin/view/view.js`, `plugin/includes/class-beds24-api-client.php`)
- What concerns to focus on? (correctness, security, accessibility, performance)
- What is out of scope? (style preferences, deliberate architectural choices)

### 2. craft the review prompt

Include in the prompt:
- The file content (or relevant sections)
- What the code is supposed to do (the contract, not the implementation)
- Focus areas (correctness, security, etc.)
- What NOT to flag (ES5 constraint is deliberate, no framework is deliberate)
- Request: findings with severity, concrete test cases for each, prioritized list

Do NOT include in the prompt:
- Prior session history
- Your own assessment of the code
- A list of things you already know are wrong

### 3. execute fresh-context review

- Use a fresh Claude session (no project context, no memory of prior work)
- For security-specific: use the security-specific prompt variant (emphasize
  OWASP Top 10, WordPress-specific issues like nonce verification, capability
  checks)
- Record the raw findings output

### 4. triage findings

For each finding, categorize:
- **Real and actionable:** The reviewer found a genuine issue. Verify the specific
  line/condition. Create a sub-issue in the relevant epic.
- **Real but deferred:** Genuine issue but not current scope. Note in the epic,
  create a low-priority issue.
- **False positive — deliberate choice:** The reviewer flagged something that is
  intentional (e.g., ES5 syntax, no framework). Document the reason in a comment.
  Do not create an issue.
- **False positive — hallucinated:** The reviewer invented a problem that doesn't
  exist in the actual code. Dismiss. Consider whether the prompt was ambiguous.

### 5. track fixes

- Create issues for real findings
- Add result comment to the adversarial review tracking issue
- When fixes land: close each sub-issue with verification evidence

### security-specific variant

For security reviews, add to the prompt:
- "Treat this as a WordPress plugin submission security audit"
- "Flag: unverified nonces, missing capability checks, unescaped output,
  SQL injection vectors, user input flowing to URLs or HTML"
- "Assume the plugin is installed on a public WordPress site with untrusted users"

Source: Epic #5 issue #23 (adversarial review pattern documentation)

