# API Client — Reference

**Status:** Implemented (Session 6, 2026-05-07)
**Class:** `Beds24_API_Client` in `plugin/includes/class-beds24-api-client.php`

This document answers "how do I call the API correctly" for future sessions
building features that use the API client. For why the client is structured
this way, see `docs/architecture.md` sections 4 and 5.

---

## Auth flow

### One-time setup (per property)

1. Operator navigates to Beds24 admin → MARKETPLACE > API
2. Clicks "Generate invite code", selects scopes (needs `inventory` and
   `properties` at minimum; `read` method on both)
3. Copies the invite code — it expires in 24 hours and is single-use
4. Plugin admin calls `$client->exchange_invite_code( $code )`
5. Client sends `GET /authentication/setup` with `code: {invite_code}` header
6. Response: `{ token, expiresIn: 86400, refreshToken }`
7. Client stores `refreshToken` in `wp_options` — invite code is now consumed

**Critical:** the exchange is a GET, not POST. The invite code is a request
header (`code:`), not a request body field. Session 5's handoff incorrectly
documented POST; Session 5b corrected this. All three primary sources agree:
openapi.yaml (line 17), OTAs guide, Guest Services guide.

### Ongoing (automatic)

Before each API call, `get_access_token()` checks the transient cache. On
cache miss:

1. Retrieves refresh token from `wp_options`
2. Sends `GET /authentication/token` with `refreshToken: {token}` header
3. Response: `{ token, expiresIn: 86400 }`
4. Caches the new access token in a transient for `expiresIn - 60` seconds

Refresh tokens do not expire as long as used within 30 days. Access tokens
last 86400 seconds (24 hours) — verified against Chill Zone in Session 6.
The Beds24 API overview example shows 3600 but real tokens are 86400.

---

## Token storage

### Refresh token

**Storage:** `wp_options`
**Key convention:** `beds24_booking_plugin_refresh_token_{property_id}`
**Example key:** `beds24_booking_plugin_refresh_token_271142`

Refresh tokens persist across requests and page loads. They survive plugin
deactivation (cleared only on uninstall). One token per Beds24 property ID.

### Access token

**Storage:** WordPress transients (`set_transient` / `get_transient`)
**Key convention:** `beds24_bkp_access_token_{property_id}`
**Example key:** `beds24_bkp_access_token_271142`
**TTL:** `max(60, expiresIn - 60)` seconds (1-minute safety buffer)

Transients are flushed on plugin deactivation. Do not use `wp_options` for
access tokens — they expire and stale tokens must not persist.

---

## Method signatures

### Constructor

```php
$client = new Beds24_API_Client( int $property_id );
// Example:
$client = new Beds24_API_Client( 271142 );
```

### exchange_invite_code

```php
$result = $client->exchange_invite_code( string $invite_code ): array|WP_Error;
```

One-time call. Stores the refresh token in `wp_options` on success. Returns
the full API response `{ token, expiresIn, refreshToken }` or `WP_Error`.

### store_refresh_token / get_refresh_token / has_refresh_token

```php
$client->store_refresh_token( string $refresh_token ): void;
$client->get_refresh_token(): string|false;
$client->has_refresh_token(): bool;
```

Direct access to the stored refresh token. Use `has_refresh_token()` in the
admin UI to check whether setup is complete.

### get_properties

```php
$result = $client->get_properties( array $params = [] ): array|WP_Error;
```

Calls `GET /properties?id={property_id}&includeAllRooms=true`. Additional
params merged in override the defaults.

**Response excerpt:**

```json
{
  "success": true,
  "data": [{
    "id": 271142,
    "name": "Trip'n'Hostel Chill Zone",
    "roomTypes": [{
      "id": 567219,
      "name": "Single Bed in 4-Bed Dormitory Room",
      "roomType": "bedInDormitory",
      "qty": 4,
      "maxPeople": 4,
      "featureCodes": [["LUXURY_LINEN"], ["CLOSETS_IN_ROOM"], ["SHARED_BATHROOM"]],
      ...
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

**Response excerpt:**

```json
{
  "success": true,
  "data": [{
    "roomId": 567219,
    "propertyId": 271142,
    "offers": [{
      "offerId": 1,
      "offerName": "",
      "price": 32,
      "unitsAvailable": 2
    }]
  }]
}
```

---

## Error pattern

All public methods return `WP_Error` on failure. **Never call these methods
without checking the return value:**

```php
$result = $client->get_offers( '2026-05-14', '2026-05-16' );

if ( is_wp_error( $result ) ) {
    // Fail loud in dev — surface the error
    wp_die( $result->get_error_message() );
}

// Use $result['data']
```

Error codes used:
- `beds24_api_error` — non-200 HTTP response from Beds24
- `beds24_json_error` — JSON parse failure on response body
- `beds24_no_refresh_token` — no refresh token stored for the property
- `beds24_missing_token` — response is 200 but missing expected token field

---

## Response shape quirks found in Session 6

These differ from what the architecture doc implied. Record them here to
prevent future sessions from re-discovering them.

### featureCodes is a list of lists

The architecture doc refers to "feature codes" as if they're a flat list.
They are not. Each element is a group (sub-array):

```json
"featureCodes": [["PRIVATE_BATHROOM"], ["BEDROOM", "BED_KING"], ["BATHROOM"]]
```

When implementing the feature-codes mapping table, flatten with array_merge
or iterate over groups. A flat mapping table (`PRIVATE_BATHROOM` → label) is
still correct — just flatten the groups first.

### maxAdult is null; use maxPeople

The architecture doc says the API provides "occupancy limits" and implies
`maxAdult`. The actual field is `maxPeople`. `maxAdult` is always null in
the Chill Zone data. Use `maxPeople` for the occupancy limit display.

### Rooms with no offers appear in data[] with empty offers[]

A room that is unavailable for the searched dates still appears in the
response as `{ roomId, propertyId, offers: [] }`. This is the correct
signal for "unavailable" — show the room card as unavailable, not hidden
(architecture principle: rooms render regardless of availability).

### expiresIn is 86400, not 3600

The Beds24 API overview's example shows `expiresIn: 3600`. Real tokens
(from both `/authentication/setup` and `/authentication/token` against
the Chill Zone account) return `expiresIn: 86400` (24 hours). The client
respects whatever `expiresIn` the API returns; this note is for awareness.

### Token length

Tokens are 152 characters for this account. The Beds24 FAQ says 152–172.

---

## Chill Zone property IDs (verified Session 6)

| Room | Beds24 ID | Type | qty | maxPeople |
|---|---|---|---|---|
| Deluxe King Suite | 567218 | double | 1 | null |
| Single Bed in 4-Bed Dormitory Room | 567219 | bedInDormitory | 4 | 4 |
| Single Room with Shared Bathroom | 567220 | single | 3 | null |
| Standard Double Room with Shared Bathroom | 567221 | double | 3 | null |

Property: Trip'n'Hostel Chill Zone, id=271142, currency=EUR.

Note: `maxPeople` for the private rooms returned null in the API response.
The dorm (`bedInDormitory`) returned `maxPeople: 4`, consistent with its
`qty: 4`. The private room occupancy limits will need to be sourced from
another field or from the WordPress plugin admin if the API doesn't surface
them.
