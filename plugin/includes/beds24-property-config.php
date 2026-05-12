<?php
/**
 * Property configuration helpers.
 *
 * Provides the current property ID and per-property configuration values
 * consumed by the plugin's frontend rendering and API calls.
 *
 * V1 architecture: Each WordPress install serves a single Beds24 property.
 * The values returned here are hardcoded in V1. When the plugin settings page
 * lands (V1.x), these function bodies change to read from wp_options; nothing
 * else in the codebase moves. These functions are the migration boundary.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get the current property's Beds24 property ID.
 *
 * V1: Hardcoded to 271142 (Chill Zone).
 *
 * V1.x migration target: replace the function body with:
 *   return (int) get_option( 'beds24_booking_plugin_current_property_id', 0 );
 * No other callers change — this function is the migration boundary.
 *
 * @return int  Beds24 property ID.
 */
function beds24_booking_plugin_get_current_property_id(): int {
    return 271142;
}

/**
 * Get the minimum stay length in nights for the current property.
 *
 * V1: Hardcoded to 2 (Chill Zone's configured minimum).
 *
 * V1.x migration target: read from wp_options or from the Beds24 API
 * property metadata response.
 *
 * @return int  Minimum stay in nights.
 */
function beds24_booking_plugin_get_min_stay(): int {
    return 2;
}
