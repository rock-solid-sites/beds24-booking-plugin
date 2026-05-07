# Beds24 API V2 Reference Snapshot

Local copies of Beds24 wiki pages and API documentation, captured
to support V1 plugin development.

**Captured:** 2026-05-07
**Captured for:** Beds24 booking plugin V1 build

## Files

- `overview.md` — API V2 capabilities, scopes reference, endpoints list, FAQ. The umbrella reference. Read this first.
- `otas-guide.md` — OTA integration guide (closest match to the plugin's role — booking engine pulling property/availability/pricing data)
- `guest-services-guide.md` — Auth flow detail (kept primarily for the curl example showing invite-code exchange via GET /authentication/setup)
- `booking-page-url-parameters.md` — URL parameters for the booking page (from Category:Developers wiki page). Includes the full parameter table for `checkin`, `checkin_hide`, `numnight`, `numadult`, `br1-{roomId}=Book`, and ~25 other parameters.
- `embedded-iframe.md` — iframe integration guide. Contains the official list of parameters the Beds24 iframe passthrough script handles.
- `openapi.yaml` — Full OpenAPI 3.0 spec (`apiV2.yaml` from beds24.com/api/v2/). 8,349 lines. Machine-readable, complete endpoint schemas and response shapes. The primary reference for Session 6's API client implementation.

## Why these are local

The Beds24 wiki blocks automated fetchers (returns 403 to `web_fetch`). Capturing locally makes the documentation accessible to Code sessions without per-page fetch friction. The OpenAPI YAML was directly fetchable from `https://beds24.com/api/v2/apiV2.yaml`.

## Key findings from capture

### Auth flow correction

**The Session 5 handoff incorrectly documents the invite-code exchange as a POST request with a JSON body. It is a GET request with the invite code as a header.**

Correct:
```bash
curl -X 'GET' \
  'https://beds24.com/api/v2/authentication/setup' \
  -H 'accept: application/json' \
  -H 'code: <invite-code>'
```

The OpenAPI spec (`openapi.yaml`, line 17) and both wiki guides confirm this. Session 6 must use GET with the `code:` header.

### Token refresh endpoint

To get a new access token from a refresh token, use:
```
GET /authentication/token
```
with header `refreshToken: <refresh-token>`. Access tokens last 24 hours. Refresh tokens last 30 days of inactivity.

### Key endpoints for the plugin

| Endpoint | Used for | Required parameters |
|---|---|---|
| `GET /authentication/setup` | Exchange invite code for refresh token | `code:` header |
| `GET /authentication/token` | Get access token from refresh token | `refreshToken:` header |
| `GET /properties` | Room metadata (names, IDs, occupancy, featureCodes) | `token:` header |
| `GET /inventory/rooms/offers` | Live availability and total stay price | `arrival`, `departure`, `numAdults` (all required) |

The `inventory` scope is required for `/inventory/rooms/offers`.
The `properties` scope is required for `/properties`.

### Booking page URL parameters — documented

The `booking-page-url-parameters.md` file contains the full parameter table. Key parameters for the plugin:

- `checkin` — check-in date (format: `YYYY-M-DD`)
- `checkin_hide` — check-in date alternative (format: `YYYY-MM-DD`)  
- `numnight` — number of nights
- `numadult` — number of adults
- `br1-{roomId}=Book` — book offer 1 for a specific room

### Multi-room parameters: undocumented

The `sr1-{roomId}=N` and `naa1-1-{roomId}=N` parameters observed in a live multi-room booking URL are **not documented** in any captured wiki page. They are not in the official parameter table and not in the iframe passthrough script's valid parameter list. They appear to be internal parameters used by Beds24's own booking flow (possibly specific to `booking3.php`). Session 6 must verify them empirically. See Known Unknowns in `docs/architecture.md`.

### GET /properties is in Beta

The spec labels `GET /properties` as "Beta." This means it is mostly finished and being tested; breaking changes are not planned.

## Versioning

Captures are dated. Beds24's API can change; if a difference between captured content and live behavior surfaces during implementation, the live wiki is authoritative. Refresh the captures by rerunning this session's process (navigate to each URL in Chrome, extract text, overwrite files, re-fetch `apiV2.yaml`).
