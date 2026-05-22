---
title: "Design: MCP Expose Abilities Evaluation"
tags: ["design", "mcp", "wordpress", "tooling"]
sources: []
contributors: ["unknown"]
created: 2026-05-22
updated: 2026-05-22
---

## Status

Evaluation planned. Plugins already installed on VPS.

## What is being evaluated

MCP Expose Abilities (69 core abilities) with MCP Adapter — two plugins, since Abilities API is now in WP core. Already installed on property sites.

## Evaluation procedure

Run against a live property site on the VPS:

1. options/get — read property config
2. options/update — set token mappings
3. content/create-page — booking page with plugin block
4. menus/create + menus/add-item + menus/assign-location — navigation
5. plugins/list — verify state
6. options/get — verify token pipeline

## Success criteria

Compare structured MCP responses against WP-CLI output for clarity, error handling, and session flow. Adopt if structured responses meaningfully reduce errors in multi-step property setup.
