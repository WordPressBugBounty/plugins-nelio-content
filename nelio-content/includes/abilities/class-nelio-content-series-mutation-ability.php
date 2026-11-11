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
				'description' => _x( 'Plain series name, trimmed, at most 200 Unicode characters. Required on creation; omit on update to preserve it.', 'text', 'nelio-content' ),
			),
			'description' => array(
				'type'        => 'string',
				'maxLength'   => 10000,
				'description' => _x( 'Plain series description, at most 10000 Unicode characters. Omit to preserve on update or default to empty on creation; an empty string clears it.', 'text', 'nelio-content' ),
			),
			'slug'        => array(
				'type'        => 'string',
				'minLength'   => 1,
				'maxLength'   => 200,
				'pattern'     => '^[a-z0-9]+(?:-[a-z0-9]+)*$',
				'description' => _x( 'Optional lowercase ASCII slug, using letters, numbers and single separating hyphens. Creation derives a slug from the name when omitted; update preserves it. Conflicts fail rather than silently suffixing or reusing another series.', 'text', 'nelio-content' ),
			),
		);
	}
	/** @return array<string,mixed> */
	protected function part_schema() {
		return array(
			'type'        => array( 'integer', 'null' ),
			'minimum'     => 1,
			'description' => _x( 'Saved positive part number, or null when unset or invalid. No position among hidden posts is inferred.', 'text', 'nelio-content' ),
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
				'description'         => $description . ' ' . _x( 'Requires Nelio Content Premium and the configured series taxonomy. Writes require the standard WordPress database connection, autocommit and transactional core tables; existing transactions and unsupported storage fail before writes. Saves are checked under database locks and confirmed before commit. Failures roll back database changes when possible; commit/connection uncertainty must be inspected before retrying. No automatic retry occurs. Plugin hooks and external side effects are outside the database transaction.', 'text', 'nelio-content' ),
				'category'            => 'nelio-content-series',
				'input_schema'        => $this->object_schema( $properties, $required, _x( 'Explicit series change; omitted optional attributes preserve their saved values unless creation defaults are specified.', 'text', 'nelio-content' ) ),
				'output_schema'       => $this->object_schema(
					array(
						'series_id'   => $this->series_id_schema(),
						'name'        => array(
							'type'        => 'string',
							'description' => _x( 'Verified saved series name.', 'text', 'nelio-content' ),
						),
						'slug'        => array(
							'type'        => 'string',
							'description' => _x( 'Verified saved series slug.', 'text', 'nelio-content' ),
						),
						'description' => array(
							'type'        => 'string',
							'description' => _x( 'Saved series description as plain text, without rendering.', 'text', 'nelio-content' ),
						),
						'changed'     => array(
							'type'        => 'boolean',
							'description' => _x( 'Whether this confirmed operation changed saved series fields, membership or part numbers. False means a verified no-op.', 'text', 'nelio-content' ),
						),
						'posts'       => array(
							'type'        => 'array',
							'maxItems'    => 100,
							'items'       => $this->object_schema(
								array(
									'post_id'     => array(
										'type'        => 'integer',
										'minimum'     => 1,
										'description' => _x( 'Authorized post explicitly affected by this operation.', 'text', 'nelio-content' ),
									),
									'part_number' => $this->part_schema(),
								),
								array( 'post_id', 'part_number' ),
								_x( 'Verified post and resulting saved part; removal returns null.', 'text', 'nelio-content' )
							),
							'description' => _x( 'Affected authorized posts only. Empty for term creation or editing; no other-post identities or membership totals are returned.', 'text', 'nelio-content' ),
						),
					),
					array( 'series_id', 'name', 'slug', 'description', 'changed', 'posts' ),
					_x( 'Verified local series mutation. Inspect the series before retrying any unconfirmed operation.', 'text', 'nelio-content' )
				),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->get_premium_ability_metadata( false ),
			)
		);
	}
}
