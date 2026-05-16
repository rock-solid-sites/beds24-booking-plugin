<?php
/**
 * Admin token settings for the Beds24 Booking Plugin.
 *
 * Stores, retrieves, and renders the design token settings UI on the
 * Property Setup admin page. Operators use these settings to supply token
 * values when the active theme's theme.json doesn't define them.
 *
 * Token layering (docs/styling-contract.md Decision 2):
 *   theme.json values take precedence. Admin settings fill gaps only —
 *   they do not override theme.json values in V1.
 *
 * Storage: individual wp_options entries.
 * Key pattern: beds24_token_{role} where {role} is the contract role name
 * with hyphens converted to underscores (e.g. beds24_token_primary_text).
 *
 * Note: not all token roles are yet consumed by beds24_generate_iframe_css().
 * The following are stored but do not yet affect the CSS textarea:
 *   Colors: success, unavailable, error, error-bg, error-border.
 *   Typography: font-size-large, line-height-body, line-height-heading.
 *   Spacing: space-xs, space-lg, space-xl.
 *   Layout: shadow-floating.
 * These will affect CSS output when the generator is extended in a future session.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * All token role definitions for the admin settings UI.
 *
 * Each entry describes one token field:
 *   - section     : groups the field under a section heading.
 *   - role        : contract role name (docs/styling-contract.md §"Design tokens consumed").
 *   - label       : human-readable field label.
 *   - type        : 'color' → <input type="color"> (hex picker); 'text' → <input type="text">.
 *   - default     : placeholder hint shown beneath the field. Not stored as the option value.
 *                   The generator's own defaults apply when no admin value is set.
 *   - description : one-line explanation of where the token is used.
 *
 * @return array<int, array{section: string, role: string, label: string, type: string, default: string, description: string}>
 */
