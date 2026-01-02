<?php

/**
 * Set email seat limit from Item (ACF) when subscription is created
 */
add_action(
	'wpinv_subscription_created',
	function ( $subscription_id, $args ) {

		if ( empty( $args['items'] ) || ! is_array( $args['items'] ) ) {
			return;
		}

		foreach ( $args['items'] as $item ) {

			if ( empty( $item['id'] ) ) {
				continue;
			}

			// Read ACF (or regular post meta) from item
			$product_limit = intval(
				get_post_meta( $item['id'], GPES_META_QUANTITY, true )
			);

			if ( $product_limit > 0 ) {
				update_post_meta(
					$subscription_id,
					GPES_META_QUANTITY,
					$product_limit
				);
				break; // one subscription per item
			}
		}
	},
	10,
	2
);

