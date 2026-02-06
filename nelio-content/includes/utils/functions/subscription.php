<?php
/**
 * Nelio Content subscription-related functions.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/utils/functions
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This function returns the current subscription plan, if any.
 *
 * @return string|false name of the current subscription plan, or `false` if it has none.
 *
 * @since 4.0.8
 */
function nelio_content_get_subscription() {
	return get_option( 'nc_subscription', false );
}

/**
 * Returns whether the current user is a paying customer or not.
 *
 * @return boolean whether the current user is a paying customer or not.
 *
 * @since 4.0.8
 */
function nelio_content_is_subscribed() {

	$subscription = nelio_content_get_subscription();
	return ! empty( $subscription );
}

/**
 * This helper function updates the current subscription.
 *
 * @param string       $plan   The plan of the subscription.
 * @param TSite_Limits $limits Max profile limit values.
 *
 * @return void
 *
 * @since 4.0.8
 */
function nelio_content_update_subscription( $plan, $limits ) {

	if ( empty( $plan ) || 'free' === $plan ) {
		delete_option( 'nc_subscription' );
	} else {
		update_option( 'nc_subscription', $plan );
	}

	update_option( 'nc_site_limits', $limits );
}

/**
 * Returns the plan related to the given product.
 *
 * @param string $product Product name.
 *
 * @return string plan related to the given product.
 *
 * @since 4.0.8
 */
function nelio_content_get_plan( $product ) {
	$map = array(
		'nc-monthly'          => 'basic',
		'nc-monthly-standard' => 'standard',
		'nc-monthly-plus'     => 'plus',
		'nc-yearly'           => 'basic',
		'nc-yearly-standard'  => 'standard',
		'nc-yearly-plus'      => 'plus',
	);
	return $map[ $product ] ?? 'basic';
}

/**
 * Returns a list of active promos.
 *
 * @return list<string> list of active promos
 *
 * @since 4.0.8
 */
function nelio_content_get_active_promos() {
	/** @var list<string>|false $promos */
	static $promos = false;

	if ( ! nelio_content_get_site_id() ) {
		return array();
	}

	// If we already know the active promos, return them.
	if ( false !== $promos ) {
		return $promos;
	}

	// Trigger side effect to load token and promos.
	nelio_content_generate_api_auth_token();

	// If we don't, let's see if there's a transient.
	/** @var list<string>|false */
	$active_promos = get_transient( 'nc_active_promos' );
	$promos        = ! empty( $active_promos ) ? $active_promos : array();
	return $promos;
}
