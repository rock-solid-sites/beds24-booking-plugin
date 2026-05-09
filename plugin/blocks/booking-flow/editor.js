/**
 * Beds24 Booking Flow — block editor registration.
 *
 * Plain JS (no JSX, no build step). Uses the global wp.* APIs that
 * WordPress enqueues in the block editor. The save() function returns
 * null because the block is server-rendered via render.php; WordPress
 * never calls save() for blocks registered with a render callback.
 *
 * The edit() function renders a static editor placeholder. A live
 * preview of the booking widget is intentionally omitted: loading the
 * widget inside the block editor is flaky (API calls, date pickers,
 * AJAX handlers all initialise in unexpected contexts). Operators
 * place the block and visit the front end to see it.
 */
( function () {
    var registerBlockType = wp.blocks.registerBlockType;
    var el                = wp.element.createElement;

    registerBlockType( 'beds24/booking-flow', {
        edit: function () {
            return el(
                'div',
                {
                    style: {
                        padding:       '1.5em',
                        border:        '1px dashed #999',
                        background:    '#f9f9f9',
                        textAlign:     'center',
                        borderRadius:  '4px',
                        color:         '#555',
                    },
                },
                el( 'strong', { style: { display: 'block', marginBottom: '0.4em', color: '#333' } },
                    'Beds24 Booking Flow'
                ),
                el( 'span', { style: { fontSize: '0.875em' } },
                    'The booking search form and room results render here on the front end.'
                )
            );
        },

        save: function () {
            // Server-rendered via render.php. WordPress stores null in
            // post content and calls the render callback on every page load.
            return null;
        },
    } );
} )();
