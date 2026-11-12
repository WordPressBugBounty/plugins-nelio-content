<?php
/** Shared contracts for explicit Premium series mutations. */
defined( 'ABSPATH' ) || exit;

/** Defines safe field inputs and confirmed series-change results. */
abstract class Nelio_Content_Series_Mutation_Ability extends Nelio_Content_Series_Ability {
	/** @return array<string,array<string,mixed>> */
	protected function term_properties() {
		return array(
			'name'        => array(
				'type'        => 'string',
				'minLength'   => 1,
				'maxLength'   => 200,
				'description' => 'Plain series name, trimmed, at most 200 Unicode characters. Required on creation; omit on update to preserve it.',
			),
			'description' => array(
				'type'        => 'string',
				'maxLength'   => 10000,
				'description' => 'Plain series description, at most 10000 Unicode characters. Omit to preserve on update or default to empty on creation; an empty string clears it.',
			),
			'slug'        => array(
				'type'        => 'string',
				'minLength'   => 1,
				'maxLength'   => 200,
				'pattern'     => '^[a-z0-9]+(?:-[a-z0-9]+)*$',
				'description' => 'Optional lowercase ASCII slug, using letters, numbers and single separating hyphens. Creation derives a slug from the name when omitted; update preserves it. Conflicts fail rather than silently suffixing or reusing another series.',
			),
		);
	}
	/** @return array<string,mixed> */
	protected function part_schema() {
		return array(
			'type'        => array( 'integer', 'null' ),
			'minimum'     => 1,
			'description' => 'Saved positive part number, or null when unset or invalid. The response does not estimate the position among posts the user cannot access.',
		);
	}
	/**
	 * @param lowercase-string                  $name Ability suffix.
	 * @param string                            $label Label.
	 * @param string                            $description Operation meaning.
	 * @param array<string,array<string,mixed>> $properties Input attributes.
	 * @param list<string>                      $required Required fields.
	 * @return void
	 */
	protected function register_mutation( $name, $label, $description, $properties, $required ) {
		wp_register_ability(
			'nelio-content/' . $name,
			array(
				'label'               => $label,
				'description'         => $description . ' Requires Nelio Content Premium and the configured series taxonomy. Saving requires the standard WordPress database connection, automatic commits enabled (autocommit) and core tables that support saving or undoing a group of changes together. If a database transaction is already open or the storage cannot support this, the request fails before saving. Records are locked while changes are checked, and the saved results are verified before committing the database transaction. If a step fails, database changes are undone when possible. If the connection fails or it is unclear whether changes were committed, inspect the saved state before retrying. No automatic retry occurs. Changes made by plugin hooks or external services cannot be undone by that database transaction.',
				'category'            => 'nelio-content-series',
				'input_schema'        => $this->object_schema( $properties, $required, 'Explicit series change; omitted optional attributes preserve their saved values unless creation defaults are specified.' ),
				'output_schema'       => $this->object_schema(
					array(
						'series_id'   => $this->series_id_schema(),
						'name'        => array(
							'type'        => 'string',
							'description' => 'Verified saved series name.',
						),
						'slug'        => array(
							'type'        => 'string',
							'description' => 'Verified saved series slug.',
						),
						'description' => array(
							'type'        => 'string',
							'description' => 'Saved series description as plain text, without rendering.',
						),
						'changed'     => array(
							'type'        => 'boolean',
							'description' => 'Whether this confirmed operation changed saved series fields, membership or part numbers. False means the saved values were checked and no changes were needed.',
						),
						'posts'       => array(
							'type'        => 'array',
							'maxItems'    => 100,
							'items'       => $this->object_schema(
								array(
									'post_id'     => array(
										'type'        => 'integer',
										'minimum'     => 1,
										'description' => 'Authorized post explicitly affected by this operation.',
									),
									'part_number' => $this->part_schema(),
								),
								array( 'post_id', 'part_number' ),
								'Verified post and resulting saved part; removal returns null.'
							),
							'description' => 'Affected authorized posts only. Empty for term creation or editing; no other-post identities or membership totals are returned.',
						),
					),
					array( 'series_id', 'name', 'slug', 'description', 'changed', 'posts' ),
					'Confirmed changes to the series saved on this site. Inspect the series before retrying any unconfirmed operation.'
				),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->get_premium_ability_metadata( false ),
			)
		);
	}
}