function beds24_token_definitions(): array {
	return [
		// --- Color tokens (docs/styling-contract.md §"Color tokens") ---
		[
			'section'     => 'color',
			'role'        => 'primary',
			'label'       => 'Primary',
			'type'        => 'color',
			'default'     => '#2563eb',
			'description' => 'Confirm Booking button background, selected room highlight.',
		],
		[
			'section'     => 'color',
			'role'        => 'primary-text',
			'label'       => 'Primary Text',
			'type'        => 'color',
			'default'     => '#ffffff',
			'description' => 'Text on primary-colored surfaces (e.g. button label).',
		],
		[
			'section'     => 'color',
			'role'        => 'accent',
			'label'       => 'Accent',
			'type'        => 'color',
			'default'     => '#f59e0b',
			'description' => 'Price emphasis, "from" labels, badge highlights.',
		],
		[
			'section'     => 'color',
			'role'        => 'surface',
			'label'       => 'Surface',
			'type'        => 'color',
			'default'     => '#ffffff',
			'description' => 'Room card backgrounds, cart background.',
		],
		[
			'section'     => 'color',
			'role'        => 'surface-text',
			'label'       => 'Surface Text',
			'type'        => 'color',
			'default'     => '#1f2937',
			'description' => 'Body text on surface backgrounds.',
		],
		[
			'section'     => 'color',
			'role'        => 'surface-muted',
			'label'       => 'Surface Muted',
			'type'        => 'color',
			'default'     => '#6b7280',
			'description' => 'Secondary text (descriptions, metadata).',
		],
		[
			'section'     => 'color',
			'role'        => 'border',
			'label'       => 'Border',
			'type'        => 'color',
			'default'     => '#e5e7eb',
			'description' => 'Card borders, dividers, input borders.',
		],
		[
			'section'     => 'color',
			'role'        => 'success',
			'label'       => 'Success',
			'type'        => 'color',
			'default'     => '#10b981',
			'description' => 'Confirmation states, "available" indicators.',
		],
		[
			'section'     => 'color',
			'role'        => 'unavailable',
			'label'       => 'Unavailable',
			'type'        => 'color',
			'default'     => '#9ca3af',
			'description' => 'Sold-out states, disabled selections.',
		],
		[
			'section'     => 'color',
			'role'        => 'error',
			'label'       => 'Error Text',
			'type'        => 'color',
			'default'     => '#dc2626',
			'description' => 'Validation error message text color.',
		],
		[
			'section'     => 'color',
			'role'        => 'error-bg',
			'label'       => 'Error Background',
			'type'        => 'color',
			'default'     => '#fef2f2',
			'description' => 'Background of error message regions.',
		],
		[
			'section'     => 'color',
			'role'        => 'error-border',
			'label'       => 'Error Border',
			'type'        => 'color',
			'default'     => '#fecaca',
			'description' => 'Border of error message regions.',
		],

		// --- Typography tokens (docs/styling-contract.md §"Typography tokens") ---
		[
			'section'     => 'typography',
			'role'        => 'font-family-body',
			'label'       => 'Font Family — Body',
			'type'        => 'text',
			'default'     => 'system-ui, -apple-system, sans-serif',
			'description' => 'Font stack for body text. A single name (e.g. Inter) triggers automatic Google Fonts loading in the iframe.',
		],
		[
			'section'     => 'typography',
			'role'        => 'font-family-heading',
			'label'       => 'Font Family — Heading',
			'type'        => 'text',
			'default'     => 'system-ui, -apple-system, sans-serif',
			'description' => 'Font stack for room names and headings. A single name (e.g. Manrope) triggers automatic Google Fonts loading.',
		],
		[
			'section'     => 'typography',
			'role'        => 'font-size-base',
			'label'       => 'Font Size — Base',
			'type'        => 'text',
			'default'     => '16px',
			'description' => 'Body text base size (e.g. 16px, 1rem).',
		],
		[
			'section'     => 'typography',
			'role'        => 'font-size-small',
			'label'       => 'Font Size — Small',
			'type'        => 'text',
			'default'     => '13px',
			'description' => 'Metadata, captions, tags (e.g. 13px, 0.875rem).',
		],
		[
			'section'     => 'typography',
			'role'        => 'font-size-large',
			'label'       => 'Font Size — Large',
			'type'        => 'text',
			'default'     => '1.25rem',
			'description' => 'Room names, prominent prices (e.g. 20px, 1.25rem).',
		],
		[
			'section'     => 'typography',
			'role'        => 'font-weight-body',
			'label'       => 'Font Weight — Body',
			'type'        => 'text',
			'default'     => '400',
			'description' => 'Body text weight (e.g. 400, normal).',
		],
		[
			'section'     => 'typography',
			'role'        => 'font-weight-heading',
			'label'       => 'Font Weight — Heading',
			'type'        => 'text',
			'default'     => '600',
			'description' => 'Heading weight (e.g. 600, bold).',
		],
		[
			'section'     => 'typography',
			'role'        => 'line-height-body',
			'label'       => 'Line Height — Body',
			'type'        => 'text',
			'default'     => '1.5',
			'description' => 'Body text line height (e.g. 1.5).',
		],
		[
			'section'     => 'typography',
			'role'        => 'line-height-heading',
			'label'       => 'Line Height — Heading',
			'type'        => 'text',
			'default'     => '1.2',
			'description' => 'Heading line height (e.g. 1.2).',
		],

		// --- Spacing tokens (docs/styling-contract.md §"Spacing tokens") ---
		[
			'section'     => 'spacing',
			'role'        => 'space-xs',
			'label'       => 'Space XS',
			'type'        => 'text',
			'default'     => '0.25rem',
			'description' => 'Tight inline gaps, tag padding (e.g. 0.25rem, 4px).',
		],
		[
			'section'     => 'spacing',
			'role'        => 'space-sm',
			'label'       => 'Space SM',
			'type'        => 'text',
			'default'     => '0.5rem',
			'description' => 'Card internal padding (mobile), small gaps (e.g. 0.5rem, 8px).',
		],
		[
			'section'     => 'spacing',
			'role'        => 'space-md',
			'label'       => 'Space MD',
			'type'        => 'text',
			'default'     => '1rem',
			'description' => 'Card padding (desktop), section gaps (mobile) (e.g. 1rem, 16px).',
		],
		[
			'section'     => 'spacing',
			'role'        => 'space-lg',
			'label'       => 'Space LG',
			'type'        => 'text',
			'default'     => '1.5rem',
			'description' => 'Section gaps (desktop), card-to-card spacing (e.g. 1.5rem, 24px).',
		],
		[
			'section'     => 'spacing',
			'role'        => 'space-xl',
			'label'       => 'Space XL',
			'type'        => 'text',
			'default'     => '2rem',
			'description' => 'Major section breaks (e.g. 2rem, 32px).',
		],

		// --- Layout tokens (docs/styling-contract.md §"Layout tokens") ---
		[
			'section'     => 'layout',
			'role'        => 'border-radius',
			'label'       => 'Border Radius',
			'type'        => 'text',
			'default'     => '0.5rem',
			'description' => 'Cards, buttons, inputs (e.g. 0.5rem, 8px).',
		],
		[
			'section'     => 'layout',
			'role'        => 'border-radius-small',
			'label'       => 'Border Radius — Small',
			'type'        => 'text',
			'default'     => '0.25rem',
			'description' => 'Tags, small accents (e.g. 0.25rem, 4px).',
		],
		[
			'section'     => 'layout',
			'role'        => 'shadow-card',
			'label'       => 'Shadow — Card',
			'type'        => 'text',
			'default'     => '0 1px 3px rgba(0,0,0,0.1)',
			'description' => 'Room card elevation. Full CSS box-shadow value.',
		],
		[
			'section'     => 'layout',
			'role'        => 'shadow-floating',
			'label'       => 'Shadow — Floating',
			'type'        => 'text',
			'default'     => '0 -2px 8px rgba(0,0,0,0.1)',
			'description' => 'Mobile cart bar, sticky elements. Full CSS box-shadow value.',
		],
	];
}

