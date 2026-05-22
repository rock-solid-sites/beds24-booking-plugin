---
title: "Design: view.js Modularization"
tags: ["design", "javascript", "architecture"]
sources: []
contributors: ["unknown"]
created: 2026-05-22
updated: 2026-05-22
---

## Decision

Split view.js with esbuild as the build step (Option A).

## Context

The file is 1,443 lines with six concerns: state store, cart logic, DOM rendering (cards + cart), booking URL construction / iframe management, mobile drawer, and form validation / search dispatch. Round 3 will add four new behaviors primarily landing in buildCard and renderRoomCards.

esbuild is a dev tool, not a runtime dependency. The ES5 IIFE output is identical from the browser's perspective. The 'no framework' constraint is about runtime, not build tooling.

## Source file map

Split into source files following existing section boundaries:

- `src/store.js` — state store (subscribe/notify)
- `src/cart.js` — cart state operations (add/remove/toggle, no DOM)
- `src/booking-url.js` — URL construction and iframe management
- `src/render-cards.js` — room card building and rendering
- `src/render-cart.js` — cart region rendering and sync functions
- `src/drawer.js` — mobile drawer open/close/padding
- `src/view.js` — init, form validation, search dispatch, event delegation (orchestrator)

## Internal structure for render-cards.js

Within render-cards.js, extract buildCard into composable functions: buildTypeBar, buildCardBody, buildTagChips, buildOfferRow, buildCartControls. Extract a prepareRoomData pipeline that handles sort, filter, unavailable positioning, and room type override resolution — separated from DOM building.

## Rejected option

Option C (split without build step, shared window namespace) is ruled out — fragile load-order dependency, worse than a single file.
