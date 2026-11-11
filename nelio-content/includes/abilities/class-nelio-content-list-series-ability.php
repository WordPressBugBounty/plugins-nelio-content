<?php
/** Discoverable Premium contract for series discovery. */
defined( 'ABSPATH' ) || exit;

/**
 * Lists basic series identities without disclosing their posts or counts.
 *
 * @extends Nelio_Content_Premium_Ability<TAbility_Input>
 */
class Nelio_Content_List_Series_Ability extends Nelio_Content_Premium_Ability {

	/** @return void */
	public function register() {
		$properties          = array_intersect_key( $this->query_properties(), array_flip( array( 'cursor', 'per_page' ) ) );
		$properties['query'] = array(
			'type'        => 'string',
			'maxLength'   => 200,
			'description' => _x( 'Optional substring of the series name, at most 200 Unicode characters. Surrounding whitespace is trimmed. Matching follows the site database’s text comparison rules; this does not search descriptions or posts.', 'text', 'nelio-content' ),
		);
		wp_register_ability(
			'nelio-content/list-series',
			array(
				'label'               => _x( 'List series', 'command', 'nelio-content' ),
				'description'         => _x( 'Requires Nelio Content Premium. Find series by name in the configured series taxonomy, including empty series. Requires permission to assign that taxonomy’s terms and edit content in at least one type with series enabled. Returns only series IDs, names and slugs; series membership and post counts are excluded. Term identities can be listed even when their posts are inaccessible. Results follow ascending series ID, with up to 100 source terms examined per page. Keep query and per_page unchanged when following next_cursor. Pagination is not a snapshot and supports source offsets up to 100000. Reads saved terms without rendering content, changing data or contacting external services.', 'text', 'nelio-content' ),
				'category'            => 'nelio-content-series',
				'input_schema'        => $this->object_schema( $properties, array(), _x( 'Optional series-name search and bounded pagination. The configured taxonomy is resolved automatically.', 'text', 'nelio-content' ) ),
				'output_schema'       => $this->page_schema(
					$this->object_schema(
						array(
							'series_id' => array(
								'type'        => 'integer',
								'minimum'     => 1,
								'description' => _x( 'Stable series term ID for subsequent series operations.', 'text', 'nelio-content' ),
							),
							'name'      => array(
								'type'        => 'string',
								'description' => _x( 'Saved human-readable series name, returned as plain text.', 'text', 'nelio-content' ),
							),
							'slug'      => array(
								'type'        => 'string',
								'description' => _x( 'Saved URL-friendly series identifier; use series_id to identify a record reliably.', 'text', 'nelio-content' ),
							),
						),
						array( 'series_id', 'name', 'slug' ),
						_x( 'One series identity from the configured taxonomy. No post identities, counts, description HTML or metadata are included.', 'text', 'nelio-content' )
					),
					_x( 'One bounded page of series identities in ascending ID order, including empty series. No membership or total count is implied.', 'text', 'nelio-content' )
				),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->get_premium_ability_metadata(),
			)
		);
	}

	/** @return string */
	protected function get_premium_ability_name() {
		return 'nelio-content/list-series';
	}
}
