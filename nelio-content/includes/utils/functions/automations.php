<?php

defined( 'ABSPATH' ) || exit;

/**
 * Returns the list of automation groups.
 *
 * @param bool $strict Report configuration/transport errors instead of an empty list.
 * @return ($strict is true ? list<TAutomation_Group>|WP_Error : list<TAutomation_Group>)
 *
 * @since 4.0.8
 */
function nelio_content_get_automation_groups( $strict = false ) {
	$site_id = nelio_content_get_site_id();
	if ( empty( $site_id ) ) {
		return $strict ? new WP_Error( 'nelio_content_feature_unavailable', _x( 'Nelio Content is not configured.', 'text', 'nelio-content' ) ) : array();
	}

	/** @var list<TAutomation_Group> */
	$groups = get_transient( 'nc_automation_groups' );
	if ( ! empty( $groups ) ) {
		return $groups;
	}

	$data = array(
		'method'              => 'GET',
		'limit_response_size' => 2 * MB_IN_BYTES,
		'timeout'             => absint( apply_filters( 'nelio_content_request_timeout', 30 ) ),
		'sslverify'           => ! nelio_content_does_api_use_proxy(),
		'headers'             => array(
			'Authorization' => 'Bearer ' . nelio_content_generate_api_auth_token(),
			'accept'        => 'application/json',
			'content-type'  => 'application/json',
		),
	);

	$url      = nelio_content_get_api_url( "/site/{$site_id}/automation-groups", 'wp' );
	$response = wp_remote_request( $url, $data );
	$response = nelio_content_extract_response_body( $response );
	if ( is_wp_error( $response ) || ! is_array( $response ) ) {
		return $strict ? new WP_Error( 'nelio_content_cloud_unavailable', _x( 'Unable to retrieve social automation groups.', 'text', 'nelio-content' ) ) : array();
	}
	if ( empty( $response ) ) {
		return array();
	}

	// Validate the fields consumed by cron before typed SQL and profile selection.
	$profile = array(
		'type'       => 'object',
		'required'   => array( 'enabled', 'publication', 'reshare' ),
		'properties' => array(
			'enabled'     => array( 'type' => 'boolean' ),
			'publication' => array(
				'type'       => 'object',
				'required'   => array( 'enabled' ),
				'properties' => array( 'enabled' => array( 'type' => 'boolean' ) ),
			),
			'reshare'     => array(
				'type'       => 'object',
				'required'   => array( 'enabled' ),
				'properties' => array( 'enabled' => array( 'type' => 'boolean' ) ),
			),
		),
	);
	$schema  = array(
		'type'  => 'array',
		'items' => array(
			'type'       => 'object',
			'required'   => array( 'id', 'priority', 'publication', 'profileSettings', 'networkSettings' ),
			'properties' => array(
				'id'              => array( 'type' => 'string' ),
				'priority'        => array( 'type' => 'number' ),
				'postType'        => array( 'type' => 'string' ),
				'authors'         => array(
					'type'  => 'array',
					'items' => array( 'type' => 'integer' ),
				),
				'taxonomies'      => array(
					'type'                 => 'object',
					'additionalProperties' => array(
						'type'  => 'array',
						'items' => array( 'type' => 'integer' ),
					),
				),
				'publication'     => array(
					'oneOf' => array(
						array(
							'type'       => 'object',
							'required'   => array( 'type' ),
							'properties' => array(
								'type' => array(
									'type' => 'string',
									'enum' => array( 'always' ),
								),
							),
						),
						array(
							'type'       => 'object',
							'required'   => array( 'type', 'days' ),
							'properties' => array(
								'type' => array(
									'type' => 'string',
									'enum' => array( 'max-age' ),
								),
								'days' => array(
									'type'    => 'number',
									'minimum' => 0,
								),
							),
						),
					),
				),
				'profileSettings' => array(
					'type'                 => 'object',
					'additionalProperties' => $profile,
				),
				'networkSettings' => array( 'type' => 'object' ),
			),
		),
	);
	if ( is_wp_error( rest_validate_value_from_schema( $response, $schema, 'groups' ) ) ) {
		return $strict ? new WP_Error( 'nelio_content_cloud_unavailable', _x( 'Cloud returned invalid social automation groups.', 'text', 'nelio-content' ) ) : array();
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
	$universal = array_values( array_filter( $groups, $is_universal ) );
	if ( empty( $universal ) ) {
		return $strict ? new WP_Error( 'nelio_content_cloud_unavailable', _x( 'Cloud returned incomplete social automation groups.', 'text', 'nelio-content' ) ) : array();
	}
	$regular = array_values( array_filter( $groups, $is_regular ) );
	$groups  = array_merge( $universal, $regular );

	set_transient( 'nc_automation_groups', $groups, 5 * MINUTE_IN_SECONDS );

	return $groups;
}
