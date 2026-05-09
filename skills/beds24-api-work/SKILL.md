---
name: beds24-api-work
description: Use when working on the Beds24 v2 API client (class-beds24-api-client.php), implementing or debugging API calls to /properties or /inventory/rooms/offers, dealing with authentication flows (invite codes, refresh tokens, access tokens), or interpreting API response shapes. Also use for questions about API scopes, token storage in wp_options vs transients, error handling patterns, or response quirks discovered during implementation. Activate any time the conversation involves Beds24 API code, even if the user just says "let's work on the API" or "the API is returning something unexpected."
---

# Beds24 v2 API — Working Reference

**Implementation:** `plugin/includes/class-beds24-api-client.php`
**Class:** `Beds24_API_Client`
**Full reference:** `references/api-client.md`
**OpenAPI spec:** `references/openapi.yaml`

---

## Auth flow

### One-time setup (per property)

1. Operator: Beds24 admin → MARKETPLACE > API → Generate invite code
2. Select scopes: `inventory:READ` and `properties:READ` minimum
3. Invite code is **single-use, expires in 24 hours** — capture immediately
4. Plugin calls `$client->exchange_invite_code( $code )`
5. Client sends `GET /authentication/setup` with header `code: {invite_code}`
6. Response: `{ token, expiresIn: 86400, refreshToken }`
7. Refresh token stored in `wp_options`; invite code is consumed

**Critical:** The exchange endpoint is **GET, not POST**. The invite code
goes in a **request header** (`code:`), not in a body field. The overview
doc's example is correct; an early session handoff incorrectly said POST.
Verified against openapi.yaml line 17, OTAs guide, and Guest Services guide.

### Ongoing (automatic per API call)

`get_access_token()` checks the transient cache. On cache miss:

1. Retrieves refresh token from `wp_options`
2. Sends `GET /authentication/token` with header `refreshToken: {token}`
3. Response: `{ token, expiresIn: 86400 }`
4. Caches access token in a WordPress transient for `expiresIn - 60` seconds

Refresh tokens don't expire as long as used within 30 days. Access tokens
last 86400 seconds (24 hours) in practice — the API overview shows 3600 but
real tokens (verified Chill Zone, Session 6) are 86400.

---

## Token storage

### Refresh token

- **Storage:** `wp_options`
- **Key:** `beds24_booking_plugin_refresh_token_{property_id}`
- **Example:** `beds24_booking_plugin_refresh_token_271142`
- Persists across requests and plugin deactivation. Cleared only on
  uninstall. One token per Beds24 property ID.

### Access token

- **Storage:** WordPress transients
- **Key:** `beds24_bkp_access_token_{property_id}`
- **TTL:** `max(60, expiresIn - 60)` seconds (1-minute safety buffer)
- Do **not** use `wp_options` for access tokens — stale tokens must not
  persist. Flushed on plugin deactivation.

---

## Method signatures

### Constructor

```php
$client = new Beds24_API_Client( int $property_id );
```

### exchange_invite_code

```php
$result = $client->exchange_invite_code( string $invite_code ): array|WP_Error;
```

One-time call. Stores the refresh token in `wp_options` on success.

### Token helpers

```php
$client->store_refresh_token( string $refresh_token ): void;
$client->get_refresh_token(): string|false;
$client->has_refresh_token(): bool;  // use in admin UI to check setup status
```

### get_properties

```php
$result = $client->get_properties( array $params = [] ): array|WP_Error;
```

Calls `GET /properties?id={property_id}&includeAllRooms=true`.

**Response shape (excerpt):**
```json
{
  "success": true,
  "data": [{
    "id": 271142,
    "roomTypes": [{
      "id": 567219,
      "name": "Single Bed in 4-Bed Dormitory Room",
      "roomType": "bedInDormitory",
      "qty": 4,
      "maxPeople": 4,
      "featureCodes": [["LUXURY_LINEN"], ["CLOSETS_IN_ROOM"], ["SHARED_BATHROOM"]]
    }]
  }]
}
```

### get_offers

