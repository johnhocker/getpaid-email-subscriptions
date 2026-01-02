<?php

add_action(
	'getpaid_after_email_details_invoice_total',
	'gpes_add_emails_to_invoice_email',
	10,
	2
);

function gpes_add_emails_to_invoice_email($invoice, $data) {

	$emails = gpes_get_emails_for_invoice( $invoice );

	if ( empty( $emails ) ) {
		return;
	}

	$template = '
		<tr class="getpaid-email-details-'.GPESGPES_META_EMAILS.'">
			<td class="getpaid-lable-td">
				Email addresses covered
			 </td>
			<td class="getpaid-value-td">
				<span class="getpaid-invoice-meta-'.GPESGPES_META_EMAILS.'-value">'.gpes_emails_to_html_lines( $emails ).'</span>
			</td>
		</tr>
	';

	echo $template;

}
