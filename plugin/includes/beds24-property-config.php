<?php
/**
 * Property configuration helpers.
 *
 * Provides the current property ID and per-property configuration values
 * consumed by the plugin's frontend rendering and API calls.
 *
 * Values are read from wp_options using the multi-property data model
 * introduced in Session 28:
 *
 *   beds24_booking_plugin_properties        — array of property config arrays
 *   beds24_booking_plugin_default_property  — int, the default property ID
 *
 * These functions are the migration boundary: callers elsewhere in the plugin
 * do not change when the data model changes — only these function bodies do.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get the current property's Beds24 property ID.
 *
 * Returns the value of beds24_booking_plugin_default_property in wp_options.
 * Set via the Properties admin page (Beds24 Booking > Properties).
 *
 * Returns 0 when no default property has been configured.
 *
 * @return int  Beds24 property ID, or 0 if not configured.
 */
function beds24_booking_plugin_get_current_property_id(): int {
    return (int) get_option( 'beds24_booking_plugin_default_property', 0 );
}

/**
 * Get the minimum stay length in nights for the current property.
 *
 * Reads from the beds24_booking_plugin_properties array in wp_options,
 * matching on the current default property ID.
 *
 * Returns 1 when no matching property is found or when min_stay is not set.
 *
 * @return int  Minimum stay in nights.
 */
function beds24_booking_plugin_get_min_stay(): int {
    $id         = beds24_booking_plugin_get_current_property_id();
    $properties = get_option( 'beds24_booking_plugin_properties', [] );
    foreach ( $properties as $prop ) {
        if ( (int) $prop['id'] === $id ) {
            return (int) $prop['min_stay'];
        }
    }
    return 1; // default when not configured
}

/**
 * Get the ISO 4217 currency code for the current property.
 *
 * Reads from the beds24_booking_plugin_properties array in wp_options,
 * matching on the current default property ID.
 *
 * Returns 'EUR' when no matching property is found or when currency is not set.
 *
 * @return string  ISO 4217 currency code (e.g. 'EUR', 'USD', 'GBP').
 */
function beds24_booking_plugin_get_currency(): string {
    $id         = beds24_booking_plugin_get_current_property_id();
    $properties = get_option( 'beds24_booking_plugin_properties', [] );
    foreach ( $properties as $prop ) {
        if ( (int) $prop['id'] === $id ) {
            return (string) $prop['currency'];
        }
    }
    return 'EUR'; // default when not configured
}
