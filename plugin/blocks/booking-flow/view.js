/**
 * Beds24 Booking Flow — search form client-side validation.
 *
 * Behaviour:
 *   - Intercepts form submission and validates the date inputs.
 *   - Shows per-failure error messages in the form's error region.
 *   - On passing validation, logs the validated dates and property ID.
 *   - The API call (get_offers) and results rendering are wired in
 *     Session 10 once the room card layer exists to consume the response.
 *
 * Minimum stay and property ID are read from data attributes on the
 * form element (data-min-stay, data-property-id), populated by the PHP
 * render callback. This keeps the JS free of hardcoded property values
 * and decoupled from PHP globals.
 *
 * Validation rules:
 *   1. Both dates must be present.
 *   2. Check-in must not be in the past.
 *   3. Check-out must be strictly after check-in.
 *   4. Stay length must be >= minimum stay (from data-min-stay).
 *
 * Plain ES5-compatible JavaScript — no build step, no dependencies.
 */
( function () {
    'use strict';

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

    /**
     * Handle form submission: validate inputs, show errors, or proceed.
     *
     * @param {Event} e
     */
    function onSubmit( e ) {
        e.preventDefault();

        var form       = e.currentTarget;
        var checkInEl  = form.querySelector( '.beds24-search-form__check-in' );
        var checkOutEl = form.querySelector( '.beds24-search-form__check-out' );
        var minStay    = parseInt( form.dataset.minStay   || '1', 10 );
        var propertyId = parseInt( form.dataset.propertyId || '0', 10 );

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

        // All rules pass.
        console.log( '[Beds24] Search validated', {
            checkIn:    checkInEl.value,
            checkOut:   checkOutEl.value,
            nights:     nights,
            propertyId: propertyId
        } );

        // Next: dispatch AJAX availability search and render room results.
        // Wired in Session 10 once the room card rendering layer is in place.
    }

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
