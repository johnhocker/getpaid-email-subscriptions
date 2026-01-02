<?php

// Copy emails from parent invoice to recurring invoice
add_action(
		'wpinv_recurring_add_subscription_payment',
		function ( $new_invoice, $parent_invoice ) {

			if ( ! is_object( $parent_invoice ) ) return;
			if ( !gpes_invoice_has_email_meta( $parent_invoice ) ) return;

			$emails = get_post_meta( $parent_invoice->get_id(), GPES_META_EMAILS, true );
			$emails = gpes_normalize_email_list( $emails );

			if ( ! empty( $emails ) ) {
				update_post_meta( $new_invoice->get_id(), GPES_META_EMAILS, $emails );
			}
		},
		10,
		2
		);

// Copy subscription meta to invoice (renewals)
add_action(
		'getpaid_subscription_invoice_created',
		function ( $invoice, $subscription )
		{
			foreach ( [ GPES_META_EMAILS, GPES_META_QUANTITY ] as $key ) {

				$value = get_post_meta( $subscription->get_id(), $key, true );

				if ( empty( $value ) ) {
					continue;
				}

				if ( $key === GPES_META_EMAILS ) {
					$value = gpes_normalize_email_list( $value );
				}

				if ( $key === GPES_META_QUANTITY ) {
					$value = max( 1, intval( $value ) );
				}

				update_post_meta( $invoice->get_id(), $key, $value );
			}
		},
		10,
		2
		);

// Copy invoice meta to subscription (initial purchase)
add_action(
		'getpaid_subscription_created',
		function ( $subscription, $invoice ) {

			if ( ! gpes_invoice_has_email_meta( $invoice ) ) return;

			foreach ( [ GPES_META_EMAILS, GPES_META_QUANTITY ] as $key ) {

				$value = get_post_meta( $invoice->get_id(), $key, true );

				if ( $key === GPES_META_EMAILS ) {
					$value = gpes_normalize_email_list( $value );
					if ( empty( $value ) ) continue;
				}

				if ( $key === GPES_META_QUANTITY ) {
					$value = max( 1, intval( $value ) );
				}

				update_post_meta( $subscription->get_id(), $key, $value );
			}
		},
		10,
		2
		);
