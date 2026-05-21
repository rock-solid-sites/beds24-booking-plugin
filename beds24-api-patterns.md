---
title: "Beds24 API Patterns"
tags: ["api", "php", "design-doc"]
sources: []
contributors: ["unknown"]
created: 2026-05-21
updated: 2026-05-21
---


## Design Specification

### auth flow

### One-time setup (per property)

1. Beds24 admin → MARKETPLACE > API → "Generate invite code" (read scopes on
   `inventory` and `properties`)
2. Plugin admin calls `$client->exchange_invite_code( $code )`
3. Client sends `GET /authentication/setup` with `code: {invite_code}` header
   **Critical: GET, not POST. The invite code is a request header, not body.**
4. Response: `{ token, expiresIn: 86400, refreshToken }`
5. Client stores `refreshToken` in `wp_options` — invite code consumed

### Ongoing (automatic access token rotation)

Before each API call, `get_access_token()` checks the transient cache. On miss:
1. Gets refresh token from `wp_options`
2. Sends `GET /authentication/token` with `refreshToken: {token}` header
3. Response: `{ token, expiresIn: 86400 }` — caches for `expiresIn - 60` seconds

Refresh tokens: do not expire if used within 30 days.
Access tokens: 86400 seconds (24 hours), verified against Chill Zone.

### token storage

| Token | Storage | Key pattern |
|---|---|---|
| Refresh | `wp_options` | `beds24_booking_plugin_refresh_token_{property_id}` |
| Access | WordPress transients | `beds24_bkp_access_token_{property_id}` |

### method signatures

```php
$client = new Beds24_API_Client( int $property_id );

// One-time setup
$result = $client->exchange_invite_code( string $invite_code ): array|WP_Error;

// Status checks
$client->has_refresh_token(): bool;
$client->get_refresh_token(): string|false;
$client->store_refresh_token( string $refresh_token ): void;

// API calls
$result = $client->get_properties( array $params = [] ): array|WP_Error;
$result = $client->get_offers(
    string $check_in,   // YYYY-MM-DD
    string $check_out,  // YYYY-MM-DD
    int    $num_adults = 1
): array|WP_Error;
```

### response shape

```json
// get_properties
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
      "featureCodes": [["LUXURY_LINEN"], ["SHARED_BATHROOM"]]
    }]
  }]
}

// get_offers
{
  "success": true,
  "data": [{
    "roomId": 567219,
    "propertyId": 271142,
    "offers": [{
      "offerId": 1,
      "price": 32,
      "unitsAvailable": 2
    }]
  }]
}
```

### error pattern

All public methods return `WP_Error` on failure. Always check:

```php
$result = $client->get_offers( '2026-05-14', '2026-05-16' );
if ( is_wp_error( $result ) ) {
    wp_die( $result->get_error_message() );  // Fail loud in dev
}
// Use $result['data']
```

Error codes:
- `beds24_api_error` — non-200 HTTP response
- `beds24_json_error` — JSON parse failure

### numadults=1 decision

Plugin sends `numAdults=1` on all offers queries. For flat per-room pricing
(all current properties), any adult count returns the same price. For dorms,
`numAdults=1` returns the per-bed price. Edge case: per-occupancy pricing on
private rooms would show wrong price. Check at rollout time for each property.

Source: `skills/beds24-api-work/references/api-client.md`, `docs/architecture.md`

