---
title: "Frontend JS Architecture"
tags: ["architecture", "javascript", "design-doc"]
sources: []
contributors: ["unknown"]
created: 2026-05-21
updated: 2026-05-21
---


## Design Specification

### the settled decision

**Plain ES5 IIFE JavaScript. No framework. No web components. No build step.**

The booking flow is a single block's `viewScript` with bounded scope: search
form, room results, cart accumulator, and the Confirm Booking handoff. A
framework would introduce a build step, bundle weight, and learning curve the
scope doesn't justify.

### why no framework

- WordPress's own guidance for block viewScripts favors vanilla JS.
- The ecosystem pattern for simple frontend interactivity is plain JS + DOM queries.
- No build step means no toolchain, no transpilation, no module bundler.
  The file can be edited and deployed directly.

### why no web components

- Shadow DOM isolates styles, which fights the styling contract's design.
- The plugin's styling is built on CSS custom properties and BEM classes targeting
  plugin-emitted DOM; shadow DOM would require a separate styling channel.
- ES6 class syntax required for custom elements conflicts with the ES5 constraint.

### why a state store

Cart state, search result state, and loading state are read by multiple render
functions and written by multiple event handlers. A shared state object with
explicit get/set and a subscribe/notify mechanism keeps render functions
decoupled from each other while making state flow traceable.

**Pattern:** `store.set(key, value)` triggers `store.subscribe(key, callback)`
notifications. Render functions subscribe; event handlers call set.

### es5 constraint — what it means in practice

- Use `var`, not `const`/`let`
- Use named function expressions, not arrow functions
- No template literals, no destructuring, no spread operators
- No `import`/`export` — the file is a single IIFE
- No `class` syntax

The default JavaScript rule file (`javascript.md`) describes modern ES6+ style.
**That guidance does not apply here.** The ES5 constraint is not a linting
preference — it is an architectural decision about the plugin's runtime
compatibility and build-step-free deployment model.

### files

- `plugin/view/view.js` — main viewScript (search form, room results, cart
  accumulator, booking URL construction). Currently ~1,443 lines, six concerns.
- `plugin/includes/render.php` — PHP render callback for the block.

