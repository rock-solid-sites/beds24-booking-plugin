<?php
/**
 * Plugin Name: Beds24 Booking Plugin
 * Plugin URI: https://github.com/rock-solid-sites/beds24-booking-plugin
 * Description: WordPress booking plugin using Beds24 v2 API for discovery and Beds24's iframe for transactions.
 * Version: 0.1.0
 * Author: Rock Solid Sites
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: beds24-booking-plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'BEDS24_BOOKING_PLUGIN_VERSION', '0.1.0' );
define( 'BEDS24_BOOKING_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BEDS24_BOOKING_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// ---------------------------------------------------------------------------
// Autoload includes
// ---------------------------------------------------------------------------

require_once BEDS24_BOOKING_PLUGIN_DIR . 'includes/class-beds24-api-client.php';

// ---------------------------------------------------------------------------
// Activation and deactivation hooks
// ---------------------------------------------------------------------------

register_activation_hook( __FILE__, 'beds24_booking_plugin_activate' );
register_deactivation_hook( __FILE__, 'beds24_booking_plugin_deactivate' );

/**
 * Plugin activation.
 *
 * Sets a version flag in wp_options so future upgrades can detect the
 * installed version. Does not create database tables — the plugin uses
 * wp_options for all persistent state.
 */
function beds24_booking_plugin_activate(): void {
    update_option( 'beds24_booking_plugin_version', BEDS24_BOOKING_PLUGIN_VERSION );
}

/**
 * Plugin deactivation.
 *
 * Flushes any cached access tokens from transients. Refresh tokens in
 * wp_options are left in place — they are removed only on uninstall.
 */
function beds24_booking_plugin_deactivate(): void {
    // Access token transients expire on their own, but flush them now
    // so a reactivation starts clean.
    $property_ids = get_option( 'beds24_booking_plugin_property_ids', [] );
    foreach ( $property_ids as $property_id ) {
        delete_transient( 'beds24_bkp_access_token_' . intval( $property_id ) );
    }
}

// ---------------------------------------------------------------------------
// Shortcode
// ---------------------------------------------------------------------------

add_shortcode( 'beds24_booking', 'beds24_booking_shortcode' );

/**
 * [beds24_booking] shortcode handler.
 *
 * V1 stub: confirms the plugin is loaded. The real search form and room
 * results rendering will replace this output in a later session.
 *
 * @param  array $atts Shortcode attributes (unused in V1 stub).
 * @return string      HTML output.
 */
function beds24_booking_shortcode( array $atts = [] ): string {
    return '<div class="beds24-booking-plugin">Beds24 Booking Plugin loaded.</div>';
}
