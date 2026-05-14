/**
 * Beds24 Booking Flow — search form client-side validation, AJAX dispatch,
 * and room card rendering.
 *
 * Behaviour:
 *   - Intercepts form submission and validates the date inputs.
 *   - Shows per-failure error messages in the form's error region.
 *   - On passing validation, dispatches a GET request to the plugin's
 *     REST route (/beds24-booking-plugin/v1/offers) with the validated dates.
 *   - On success: renders one room card per room in .beds24-room-results.
 *     Cards render for both available and unavailable rooms; unavailable rooms
 *     get the --unavailable modifier and show "Not available for selected dates."
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
    // Room card rendering
    // -----------------------------------------------------------------------

    /**
     * Build a single room card DOM element.
     *
     * An offer with no matching WordPress post (room.wpContent === null) fails
     * loud: a console.warn names the missing room ID and the corrective action.
     * The card still renders, using the Beds24 room ID as a fallback title, so
     * the mismatch is visible in the UI as well as the console.
     *
     * @param  {Object} room            Room item from the enriched offers response.
     * @param  {number} nights          Number of nights in the search.
     * @param  {string} currencySymbol  Currency symbol (e.g. '€').
     * @return {HTMLElement}            The built card element.
     */
    function buildCard( room, nights, currencySymbol ) {
        var content   = room.wpContent;
        var offers    = room.offers || [];
        var available = offers.length > 0;

        if ( ! content ) {
            console.warn(
                '[Beds24] No matching beds24_room post for roomId: ' + room.roomId +
                '. Create a beds24_room post with _beds24_room_id set to ' + room.roomId + '.'
            );
        }

        var card = document.createElement( 'div' );
        card.className = 'beds24-room-card' + ( available ? '' : ' beds24-room-card--unavailable' );
        card.setAttribute( 'data-room-id', String( room.roomId ) );

        // Room name heading.
        var nameEl = document.createElement( 'h3' );
        nameEl.className = 'beds24-room-card__name';
        nameEl.textContent = content ? content.title : ( 'Room ' + room.roomId );
        card.appendChild( nameEl );

        // Body: photo + description side by side.
        var bodyEl = document.createElement( 'div' );
        bodyEl.className = 'beds24-room-card__body';

        if ( content && content.imageUrl ) {
            var photoEl = document.createElement( 'div' );
            photoEl.className = 'beds24-room-card__photo';
            var imgEl = document.createElement( 'img' );
            imgEl.src = content.imageUrl;
            imgEl.alt = content.imageAlt || content.title || '';
            imgEl.loading = 'lazy';
            photoEl.appendChild( imgEl );
            bodyEl.appendChild( photoEl );
        }

        var contentEl = document.createElement( 'div' );
        contentEl.className = 'beds24-room-card__content';
        if ( content && content.description ) {
            var descEl = document.createElement( 'p' );
            descEl.className = 'beds24-room-card__description';
            descEl.textContent = content.description;
            contentEl.appendChild( descEl );
        }
        bodyEl.appendChild( contentEl );
        card.appendChild( bodyEl );

        // Offer row: price (available) or unavailable notice.
        var offerEl = document.createElement( 'div' );
        offerEl.className = 'beds24-room-card__offer';

        if ( available ) {
            var offer    = offers[ 0 ];
            var perNight = nights > 0 ? Math.round( offer.price / nights ) : offer.price;
            var priceEl  = document.createElement( 'p' );
            priceEl.className = 'beds24-room-card__price';
            priceEl.textContent = 'from ' + currencySymbol + perNight + ' / night';
            offerEl.appendChild( priceEl );
        } else {
            var noticeEl = document.createElement( 'p' );
            noticeEl.className = 'beds24-room-card__unavailable-notice';
            noticeEl.textContent = 'Not available for selected dates';
            offerEl.appendChild( noticeEl );
        }

        card.appendChild( offerEl );
        return card;
    }

    /**
     * Render all room cards into the results container and reveal it.
     *
     * Clears any previous results before inserting new cards.
     *
     * @param {Array}       rooms           Room items from the enriched offers response.
     * @param {number}      nights          Number of nights in the search.
     * @param {string}      currencySymbol  Currency symbol (e.g. '€').
     * @param {HTMLElement} container       The .beds24-room-results element.
     */
    function renderRoomCards( rooms, nights, currencySymbol, container ) {
        if ( ! container ) {
            console.error( '[Beds24] Room results container (.beds24-room-results) not found in the page DOM.' );
            return;
        }

        // Clear previous results.
        container.textContent = '';

        var i;
        for ( i = 0; i < rooms.length; i++ ) {
            container.appendChild( buildCard( rooms[ i ], nights, currencySymbol ) );
        }

        container.removeAttribute( 'hidden' );
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
     * Renders room cards into .beds24-room-results. Each room in the response
     * gets a card — available rooms show price, unavailable rooms show a notice.
     * When no rooms have availability, all cards render in the unavailable state.
     *
     * @param {Object}      data  Parsed JSON body from the REST route.
     * @param {HTMLElement} form  The search form.
     */
    function handleSearchResponse( data, form ) {
        var rooms          = ( data && data.data )           ? data.data           : [];
        var currencySymbol = ( data && data.currencySymbol ) ? data.currencySymbol : '€';

        clearError( form );

        // Compute nights from the form's current date values.
        var checkInEl  = form.querySelector( '.beds24-search-form__check-in' );
        var checkOutEl = form.querySelector( '.beds24-search-form__check-out' );
        var nights     = 1;
        if ( checkInEl && checkInEl.value && checkOutEl && checkOutEl.value ) {
            nights = countNights(
                parseLocalDate( checkInEl.value ),
                parseLocalDate( checkOutEl.value )
            );
        }

        var container = document.querySelector( '.beds24-room-results' );
        renderRoomCards( rooms, nights, currencySymbol, container );

        // Log for observability — useful during development and debugging.
        var offerCount = 0;
        var i;
        for ( i = 0; i < rooms.length; i++ ) {
            offerCount += ( rooms[ i ].offers || [] ).length;
        }
        if ( offerCount === 0 ) {
            console.log( '[Beds24] No availability for selected dates', { roomCount: rooms.length } );
        } else {
            console.log( '[Beds24] Offers received', { roomCount: rooms.length, offerCount: offerCount } );
        }
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
