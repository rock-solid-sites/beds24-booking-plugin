<?php
/**
 * Beds24 Booking Plugin admin pages.
 *
 * Registers a top-level "Beds24 Booking" admin menu and the
 * "Property Setup" submenu page. The setup page displays:
 *
 *   1. The generated iframe CSS for the operator to copy into Beds24's
 *      "Insert in HTML <HEAD> bottom" admin field.
 *   2. A token settings form where operators configure design token values
 *      for themes that don't supply them via theme.json.
 *
 * CSS generation pipeline (Session 21):
 *   beds24_read_theme_tokens()          → theme.json-derived values (takes precedence)
 *   beds24_token_get_all_admin_tokens() → operator-configured fallback values
 *   array_merge(admin, theme)           → merged set; theme overrides admin
 *   beds24_read_theme_font_sources()    → Google Fonts URLs from theme.json fontFace
 *   beds24_generate_iframe_css(merged, sources) → CSS with @import prepended when needed
 *
 * See docs/styling-contract.md Decision 2 (admin settings as fallback) and
 * Decision 5 (CSS is generated programmatically) for the workflow this page supports.
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
 * Processing order:
 *   1. Handle any pending token settings form submission (nonce-verified).
 *   2. Build the combined token set: theme.json values override admin settings.
 *   3. Resolve font sources from theme.json fontFace data.
 *   4. Generate the iframe CSS from the combined tokens + font sources.
 *   5. Render the CSS textarea (copy affordance).
 *   6. Render the token settings form below the textarea.
 */
function beds24_booking_admin_setup_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'beds24-booking-plugin' ) );
	}

	// Step 1: Handle token settings save (if this is a form submission).
	$settings_saved = beds24_token_save_settings();

	// Step 2: Build the combined token set.
	// Admin settings are the base; theme.json values override them.
	$theme_tokens  = beds24_read_theme_tokens();
	$admin_tokens  = beds24_token_get_all_admin_tokens();
	$merged_tokens = array_merge( $admin_tokens, $theme_tokens );

	// Step 3: Resolve font sources from theme.json fontFace data.
	// Used by the CSS generator to emit @import rules for web fonts.
	$font_sources = beds24_read_theme_font_sources();

	// Step 4: Generate the iframe CSS.
	$css = beds24_generate_iframe_css( $merged_tokens, $font_sources );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Beds24 Property Setup', 'beds24-booking-plugin' ); ?></h1>

		<?php if ( $settings_saved ) : ?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Token settings saved.', 'beds24-booking-plugin' ); ?></p>
			</div>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Iframe CSS', 'beds24-booking-plugin' ); ?></h2>

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
		// Step 6: Render the token settings form below the CSS textarea.
		// Pass theme_tokens so the form marks roles that are already covered.
		beds24_token_render_settings_section( $theme_tokens );
		?>
	</div>
	<?php
}
