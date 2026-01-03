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
/*	add_action( 'admin_init', function () {

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
	} );*/

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
				<!-- <a class="button"
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
				</a> -->
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
// 		echo '<pre>'.print_r($invoice_ids,TRUE).'</pre>';


		$subs = getpaid_get_subscriptions(['invoice_in' => $invoice_ids]);
// 		echo '<pre>'.print_r($subs,TRUE).'</pre>';

		if ( empty( $subs ) ) {
			echo '<p>No matches.</p>';
			continue;
		}

		echo '<table class="widefat striped">';
		echo '<thead>
			<tr>
				<th>Subscription</th>
				<th>Subscription Status</th>
				<th>Customer Name</th>
				<th>Customer Email</th>
				<th>Subscption</th>
				<th>latest Invoice</th>
				<th>Emails Covered</th>
			</tr>
		</thead><tbody>';

		foreach ( $subs as $subscription ) {

			$row = gpes_resolve_subscription_row_from_invoice( $subscription, $email );
			if ( ! $row ) {
				continue;
			}
			//echo '<pre>'.print_r($row,TRUE).'</pre>';

			$view_sub_url = admin_url(
				'admin.php?page=wpinv-subscriptions&view=edit&id=' . absint( $row['subscription_id'] )
			);

			echo '<tr>';
			echo '<td> <a href="'.$view_sub_url.'" >#' . intval( $row['subscription_id'] ) . '</td>';
			echo '<td>' . esc_html( $row['subscription_status'] ) . '</td>';
			echo '<td>' . esc_html( $row['customer_name'] ) . '</td>';
			echo '<td>' . esc_html( $row['customer_email'] ) . '</td>';
			echo '<td> <a href="' . esc_url( get_edit_post_link( $row['latest_invoice_id'] ) ) . '">'.$row['latest_invoice_number'].'</a></td>';
			echo '<td>' . esc_html( $row['sub_data'] ) . '</td>';
			echo '<td>' . $row['emails_covered'] . '</td>';
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
			SELECT DISTINCT pm.post_id
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

	if ( ! function_exists( 'wpinv_get_invoice' ) ) {
		error_log('wpinv_get_invoice does not exist');
		return null;
	}

	$invoice = wpinv_get_invoice( $invoice_id );
	if ( ! $invoice ) {
		error_log('invoice is empty');
		return null;
	}

	//var_dump( get_class_methods( $invoice ) );

	$customer_name  = $invoice->get_customer_full_name();
	$customer_email = $invoice->get_customer_email();
	$status         = '';
	$sub	        = '';

	//$customer = $invoice->get_customer();
	//if ( $customer ) {
// 		$customer_name  = $customer->get_name();
// 		$customer_email = $customer->get_email();
	//}

// 	if ( method_exists( $invoice, 'get_subscription_id' ) ) {
		if ( $invoice->is_recurring() && function_exists( 'getpaid_get_invoice_subscription' ) ) {
			$sub = getpaid_get_invoice_subscription( $invoice );
			if ( $sub ) {
				$status = $sub->get_status();
			}
		}
// 	}

	return [
		'email' => $email,
		'type' => 'Invoice',
		'invoice_id' => $invoice_id,
		'subscription_status' => $status,
		'custname' => $customer_name,
		'cust_email' => $customer_email,
		//	'sub_data' => print_r(get_class_methods($sub),true),
		//	'invoice_data' => var_export(get_class_methods($invoice),TRUE),
	];
}


function gpes_get_latest_invoice_for_subscription( $subscription )
{
	$parent = $subscription->get_parent_invoice();

	if ( !$parent->exists() ) {
		return null;
	}

	$statuses = array_keys( wpinv_get_invoice_statuses() );

	$invoices = get_posts(
			array(
					'post_parent' => $parent->get_id(),
					'numberposts' => 1,
					'post_status' => $statuses,
					'orderby'     => 'date',
					'order'       => 'DESC',
					'post_type'   => GPES_INVOICE_POST_TYPE,
			)
		);

	if (empty($invoices)) {
		return $parent;
	}

	return new WPInv_Invoice($invoices[0]);

}

function gpes_resolve_subscription_row_from_invoice( $subscription, $email ) {

	$customer = $subscription->get_customer();
	$invoice  = gpes_get_latest_invoice_for_subscription( $subscription );

	//echo '<pre>'.print_r($invoice,TRUE).'</pre>';


	$emails_covered = '';
	if (!empty($invoice)) {
		$emails_listed = gpes_get_emails_for_invoice($invoice);
		$emails_covered = gpes_emails_to_html_lines($emails_listed);
	}


	return [
			'search_email'        => $email,
			'subscription_id'     => $subscription->get_id(),
			'subscription_status' => $subscription->get_status(),
			'latest_invoice_id'   => $invoice ? $invoice->get_id() : '',
			'latest_invoice_number'   => $invoice ? $invoice->get_number() : '',
			'customer_name'       => $customer ? $customer->display_name : '',
			'customer_email'      => $customer ? $customer->user_email : '',
			'emails_covered'      => $emails_covered,
	];
}
