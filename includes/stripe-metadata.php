<?php

/**
 * Add email addresses to Stripe charge metadata
 */
add_filter(
	'getpaid_stripe_payment_intent_args',
	function ( $args, $invoice ) {

		if ( ! is_object( $invoice ) ) {
			return $args;
		}

		$emails = gpes_get_emails_for_invoice($invoice);

		if ( empty( $emails ) ) {
			return $args;
		}

		$invoice_id = $invoice->get_id();

		$args['metadata']['email_addresses'] = implode( ', ', $emails );
		$args['metadata']['invoice_id']	  = $invoice_id;

		return $args;

	},
	10,
	2
);
