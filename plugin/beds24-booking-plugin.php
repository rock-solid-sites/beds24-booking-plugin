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
require_once BEDS24_BOOKING_PLUGIN_DIR . 'includes/beds24-property-config.php';
require_once BEDS24_BOOKING_PLUGIN_DIR . 'includes/class-beds24-offers-route.php';
require_once BEDS24_BOOKING_PLUGIN_DIR . 'includes/beds24-room-cpt.php';
require_once BEDS24_BOOKING_PLUGIN_DIR . 'includes/beds24-room-meta-box.php';
require_once BEDS24_BOOKING_PLUGIN_DIR . 'includes/iframe-css-generator.php';
require_once BEDS24_BOOKING_PLUGIN_DIR . 'includes/beds24-admin-page.php';

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
// REST route registration
// ---------------------------------------------------------------------------

Beds24_Offers_Route::register();

// ---------------------------------------------------------------------------
// Frontend script localization
// ---------------------------------------------------------------------------

add_action( 'wp_enqueue_scripts', 'beds24_booking_plugin_localize_scripts' );

/**
 * Localize the booking-flow view script with the REST nonce and endpoint URL.
 *
 * wp_localize_script() stores data against the script handle in WP_Scripts.
 * The data is output (as a JS variable assignment) whenever the script is
 * printed — even when the script is enqueued lazily via block rendering.
 *
 * The script handle 'beds24-booking-flow-view-script' is registered
 * automatically by register_block_type() reading block.json on init.
 */
function beds24_booking_plugin_localize_scripts(): void {
    wp_localize_script(
        'beds24-booking-flow-view-script',
        'beds24BookingPlugin',
        [
            'nonce'   => wp_create_nonce( 'wp_rest' ),
            'restUrl' => rest_url( 'beds24-booking-plugin/v1/offers' ),
        ]
    );
}

// ---------------------------------------------------------------------------
// Block registration
// ---------------------------------------------------------------------------

add_action( 'init', 'beds24_booking_plugin_register_blocks' );

/**
 * Register the beds24/booking-flow block.
 *
 * WordPress reads block.json from the block directory and handles
 * editorScript enqueueing and render callback wiring automatically.
 * The render callback is defined in blocks/booking-flow/render.php.
 */
function beds24_booking_plugin_register_blocks(): void {
    register_block_type( BEDS24_BOOKING_PLUGIN_DIR . 'blocks/booking-flow' );
}
