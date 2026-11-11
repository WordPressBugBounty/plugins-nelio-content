<?php
/**
 * Registration, authorization and execution of the get-post-analytics ability.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Implements the get-post-analytics ability.
 *
 * @extends Nelio_Content_Ability<array{post_id:int}>
 */
class Nelio_Content_Get_Post_Analytics_Ability extends Nelio_Content_Ability {

	/**
	 * Registers the get-post-analytics ability.
	 *
	 * @return void
	 */
	public function register() {
		$metric_schema = array(
			'type'                 => 'object',
			'description'          => _x( 'Cached lifetime metric counts indexed by metric or network name. Coverage varies by provider; zero alone does not establish availability.', 'text', 'nelio-content' ),
			'additionalProperties' => array(
				'type'        => 'integer',
				'minimum'     => 0,
				'description' => _x( 'Non-negative cached count for the metric identified by this key.', 'text', 'nelio-content' ),
			),
		);

		wp_register_ability(
			'nelio-content/get-post-analytics',
			array(
				'label'               => _x( 'Get post analytics', 'command', 'nelio-content' ),
				'description'         => _x( 'Read cached lifetime analytics without refreshing providers. Engagement contains available network counters and WordPress comments; pageviews come from the configured Google Analytics property. Zero does not prove provider coverage. No date-range breakdown is available.', 'text', 'nelio-content' ),
				'category'            => 'nelio-content-analytics',
				'input_schema'        => $this->object_schema(
					array(
						'post_id' => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => _x( 'WordPress post ID to inspect. The post must be readable and its post type must have analytics enabled.', 'text', 'nelio-content' ),
						),
					),
					array( 'post_id' ),
					_x( 'Identify a readable WordPress post whose cached lifetime analytics should be retrieved.', 'text', 'nelio-content' )
				),
				'output_schema'       => $this->object_schema(
					array(
						'post_id'    => array(
							'type'        => 'integer',
							'description' => _x( 'WordPress post ID to which these cached metrics belong.', 'text', 'nelio-content' ),
						),
						'engagement' => array_merge( $metric_schema, array( 'description' => _x( 'Cached engagement counters keyed by network or metric name, including total and WordPress comments. Networks may expose different counters.', 'text', 'nelio-content' ) ) ),
						'pageviews'  => array_merge( $metric_schema, array( 'description' => _x( 'Cached pageview counts from the configured Google Analytics property, including total and available social network breakdowns.', 'text', 'nelio-content' ) ) ),
					),
					array( 'post_id', 'engagement', 'pageviews' ),
					_x( 'Cached lifetime engagement and pageview metrics for the post. Providers are not refreshed and no date-range breakdown is available.', 'text', 'nelio-content' )
				),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->get_ability_metadata(),
			)
		);
	}

	/**
	 * Checks whether the current user may execute this ability.
	 *
	 * @param array{post_id:int} $input Validated ability input.
	 * @return bool|WP_Error
	 */
	public function permissions( $input ) {
		return $this->can_use_plugin( $input );
	}

	/**
	 * Uses cached metrics only; avoids hidden remote refreshes.
	 *
	 * @param array{post_id:int} $input Validated ability input.
	 * @return array<string,mixed>|WP_Error
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( true !== $allowed ) {
			return is_wp_error( $allowed ) ? $allowed : new WP_Error( 'nelio_content_permission_denied', _x( 'You cannot execute this ability.', 'user', 'nelio-content' ) );
		}

		$post_id = $input['post_id'];
		if ( ! in_array( get_post_type( $post_id ), nelio_content_get_post_types( 'analytics' ), true ) ) {
			return new WP_Error( 'nelio_content_feature_unavailable', _x( 'Analytics are disabled for this post type.', 'user', 'nelio-content' ) );
		}
		if ( ! get_post_meta( $post_id, '_nc_last_update', true ) ) {
			return new WP_Error( 'nelio_content_analytics_unavailable', _x( 'No cached analytics are available for this post.', 'user', 'nelio-content' ) );
		}
		$statistics = Nelio_Content_Analytics_Helper::instance()->get_post_stats( $post_id, true );
		return array(
			'post_id'    => $post_id,
			'engagement' => (object) array_map( 'absint', $statistics['engagement'] ),
			'pageviews'  => (object) array_map( 'absint', $statistics['pageviews'] ),
		);
	}
}
