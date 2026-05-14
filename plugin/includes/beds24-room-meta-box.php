<?php
/**
 * Meta box for the Beds24 room ID on the `beds24_room` edit screen.
 *
 * The Beds24 room ID is the join key between a WordPress room post and the
 * Beds24 v2 API. At render time, the plugin queries `_beds24_room_id` to match
 * each API offer response to the correct WordPress content (description, photo,
 * amenity terms).
 *
 * Input path: this classic meta box (not a block editor sidebar panel). The
 * field is also registered via register_post_meta() in beds24-room-cpt.php so
 * it is REST-accessible if needed by future tooling.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ---------------------------------------------------------------------------
// Meta box registration
// ---------------------------------------------------------------------------

add_action( 'add_meta_boxes_beds24_room', 'beds24_register_room_id_meta_box' );

/**
 * Register the Beds24 Room ID meta box on the `beds24_room` edit screen.
 *
 * Uses the CPT-specific `add_meta_boxes_{post_type}` hook, which fires only
 * for this post type and avoids a redundant post-type check in the callback.
 *
 * Context 'side' places the box in the right sidebar of the block editor —
 * appropriate for a single configuration field that doesn't need editor width.
 */
function beds24_register_room_id_meta_box(): void {
    add_meta_box(
        'beds24_room_id',
        __( 'Beds24 Room ID', 'beds24-booking-plugin' ),
        'beds24_render_room_id_meta_box',
        'beds24_room',
        'side',
        'default'
    );
}

// ---------------------------------------------------------------------------
// Render callback
// ---------------------------------------------------------------------------

/**
 * Render the Beds24 Room ID meta box.
 *
 * Outputs a labeled number input pre-populated with the current meta value (or
 * empty on a new post) plus a nonce field for save verification.
 *
 * @param WP_Post $post  The post being edited.
 */
function beds24_render_room_id_meta_box( WP_Post $post ): void {
    $value = (int) get_post_meta( $post->ID, '_beds24_room_id', true );

    wp_nonce_field( 'beds24_room_id_save_' . $post->ID, 'beds24_room_id_nonce' );
    ?>
    <p>
        <label for="beds24_room_id" style="font-weight:600;display:block;margin-bottom:4px;">
            <?php esc_html_e( 'Room ID', 'beds24-booking-plugin' ); ?>
        </label>
        <input
            type="number"
            id="beds24_room_id"
            name="beds24_room_id"
            value="<?php echo esc_attr( $value > 0 ? $value : '' ); ?>"
            min="1"
            step="1"
            style="width:100%;"
        />
    </p>
    <p class="description">
        <?php esc_html_e( 'Integer room ID from the Beds24 v2 API. Links this room\'s content to live availability and pricing.', 'beds24-booking-plugin' ); ?>
    </p>
    <?php
}

// ---------------------------------------------------------------------------
// Save callback
// ---------------------------------------------------------------------------

add_action( 'save_post_beds24_room', 'beds24_save_room_id_meta' );

/**
 * Save the Beds24 room ID meta value.
 *
 * Guards:
 *  1. Autosave — skip; the meta box is not present during autosave requests.
 *  2. Nonce — verify the nonce is present and valid.
 *  3. Capability — verify the current user can edit this post.
 *
 * Sanitization: absint() — the room ID is a positive integer; any non-numeric
 * or zero value is treated as "unset" and the meta row is deleted.
 *
 * @param int $post_id  The post ID being saved.
 */
function beds24_save_room_id_meta( int $post_id ): void {
    // 1. Autosave guard.
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // 2. Nonce check.
    $nonce = isset( $_POST['beds24_room_id_nonce'] )
        ? sanitize_key( $_POST['beds24_room_id_nonce'] )
        : '';
    if ( ! wp_verify_nonce( $nonce, 'beds24_room_id_save_' . $post_id ) ) {
        return;
    }

    // 3. Capability check.
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Save or delete.
    $raw    = isset( $_POST['beds24_room_id'] ) ? $_POST['beds24_room_id'] : '';
    $room_id = absint( $raw );

    if ( $room_id > 0 ) {
        update_post_meta( $post_id, '_beds24_room_id', $room_id );
    } else {
        delete_post_meta( $post_id, '_beds24_room_id' );
    }
}
