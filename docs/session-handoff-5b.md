# Session 5b Handoff — 2026-05-07

## What this session did

Captured local markdown copies of Beds24 wiki pages and the full OpenAPI spec to support V1 plugin development. All files written to `docs/reference/beds24-api-v2/`.

---

## Captures

### Successfully captured (6 files)

| File | Source | Notes |
|---|---|---|
| `overview.md` | Category:API_V2 | Complete — scopes, endpoints list, auth flow, best practices, FAQ. 709 lines. |
| `otas-guide.md` | OTAs:_How_to_connect_to_Beds24_using_API_V2 | Complete — auth flow, GET /properties, calendar/availability/offers description. Gestures at endpoint detail; full schemas in openapi.yaml. |
| `guest-services-guide.md` | Guest_Services:_How_to_connect_to_Beds24_using_API_V2 | Complete — auth flow and curl example. **Note added: GET not POST.** |
| `booking-page-url-parameters.md` | Category:Developers | Complete — full parameter table (~25 parameters). Key doc for URL construction. |
| `embedded-iframe.md` | Embedded_Iframe | Complete — iframe passthrough script and its valid parameter list. |
| `openapi.yaml` | beds24.com/api/v2/apiV2.yaml | **Full OpenAPI 3.0 spec, 8,349 lines.** Directly fetchable (no auth required for the YAML file, only for the API endpoints themselves). The primary reference for Session 6's API client. |

### Not applicable

- Booking-page URL parameters page: **found and captured** as `booking-page-url-parameters.md`. URL was `https://wiki.beds24.com/index.php/Category:Developers`.
- OpenAPI spec: **captured** as `openapi.yaml` using approach 6a (direct YAML fetch). The `/openapi.json`, `/swagger.json`, and `/v3/api-docs` paths returned 401; `apiV2.yaml` returned 200 directly.

---

## Critical correction for Session 6

**The Session 5 handoff incorrectly documents the invite-code exchange.** It says:

```
POST https://beds24.com/api/v2/authentication/setup
Body: { "code": "<invite-code>" }
```

**This is wrong.** The correct call is:

```bash
curl -X 'GET' \
  'https://beds24.com/api/v2/authentication/setup' \
  -H 'accept: application/json' \
  -H 'code: <invite-code>'
```

Confirmed by: the OpenAPI spec (line 17 of openapi.yaml), the OTAs guide, and the Guest Services guide — all three agree it is `GET` with the code as a header.

Session 6 must use GET with the `code:` header or the invite code exchange will return 400.

---

## Content gaps in captured files

### OTAs guide — endpoint detail

The OTAs guide (`otas-guide.md`) describes what each endpoint does but does not document parameters or response schemas. Example: it says "GET /inventory/rooms/offers can be used to retrieve offers based on specified criteria" but doesn't list the required parameters (`arrival`, `departure`, `numAdults`).

**Session 6 action:** Use `openapi.yaml` directly for parameter lists and response schemas. The wiki guides are useful for understanding intent; the YAML is the schema authority.

### Multi-room URL parameters: undocumented

The `sr1-{roomId}=N` and `naa1-1-{roomId}=N` parameters from the architecture doc's URL scheme are **not documented** anywhere in the captured wiki pages:

- Not in the Booking Page Parameters table (`booking-page-url-parameters.md`)
- Not in the iframe passthrough script's valid parameters (`embedded-iframe.md`)
- Not in any search result across 30 results for "booking page url parameters"

The documented single-room booking format uses `br1-{roomId}=Book`. The architecture doc uses `booking3.php` while the wiki examples use `booking2.php`. The `sr1-`/`naa1-` parameters appear to be internal to Beds24's own multi-room booking flow.

**Session 6 action:** Verify multi-room URL parameters empirically. Test approach:
1. Open `https://beds24.com/booking3.php?propid=271142` in Chrome
2. Add two rooms to the cart manually
3. Inspect the resulting URL to confirm the parameter format
4. Also test `booking2.php` with `multiroom=1` to see if it uses different parameters

The Known Unknowns in `docs/architecture.md` already flag this. Start with the simplest test (selected rooms only, no ghost entries) per the architecture doc.

### GET /properties is Beta

Labelled "Beta" in the OpenAPI spec. Mostly finished; breaking changes not planned. Proceed with implementation.

---

## Repo state at session end

- Branch: `main`
- HEAD: `3cc9979` (Capture Beds24 API V2 wiki references locally)
- Remote: pushed and in sync with `origin/main`
- Working tree: clean

---

## Session 6 scope

**V1 build phase 1: WordPress plugin scaffold + Beds24 v2 API client.**

**Read before implementing:**
1. `docs/reference/beds24-api-v2/openapi.yaml` — the schema authority for all API endpoints
2. `docs/reference/beds24-api-v2/overview.md` — auth flow, scopes, best practices
3. `docs/reference/beds24-api-v2/booking-page-url-parameters.md` — URL construction reference
4. `docs/architecture.md` sections 4 (Data sources) and 10 (Known unknowns)
5. `docs/skill/property-setup.md` — auth flow notes, invite code location

**Starting work in order:**

### 1. Exchange invite code for refresh token

The invite code is stored in the operator's password manager (labeled "Beds24 Chill Zone — API invite code (not yet exchanged)"). Exchange it using:

```bash
curl -X 'GET' \
  'https://beds24.com/api/v2/authentication/setup' \
  -H 'accept: application/json' \
  -H 'code: <invite-code-from-password-manager>'
```

Store the resulting `refreshToken` in `.env`:
```
BEDS24_REFRESH_TOKEN_CHILL_ZONE=<token>
```

Verify `.env` is gitignored: `git check-ignore -v .env`

### 2. WordPress plugin scaffold

Basic PHP plugin structure. See Session 5 handoff for file structure suggestion.

### 3. Beds24 v2 API client

Class handling: refresh token → access token exchange, access token caching in WP transients, `GET /properties`, `GET /inventory/rooms/offers`.

Full endpoint schemas are in `openapi.yaml`. Required parameters for offers: `arrival` (string, YYYY-MM-DD), `departure` (string, YYYY-MM-DD), `numAdults` (integer).

### 4. Verify multi-room URL parameters empirically

Before implementing URL construction, verify whether `booking3.php` accepts `sr1-{roomId}=N` and `naa1-1-{roomId}=N` by testing against the live Beds24 page. Document findings before writing the URL-construction code.
