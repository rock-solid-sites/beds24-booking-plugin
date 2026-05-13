<?php
/**
 * REST route: GET /beds24-booking-plugin/v1/offers
 *
 * Returns live availability and pricing from the Beds24 v2 API for a
 * requested date range. Consumed by the booking-flow block's view script.
 *
 * Authentication: wp_rest nonce via X-WP-Nonce header. The nonce is
 * localized to the view script by beds24_booking_plugin_localize_scripts()
 * in the main plugin file. Requests without a valid nonce are rejected
 * with 403.
 *
 * Rate limiting: sliding window — 30 requests per 60 seconds per IP
 * address. Excess requests return 429 with a Retry-After header.
 * The transient key used is beds24_bkp_rl_{16-char-ip-hash}.
 *
 * To flush a rate-limit transient during development (replace {hash}
 * with the first 16 chars of md5( $_SERVER['REMOTE_ADDR'] )):
 *   wp transient delete beds24_bkp_rl_{hash}
 * Or delete all plugin rate-limit transients:
 *   wp transient list --search="beds24_bkp_rl_*" | xargs wp transient delete
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Beds24_Offers_Route {

    /**
     * REST namespace for this plugin's routes.
     */
    const NAMESPACE = 'beds24-booking-plugin/v1';

    /**
     * Route path (relative to the namespace).
     */
    const ROUTE = '/offers';

    /**
     * Maximum requests allowed per IP per rate-limit window.
     */
    const RATE_LIMIT = 30;

    /**
     * Duration of the rate-limit window in seconds.
     */
    const RATE_WINDOW = 60;

    /**
     * Register the rest_api_init hook. Called once from the main plugin file.
     */
    public static function register(): void {
        add_action( 'rest_api_init', [ self::class, 'register_route' ] );
    }

    /**
     * Register the route with WordPress's REST server.
     */
    public static function register_route(): void {
        register_rest_route(
            self::NAMESPACE,
            self::ROUTE,
            [
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => [ self::class, 'handle' ],
                'permission_callback' => [ self::class, 'check_permission' ],
                'args'                => [
                    'check_in'  => [
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                        'validate_callback' => [ self::class, 'validate_date' ],
                        'description'       => 'Arrival date (YYYY-MM-DD).',
                    ],
                    'check_out' => [
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                        'validate_callback' => [ self::class, 'validate_date' ],
                        'description'       => 'Departure date (YYYY-MM-DD).',
                    ],
                ],
            ]
        );
    }

    /**
     * Validate that a parameter value is a well-formed calendar date (YYYY-MM-DD).
     *
     * @param  string $value  The raw parameter value.
     * @return bool           True if valid, false to let WP return a 400 error.
     */
    public static function validate_date( string $value ): bool {
        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) {
            return false;
        }
        $parts = explode( '-', $value );
        return checkdate( (int) $parts[1], (int) $parts[2], (int) $parts[0] );
    }

    /**
     * Permission callback: require a valid wp_rest nonce.
     *
     * WordPress's REST authentication pipeline checks the X-WP-Nonce header
     * automatically, but we also verify it explicitly here so that requests
     * without a nonce are always rejected, even on installations where the
     * automatic check is modified by other plugins.
     *
     * @param  WP_REST_Request  $request
     * @return true|\WP_Error
     */
    public static function check_permission( \WP_REST_Request $request ): true|\WP_Error {
        $nonce = $request->get_header( 'X-WP-Nonce' );

        if ( ! $nonce ) {
            return new \WP_Error(
                'rest_forbidden',
                'A valid nonce is required.',
                [ 'status' => 403 ]
            );
        }

        if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
            return new \WP_Error(
                'rest_forbidden',
                'Invalid or expired nonce.',
                [ 'status' => 403 ]
            );
        }

        return true;
    }

    /**
     * Route handler: enforce rate limit, call the API client, return results.
     *
     * @param  WP_REST_Request  $request
     * @return WP_REST_Response|\WP_Error
     */
    public static function handle( \WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
        // Rate limiting — returns a 429 WP_REST_Response on excess, null otherwise.
        $rate_response = self::check_rate_limit();
        if ( $rate_response !== null ) {
            return $rate_response;
        }

        $check_in  = $request->get_param( 'check_in' );
        $check_out = $request->get_param( 'check_out' );

        $property_id = beds24_booking_plugin_get_current_property_id();
        $client      = new Beds24_API_Client( $property_id );
        $result      = $client->get_offers( $check_in, $check_out );

        if ( is_wp_error( $result ) ) {
            // Translate the API client's WP_Error into a REST error response.
            // 502 signals to the caller that the upstream API (Beds24) failed.
            return new \WP_Error(
                $result->get_error_code(),
                $result->get_error_message(),
                [ 'status' => 502 ]
            );
        }

        return new \WP_REST_Response( $result, 200 );
    }

    /**
     * Enforce the per-IP sliding-window rate limit.
     *
     * Returns null when the request is within the allowed rate, or a
     * WP_REST_Response with status 429 and a Retry-After header when
     * the limit is exceeded.
     *
     * The sliding-window model means each new request within the window
     * resets the window's TTL. A burst of 30 requests blocks further
     * requests for RATE_WINDOW seconds after the last request in the burst.
     *
     * @return WP_REST_Response|null  429 response on excess; null if OK.
     */
    private static function check_rate_limit(): ?\WP_REST_Response {
        $ip      = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
        $ip_hash = substr( md5( $ip ), 0, 16 );
        $key     = 'beds24_bkp_rl_' . $ip_hash;

        $count = get_transient( $key );

        if ( $count === false ) {
            // First request in this window.
            set_transient( $key, 1, self::RATE_WINDOW );
            return null;
        }

        if ( (int) $count >= self::RATE_LIMIT ) {
            $response = new \WP_REST_Response(
                [
                    'code'    => 'beds24_rate_limited',
                    'message' => 'Too many search requests. Please wait a moment before searching again.',
                ],
                429
            );
            $response->header( 'Retry-After', (string) self::RATE_WINDOW );
            return $response;
        }

        // Increment the counter. set_transient with the same key resets the
        // TTL (sliding window behaviour).
        set_transient( $key, (int) $count + 1, self::RATE_WINDOW );
        return null;
    }
}