/**
 * Convert a token role name to its wp_options key.
 *
 * Hyphens in role names are converted to underscores, and the prefix
 * beds24_token_ is prepended.
 *
 * @param string $role Contract role name (e.g. 'primary-text').
 * @return string Option key (e.g. 'beds24_token_primary_text').
 */
function beds24_token_role_to_option_key( string $role ): string {
	return 'beds24_token_' . str_replace( '-', '_', $role );
}

/**
 * Retrieve the saved admin setting for a token role.
 *
 * Returns an empty string when no setting has been saved. The generator's
 * built-in defaults apply when the admin setting is empty.
 *
 * @param string $role Contract role name.
 * @return string Saved value, or empty string.
 */
function beds24_token_get_option( string $role ): string {
	return (string) get_option( beds24_token_role_to_option_key( $role ), '' );
}

/**
 * Return all saved admin token settings as a token array.
 *
 * Only roles with non-empty saved values are included. The result is
 * suitable for passing to beds24_generate_iframe_css() as the $tokens
 * argument (alongside theme.json tokens which take precedence).
 *
 * @return array<string, string> Token role → value. Empty array when no
 *                               settings have been saved.
 */
function beds24_token_get_all_admin_tokens(): array {
	$tokens = [];
	foreach ( beds24_token_definitions() as $def ) {
		$value = beds24_token_get_option( $def['role'] );
		if ( '' !== $value ) {
			$tokens[ $def['role'] ] = $value;
		}
	}
	return $tokens;
}

/**
 * Process a token settings form submission.
 *
 * Called at the top of the Property Setup page callback. Validates the
 * nonce and capability, then saves each token field to wp_options.
 * Empty values delete the option (so the generator default applies).
 *
 * Returns true when settings were saved, false when the request was not
 * a settings form submission (normal page load).
 *
 * @return bool True on successful save; false when not a save request.
 */
function beds24_token_save_settings(): bool {
	if ( ! isset( $_POST['beds24_token_nonce'] ) ) {
		return false;
	}

	if ( ! wp_verify_nonce( sanitize_key( $_POST['beds24_token_nonce'] ), 'beds24_token_save' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'beds24-booking-plugin' ) );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to save these settings.', 'beds24-booking-plugin' ) );
	}

	foreach ( beds24_token_definitions() as $def ) {
		$field_name = 'beds24_token_' . str_replace( '-', '_', $def['role'] );
		$option_key = beds24_token_role_to_option_key( $def['role'] );

		// Theme.json-controlled roles are not rendered as inputs; they won't be
		// in $_POST. The isset() check naturally skips them.
		if ( ! isset( $_POST[ $field_name ] ) ) {
			continue;
		}

		$raw = (string) $_POST[ $field_name ];

		if ( 'color' === $def['type'] ) {
			// sanitize_hex_color() returns null for non-hex values.
			$value = sanitize_hex_color( $raw ) ?? '';
		} else {
			// CSS value strings: strip HTML tags and characters that would
			// break a CSS declaration (braces, semicolons, control chars).
			$value = sanitize_text_field( $raw );
			$value = preg_replace( '/[{};\x00-\x1f]/', '', $value ) ?? '';
		}

		if ( '' === $value ) {
			// Empty value — delete the option so the generator default applies.
			delete_option( $option_key );
		} else {
			// autoload = false: these options are only needed on the admin page
			// and CSS generation path; don't load them on every page request.
			update_option( $option_key, $value, false );
		}
	}

	return true;
}

/**
 * Render the token settings section below the CSS textarea.
 *
 * Renders a form with four section groups (color, typography, spacing,
 * layout). For each token:
 *   - If theme.json provides a value: renders a read-only display labeled
 *     "Set by theme.json". The field is not in the form — theme values
 *     are not overridable through admin settings in V1.
 *   - Otherwise: renders an editable input (color picker or text field).
 *
 * The caller is responsible for calling beds24_token_save_settings() before
 * this function to process any pending form submission.
 *
 * @param array<string, string> $theme_tokens Token values from beds24_read_theme_tokens().
 *                                             Used to mark which roles are already covered.
 */
