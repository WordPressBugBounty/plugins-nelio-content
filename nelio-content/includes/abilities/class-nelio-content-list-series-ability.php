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
			'description' => 'Optional substring of the series name, at most 200 Unicode characters. Surrounding whitespace is trimmed. Matching follows the site database’s text comparison rules; this does not search descriptions or posts.',
		);
		wp_register_ability(
			'nelio-content/list-series',
			array(
				'label'               => _x( 'List series', 'command', 'nelio-content' ),
				'description'         => 'Requires Nelio Content Premium. Find series by name in the configured series taxonomy, including empty series. Requires permission to assign that taxonomy’s terms and edit content in at least one type with series enabled. Returns only series IDs, names and slugs; series membership and post counts are excluded. Term identities can be listed even when their posts are inaccessible. Results follow ascending series ID, with up to 100 series checked per page before applying access restrictions. Keep query and per_page unchanged when following next_cursor. Records may change between page requests. The search can skip at most 100000 records. Reads saved terms without rendering content, changing data or contacting external services.',
				'category'            => 'nelio-content-series',
				'input_schema'        => $this->object_schema( $properties, array(), 'Optional series-name search and results returned one page at a time with page size limits. The configured taxonomy is resolved automatically.' ),
				'output_schema'       => $this->page_schema(
					$this->object_schema(
						array(
							'series_id' => array(
								'type'        => 'integer',
								'minimum'     => 1,
								'description' => 'Stable series term ID for subsequent series operations.',
							),
							'name'      => array(
								'type'        => 'string',
								'description' => 'Saved human-readable series name, returned as plain text.',
							),
							'slug'      => array(
								'type'        => 'string',
								'description' => 'Saved URL-friendly series identifier; use series_id to identify a record reliably.',
							),
						),
						array( 'series_id', 'name', 'slug' ),
						'One series identity from the configured taxonomy. No post identities, counts, description HTML or metadata are included.'
					),
					'One page of series IDs, names and slugs in ascending ID order, including empty series. This does not provide the series membership or total count.'
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
