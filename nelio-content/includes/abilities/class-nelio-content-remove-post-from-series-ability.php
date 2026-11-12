<?php
/** Premium contract for remove-post-from-series. */
defined( 'ABSPATH' ) || exit;

/** Exposes an explicit series mutation in the shared catalog. */
class Nelio_Content_Remove_Post_From_Series_Ability extends Nelio_Content_Series_Mutation_Ability {
	/** @return void */
	public function register() {
		$properties = array(
			'series_id' => $this->series_id_schema(),
			'post_id'   => $this->query_properties()['post_id'],
		);
		$this->register_mutation( 'remove-post-from-series', 'Remove post from series', 'Remove only the selected series and its saved part from one readable/editable enabled post with taxonomy assignment permission. Other series and their parts remain unchanged. Repetition does not make changes once membership and part metadata are absent.', $properties, array( 'series_id', 'post_id' ) );
	}
	/** @return string */
	protected function get_premium_ability_name() {
		return 'nelio-content/remove-post-from-series';
	}
}
