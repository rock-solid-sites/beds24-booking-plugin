/**
 * Beds24 Booking Flow — search form client-side validation and AJAX dispatch.
 *
 * Behaviour:
 *   - Intercepts form submission and validates the date inputs.
 *   - Shows per-failure error messages in the form's error region.
 *   - On passing validation, dispatches a GET request to the plugin's
 *     REST route (/beds24-booking-plugin/v1/offers) with the validated dates.
 *   - On success: logs the parsed offer data and count to the console.
 *     Room card rendering is wired in a later session.
 *   - On error: surfaces a user-readable message in the form's error region.
 *
 * Minimum stay and property ID are read from data attributes on the form
 * element (data-min-stay, data-property-id), populated by the PHP render
 * callback. The REST nonce and endpoint URL are read from the localized
 * window.beds24BookingPlugin object, populated by wp_localize_script() in
 * the main plugin file.
 *
 * Validation rules:
 *   1. Both dates must be present.
 *   2. Check-in must not be in the past.
 *   3. Check-out must be strictly after check-in.
 *   4. Stay length must be >= minimum stay (from data-min-stay).
 *
 * Plain ES5-compatible syntax. Uses fetch() (requires a modern browser —
 * all browsers that support WordPress's block editor support fetch).
 */
( function () {
    'use strict';

    // -----------------------------------------------------------------------
    // DOM helpers
    // -----------------------------------------------------------------------

    /**
     * Show an error message in the form's error region and reveal it.
     *
     * @param {HTMLElement} form
     * @param {string}      message
     */
    function showError( form, message ) {
        var el = form.querySelector( '.beds24-search-form__error' );
        if ( ! el ) {
            return;
        }
        el.textContent = message;
        el.removeAttribute( 'hidden' );
    }

    /**
     * Clear the error region content and hide it.
     *
     * @param {HTMLElement} form
     */
    function clearError( form ) {
        var el = form.querySelector( '.beds24-search-form__error' );
        if ( ! el ) {
            return;
        }
        el.textContent = '';
        el.setAttribute( 'hidden', '' );
    }

    // -----------------------------------------------------------------------
    // Date utilities
    // -----------------------------------------------------------------------

    /**
     * Parse a YYYY-MM-DD date string into a Date at midnight local time.
     *
     * The Date constructor treats bare ISO-format strings (YYYY-MM-DD) as
     * UTC, shifting the date by the user's timezone offset and producing
     * off-by-one errors for local date comparisons. Explicit year/month/day
     * construction avoids this.
     *
     * @param  {string} value  Date string from <input type="date">.
     * @return {Date}
     */
    function parseLocalDate( value ) {
        var parts = value.split( '-' );
        return new Date(
            parseInt( parts[ 0 ], 10 ),
            parseInt( parts[ 1 ], 10 ) - 1,
            parseInt( parts[ 2 ], 10 )
        );
    }

    /**
     * Return the number of whole nights between two dates.
     *
     * @param  {Date} checkIn
     * @param  {Date} checkOut
     * @return {number}
     */
    function countNights( checkIn, checkOut ) {
        var ms = checkOut.getTime() - checkIn.getTime();
        return Math.round( ms / ( 1000 * 60 * 60 * 24 ) );
    }

    // -----------------------------------------------------------------------
    // AJAX dispatch
    // -----------------------------------------------------------------------

    /**
     * Dispatch an availability search to the plugin's REST route.
     *
     * Reads the nonce and REST URL from window.beds24BookingPlugin (populated
     * by wp_localize_script in the main plugin file). Calls handleSearchResponse
     * on a 200 response, handleSearchError on any non-200 or network failure.
     *
     * @param {string}      checkIn   Validated check-in date (YYYY-MM-DD).
     * @param {string}      checkOut  Validated check-out date (YYYY-MM-DD).
     * @param {HTMLElement} form      The search form (passed through to response/error handlers).
     */
    function searchOffers( checkIn, checkOut, form ) {
        var config  = window.beds24BookingPlugin || {};
        var nonce   = config.nonce   || '';
        var restUrl = config.restUrl || '';

        if ( ! restUrl ) {
            // Localization failed — the script data wasn't injected.
            handleSearchError( form, 'Search is temporarily unavailable. Please reload the page and try again.' );
            return;
        }

        var url = restUrl +
            '?check_in='  + encodeURIComponent( checkIn ) +
            '&check_out=' + encodeURIComponent( checkOut );

        fetch( url, {
            method: 'GET',
            headers: {
                'X-WP-Nonce': nonce,
            },
        } )
        .then( function( response ) {
            return response.json().then( function( data ) {
                return { status: response.status, data: data };
            } );
        } )
        .then( function( result ) {
            if ( result.status !== 200 ) {
                // Surface the message from the REST error response if present.
                var msg = ( result.data && result.data.message )
                    ? result.data.message
                    : 'Search failed. Please try again.';
                handleSearchError( form, msg );
                return;
            }
            handleSearchResponse( result.data, form );
        } )
        .catch( function() {
            handleSearchError( form, 'Network error. Please check your connection and try again.' );
        } );
    }

    /**
     * Handle a successful offers response (HTTP 200).
     *
     * Phase: logs offer data to the console only. Room card rendering
     * is wired in a later session once the card layer exists to consume
     * the response.
     *
     * No-availability (all rooms have empty offers[]) is logged distinctly
     * from an error — it is a valid state, not a failure.
     *
     * @param {Object}      data  Parsed JSON body from the REST route.
     * @param {HTMLElement} form  The search form (available for future DOM updates).
     */
    function handleSearchResponse( data, form ) { // eslint-disable-line no-unused-vars
        var rooms = ( data && data.data ) ? data.data : [];
        var offerCount = 0;
        var i;
        for ( i = 0; i < rooms.length; i++ ) {
            offerCount += ( rooms[ i ].offers || [] ).length;
        }

        if ( offerCount === 0 ) {
            console.log( '[Beds24] No availability for selected dates', {
                roomCount: rooms.length,
                data: data,
            } );
        } else {
            console.log( '[Beds24] Offers received', {
                roomCount:  rooms.length,
                offerCount: offerCount,
                data:       data,
            } );
        }

        // Room card rendering wired in a later session.
    }

    /**
     * Handle a failed offers request.
     *
     * Surfaces the error message in the form's existing error region via
     * the showError() helper. Using showError() keeps the error presentation
     * consistent with validation errors.
     *
     * @param {HTMLElement} form
     * @param {string}      message  User-readable error description.
     */
    function handleSearchError( form, message ) {
        showError( form, message );
    }

    // -----------------------------------------------------------------------
    // Form submission
    // -----------------------------------------------------------------------

    /**
     * Handle form submission: validate inputs, show errors, or dispatch search.
     *
     * @param {Event} e
     */
    function onSubmit( e ) {
        e.preventDefault();

        var form       = e.currentTarget;
        var checkInEl  = form.querySelector( '.beds24-search-form__check-in' );
        var checkOutEl = form.querySelector( '.beds24-search-form__check-out' );
        var minStay    = parseInt( form.dataset.minStay   || '1', 10 );

        clearError( form );

        // Rule 1: both dates must be present.
        if ( ! checkInEl || ! checkInEl.value ) {
            showError( form, 'Please enter a check-in date.' );
            if ( checkInEl ) {
                checkInEl.focus();
            }
            return;
        }
        if ( ! checkOutEl || ! checkOutEl.value ) {
            showError( form, 'Please enter a check-out date.' );
            if ( checkOutEl ) {
                checkOutEl.focus();
            }
            return;
        }

        var today = new Date();
        today.setHours( 0, 0, 0, 0 );

        var checkIn  = parseLocalDate( checkInEl.value );
        var checkOut = parseLocalDate( checkOutEl.value );

        // Rule 2: check-in must not be in the past.
        if ( checkIn < today ) {
            showError( form, 'Check-in date cannot be in the past.' );
            checkInEl.focus();
            return;
        }

        // Rule 3: check-out must be strictly after check-in.
        if ( checkOut <= checkIn ) {
            showError( form, 'Check-out date must be after check-in date.' );
            checkOutEl.focus();
            return;
        }

        // Rule 4: stay length must meet minimum.
        var nights = countNights( checkIn, checkOut );
        if ( nights < minStay ) {
            showError(
                form,
                'Minimum stay is ' + minStay + ( minStay === 1 ? ' night.' : ' nights.' )
            );
            checkInEl.focus();
            return;
        }

        // All validation rules pass — dispatch the availability search.
        searchOffers( checkInEl.value, checkOutEl.value, form );
    }

    // -----------------------------------------------------------------------
    // Initialisation
    // -----------------------------------------------------------------------

    /**
     * Attach submit handler to the search form.
     */
    function init() {
        var form = document.querySelector( '.beds24-search-form' );
        if ( ! form ) {
            return;
        }
        form.addEventListener( 'submit', onSubmit );
    }

    // Guard against DOMContentLoaded having already fired (e.g. deferred scripts).
    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', init );
    } else {
        init();
    }
}() );
