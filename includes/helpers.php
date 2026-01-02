<?php

define( 'GPES_META_EMAILS', 'email_addresses_paid_for' );
define( 'GPES_META_QUANTITY', 'email_quantity_allowed' );
define( 'GPES_INVOICE_POST_TYPE', 'wpi_invoice' );



function gpes_normalize_email_list( $emails ) {

	if ( empty( $emails ) ) {
		return [];
	}

	if ( is_string( $emails ) ) {
		$emails = preg_split( '/\r\n|\r|\n/', $emails );
	}

	$emails = array_map( 'trim', (array) $emails );
	$emails = array_filter( $emails, 'is_email' );

	return array_values( array_unique( $emails ) );
}

function gpes_invoice_has_email_meta( $invoice ) {
	if ( ! is_object( $invoice ) || ! method_exists( $invoice, 'get_id' ) ) {
		return false;
	}

	return ! empty(
			get_post_meta( $invoice->get_id(), GPES_META_EMAILS, true )
			);
}


function gpes_get_emails_for_invoice( $invoice ) {
	if ( ! is_object( $invoice ) ) {
		return [];
	}

	$emails = get_post_meta(
			$invoice->get_id(),
			GPES_META_EMAILS,
			true
			);

	if ( empty( $emails ) && method_exists( $invoice, 'get_subscription_id' ) ) {
		$sub_id = $invoice->get_subscription_id();
		if ( $sub_id ) {
			$emails = get_post_meta( $sub_id, GPES_META_EMAILS, true );
		}
	}

	return gpes_normalize_email_list( $emails );
}

function gpes_normalize_search_input( $raw ) {

	if ( empty( $raw ) ) {
		return [];
	}

	$lines_array = preg_split( '/\r\n|\r|\n/', $raw );

	if ( empty( $lines_array ) ) {
		return [];
	}

	return array_filter(
			array_map(
					function ( $line ) {
						return strtolower( trim( $line ) );
					},
					$lines_array
					)
			);
}

function gpes_get_dedupe_key( $post, $invoice = null ) {

	if ( $invoice && method_exists( $invoice, 'get_subscription_id' ) ) {
		$sub_id = $invoice->get_subscription_id();
		if ( $sub_id ) {
			return 'sub_' . $sub_id;
		}
	}

	return 'post_' . $post->ID;
}


/**
 * Convert a normalized array of emails into an HTML-escaped string, one per line.
 *
 * @param array $emails Normalized array of emails.
 * @return string HTML string with emails separated by <br>.
 */
function gpes_emails_to_html_lines( array $emails ) : string {
	if ( empty( $emails ) ) {
		return '';
	}

	$escaped = array_map( 'esc_html', $emails );
	return implode( '<br />', $escaped );
}