```php
$result = $client->get_offers(
    string $check_in,   // YYYY-MM-DD
    string $check_out,  // YYYY-MM-DD
    int    $num_adults = 1
): array|WP_Error;
```

Calls `GET /inventory/rooms/offers?arrival=…&departure=…&numAdults=…&propertyId=…`.

**Response shape (excerpt):**
```json
{
  "success": true,
  "data": [{
    "roomId": 567219,
    "propertyId": 271142,
    "offers": [{ "offerId": 1, "price": 32, "unitsAvailable": 2 }]
  }]
}
```

---

## Error pattern

All public methods return `WP_Error` on failure. Always check:

```php
$result = $client->get_offers( '2026-05-14', '2026-05-16' );
if ( is_wp_error( $result ) ) {
    wp_die( $result->get_error_message() );  // fail loud in dev
}
// use $result['data']
```

**Error codes:**
- `beds24_api_error` — non-200 HTTP response from Beds24
- `beds24_json_error` — JSON parse failure
- `beds24_no_refresh_token` — no refresh token stored for property
- `beds24_missing_token` — 200 response but missing expected token field

---

## Response shape quirks (Session 6 findings)

Do not re-discover these at implementation time.

### featureCodes is a list of lists, not a flat list

```json
"featureCodes": [["PRIVATE_BATHROOM"], ["BEDROOM", "BED_KING"], ["BATHROOM"]]
```

Each element is a group. Flatten groups before looking up in the mapping
table: `array_merge(...$featureCodes)` or iterate over groups.

### maxAdult is null — use maxPeople

`maxPeople` is the occupancy limit field. `maxAdult` is always null in
the Chill Zone data. For the dorm, `maxPeople` equals `qty` (4). Private
rooms return `maxPeople: null` — occupancy limits for privates may need to
come from another source.

### Rooms with no offers appear with empty offers[]

An unavailable room still appears as `{ roomId, propertyId, offers: [] }`.
This is the signal for "unavailable" — show the card as unavailable, not
hidden. Architecture principle: rooms render regardless of availability.

### expiresIn is 86400, not 3600

The API overview's example shows 3600. Real tokens return 86400 (24h).
The client respects whatever `expiresIn` the API returns; this is an
expectations note.

### Token length

152 characters for the Chill Zone account. Beds24 FAQ says 152–172.

---

## Chill Zone property IDs (verified Session 6)

Property: Trip'n'Hostel Chill Zone, id=271142, currency=EUR

| Room | Beds24 ID | Type | qty | maxPeople |
|---|---|---|---|---|
| Deluxe King Suite | 567218 | double | 1 | null |
| Single Bed in 4-Bed Dormitory Room | 567219 | bedInDormitory | 4 | 4 |
| Single Room with Shared Bathroom | 567220 | single | 3 | null |
| Standard Double Room with Shared Bathroom | 567221 | double | 3 | null |

---

## References

| File | Contents | When to read |
|---|---|---|
| `references/api-client.md` | Full API client doc (this skill is derived from it) | If detail is missing above |
| `references/openapi.yaml` | Full OpenAPI spec (8,349 lines) | Schema authority; endpoint parameters; response schemas |
| `references/overview.md` | Beds24 API overview (709 lines) | Auth flow narrative, scopes list |
| `references/otas-guide.md` | OTA integration guide (116 lines) | Auth flow with corrected curl examples |
| `references/guest-services-guide.md` | Guest services guide (81 lines) | Auth flow for guest-facing integrations |
| `references/booking-page-url-parameters.md` | URL parameter reference (163 lines) | Booking page URL construction |
| `references/embedded-iframe.md` | Embedded iframe guide (135 lines) | Iframe integration patterns |

**openapi.yaml table of contents (key sections):**
- Lines 1–50: API info, servers, auth schemes
- Lines 51–200: Authentication endpoints (`/authentication/setup`, `/authentication/token`)
- Lines 201–1000: Properties endpoints
- Lines 1001–3000: Inventory endpoints (rooms, offers)
- Lines 3001+: Bookings, guests, messages, other endpoints
