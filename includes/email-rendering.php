<?php

add_action(
		'getpaid_after_email_details_invoice_total',
		'gpes_add_emails_to_invoice_email',
		10,
		2
		);

function gpes_add_emails_to_invoice_email( $invoice, $data ) {

	if ( ! is_object( $invoice ) ) {
		return;
	}

	$emails = gpes_get_emails_for_invoice( $invoice );

	if ( empty( $emails ) ) {
		return;
	}

	?>
    <tr class="getpaid-email-details-<?php echo esc_attr( GPES_META_EMAILS ); ?>">
        <td class="getpaid-label-td">
            <?php esc_html_e( 'Email addresses covered', 'getpaid-email-subscriptions' ); ?>
        </td>
        <td class="getpaid-value-td">
            <span class="getpaid-invoice-meta-<?php echo esc_attr( GPES_META_EMAILS ); ?>-value">
                <?php echo gpes_emails_to_html_lines( $emails ); ?>
            </span>
        </td>
    </tr>
    <?php
}
