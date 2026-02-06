<?php
namespace Nelio_Content\Compat\WooCommerce;

defined( 'ABSPATH' ) || exit;

/**
 * Hooks into WordPress.
 *
 * @return void
 */
function init_hooks() {
	add_filter( 'nelio_content_available_post_types_setting', __NAMESPACE__ . '\add_order_type' );
	add_filter( 'nelio_content_get_post_types', __NAMESPACE__ . '\remove_product_type' );
	add_filter( 'nelio_content_post_statuses', __NAMESPACE__ . '\maybe_add_order_statuses', 10, 2 );
}
add_action( 'woocommerce_init', __NAMESPACE__ . '\init_hooks' );

/**
 * Callback to add order type.
 *
 * @param list<array{value:string, label:string}> $types Types.
 *
 * @return list<array{value:string, label:string}>
 */
function add_order_type( $types ) {
	$shop_order = get_post_type_object( 'shop_order' );
	if ( empty( $shop_order ) ) {
		return $types;
	}

	if ( empty( $shop_order->labels->singular_name ) ) {
		return $types;
	}

	if ( ! is_string( $shop_order->labels->singular_name ) ) {
		return $types;
	}

	array_push(
		$types,
		array(
			'value' => $shop_order->name,
			'label' => $shop_order->labels->singular_name,
		)
	);
	return $types;
}

/**
 * Callback to add order statuses.
 *
 * @param list<TPost_Status> $statuses  Statuses.
 * @param string             $post_type Post type.
 *
 * @return list<TPost_Status>
 */
function maybe_add_order_statuses( $statuses, $post_type ) {

	if ( 'shop_order' !== $post_type ) {
		return $statuses;
	}

	$wc_statuses = wc_get_order_statuses();
	$wc_statuses = array_map(
		function ( $key, $value ) use ( $post_type ) {
			/** @var string $key   */
			/** @var string $value */

			return array(
				'slug'      => $key,
				'name'      => $value,
				'icon'      => 'store',
				'postTypes' => array( $post_type ),
				'roles'     => array( 'shop_manager' ),
			);
		},
		array_keys( $wc_statuses ),
		array_values( $wc_statuses )
	);

	return $wc_statuses;
}

/**
 * Add post types.
 *
 * @param list<string> $post_types post types.
 *
 * @return list<string>
 */
function remove_product_type( $post_types ) {
	$post_types = array_filter( $post_types, fn( $t ) => 'product' !== $t );
	return array_values( $post_types );
}
