<?php
/** Premium series-detail contract shared with Free discovery. */
defined( 'ABSPATH' ) || exit;

/**
 * Reads a saved series identity, description and archive link.
 */
class Nelio_Content_Get_Series_Ability extends Nelio_Content_Series_Ability {
	/** @return void */
	public function register() {
		$fields = array();
		foreach ( array(
			'name'        => _x( 'Saved series name as plain text.', 'text', 'nelio-content' ),
			'slug'        => _x( 'Saved URL-friendly series identifier.', 'text', 'nelio-content' ),
			'description' => _x( 'Saved series description as plain text, with HTML removed and without rendering shortcodes or blocks.', 'text', 'nelio-content' ),
			'url'         => _x( 'Current HTTP(S) archive link. An empty string means no usable archive link is available; no URL is fetched.', 'text', 'nelio-content' ),
		) as $key => $description ) {
			$fields[ $key ] = array(
				'type'        => 'string',
				'description' => $description,
			);
		}
		$fields['series_id'] = $this->series_id_schema();
		wp_register_ability(
			'nelio-content/get-series',
			array(
				'label'               => _x( 'Get series', 'command', 'nelio-content' ),
				'description'         => _x( 'Requires Nelio Content Premium. Read a saved series name, slug, plain-text description and current archive link from the configured series taxonomy, including empty series. Requires taxonomy assignment permission and editing capability in an enabled attached content type. Series identity and description are shared taxonomy data; access does not imply permission to read member posts. No membership, post counts, metadata, content rendering or external requests are included.', 'text', 'nelio-content' ),
				'category'            => 'nelio-content-series',
				'input_schema'        => $this->object_schema( array( 'series_id' => $this->series_id_schema() ), array( 'series_id' ), _x( 'An existing series ID from series discovery, resolved in the configured taxonomy.', 'text', 'nelio-content' ) ),
				'output_schema'       => $this->object_schema( $fields, array_keys( $fields ), _x( 'Saved series detail without member posts, counts or arbitrary metadata.', 'text', 'nelio-content' ) ),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->get_premium_ability_metadata(),
			)
		);
	}
	/** @return string */
	protected function get_premium_ability_name() {
		return 'nelio-content/get-series';
	}
}
