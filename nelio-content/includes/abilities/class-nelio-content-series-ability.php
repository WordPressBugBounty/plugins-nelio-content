<?php
/** Shared series contract schemas. */
defined( 'ABSPATH' ) || exit;

/**
 * Defines identity inputs shared by series detail and membership queries.
 *
 * @extends Nelio_Content_Premium_Ability<TAbility_Input>
 */
abstract class Nelio_Content_Series_Ability extends Nelio_Content_Premium_Ability {
	/** @return array<string,mixed> */
	protected function series_id_schema() {
		return array(
			'type'        => 'integer',
			'minimum'     => 1,
			'description' => 'Existing series term ID in the site’s configured series taxonomy.',
		);
	}
}
