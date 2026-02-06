<?php

defined( 'ABSPATH' ) || exit;

/**
 * Returns the list of automation groups.
 *
 * @return list<TAutomation_Group> list of automation groups.
 *
 * @since 4.0.8
 */
function nelio_content_get_automation_groups() {
	$site_id = nelio_content_get_site_id();
	if ( empty( $site_id ) ) {
		return array();
	}

	/** @var list<TAutomation_Group> */
	$groups = get_transient( 'nc_automation_groups' );
	if ( ! empty( $groups ) ) {
		return $groups;
	}

	$data = array(
		'method'    => 'GET',
		'timeout'   => absint( apply_filters( 'nelio_content_request_timeout', 30 ) ),
		'sslverify' => ! nelio_content_does_api_use_proxy(),
		'headers'   => array(
			'Authorization' => 'Bearer ' . nelio_content_generate_api_auth_token(),
			'accept'        => 'application/json',
			'content-type'  => 'application/json',
		),
	);

	$url      = nelio_content_get_api_url( "/site/{$site_id}/automation-groups", 'wp' );
	$response = wp_remote_request( $url, $data );
	$response = nelio_content_extract_response_body( $response );
	if ( is_wp_error( $response ) ) {
		return array();
	}

	$is_universal = function ( $group ) {
		/** @var TAutomation_Group $group */
		return 'universal' === $group['id'];
	};

	$is_regular = function ( $group ) {
		/** @var TAutomation_Group $group */
		return 'universal' !== $group['id'];
	};

	/** @var list<TAutomation_Group> $groups */
	$groups    = $response;
	$universal = array_values( array_filter( $groups, $is_universal ) )[0];
	$regular   = array_values( array_filter( $groups, $is_regular ) );
	$groups    = array_merge( array( $universal ), $regular );

	set_transient( 'nc_automation_groups', $groups, 5 * MINUTE_IN_SECONDS );

	return $groups;
}
