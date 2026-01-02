<?php


add_filter(
		'getpaid_invoice_line_items',
		function ( $items, $invoice ) {

			$emails = gpes_get_emails_for_invoice($invoice);

			if ( empty( $emails ) ) return $items;

			$items[] = [
					'name'  => 'Email addresses covered',
					'price' => '',
					'qty'   => implode( '<br />', $emails ),
			];

			return $items;
		},
		10,
		2
		);

/**
 * Add email addresses to PDF invoices
 */
add_action(
		'getpaid_pdf_invoice_after_line_items',
		function ( $invoice ) {

			if ( ! is_object( $invoice ) ) {
				return;
			}

			// Try invoice meta first
			$emails = gpes_get_emails_for_invoice($invoice);

			if ( empty( $emails ) ) {
				return;
			}

			echo '<p><strong>Email address(es) covered:</strong><br>';
			echo esc_html( implode( ', ', $emails ) );
			echo '</p>';

		},
		10
		);

/**
 * Add email addresses to invoice email content
 */
add_action(
		'getpaid_invoice_email_after_line_items',
		function ( $invoice ) {

			if ( ! is_object( $invoice ) ) {
				return;
			}

			// First try invoice meta
			$emails = gpes_get_emails_for_invoice($invoice);

			if ( empty( $emails ) ) {
				return;
			}

			echo "\n\n";
			echo "Email address(es) covered:\n";
			echo implode( "\n", $emails );
			echo "\n\n";

		},
		10,
		1
	);
