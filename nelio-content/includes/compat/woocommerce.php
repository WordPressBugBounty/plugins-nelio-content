<?php
/**
 * This file adds compatibility with WooCommerce.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/compat
 * @author     Antonio Villegas <antonio.villegas@neliosoftware.com>
 * @since      3.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}//end if

function nc_woocommerce_hooks() {

	add_filter( 'nelio_content_available_post_types_setting', 'nc_woocommerce_add_order_type' );
	add_filter( 'nelio_content_post_statuses', 'nc_woocommerce_maybe_add_order_statuses', 10, 2 );

}//end nc_woocommerce_hooks()
add_action( 'woocommerce_init', 'nc_woocommerce_hooks' );

function nc_woocommerce_add_order_type( $types ) {
	$shop_order = get_post_type_object( 'shop_order' );
	array_push(
		$types,
		array(
			'value' => $shop_order->name,
			'label' => $shop_order->labels->singular_name,
		)
	);
	return $types;
}//end nc_woocommerce_add_order_type()

function nc_woocommerce_maybe_add_order_statuses( $statuses, $post_type ) {

	if ( 'shop_order' !== $post_type ) {
		return $statuses;
	}//end if

	$wc_statuses = wc_get_order_statuses();
	$wc_statuses = array_map(
		function ( $key, $value ) {
			return array(
				'slug' => $key,
				'name' => $value,
				'icon' => 'cart',
			);
		},
		array_keys( $wc_statuses ),
		array_values( $wc_statuses )
	);

	return $wc_statuses;
}//end nc_woocommerce_maybe_add_order_statuses()