function beds24_token_render_settings_section( array $theme_tokens ): void {
	$definitions = beds24_token_definitions();

	$sections = [
		'color'      => 'Color Tokens',
		'typography' => 'Typography Tokens',
		'spacing'    => 'Spacing Tokens',
		'layout'     => 'Layout Tokens',
	];

	?>
	<hr>
	<h2><?php esc_html_e( 'Design Token Settings', 'beds24-booking-plugin' ); ?></h2>
	<p>
		<?php
		esc_html_e(
			'Configure design token values for properties whose themes do not provide them via theme.json. ' .
			'Theme.json values are shown as read-only — they take precedence and cannot be overridden here in V1.',
			'beds24-booking-plugin'
		);
		?>
	</p>

	<form method="post" action="">
		<?php wp_nonce_field( 'beds24_token_save', 'beds24_token_nonce' ); ?>

		<?php foreach ( $sections as $section_key => $section_title ) : ?>
			<?php
			$section_defs = array_values(
				array_filter( $definitions, static fn( $d ) => $d['section'] === $section_key )
			);
			if ( empty( $section_defs ) ) {
				continue;
			}
			?>

			<h3><?php echo esc_html( $section_title ); ?></h3>
			<table class="form-table" role="presentation">
				<tbody>
					<?php foreach ( $section_defs as $def ) : ?>
						<?php
						$role        = $def['role'];
						$field_id    = 'beds24_admin_token_' . str_replace( '-', '_', $role );
						$field_name  = 'beds24_token_' . str_replace( '-', '_', $role );
						$theme_value = $theme_tokens[ $role ] ?? null;
						$saved_value = beds24_token_get_option( $role );
						?>
						<tr>
							<th scope="row">
								<label for="<?php echo esc_attr( $field_id ); ?>">
									<?php echo esc_html( $def['label'] ); ?>
								</label>
							</th>
							<td>
								<?php if ( null !== $theme_value ) : ?>
									<?php // Read-only: theme.json has this role covered. ?>
									<div class="beds24-token-theme-value" style="display:flex;align-items:center;gap:8px;">
										<?php if ( 'color' === $def['type'] ) : ?>
											<span
												style="display:inline-block;width:20px;height:20px;border-radius:3px;border:1px solid #ccd0d4;background:<?php echo esc_attr( $theme_value ); ?>;"
												aria-hidden="true"
											></span>
										<?php endif; ?>
										<code style="background:#f0f0f0;padding:2px 6px;border-radius:3px;color:#444;">
											<?php echo esc_html( $theme_value ); ?>
										</code>
										<em style="color:#646970;">
											<?php esc_html_e( 'Set by theme.json', 'beds24-booking-plugin' ); ?>
										</em>
									</div>
								<?php elseif ( 'color' === $def['type'] ) : ?>
									<?php // Editable color picker. ?>
									<div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
										<input
											type="color"
											id="<?php echo esc_attr( $field_id ); ?>"
											name="<?php echo esc_attr( $field_name ); ?>"
											value="<?php echo esc_attr( '' !== $saved_value ? $saved_value : $def['default'] ); ?>"
										>
										<?php if ( '' === $saved_value ) : ?>
											<span style="color:#646970;font-size:12px;">
												<?php
												printf(
													/* translators: %s: default color value */
													esc_html__( 'Default: %s', 'beds24-booking-plugin' ),
													esc_html( $def['default'] )
												);
												?>
											</span>
										<?php endif; ?>
									</div>
									<p class="description"><?php echo esc_html( $def['description'] ); ?></p>
								<?php else : ?>
									<?php // Editable text input. ?>
									<input
										type="text"
										id="<?php echo esc_attr( $field_id ); ?>"
										name="<?php echo esc_attr( $field_name ); ?>"
										value="<?php echo esc_attr( $saved_value ); ?>"
										placeholder="<?php echo esc_attr( $def['default'] ); ?>"
										class="regular-text"
									>
									<p class="description">
										<?php echo esc_html( $def['description'] ); ?>
										<?php if ( '' !== $def['default'] ) : ?>
											<?php
											printf(
												/* translators: %s: default token value */
												esc_html__( ' Default: %s', 'beds24-booking-plugin' ),
												esc_html( $def['default'] )
											);
											?>
										<?php endif; ?>
									</p>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

		<?php endforeach; ?>

		<?php submit_button( __( 'Save Token Settings', 'beds24-booking-plugin' ) ); ?>
	</form>
	<?php
}
