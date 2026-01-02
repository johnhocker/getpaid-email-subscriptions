<?php

add_filter(
		'wpinv_email_invoice_items',
		'gpes_add_emails_to_invoice_email',
		10,
		2
		);

function gpes_add_emails_to_invoice_email( $content, $invoice ) {

	$emails = gpes_get_emails_for_invoice( $invoice );

	if ( empty( $emails ) ) {
		return $content;
	}

	$content .= "\n\nEmails Covered:\n";

	foreach ( $emails as $email ) {
		$content .= '- ' . $email . "\n";
	}

	return $content;
}
