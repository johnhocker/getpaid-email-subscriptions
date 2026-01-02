<?php
/**
 * Admin submenu
 */
add_action( 'admin_menu', function () {
	add_submenu_page(
			'wpinv',
			'Email Subscription Lookup',
			'Email Subscription Lookup',
			'manage_options',
			'gpes-email-lookup',
			'gpes_render_lookup_page'
			);
} );

	/**
	 * CSV export handler
	 */
	add_action( 'admin_init', function () {

		if ( empty( $_GET['gpes_export'] ) || empty( $_GET['emails'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$emails = gpes_normalize_search_input( $_GET['emails'] );

		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename=email-subscriptions.csv' );

		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, [
				'Email',
				'Record Type',
				'Invoice ID',
				'Subscription Status',
				'Customer Name',
				'Customer Email',
		] );

		global $wpdb;

		foreach ( $emails as $email ) {

			$invoice_ids = gpes_lookup_invoice_ids_by_email( $email );

			foreach ( $invoice_ids as $invoice_id ) {

				$row = gpes_resolve_invoice_row( $invoice_id, $email );
				if ( $row ) {
					fputcsv( $out, $row );
				}
			}
		}

		fclose( $out );
		exit;
	} );

		/**
		 * Lookup page UI
		 */
		function gpes_render_lookup_page() {

			$query = isset( $_GET['emails'] )
			? sanitize_textarea_field( $_GET['emails'] )
			: '';
			?>
	<div class="wrap">
		<h1>Email Subscription Lookup</h1>

		<form method="get">
			<input type="hidden" name="page" value="gpes-email-lookup" />

			<textarea
				name="emails"
				rows="4"
				class="large-text"
				placeholder="one@example.com&#10;two@example.com"><?php
				echo esc_textarea( $query );
			?></textarea>

			<p class="description">One email per line.</p>

			<?php submit_button( 'Search' ); ?>

			<?php if ( $query ) : ?>
				<a class="button"
				   href="<?php echo esc_url(
					   add_query_arg(
						   [
							   'page'        => 'gpes-email-lookup',
							   'emails'      => rawurlencode( $query ),
							   'gpes_export' => 1,
						   ],
						   admin_url( 'admin.php' )
					   )
				   ); ?>">
					Export CSV
				</a>
			<?php endif; ?>
		</form>

		<?php
		if ( $query ) {
			gpes_render_results( $query );
		}
		?>
	</div>
	<?php
}

/**
 * Render results table
 */
function gpes_render_results( $raw ) {

	$emails = gpes_normalize_search_input( $raw );

	echo '<h2>Results</h2>';

	foreach ( $emails as $email ) {

		echo '<h3>' . esc_html( $email ) . '</h3>';

		$invoice_ids = gpes_lookup_invoice_ids_by_email( $email );

		if ( empty( $invoice_ids ) ) {
			echo '<p>No matches.</p>';
			continue;
		}

		echo '<table class="widefat striped">';
		echo '<thead>
			<tr>
				<th>Invoice</th>
				<th>Subscription Status</th>
				<th>Customer</th>
				<th>Email</th>
				<th>Action</th>
			</tr>
		</thead><tbody>';

		foreach ( $invoice_ids as $invoice_id ) {

			$row = gpes_resolve_invoice_row( $invoice_id, $email );
			if ( ! $row ) {
				continue;
			}

			list( , , $id, $status, $name, $cemail ) = $row;

			echo '<tr>';
			echo '<td>#' . intval( $id ) . '</td>';
			echo '<td>' . esc_html( $status ) . '</td>';
			echo '<td>' . esc_html( $name ) . '</td>';
			echo '<td>' . esc_html( $cemail ) . '</td>';
			echo '<td><a href="' . esc_url( get_edit_post_link( $id ) ) . '">Open</a></td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
	}
}

/**
 * Find invoice IDs containing a given email
 * (excluding draft / trash invoices)
 */
function gpes_lookup_invoice_ids_by_email( $email ) {
	global $wpdb;

	return $wpdb->get_col(
		$wpdb->prepare(
			"
			SELECT pm.post_id
			FROM {$wpdb->postmeta} pm
			INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
			WHERE pm.meta_key = %s
			  AND pm.meta_value LIKE %s
			  AND p.post_type = %s
			  AND p.post_status NOT IN ('draft', 'trash', 'auto-draft')
			",
			GPES_META_EMAILS,
			'%' . $wpdb->esc_like( $email ) . '%',
			GPES_INVOICE_POST_TYPE
		)
	);
}

/**
 * Resolve invoice → subscription → customer
 */
function gpes_resolve_invoice_row( $invoice_id, $email ) {

	if ( ! function_exists( 'getpaid_get_invoice' ) ) {
		return null;
	}

	$invoice = getpaid_get_invoice( $invoice_id );
	if ( ! $invoice ) {
		return null;
	}

	$customer_name  = '';
	$customer_email = '';
	$status         = '';

	$customer = $invoice->get_customer();
	if ( $customer ) {
		$customer_name  = $customer->get_name();
		$customer_email = $customer->get_email();
	}

	if ( method_exists( $invoice, 'get_subscription_id' ) ) {
		$sub_id = $invoice->get_subscription_id();
		if ( $sub_id && function_exists( 'getpaid_get_subscription' ) ) {
			$sub = getpaid_get_subscription( $sub_id );
			if ( $sub ) {
				$status = $sub->get_status();
			}
		}
	}

	return [
		$email,
		'Invoice',
		$invoice_id,
		$status,
		$customer_name,
		$customer_email,
	];
}
