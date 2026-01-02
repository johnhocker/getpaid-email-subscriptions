<?php
/**
 * Invoice meta fields for Email Subscriptions
 */

defined( 'ABSPATH' ) || exit;

/**
 * Register meta box on admin init
 */
add_action( 'admin_init', 'gpes_register_invoice_meta_box' );

function gpes_register_invoice_meta_box() {
	add_meta_box(
			'gpes-email-subscription',
			'Email Subscription Details',
			'gpes_render_invoice_meta_box',
			GPES_INVOICE_POST_TYPE,
			'normal',
			'default'
			);
}

/**
 * Render meta box UI
 */
function gpes_render_invoice_meta_box( $post ) {

	$emails   = get_post_meta( $post->ID, GPES_META_EMAILS, true );
	$quantity = get_post_meta( $post->ID, GPES_META_QUANTITY, true );

	wp_nonce_field( 'gpes_invoice_meta', 'gpes_invoice_meta_nonce' );
	?>

	<p>
		<label for="gpes_email_addresses">
			<strong>Email addresses covered</strong>
		</label><br />
		<textarea
			id="gpes_email_addresses"
			name="gpes_email_addresses"
			rows="5"
			class="widefat"
			placeholder="user1@domain.com&#10;user2@domain.com"
		><?php echo esc_textarea( is_array( $emails ) ? implode( "\n", $emails ) : $emails ); ?></textarea>
	</p>

	<p>
		<label for="gpes_email_quantity">
			<strong>Email addresses allowed</strong>
		</label><br />
		<input
			type="number"
			min="1"
			id="gpes_email_quantity"
			name="gpes_email_quantity"
			value="<?php echo esc_attr( $quantity ); ?>"
			class="small-text"
		/>
	</p>

	<p class="description">
		These values will be copied to the subscription and future invoices.
	</p>

	<?php
}

/**
 * Save invoice meta
 */
add_action( 'save_post_'.GPES_INVOICE_POST_TYPE, 'gpes_save_invoice_meta', 10, 2 );

function gpes_save_invoice_meta( $post_id, $post ) {

	// Do not run on autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Verify nonce
	if (
		! isset( $_POST['gpes_invoice_meta_nonce'] ) ||
		! wp_verify_nonce( $_POST['gpes_invoice_meta_nonce'], 'gpes_invoice_meta' )
	) {
		return;
	}

	// Check permissions
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Emails
	if ( isset( $_POST['gpes_email_addresses'] ) ) {
		$emails = gpes_normalize_email_list( wp_unslash( $_POST['gpes_email_addresses'] ) );

		if ( ! empty( $emails ) ) {
			update_post_meta( $post_id, GPES_META_EMAILS, $emails );
		} else {
			delete_post_meta( $post_id, GPES_META_EMAILS );
		}
	}

	// Quantity allowed
	if ( isset( $_POST['gpes_email_quantity'] ) ) {
		$quantity = absint( $_POST['gpes_email_quantity'] );

		if ( $quantity > 0 ) {
			update_post_meta( $post_id, GPES_META_QUANTITY, $quantity );
		} else {
			delete_post_meta( $post_id, GPES_META_QUANTITY );
		}
	}
}
