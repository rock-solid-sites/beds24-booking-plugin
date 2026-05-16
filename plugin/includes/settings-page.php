<?php
/**
 * Properties settings page for the Beds24 Booking Plugin.
 *
 * Registers and renders the "Properties" submenu page under the
 * "Beds24 Booking" top-level admin menu. Operators use this page to:
 *
 *   - Add, edit, and remove Beds24 property configurations.
 *   - Exchange a Beds24 invite code for a stored refresh token per property.
 *   - Select the default property the booking block renders.
 *
 * Data model (wp_options):
 *   beds24_booking_plugin_properties         — array of property config arrays
 *   beds24_booking_plugin_default_property   — int, default property ID
 *
 * Refresh tokens remain under beds24_booking_plugin_refresh_token_{id},
 * managed entirely by Beds24_API_Client.
 *
 * Migration: beds24_booking_plugin_maybe_migrate_property() runs on
 * admin_init and seeds the Chill Zone entry from the old single-property
 * token if the new data model is empty.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------------
// Migration
// ---------------------------------------------------------------------------

/**
 * Migrate from the old single-property hard-coded data model to the new
 * wp_options multi-property data model.
 *
 * Runs on admin_init. Exits immediately if the new data model is already
 * populated or if no old single-property token exists (fresh install).
 */
function beds24_booking_plugin_maybe_migrate_property(): void {
	$properties = get_option( 'beds24_booking_plugin_properties', [] );
	if ( ! empty( $properties ) ) {
		return; // already migrated
	}
	// Check for old single-property refresh token (property 271142, Chill Zone).
	$old_token = get_option( 'beds24_booking_plugin_refresh_token_271142', false );
	if ( ! $old_token ) {
		return; // fresh install, nothing to migrate
	}
	// Seed a Chill Zone entry.
	update_option( 'beds24_booking_plugin_properties', [
		[
			'id'       => 271142,
			'name'     => 'Chill Zone',
			'min_stay' => 2,
			'currency' => 'EUR',
		],
	] );
	update_option( 'beds24_booking_plugin_default_property', 271142 );
}
add_action( 'admin_init', 'beds24_booking_plugin_maybe_migrate_property' );

// ---------------------------------------------------------------------------
// Form processing helpers
// ---------------------------------------------------------------------------

/**
 * Process the "save properties" form submission.
 *
 * Validates nonce and capability, then persists all non-invite-code property
 * fields and the default property selection to wp_options.
 *
 * @return string|false  'saved' on successful save, 'added' when a blank
 *                       property was appended, false when not a form submission.
 */
function beds24_booking_plugin_properties_save(): string|false {
	if ( ! isset( $_POST['beds24_properties_nonce'] ) ) {
		return false;
	}

	if ( ! wp_verify_nonce( sanitize_key( $_POST['beds24_properties_nonce'] ), 'beds24_properties_save' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'beds24-booking-plugin' ) );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to save these settings.', 'beds24-booking-plugin' ) );
	}

	// "Add Property" button was clicked — append a blank entry and return.
	if ( isset( $_POST['beds24_add_property'] ) ) {
		$properties   = get_option( 'beds24_booking_plugin_properties', [] );
		$properties[] = [
			'id'       => 0,
			'name'     => '',
			'min_stay' => 1,
			'currency' => 'EUR',
		];
		update_option( 'beds24_booking_plugin_properties', $properties );
		return 'added';
	}

	// Normal save: rebuild the properties array from POST data.
	$raw_ids       = isset( $_POST['beds24_prop_id'] )       ? (array) $_POST['beds24_prop_id']       : [];
	$raw_names     = isset( $_POST['beds24_prop_name'] )     ? (array) $_POST['beds24_prop_name']     : [];
	$raw_min_stays = isset( $_POST['beds24_prop_min_stay'] ) ? (array) $_POST['beds24_prop_min_stay'] : [];
	$raw_currencies = isset( $_POST['beds24_prop_currency'] ) ? (array) $_POST['beds24_prop_currency'] : [];
	$removed        = isset( $_POST['beds24_remove_prop'] )   ? (array) $_POST['beds24_remove_prop']   : [];

	$properties = [];
	$count      = count( $raw_ids );

	for ( $i = 0; $i < $count; $i++ ) {
		$id = absint( $raw_ids[ $i ] ?? 0 );

		// Skip rows the operator marked for removal.
		if ( in_array( (string) $i, $removed, true ) ) {
			continue;
		}

		$properties[] = [
			'id'       => $id,
			'name'     => sanitize_text_field( $raw_names[ $i ] ?? '' ),
			'min_stay' => absint( $raw_min_stays[ $i ] ?? 1 ),
			'currency' => strtoupper( sanitize_text_field( $raw_currencies[ $i ] ?? 'EUR' ) ),
		];
	}

	update_option( 'beds24_booking_plugin_properties', $properties );

	// Default property.
	$default_id = absint( $_POST['beds24_default_property'] ?? 0 );
	update_option( 'beds24_booking_plugin_default_property', $default_id );

	return 'saved';
}

