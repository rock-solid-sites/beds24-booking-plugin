<?php
/**
 * Custom post type and taxonomy registration for room content.
 *
 * Registers:
 *  - `beds24_room`    — CPT for operator-managed room content (title,
 *                       description, featured image, Beds24 room ID).
 *  - `beds24_amenity` — flat taxonomy for custom amenities not covered by
 *                       Beds24's OTA featureCode vocabulary (e.g., "Hot spring
 *                       access," "Hammock terrace"). Standard Beds24 featureCodes
 *                       are resolved at render time by the plugin's built-in
 *                       mapping table and are NOT stored here.
 *  - `_beds24_room_id` post meta — links each room post to a Beds24 room ID.
 *
 * Architecture note: Beds24 is the source of truth for bookings and
 * availability; WordPress is the source of truth for presentation content.
 * Room descriptions, photos, and amenity labels live here. The Beds24 room ID
 * stored in `_beds24_room_id` is the join key that connects API responses to
 * the correct WordPress content at render time.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'init', 'beds24_register_room_post_type', 0 );

/**
 * Register the `beds24_room` custom post type and `beds24_amenity` taxonomy.
 *
 * Priority 0 ensures registration happens before any other `init` callback
 * that might need the CPT or taxonomy to already exist.
 */
function beds24_register_room_post_type(): void {

    // -------------------------------------------------------------------------
    // Custom post type: beds24_room
    // -------------------------------------------------------------------------

    register_post_type(
        'beds24_room',
        [
            'labels'              => [
                'name'               => __( 'Rooms', 'beds24-booking-plugin' ),
                'singular_name'      => __( 'Room', 'beds24-booking-plugin' ),
                'add_new'            => __( 'Add New Room', 'beds24-booking-plugin' ),
                'add_new_item'       => __( 'Add New Room', 'beds24-booking-plugin' ),
                'edit_item'          => __( 'Edit Room', 'beds24-booking-plugin' ),
                'new_item'           => __( 'New Room', 'beds24-booking-plugin' ),
                'view_item'          => __( 'View Room', 'beds24-booking-plugin' ),
                'search_items'       => __( 'Search Rooms', 'beds24-booking-plugin' ),
                'not_found'          => __( 'No rooms found.', 'beds24-booking-plugin' ),
                'not_found_in_trash' => __( 'No rooms found in trash.', 'beds24-booking-plugin' ),
                'menu_name'          => __( 'Rooms', 'beds24-booking-plugin' ),
            ],
            // Not front-end queryable — no archive, no individual permalinks.
            // Room content is delivered via the plugin's block, not via WordPress
            // URL routing.
            'public'              => false,
            'publicly_queryable'  => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => false,
            'show_in_rest'        => true,  // Required for the block editor to load.
            'supports'            => [ 'title', 'editor', 'thumbnail', 'revisions' ],
            'has_archive'         => false,
            'rewrite'             => false,
            'query_var'           => false,
            'menu_icon'           => 'dashicons-admin-home',
            'menu_position'       => 6,
        ]
    );

    // -------------------------------------------------------------------------
    // Custom taxonomy: beds24_amenity
    // -------------------------------------------------------------------------

    register_taxonomy(
        'beds24_amenity',
        'beds24_room',
        [
            'labels'            => [
                'name'                       => __( 'Amenities', 'beds24-booking-plugin' ),
                'singular_name'              => __( 'Amenity', 'beds24-booking-plugin' ),
                'search_items'               => __( 'Search Amenities', 'beds24-booking-plugin' ),
                'all_items'                  => __( 'All Amenities', 'beds24-booking-plugin' ),
                'edit_item'                  => __( 'Edit Amenity', 'beds24-booking-plugin' ),
                'update_item'                => __( 'Update Amenity', 'beds24-booking-plugin' ),
                'add_new_item'               => __( 'Add New Amenity', 'beds24-booking-plugin' ),
                'new_item_name'              => __( 'New Amenity Name', 'beds24-booking-plugin' ),
                'separate_items_with_commas' => __( 'Separate amenities with commas', 'beds24-booking-plugin' ),
                'add_or_remove_items'        => __( 'Add or remove amenities', 'beds24-booking-plugin' ),
                'choose_from_most_used'      => __( 'Choose from the most used amenities', 'beds24-booking-plugin' ),
                'not_found'                  => __( 'No amenities found.', 'beds24-booking-plugin' ),
                'menu_name'                  => __( 'Amenities', 'beds24-booking-plugin' ),
            ],
            // Flat (tag-like), not hierarchical (category-like). Amenity terms
            // are shared across all rooms on this install.
            'hierarchical'      => false,
            'public'            => false,
            'show_ui'           => true,
            'show_in_rest'      => true,
            'show_in_nav_menus' => false,
            'rewrite'           => false,
            'query_var'         => false,
        ]
    );

    // -------------------------------------------------------------------------
    // Post meta: _beds24_room_id
    // -------------------------------------------------------------------------

    // Registers the schema explicitly so the field is REST-accessible and
    // type-safe. The underscore prefix marks this as "private" meta — it does
    // not appear in the default Custom Fields UI. The meta box in
    // beds24-room-meta-box.php is the only operator input path.
    //
    // The `auth_callback` is required: WordPress protects underscored meta from
    // the REST API by default; an explicit callback opts this field back in for
    // users with edit_posts capability.
    register_post_meta(
        'beds24_room',
        '_beds24_room_id',
        [
            'type'              => 'integer',
            'description'       => 'Beds24 v2 API room ID. Links this post to availability and pricing data.',
            'single'            => true,
            'show_in_rest'      => true,
            'sanitize_callback' => 'absint',
            'auth_callback'     => function (): bool {
                return current_user_can( 'edit_posts' );
            },
        ]
    );
}

// ---------------------------------------------------------------------------
// Room content lookup
// ---------------------------------------------------------------------------

/**
 * Get a published beds24_room post by its Beds24 room ID.
 *
 * Used by the REST route handler to join WordPress content into the offers
 * response. Returns null if no published post has `_beds24_room_id` matching
 * the given value — callers should treat null as a data problem (no post
 * seeded for that room ID) and log clearly.
 *
 * @param  int          $room_id  Beds24 v2 API room ID.
 * @return WP_Post|null           Matching published post, or null if not found.
 */
function beds24_get_room_post_by_room_id( int $room_id ): ?WP_Post {
    if ( $room_id <= 0 ) {
        return null;
    }
    $posts = get_posts( [
        'post_type'              => 'beds24_room',
        'post_status'            => 'publish',
        'meta_key'               => '_beds24_room_id',
        'meta_value'             => $room_id,
        'numberposts'            => 1,
        'no_found_rows'          => true,
        'update_post_term_cache' => false,
    ] );
    return $posts ? $posts[0] : null;
}
