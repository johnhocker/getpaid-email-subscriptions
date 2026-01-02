<?php
/**
 * Plugin Name: GetPaid – Email Address Subscriptions
 * Description: Manages paid-for email addresses for GetPaid subscriptions, including renewals, invoices, and admin lookup.
 * Version: 1.0.0
 * Author: John Hocker <jhocker@dnet.net>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'plugins_loaded', function () {

	if ( ! function_exists( 'getpaid' ) ) {
		add_action( 'admin_notices', function () {
			echo '<div class="notice notice-error"><p>
				<strong>GetPaid – Email Address Subscriptions</strong> requires the GetPaid plugin to be active.
			</p></div>';
		} );
		return;
	}

	gpes_bootstrap();

}, 5 );

/**
 * Bootstrap plugin
 */
function gpes_bootstrap() {

	define( 'GPES_PATH', plugin_dir_path( __FILE__ ) );
	define( 'GPES_URL', plugin_dir_url( __FILE__ ) );
	define( 'GPES_VERSION', '1.0.0' );

	require_once GPES_PATH . 'includes/helpers.php';
	require_once GPES_PATH . 'includes/invoices-meta.php';
	require_once GPES_PATH . 'includes/renewal-copy.php';
	require_once GPES_PATH . 'includes/invoice-display.php';
	require_once GPES_PATH . 'includes/email-rendering.php';
	require_once GPES_PATH . 'includes/subscription-create.php';
	require_once GPES_PATH . 'includes/stripe-metadata.php';

	if ( is_admin() ) {
		require_once GPES_PATH . 'admin/admin-lookup.php';
	}

}