/**
 * Process an invite code exchange form submission.
 *
 * Validates its own nonce (action: beds24_invite_exchange) so it is
 * distinguished from the property save action.
 *
 * @return array|false  On exchange attempt: array with keys 'success' (bool)
 *                      and 'message' (string). False when not an exchange request.
 */
function beds24_booking_plugin_process_invite_exchange(): array|false {
	if ( ! isset( $_POST['beds24_invite_nonce'] ) ) {
		return false;
	}

	if ( ! wp_verify_nonce( sanitize_key( $_POST['beds24_invite_nonce'] ), 'beds24_invite_exchange' ) ) {
		wp_die( esc_html__( 'Security check failed.', 'beds24-booking-plugin' ) );
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to perform this action.', 'beds24-booking-plugin' ) );
	}

	$property_id = absint( $_POST['beds24_exchange_property_id'] ?? 0 );
	$invite_code = sanitize_text_field( wp_unslash( $_POST['beds24_invite_code'] ?? '' ) );

	if ( $property_id <= 0 ) {
		return [
			'success' => false,
			'message' => __( 'Invalid property ID. Save the property first, then exchange the invite code.', 'beds24-booking-plugin' ),
		];
	}

	if ( '' === $invite_code ) {
		return [
			'success' => false,
			'message' => __( 'Invite code cannot be empty.', 'beds24-booking-plugin' ),
		];
	}

	$client = new Beds24_API_Client( $property_id );
	$result = $client->exchange_invite_code( $invite_code );

	if ( is_wp_error( $result ) ) {
		return [
			'success' => false,
			/* translators: %s: WP_Error message */
			'message' => sprintf( __( 'Exchange failed: %s', 'beds24-booking-plugin' ), $result->get_error_message() ),
		];
	}

	return [
		'success' => true,
		'message' => __( 'Invite code accepted. Refresh token stored.', 'beds24-booking-plugin' ),
	];
}

// ---------------------------------------------------------------------------
// Page callback
// ---------------------------------------------------------------------------

/**
 * Render the Properties admin page.
 *
 * Processing order:
 *   1. Handle invite code exchange (separate nonce action).
 *   2. Handle property save / add-property submission.
 *   3. Load current properties from wp_options.
 *   4. Render the properties list and form.
 */
