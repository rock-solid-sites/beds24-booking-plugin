<?php
/**
 * Beds24 Booking Plugin admin pages.
 *
 * Registers a top-level "Beds24 Booking" admin menu and the
 * "Property Setup" submenu page. The setup page displays the
 * generated iframe CSS for the operator to copy into Beds24's
 * "Insert in HTML <HEAD> bottom" admin field.
 *
 * See docs/styling-contract.md Decision 5 and §"Iframe CSS generation"
 * for the workflow this page supports.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'admin_menu', 'beds24_booking_register_admin_menu' );

/**
 * Register the plugin's top-level admin menu and subpages.
 *
 * A top-level menu is registered now rather than using Tools because
 * the plugin will have additional admin pages (property settings, token
 * configuration) in later sessions. The Property Setup page is the
 * first submenu entry.
 */
function beds24_booking_register_admin_menu(): void {
	// Top-level menu.
	add_menu_page(
		__( 'Beds24 Booking', 'beds24-booking-plugin' ),
		__( 'Beds24 Booking', 'beds24-booking-plugin' ),
		'manage_options',
		'beds24-booking',
		'beds24_booking_admin_setup_page',
		'dashicons-calendar-alt',
		58 // position: after "Appearance" (60), before "Plugins" (65)
	);

	// "Property Setup" submenu — same callback as the top-level page so
	// the first menu item has a useful label rather than echoing the
	// parent menu title.
	add_submenu_page(
		'beds24-booking',
		__( 'Property Setup — Beds24 Booking', 'beds24-booking-plugin' ),
		__( 'Property Setup', 'beds24-booking-plugin' ),
		'manage_options',
		'beds24-booking',
		'beds24_booking_admin_setup_page'
	);
}

/**
 * Render the Property Setup admin page.
 *
 * Generates the iframe CSS using default token values and displays it in a
 * copyable textarea. When the theme.json reader and token settings page land
 * in a later session, this page will call the generator with the
 * currently-configured tokens rather than the defaults.
 */
function beds24_booking_admin_setup_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'beds24-booking-plugin' ) );
	}

	$css = beds24_generate_iframe_css();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Beds24 Property Setup', 'beds24-booking-plugin' ); ?></h1>

		<p>
			<?php esc_html_e( 'Copy the CSS below and paste it into the', 'beds24-booking-plugin' ); ?>
			<strong><?php esc_html_e( 'Insert in HTML &lt;HEAD&gt; bottom', 'beds24-booking-plugin' ); ?></strong>
			<?php esc_html_e( 'field in Beds24 admin for this property.', 'beds24-booking-plugin' ); ?>
		</p>
		<p>
			<?php esc_html_e( 'When design tokens change (theme updates, plugin settings), return here, copy the updated CSS, and re-paste it in Beds24 admin.', 'beds24-booking-plugin' ); ?>
		</p>

		<p>
			<button type="button" id="beds24-copy-css" class="button button-secondary">
				<?php esc_html_e( 'Copy to Clipboard', 'beds24-booking-plugin' ); ?>
			</button>
		</p>

		<textarea
			id="beds24-iframe-css"
			class="large-text code"
			rows="35"
			readonly
			style="font-family:monospace;font-size:12px;resize:vertical;"
		><?php echo esc_textarea( $css ); ?></textarea>
	</div>

	<script>
	( function () {
		var btn = document.getElementById( 'beds24-copy-css' );
		var ta  = document.getElementById( 'beds24-iframe-css' );

		if ( ! btn || ! ta ) {
			return;
		}

		btn.addEventListener( 'click', function () {
			var originalText = btn.textContent;

			function onSuccess() {
				btn.textContent = '<?php echo esc_js( __( 'Copied!', 'beds24-booking-plugin' ) ); ?>';
				setTimeout( function () {
					btn.textContent = originalText;
				}, 2000 );
			}

			if ( navigator.clipboard && navigator.clipboard.writeText ) {
				navigator.clipboard.writeText( ta.value ).then( onSuccess ).catch( function () {
					// Clipboard API failed — fall back to execCommand.
					ta.select();
					document.execCommand( 'copy' );
					onSuccess();
				} );
			} else {
				// execCommand fallback for environments without Clipboard API.
				ta.select();
				document.execCommand( 'copy' );
				onSuccess();
			}
		} );
	}() );
	</script>
	<?php
}
