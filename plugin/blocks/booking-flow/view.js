/**
 * Beds24 Booking Flow — search form, room card rendering, and cart accumulator.
 *
 * Behaviour:
 *   - Intercepts form submission and validates the date inputs.
 *   - Shows per-failure error messages in the form's error region.
 *   - On passing validation, dispatches a GET request to the plugin's
 *     REST route (/beds24-booking-plugin/v1/offers) with the validated dates.
 *   - On success: renders one room card per room in .beds24-room-results.
 *     Cards render for both available and unavailable rooms; unavailable rooms
 *     get the --unavailable modifier and show "Not available for selected dates."
 *   - Available dorm cards: quantity [−/+] control (1 to N available beds).
 *   - Available private cards: Add/Remove toggle (0 or 1 in cart).
 *   - Cart region (.beds24-cart): shows selected rooms, per-item totals, and
 *     running per-night total. Hidden when cart is empty.
 *   - On error: surfaces a user-readable message in the form's error region.
 *
 * State model:
 *   A plain-JS store (subscribe/notify) holds all mutable UI state:
 *   { cart, searchDates, currencySymbol }. Render functions subscribe; event
 *   handlers call store.set(). No framework, no web components.
 *
 * Cart data model (per item):
 *   { quantity, unitPrice, roomType, name }
 *   quantity  — beds selected (dorm) or 1 (private)
 *   unitPrice — per-bed-per-night (dorm) or per-room-per-night (private)
 *   roomType  — Beds24 roomType string (e.g. 'bedInDormitory', 'double')
 *   name      — room display name
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
    // State store (subscribe / notify)
    // -----------------------------------------------------------------------

    /**
     * Minimal state store: get / set / subscribe.
     *
     * set() merges partial updates at the top level (one level deep).
     * Subscribers receive the full state object after every set() call.
     */
    var store = ( function () {
        var state = {
            cart:           {},   // roomId (string) → cartItem
            searchDates:    { checkIn: '', checkOut: '', nights: 0 },
            currencySymbol: '€',
        };
        var listeners = [];

        return {
            get: function () {
                return state;
            },
            set: function ( updates ) {
                var key;
                for ( key in updates ) {
                    if ( updates.hasOwnProperty( key ) ) {
                        state[ key ] = updates[ key ];
                    }
                }
                var i;
                for ( i = 0; i < listeners.length; i++ ) {
                    listeners[ i ]( state );
                }
            },
            subscribe: function ( fn ) {
                listeners.push( fn );
            },
        };
    }() );

    // -----------------------------------------------------------------------
    // DOM helpers
    // -----------------------------------------------------------------------

    /**
     * Walk up the DOM from el to find the nearest ancestor matching selector.
     *
     * @param  {Element} el
     * @param  {string}  selector
     * @return {Element|null}
     */
    function closestEl( el, selector ) {
        var curr = el;
        while ( curr && curr !== document ) {
            if ( curr.matches && curr.matches( selector ) ) {
                return curr;
            }
            curr = curr.parentElement;
        }
        return null;
    }

    /**
     * Shallow-copy a plain object (one level deep).
     *
     * @param  {Object} obj
     * @return {Object}
     */
    function shallowCopy( obj ) {
        var copy = {};
        var key;
        for ( key in obj ) {
            if ( obj.hasOwnProperty( key ) ) {
                copy[ key ] = obj[ key ];
            }
        }
        return copy;
    }

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
    // Cart state operations
    // -----------------------------------------------------------------------

    /**
     * Add one dorm bed (or increment existing qty) for roomId.
     * Reads unit price and room metadata from data attributes on the card.
     *
     * @param {string}      roomId  Beds24 room ID as a string.
     * @param {HTMLElement} card    The .beds24-room-card element.
     */
    function addDormBed( roomId, card ) {
        var state      = store.get();
        var item       = state.cart[ roomId ];
        var ctrlEl     = card.querySelector( '.beds24-room-card__qty-control' );
        var maxQty     = ctrlEl ? ( parseInt( ctrlEl.dataset.maxQty, 10 ) || 1 ) : 1;
        var currentQty = item ? item.quantity : 0;
        var newQty     = currentQty + 1;

        if ( newQty > maxQty ) {
            return;
        }

        var newCart          = shallowCopy( state.cart );
        newCart[ roomId ]    = {
            quantity:  newQty,
            unitPrice: parseInt( card.dataset.unitPrice, 10 ) || 0,
            roomType:  card.dataset.roomType  || 'double',
            name:      card.dataset.roomName  || ( 'Room ' + roomId ),
        };
        store.set( { cart: newCart } );
    }

    /**
     * Remove one dorm bed (or decrement existing qty) for roomId.
     * Removes the item entirely when qty reaches zero.
     *
     * @param {string} roomId
     */
    function removeDormBed( roomId ) {
        var state = store.get();
        var item  = state.cart[ roomId ];
        if ( ! item ) {
            return;
        }

        var newQty  = item.quantity - 1;
        var newCart = shallowCopy( state.cart );

        if ( newQty <= 0 ) {
            delete newCart[ roomId ];
        } else {
            newCart[ roomId ] = {
                quantity:  newQty,
                unitPrice: item.unitPrice,
                roomType:  item.roomType,
                name:      item.name,
            };
        }
        store.set( { cart: newCart } );
    }

    /**
     * Toggle a private room in the cart (Add when absent, Remove when present).
     * Reads unit price and room metadata from data attributes on the card.
     *
     * @param {string}      roomId
     * @param {HTMLElement} card
     */
    function togglePrivateRoom( roomId, card ) {
        var state   = store.get();
        var newCart = shallowCopy( state.cart );

        if ( newCart[ roomId ] ) {
            delete newCart[ roomId ];
        } else {
            newCart[ roomId ] = {
                quantity:  1,
                unitPrice: parseInt( card.dataset.unitPrice, 10 ) || 0,
                roomType:  card.dataset.roomType || 'double',
                name:      card.dataset.roomName || ( 'Room ' + roomId ),
            };
        }
        store.set( { cart: newCart } );
    }

    // -----------------------------------------------------------------------
    // Cart region rendering
    // -----------------------------------------------------------------------

    /**
     * Build a single cart list item element.
     *
     * @param  {Object} item            Cart item (quantity, unitPrice, roomType, name).
     * @param  {string} currencySymbol  Currency symbol.
     * @return {HTMLElement}
     */
    function buildCartListItem( item, currencySymbol ) {
        var sym = currencySymbol || '€';

        var li     = document.createElement( 'li' );
        li.className = 'beds24-cart__item';

        var nameEl       = document.createElement( 'span' );
        nameEl.className = 'beds24-cart__item-name';
        nameEl.textContent = item.name;

        var detailEl       = document.createElement( 'span' );
        detailEl.className = 'beds24-cart__item-detail';
        if ( item.roomType === 'bedInDormitory' ) {
            detailEl.textContent =
                item.quantity + ' bed' + ( item.quantity !== 1 ? 's' : '' ) +
                ' × ' + sym + item.unitPrice + ' / night';
        } else {
            detailEl.textContent = sym + item.unitPrice + ' / night';
        }

        var totalEl       = document.createElement( 'span' );
        totalEl.className = 'beds24-cart__item-total';
        totalEl.textContent = sym + ( item.quantity * item.unitPrice ) + ' / night';

        li.appendChild( nameEl );
        li.appendChild( detailEl );
        li.appendChild( totalEl );
        return li;
    }

    /**
     * Re-render the cart region from current store state.
     * Hides the region when the cart is empty; reveals it when non-empty.
     *
     * @param {Object} state  Current store state.
     */
    function renderCart( state ) {
        var cartEl = document.querySelector( '.beds24-cart' );
        if ( ! cartEl ) {
            return;
        }

        var items   = state.cart;
        var roomIds = Object.keys( items );
        var listEl  = cartEl.querySelector( '.beds24-cart__list' );
        var totalEl = cartEl.querySelector( '.beds24-cart__total' );

        // Always clear and rebuild — simple and correct.
        if ( listEl ) {
            listEl.textContent = '';
        }

        if ( roomIds.length === 0 ) {
            cartEl.setAttribute( 'hidden', '' );
            return;
        }

        var sym   = state.currencySymbol || '€';
        var total = 0;
        var i, id, item;
        for ( i = 0; i < roomIds.length; i++ ) {
            id   = roomIds[ i ];
            item = items[ id ];
            total += item.quantity * item.unitPrice;
            if ( listEl ) {
                listEl.appendChild( buildCartListItem( item, sym ) );
            }
        }

        if ( totalEl ) {
            totalEl.textContent = sym + total + ' / night';
        }

        cartEl.removeAttribute( 'hidden' );
    }

    // -----------------------------------------------------------------------
    // Card control sync (selected state + qty/button updates)
    // -----------------------------------------------------------------------

    /**
     * Sync per-card visual state to current store state.
     *
     * Called on every store update. Updates:
     *   - beds24-room-card--selected modifier
     *   - Dorm qty widget (value display, dec/inc disabled state)
     *   - Private cart button (label, in-cart modifier)
     *
     * @param {Object} state  Current store state.
     */
    function syncCardControls( state ) {
        var cards = document.querySelectorAll( '.beds24-room-card' );
        var i, card, roomId, item, qty, maxQty, qtyControl, qtyValueEl, decBtn, incBtn, cartBtn;

        for ( i = 0; i < cards.length; i++ ) {
            card   = cards[ i ];
            roomId = card.dataset.roomId;
            if ( ! roomId ) {
                continue;
            }

            item = state.cart[ roomId ];
            qty  = item ? item.quantity : 0;

            // Selected-state modifier.
            if ( qty > 0 ) {
                card.classList.add( 'beds24-room-card--selected' );
            } else {
                card.classList.remove( 'beds24-room-card--selected' );
            }

            // Dorm quantity widget.
            qtyControl = card.querySelector( '.beds24-room-card__qty-control' );
            if ( qtyControl ) {
                maxQty     = parseInt( qtyControl.dataset.maxQty, 10 ) || 1;
                qtyValueEl = qtyControl.querySelector( '.beds24-room-card__qty-value' );
                decBtn     = qtyControl.querySelector( '.beds24-room-card__qty-btn--dec' );
                incBtn     = qtyControl.querySelector( '.beds24-room-card__qty-btn--inc' );

                if ( qtyValueEl ) {
                    qtyValueEl.textContent = String( qty );
                }
                if ( decBtn ) {
                    decBtn.disabled = ( qty <= 0 );
                }
                if ( incBtn ) {
                    incBtn.disabled = ( qty >= maxQty );
                }
            }

            // Private Add/Remove toggle button.
            cartBtn = card.querySelector( '.beds24-room-card__cart-btn' );
            if ( cartBtn ) {
                if ( qty > 0 ) {
                    cartBtn.textContent = 'Remove';
                    cartBtn.classList.add( 'beds24-room-card__cart-btn--in-cart' );
                } else {
                    cartBtn.textContent = 'Add';
                    cartBtn.classList.remove( 'beds24-room-card__cart-btn--in-cart' );
                }
            }
        }
    }

    // -----------------------------------------------------------------------
    // Room card rendering
    // -----------------------------------------------------------------------

    /**
     * Build the dorm quantity [−] [N] [+] control element.
     *
     * @param  {number}      maxQty  Maximum selectable beds (= offer.unitsAvailable).
     * @return {HTMLElement}
     */
    function buildDormQtyControl( maxQty ) {
        var ctrl     = document.createElement( 'div' );
        ctrl.className = 'beds24-room-card__qty-control';
        ctrl.setAttribute( 'data-max-qty', String( maxQty ) );

        var decBtn       = document.createElement( 'button' );
        decBtn.type      = 'button';
        decBtn.className = 'beds24-room-card__qty-btn beds24-room-card__qty-btn--dec';
        decBtn.textContent = '−'; // MINUS SIGN
        decBtn.setAttribute( 'aria-label', 'Remove bed' );
        decBtn.disabled  = true; // starts at qty=0

        var qtyVal       = document.createElement( 'span' );
        qtyVal.className = 'beds24-room-card__qty-value';
        qtyVal.setAttribute( 'aria-live', 'polite' );
        qtyVal.textContent = '0';

        var incBtn       = document.createElement( 'button' );
        incBtn.type      = 'button';
        incBtn.className = 'beds24-room-card__qty-btn beds24-room-card__qty-btn--inc';
        incBtn.textContent = '+';
        incBtn.setAttribute( 'aria-label', 'Add bed' );
        incBtn.disabled  = ( maxQty <= 0 );

        ctrl.appendChild( decBtn );
        ctrl.appendChild( qtyVal );
        ctrl.appendChild( incBtn );
        return ctrl;
    }

    /**
     * Build the private room Add/Remove toggle button.
     *
     * @return {HTMLElement}
     */
    function buildPrivateCartBtn() {
        var btn       = document.createElement( 'button' );
        btn.type      = 'button';
        btn.className = 'beds24-room-card__cart-btn';
        btn.textContent = 'Add';
        return btn;
    }

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
        var offer     = available ? offers[ 0 ] : null;
        var perNight  = ( offer && nights > 0 ) ? Math.round( offer.price / nights ) : ( offer ? offer.price : 0 );
        var roomType  = room.roomType || null;
        var isDorm    = roomType === 'bedInDormitory';
        var roomName  = content ? content.title : ( 'Room ' + room.roomId );

        if ( ! content ) {
            console.warn(
                '[Beds24] No matching beds24_room post for roomId: ' + room.roomId +
                '. Create a beds24_room post with _beds24_room_id set to ' + room.roomId + '.'
            );
        }

        if ( available && ! roomType ) {
            console.warn(
                '[Beds24] roomType missing for roomId: ' + room.roomId +
                '. The get_properties() cache may be stale or the API call failed. ' +
                'Defaulting to private room control.'
            );
        }

        var card         = document.createElement( 'div' );
        card.className   = 'beds24-room-card' + ( available ? '' : ' beds24-room-card--unavailable' );
        card.setAttribute( 'data-room-id',   String( room.roomId ) );
        card.setAttribute( 'data-room-type', roomType || 'double' );
        card.setAttribute( 'data-unit-price', String( perNight ) );
        card.setAttribute( 'data-room-name', roomName );

        // Room name heading.
        var nameEl       = document.createElement( 'h3' );
        nameEl.className = 'beds24-room-card__name';
        nameEl.textContent = roomName;
        card.appendChild( nameEl );

        // Body: photo + description side by side.
        var bodyEl     = document.createElement( 'div' );
        bodyEl.className = 'beds24-room-card__body';

        if ( content && content.imageUrl ) {
            var photoEl     = document.createElement( 'div' );
            photoEl.className = 'beds24-room-card__photo';
            var imgEl     = document.createElement( 'img' );
            imgEl.src     = content.imageUrl;
            imgEl.alt     = content.imageAlt || roomName;
            imgEl.loading = 'lazy';
            photoEl.appendChild( imgEl );
            bodyEl.appendChild( photoEl );
        }

        var contentEl     = document.createElement( 'div' );
        contentEl.className = 'beds24-room-card__content';

        if ( content && content.description ) {
            var descEl       = document.createElement( 'p' );
            descEl.className = 'beds24-room-card__description';
            descEl.textContent = content.description;
            contentEl.appendChild( descEl );
        }

        // Amenity chips — render only when terms are present.
        var amenities = ( content && content.amenities && content.amenities.length )
            ? content.amenities
            : [];
        if ( amenities.length > 0 ) {
            var tagsEl     = document.createElement( 'div' );
            tagsEl.className = 'beds24-room-card__tags';
            var j, tagEl;
            for ( j = 0; j < amenities.length; j++ ) {
                tagEl           = document.createElement( 'span' );
                tagEl.className = 'beds24-room-card__tag';
                tagEl.textContent = amenities[ j ];
                tagsEl.appendChild( tagEl );
            }
            contentEl.appendChild( tagsEl );
        }

        bodyEl.appendChild( contentEl );
        card.appendChild( bodyEl );

        // Offer row: price (and cart control for available rooms).
        var offerEl     = document.createElement( 'div' );
        offerEl.className = 'beds24-room-card__offer';

        if ( available ) {
            var priceEl       = document.createElement( 'p' );
            priceEl.className = 'beds24-room-card__price';
            priceEl.textContent = 'from ' + currencySymbol + perNight + ' / night';
            offerEl.appendChild( priceEl );

            // Cart control — dorm: qty widget; private: Add/Remove toggle.
            var maxQty    = offer && offer.unitsAvailable ? offer.unitsAvailable : 1;
            var controlEl = isDorm
                ? buildDormQtyControl( maxQty )
                : buildPrivateCartBtn();
            offerEl.appendChild( controlEl );
        } else {
            var noticeEl       = document.createElement( 'p' );
            noticeEl.className = 'beds24-room-card__unavailable-notice';
            noticeEl.textContent = 'Not available for selected dates';
            offerEl.appendChild( noticeEl );
        }

        card.appendChild( offerEl );
        return card;
    }

    /**
     * Render all room cards into the results container and reveal it.
     * Clears any previous results and resets the cart before inserting new cards.
     *
     * @param {Array}       rooms           Room items from the enriched offers response.
     * @param {number}      nights          Number of nights in the search.
     * @param {string}      currencySymbol  Currency symbol.
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
    // Cart event handling (document-level delegation)
    // -----------------------------------------------------------------------

    /**
     * Handle clicks on cart control buttons anywhere in the document.
     * Uses event delegation — one listener covers all current and future cards.
     *
     * @param {Event} e
     */
    function onCartClick( e ) {
        var btn  = e.target;
        var card, roomId;

        // Dorm: increment bed count.
        if ( btn.classList.contains( 'beds24-room-card__qty-btn--inc' ) ) {
            card   = closestEl( btn, '.beds24-room-card' );
            roomId = card && card.dataset.roomId;
            if ( roomId ) {
                addDormBed( roomId, card );
            }
            return;
        }

        // Dorm: decrement bed count.
        if ( btn.classList.contains( 'beds24-room-card__qty-btn--dec' ) ) {
            card   = closestEl( btn, '.beds24-room-card' );
            roomId = card && card.dataset.roomId;
            if ( roomId ) {
                removeDormBed( roomId );
            }
            return;
        }

        // Private room: Add/Remove toggle.
        if ( btn.classList.contains( 'beds24-room-card__cart-btn' ) ) {
            card   = closestEl( btn, '.beds24-room-card' );
            roomId = card && card.dataset.roomId;
            if ( roomId ) {
                togglePrivateRoom( roomId, card );
            }
            return;
        }
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
        .then( function ( response ) {
            return response.json().then( function ( data ) {
                return { status: response.status, data: data };
            } );
        } )
        .then( function ( result ) {
            if ( result.status !== 200 ) {
                var msg = ( result.data && result.data.message )
                    ? result.data.message
                    : 'Search failed. Please try again.';
                handleSearchError( form, msg );
                return;
            }
            handleSearchResponse( result.data, form );
        } )
        .catch( function () {
            handleSearchError( form, 'Network error. Please check your connection and try again.' );
        } );
    }

    /**
     * Handle a successful offers response (HTTP 200).
     *
     * Resets the cart, updates search dates in the store, then renders room cards.
     *
     * @param {Object}      data  Parsed JSON body from the REST route.
     * @param {HTMLElement} form  The search form.
     */
    function handleSearchResponse( data, form ) {
        var rooms          = ( data && data.data )           ? data.data           : [];
        var currencySymbol = ( data && data.currencySymbol ) ? data.currencySymbol : '€';

        clearError( form );

        var checkInEl  = form.querySelector( '.beds24-search-form__check-in' );
        var checkOutEl = form.querySelector( '.beds24-search-form__check-out' );
        var nights     = 1;
        if ( checkInEl && checkInEl.value && checkOutEl && checkOutEl.value ) {
            nights = countNights(
                parseLocalDate( checkInEl.value ),
                parseLocalDate( checkOutEl.value )
            );
        }

        // Reset cart and update search context. Subscribers fire immediately,
        // hiding the (now-empty) cart region before cards are re-rendered.
        store.set( {
            cart:           {},
            searchDates:    {
                checkIn:  checkInEl ? checkInEl.value  : '',
                checkOut: checkOutEl ? checkOutEl.value : '',
                nights:   nights,
            },
            currencySymbol: currencySymbol,
        } );

        var container = document.querySelector( '.beds24-room-results' );
        renderRoomCards( rooms, nights, currencySymbol, container );

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
        var minStay    = parseInt( form.dataset.minStay || '1', 10 );

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

        searchOffers( checkInEl.value, checkOutEl.value, form );
    }

    // -----------------------------------------------------------------------
    // Initialisation
    // -----------------------------------------------------------------------

    /**
     * Attach form submit handler, cart click delegation, and store subscriptions.
     */
    function init() {
        var form = document.querySelector( '.beds24-search-form' );
        if ( ! form ) {
            return;
        }

        form.addEventListener( 'submit', onSubmit );

        // Document-level delegation for all cart control clicks.
        document.addEventListener( 'click', onCartClick );

        // Store subscribers — fire on every state change.
        store.subscribe( renderCart );
        store.subscribe( syncCardControls );
    }

    // Guard against DOMContentLoaded having already fired (e.g. deferred scripts).
    if ( document.readyState === 'loading' ) {
        document.addEventListener( 'DOMContentLoaded', init );
    } else {
        init();
    }
}() );
