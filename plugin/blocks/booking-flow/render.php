<?php
/**
 * Server-side render for the beds24/booking-flow block.
 *
 * WordPress passes three variables into this file's scope:
 *
 * @var array    $attributes  Block attributes (none in V1 — all configuration
 *                            comes from site-wide plugin settings).
 * @var string   $content     Inner blocks content (empty; this block has no
 *                            inner blocks).
 * @var WP_Block $block       The block instance.
 *
 * The outer wrapper uses the BEM block class beds24-booking-flow together
 * with WordPress's auto-named wp-block-beds24-booking-flow. CSS custom
 * property defaults are defined on .beds24-booking-flow in style.css.
 *
 * The search form emits two native <input type="date"> fields plus a submit
 * button. No guest picker — date-only is a project design principle.
 * See docs/architecture.md §"Three design principles", principle 1.
 *
 * The minimum stay and property ID are passed to the frontend JS via
 * data attributes on the form element, keeping the JS free of PHP
 * dependencies and hardcoded property values.
 *
 * Note: if this block is placed more than once on a page, the static
 * id attributes (beds24-check-in, beds24-check-out) will duplicate.
 * V1 assumes one block instance per page. Multi-instance support
 * requires dynamic IDs and is deferred.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$property_id = beds24_booking_plugin_get_current_property_id();
$min_stay    = beds24_booking_plugin_get_min_stay();
?>
<div class="beds24-booking-flow wp-block-beds24-booking-flow">
    <form
        class="beds24-search-form"
        novalidate
        data-property-id="<?php echo esc_attr( $property_id ); ?>"
        data-min-stay="<?php echo esc_attr( $min_stay ); ?>"
    >
        <p class="beds24-search-form__min-stay">
            <?php
            echo esc_html(
                sprintf(
                    /* translators: %d: minimum stay length in nights */
                    _n(
                        'Minimum stay: %d night',
                        'Minimum stay: %d nights',
                        $min_stay,
                        'beds24-booking-plugin'
                    ),
                    $min_stay
                )
            );
            ?>
        </p>

        <div class="beds24-search-form__fields">
            <div class="beds24-search-form__field-group">
                <label class="beds24-search-form__label" for="beds24-check-in">
                    <?php esc_html_e( 'Check-in', 'beds24-booking-plugin' ); ?>
                </label>
                <input
                    class="beds24-search-form__check-in"
                    type="date"
                    id="beds24-check-in"
                    name="check_in"
                    required
                    aria-required="true"
                >
            </div>

            <div class="beds24-search-form__field-group">
                <label class="beds24-search-form__label" for="beds24-check-out">
                    <?php esc_html_e( 'Check-out', 'beds24-booking-plugin' ); ?>
                </label>
                <input
                    class="beds24-search-form__check-out"
                    type="date"
                    id="beds24-check-out"
                    name="check_out"
                    required
                    aria-required="true"
                >
            </div>
        </div>

        <div
            class="beds24-search-form__error"
            role="alert"
            aria-live="assertive"
            hidden
        ></div>

        <button class="beds24-search-form__submit" type="submit">
            <?php esc_html_e( 'Search Rooms', 'beds24-booking-plugin' ); ?>
        </button>
    </form>

    <div
        class="beds24-room-results"
        hidden
        aria-live="polite"
        aria-label="<?php esc_attr_e( 'Room search results', 'beds24-booking-plugin' ); ?>"
    ></div>

    <div
        class="beds24-cart"
        hidden
        aria-label="<?php esc_attr_e( 'Your booking cart', 'beds24-booking-plugin' ); ?>"
    >
        <h2 class="beds24-cart__heading">
            <?php esc_html_e( 'Your Stay', 'beds24-booking-plugin' ); ?>
        </h2>
        <ul class="beds24-cart__list" aria-label="<?php esc_attr_e( 'Selected rooms', 'beds24-booking-plugin' ); ?>"></ul>
        <div class="beds24-cart__footer">
            <span class="beds24-cart__total-label">
                <?php esc_html_e( 'Total per night', 'beds24-booking-plugin' ); ?>
            </span>
            <span class="beds24-cart__total"></span>
        </div>
        <div class="beds24-cart__actions">
            <button
                class="beds24-cart__confirm-button"
                type="button"
                disabled
                aria-label="<?php esc_attr_e( 'Confirm your booking', 'beds24-booking-plugin' ); ?>"
            >
                <?php esc_html_e( 'Confirm Booking', 'beds24-booking-plugin' ); ?>
            </button>
        </div>
    </div>

    <div
        class="beds24-booking-iframe-wrapper"
        hidden
        aria-label="<?php esc_attr_e( 'Beds24 booking form', 'beds24-booking-plugin' ); ?>"
    >
        <div class="beds24-booking-iframe-nav">
            <button
                class="beds24-booking-iframe-nav__back"
                type="button"
                aria-label="<?php esc_attr_e( 'Return to room selection', 'beds24-booking-plugin' ); ?>"
            >
                <?php esc_html_e( '← Back to rooms', 'beds24-booking-plugin' ); ?>
            </button>
        </div>
        <iframe
            class="beds24-booking-iframe"
            title="<?php esc_attr_e( 'Confirm your booking', 'beds24-booking-plugin' ); ?>"
            referrerpolicy="origin"
        ></iframe>
    </div>
</div>
