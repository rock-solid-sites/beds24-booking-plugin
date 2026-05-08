<?php
/**
 * Beds24 v2 API client.
 *
 * Handles authentication, token storage, token rotation, and the two
 * endpoint methods the plugin uses for discovery (getProperties and
 * getOffers).
 *
 * Auth flow (one-time per property):
 *   1. Operator generates an invite code in Beds24 admin → MARKETPLACE > API.
 *   2. Plugin calls exchange_invite_code( $code ) which hits
 *      GET /authentication/setup with the code as a header.
 *   3. The resulting refresh token is stored in wp_options under
 *      beds24_booking_plugin_refresh_token_{property_id}.
 *
 * Ongoing auth (automatic):
 *   4. Before each API call, get_access_token() checks the transient
 *      beds24_bkp_access_token_{property_id}.
 *   5. On cache miss, it calls GET /authentication/token with the stored
 *      refresh token and caches the new access token for (expiresIn - 60)
 *      seconds.
 *
 * Error handling:
 *   All public methods return WP_Error on failure — never silently degrade.
 *   HTTP errors, JSON parse errors, and missing token states all surface as
 *   WP_Error. Callers check with is_wp_error() before using the result.
 *
 * @see docs/skill/api-client.md for the full reference.
 * @see docs/reference/beds24-api-v2/openapi.yaml for endpoint schemas.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Beds24_API_Client {

    /**
     * Beds24 v2 API base URL. No trailing slash.
     */
    private const BASE_URL = 'https://beds24.com/api/v2';

    /**
     * Default HTTP timeout in seconds for all API requests.
     */
    private const TIMEOUT = 15;

    /**
     * The Beds24 property ID this client instance operates on.
     *
     * @var int
     */
    private int $property_id;

    /**
     * @param int $property_id  Beds24 property ID (e.g. 271142 for Chill Zone).
     */
    public function __construct( int $property_id ) {
        $this->property_id = $property_id;
    }

    // -----------------------------------------------------------------------
    // Token storage keys
    // -----------------------------------------------------------------------

    /**
     * wp_options key for the property's refresh token.
     * Convention: beds24_booking_plugin_refresh_token_{property_id}
     */
    private function refresh_token_option_key(): string {
        return 'beds24_booking_plugin_refresh_token_' . $this->property_id;
    }

    /**
     * Transient key for the property's cached access token.
     * Convention: beds24_bkp_access_token_{property_id}
     * (Shortened to stay under WordPress's 172-char transient key limit.)
     */
    private function access_token_transient_key(): string {
        return 'beds24_bkp_access_token_' . $this->property_id;
    }

    // -----------------------------------------------------------------------
    // Refresh token — one-time setup
    // -----------------------------------------------------------------------

    /**
     * Store a refresh token in wp_options for this property.
     *
     * Called after a successful invite code exchange. May also be called
     * directly by the plugin admin UI when seeding a token from another
     * source.
     *
     * @param string $refresh_token
     */
    public function store_refresh_token( string $refresh_token ): void {
        update_option( $this->refresh_token_option_key(), $refresh_token );
    }

    /**
     * Retrieve the stored refresh token for this property.
     *
     * @return string|false  The refresh token, or false if not set.
     */
    public function get_refresh_token(): string|false {
        return get_option( $this->refresh_token_option_key(), false );
    }

    /**
     * Returns true if a refresh token is stored for this property.
     */
    public function has_refresh_token(): bool {
        return $this->get_refresh_token() !== false;
    }

    /**
     * Exchange a Beds24 invite code for a refresh token and store it.
     *
     * This is a one-time operation. The invite code is consumed on success
     * and cannot be reused. On failure, the code may also be consumed
     * depending on the error — do not retry with the same code.
     *
     * Endpoint: GET /authentication/setup
     * Header:   code: {invite_code}
     *
     * On success, stores the refresh token in wp_options and returns the
     * full response array: { token, expiresIn, refreshToken }.
     *
     * @param  string          $invite_code  The invite code from Beds24 admin.
     * @return array|WP_Error  Parsed response on success, WP_Error on failure.
     */
    public function exchange_invite_code( string $invite_code ): array|\WP_Error {
        $response = wp_remote_get(
            self::BASE_URL . '/authentication/setup',
            [
                'headers' => [
                    'accept'  => 'application/json',
                    'code'    => $invite_code,
                ],
                'timeout' => self::TIMEOUT,
            ]
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $http_code = wp_remote_retrieve_response_code( $response );
        $body      = wp_remote_retrieve_body( $response );
        $data      = json_decode( $body, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new \WP_Error(
                'beds24_json_error',
                sprintf(
                    'Failed to parse authentication/setup response: %s. Raw body: %s',
                    json_last_error_msg(),
                    $body
                )
            );
        }

        if ( $http_code !== 200 ) {
            return new \WP_Error(
                'beds24_api_error',
                sprintf(
                    'Authentication setup failed (HTTP %d). Response: %s',
                    $http_code,
                    $body
                )
            );
        }

        if ( empty( $data['refreshToken'] ) ) {
            return new \WP_Error(
                'beds24_missing_token',
                'Authentication setup response missing refreshToken field. Full response: ' . $body
            );
        }

        $this->store_refresh_token( $data['refreshToken'] );

        return $data;
    }

    // -----------------------------------------------------------------------
    // Access token — automatic rotation
    // -----------------------------------------------------------------------

    /**
     * Get a valid access token, acquiring one if the cached token is expired.
     *
     * Checks the transient cache first. On cache miss, exchanges the stored
     * refresh token for a new access token via GET /authentication/token and
     * caches it with a (expiresIn - 60) second TTL.
     *
     * @return string|\WP_Error  Access token string, or WP_Error on failure.
     */
    private function get_access_token(): string|\WP_Error {
        $cached = get_transient( $this->access_token_transient_key() );

        if ( $cached !== false ) {
            return $cached;
        }

        $refresh_token = $this->get_refresh_token();

        if ( ! $refresh_token ) {
            return new \WP_Error(
                'beds24_no_refresh_token',
                sprintf(
                    'No refresh token stored for property %d. ' .
                    'Run the invite code exchange via Beds24 Booking Plugin settings.',
                    $this->property_id
                )
            );
        }

        $response = wp_remote_get(
            self::BASE_URL . '/authentication/token',
            [
                'headers' => [
                    'accept'       => 'application/json',
                    'refreshToken' => $refresh_token,
                ],
                'timeout' => self::TIMEOUT,
            ]
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $http_code = wp_remote_retrieve_response_code( $response );
        $body      = wp_remote_retrieve_body( $response );
        $data      = json_decode( $body, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new \WP_Error(
                'beds24_json_error',
                sprintf(
                    'Failed to parse authentication/token response: %s',
                    json_last_error_msg()
                )
            );
        }

        if ( $http_code !== 200 ) {
            return new \WP_Error(
                'beds24_api_error',
                sprintf(
                    'Token refresh failed (HTTP %d). Response: %s',
                    $http_code,
                    $body
                )
            );
        }

        if ( empty( $data['token'] ) ) {
            return new \WP_Error(
                'beds24_missing_token',
                'Token refresh response missing token field. Full response: ' . $body
            );
        }

        $token      = $data['token'];
        $expires_in = isset( $data['expiresIn'] ) ? (int) $data['expiresIn'] : 3600;
        $ttl        = max( 60, $expires_in - 60 ); // 1-minute safety buffer

        set_transient( $this->access_token_transient_key(), $token, $ttl );

        return $token;
    }

    // -----------------------------------------------------------------------
    // HTTP — authenticated GET
    // -----------------------------------------------------------------------

    /**
     * Make an authenticated GET request to the Beds24 v2 API.
     *
     * Acquires a fresh access token if the cached one is expired.
     * Returns the parsed JSON response body as an array.
     *
     * @param  string          $endpoint  Path relative to BASE_URL (e.g. '/properties').
     * @param  array           $params    Query string parameters as key => value.
     * @return array|\WP_Error            Parsed response array, or WP_Error on failure.
     */
    private function get( string $endpoint, array $params = [] ): array|\WP_Error {
        $token = $this->get_access_token();

        if ( is_wp_error( $token ) ) {
            return $token;
        }

        $url = self::BASE_URL . $endpoint;

        if ( ! empty( $params ) ) {
            $url = add_query_arg( $params, $url );
        }

        $response = wp_remote_get(
            $url,
            [
                'headers' => [
                    'accept' => 'application/json',
                    'token'  => $token,
                ],
                'timeout' => self::TIMEOUT,
            ]
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $http_code = wp_remote_retrieve_response_code( $response );
        $body      = wp_remote_retrieve_body( $response );
        $data      = json_decode( $body, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return new \WP_Error(
                'beds24_json_error',
                sprintf(
                    'Failed to parse API response from %s: %s',
                    $endpoint,
                    json_last_error_msg()
                )
            );
        }

        if ( $http_code !== 200 ) {
            return new \WP_Error(
                'beds24_api_error',
                sprintf(
                    'API request to %s failed (HTTP %d). Response: %s',
                    $endpoint,
                    $http_code,
                    $body
                )
            );
        }

        return $data;
    }

    // -----------------------------------------------------------------------
    // Public endpoint methods
    // -----------------------------------------------------------------------

    /**
     * GET /properties — property and room metadata.
     *
     * Fetches the Beds24 property record including all room types.
     * Used once at setup time and cached by callers (not cached here —
     * property data changes infrequently; caching strategy is the caller's
     * responsibility for V1).
     *
     * Default parameters:
     *   includeAllRooms=true  — includes rooms regardless of availability
     *   id={property_id}      — scoped to this client's property
     *
     * Response shape (excerpt):
     *   {
     *     "success": true,
     *     "data": [{
     *       "id": 271142,
     *       "name": "...",
     *       "roomTypes": [{
     *         "id": 567219,
     *         "name": "...",
     *         "roomType": "bedInDormitory",
     *         "qty": 4,
     *         "maxPeople": 4,
     *         "featureCodes": [["SHARED_BATHROOM"], ["HEATING_INDIVIDUAL"]],
     *         ...
     *       }]
     *     }]
     *   }
     *
     * Note: featureCodes is a list of lists (feature groups), not a flat list.
     * Note: maxPeople is the occupancy limit field; maxAdult is null.
     *
     * @param  array           $params  Additional query params to merge.
     * @return array|\WP_Error          Parsed response, or WP_Error on failure.
     */
    public function get_properties( array $params = [] ): array|\WP_Error {
        return $this->get(
            '/properties',
            array_merge(
                [
                    'id'             => $this->property_id,
                    'includeAllRooms' => 'true',
                ],
                $params
            )
        );
    }

    /**
     * GET /inventory/rooms/offers — live availability and pricing.
     *
     * Fetches available offers for all rooms in this property for the
     * given date range and adult count.
     *
     * Date format: YYYY-MM-DD (e.g. '2026-05-14').
     *
     * numAdults is sent as 1 for all searches (architecture decision §5):
     * - Dorms: returns per-bed total price. Display: total/nights = price/bed/night.
     * - Privates: returns per-room total price. Display: total/nights = price/night.
     * Override the default only for per-occupancy properties (see architecture §5).
     *
     * Response shape (excerpt):
     *   {
     *     "success": true,
     *     "data": [{
     *       "roomId": 567219,
     *       "propertyId": 271142,
     *       "offers": [{
     *         "offerId": 1,
     *         "offerName": "",
     *         "price": 32,
     *         "unitsAvailable": 2
     *       }]
     *     }]
     *   }
     *
     * Rooms with no available offers appear in data[] with an empty offers[].
     * They should be displayed as unavailable, not hidden (architecture §3).
     *
     * @param  string          $check_in   Arrival date (YYYY-MM-DD).
     * @param  string          $check_out  Departure date (YYYY-MM-DD).
     * @param  int             $num_adults Number of adults / beds (default 1).
     * @return array|\WP_Error             Parsed response, or WP_Error on failure.
     */
    public function get_offers( string $check_in, string $check_out, int $num_adults = 1 ): array|\WP_Error {
        return $this->get(
            '/inventory/rooms/offers',
            [
                'arrival'    => $check_in,
                'departure'  => $check_out,
                'numAdults'  => $num_adults,
                'propertyId' => $this->property_id,
            ]
        );
    }
}