function beds24_booking_properties_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'beds24-booking-plugin' ) );
	}

	// Step 1: Invite code exchange (separate form, separate nonce).
	$exchange_result = beds24_booking_plugin_process_invite_exchange();

	// Step 2: Property save / add-property.
	$save_result = beds24_booking_plugin_properties_save();

	// Step 3: Load current state.
	$properties      = get_option( 'beds24_booking_plugin_properties', [] );
	$default_prop_id = (int) get_option( 'beds24_booking_plugin_default_property', 0 );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Properties — Beds24 Booking', 'beds24-booking-plugin' ); ?></h1>

		<?php if ( 'saved' === $save_result ) : ?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Properties saved.', 'beds24-booking-plugin' ); ?></p>
			</div>
		<?php elseif ( 'added' === $save_result ) : ?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'New property row added. Fill in the details and save.', 'beds24-booking-plugin' ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( false !== $exchange_result ) : ?>
			<div class="notice <?php echo $exchange_result['success'] ? 'notice-success' : 'notice-error'; ?> is-dismissible">
				<p><?php echo esc_html( $exchange_result['message'] ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( empty( $properties ) ) : ?>
			<p>
				<?php esc_html_e( 'No properties configured yet. Click "Add Property" to get started.', 'beds24-booking-plugin' ); ?>
			</p>
		<?php endif; ?>

		<form method="post" action="">
			<?php wp_nonce_field( 'beds24_properties_save', 'beds24_properties_nonce' ); ?>

			<?php if ( ! empty( $properties ) ) : ?>

				<table class="wp-list-table widefat fixed striped" style="margin-bottom:1.5em;">
					<thead>
						<tr>
							<th scope="col" style="width:22%;"><?php esc_html_e( 'Display Name', 'beds24-booking-plugin' ); ?></th>
							<th scope="col" style="width:16%;"><?php esc_html_e( 'Property ID', 'beds24-booking-plugin' ); ?></th>
							<th scope="col" style="width:12%;"><?php esc_html_e( 'Min Stay (nights)', 'beds24-booking-plugin' ); ?></th>
							<th scope="col" style="width:10%;"><?php esc_html_e( 'Currency', 'beds24-booking-plugin' ); ?></th>
							<?php if ( count( $properties ) > 1 ) : ?>
								<th scope="col" style="width:10%;"><?php esc_html_e( 'Default', 'beds24-booking-plugin' ); ?></th>
							<?php endif; ?>
							<th scope="col"><?php esc_html_e( 'Token', 'beds24-booking-plugin' ); ?></th>
							<th scope="col" style="width:8%;"><?php esc_html_e( 'Remove', 'beds24-booking-plugin' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $properties as $index => $prop ) : ?>
							<?php
							$prop_id  = (int) ( $prop['id'] ?? 0 );
							$client   = new Beds24_API_Client( $prop_id );
							$has_token = $prop_id > 0 && $client->has_refresh_token();
							?>
							<tr>
								<td>
									<input
										type="text"
										name="beds24_prop_name[]"
										value="<?php echo esc_attr( $prop['name'] ?? '' ); ?>"
										placeholder="<?php esc_attr_e( 'e.g. Chill Zone', 'beds24-booking-plugin' ); ?>"
										class="regular-text"
									>
								</td>
								<td>
									<input
										type="number"
										name="beds24_prop_id[]"
										value="<?php echo esc_attr( $prop_id > 0 ? $prop_id : '' ); ?>"
										placeholder="<?php esc_attr_e( 'e.g. 271142', 'beds24-booking-plugin' ); ?>"
										min="1"
										style="width:100%;"
									>
									<?php if ( $has_token ) : ?>
										<span style="color:#00a32a;font-size:12px;">
											<?php esc_html_e( 'Token stored.', 'beds24-booking-plugin' ); ?>
										</span>
									<?php endif; ?>
								</td>
								<td>
									<input
										type="number"
										name="beds24_prop_min_stay[]"
										value="<?php echo esc_attr( (int) ( $prop['min_stay'] ?? 1 ) ); ?>"
										min="1"
										style="width:80px;"
									>
								</td>
								<td>
									<input
										type="text"
										name="beds24_prop_currency[]"
										value="<?php echo esc_attr( $prop['currency'] ?? 'EUR' ); ?>"
										placeholder="EUR"
										maxlength="3"
										style="width:60px;text-transform:uppercase;"
									>
								</td>
								<?php if ( count( $properties ) > 1 ) : ?>
									<td style="text-align:center;">
										<input
											type="radio"
											name="beds24_default_property"
											value="<?php echo esc_attr( $prop_id ); ?>"
											<?php checked( $prop_id, $default_prop_id ); ?>
											<?php echo $prop_id <= 0 ? 'disabled' : ''; ?>
										>
									</td>
								<?php else : ?>
									<?php // Single property — hidden default keeps the option in sync. ?>
									<input type="hidden" name="beds24_default_property" value="<?php echo esc_attr( $prop_id ); ?>">
								<?php endif; ?>
								<td>
									<?php if ( $has_token ) : ?>
										<em style="color:#646970;font-size:12px;">
											<?php esc_html_e( 'Connected', 'beds24-booking-plugin' ); ?>
										</em>
									<?php else : ?>
										<em style="color:#d63638;font-size:12px;">
											<?php esc_html_e( 'No token', 'beds24-booking-plugin' ); ?>
										</em>
									<?php endif; ?>
								</td>
								<td>
									<label>
										<input
											type="checkbox"
											name="beds24_remove_prop[]"
											value="<?php echo esc_attr( $index ); ?>"
										>
										<?php esc_html_e( 'Remove', 'beds24-booking-plugin' ); ?>
									</label>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

			<?php endif; ?>

			<p>
				<button type="submit" name="beds24_add_property" value="1" class="button button-secondary">
					<?php esc_html_e( 'Add Property', 'beds24-booking-plugin' ); ?>
				</button>
				<?php submit_button( __( 'Save Properties', 'beds24-booking-plugin' ), 'primary', 'beds24_save_properties', false ); ?>
			</p>

		</form>

		<?php if ( ! empty( $properties ) ) : ?>
			<hr>
			<h2><?php esc_html_e( 'Connect a Property (Invite Code Exchange)', 'beds24-booking-plugin' ); ?></h2>
			<p>
				<?php
				esc_html_e(
					'Generate an invite code in Beds24 admin under MARKETPLACE > API. ' .
					'Enter the code below and click Connect. The invite code is consumed on success ' .
					'and cannot be reused.',
					'beds24-booking-plugin'
				);
				?>
			</p>

			<form method="post" action="">
				<?php wp_nonce_field( 'beds24_invite_exchange', 'beds24_invite_nonce' ); ?>

				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="beds24_exchange_property_id">
									<?php esc_html_e( 'Property ID', 'beds24-booking-plugin' ); ?>
								</label>
							</th>
							<td>
								<select id="beds24_exchange_property_id" name="beds24_exchange_property_id">
									<?php foreach ( $properties as $prop ) : ?>
										<?php $prop_id = (int) ( $prop['id'] ?? 0 ); ?>
										<?php if ( $prop_id > 0 ) : ?>
											<option value="<?php echo esc_attr( $prop_id ); ?>">
												<?php
												echo esc_html(
													( '' !== ( $prop['name'] ?? '' ) ? $prop['name'] . ' — ' : '' ) .
													$prop_id
												);
												?>
											</option>
										<?php endif; ?>
									<?php endforeach; ?>
								</select>
								<p class="description">
									<?php esc_html_e( 'Select the property to connect. Save the property row first if it is new.', 'beds24-booking-plugin' ); ?>
								</p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="beds24_invite_code">
									<?php esc_html_e( 'Invite Code', 'beds24-booking-plugin' ); ?>
								</label>
							</th>
							<td>
								<input
									type="text"
									id="beds24_invite_code"
									name="beds24_invite_code"
									value=""
									class="regular-text"
									autocomplete="off"
									placeholder="<?php esc_attr_e( 'Paste invite code here', 'beds24-booking-plugin' ); ?>"
								>
								<p class="description">
									<?php esc_html_e( 'The code from Beds24 MARKETPLACE > API. Single-use — do not retry a failed code.', 'beds24-booking-plugin' ); ?>
								</p>
							</td>
						</tr>
					</tbody>
				</table>

				<?php submit_button( __( 'Connect', 'beds24-booking-plugin' ), 'secondary', 'beds24_exchange_submit' ); ?>
			</form>
		<?php endif; ?>

	</div>
	<?php
}
