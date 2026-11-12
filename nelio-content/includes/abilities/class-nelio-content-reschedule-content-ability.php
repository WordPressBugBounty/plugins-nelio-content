<?php
/** Date-only publication changes through the existing calendar save operation. */
defined( 'ABSPATH' ) || exit;

/**
 * Changes the saved post date; related-item scheduling belongs to normal Cloud sync.
 *
 * @extends Nelio_Content_Ability<TAbility_Input>
 */
class Nelio_Content_Reschedule_Content_Ability extends Nelio_Content_Ability {

	/** @return void */
	public function register() {
		$metadata                = $this->get_ability_metadata( false );
		$metadata['annotations'] = array(
			'readonly'    => false,
			'destructive' => true,
			'idempotent'  => false,
		);
		wp_register_ability(
			'nelio-content/reschedule-content',
			array(
				'label'               => _x( 'Reschedule content', 'command', 'nelio-content' ),
				'description'         => 'Change the publication date of one saved WordPress post enabled in the editorial calendar. Available in Free and Premium. Requires post editing permission and publishing permission for published or scheduled posts. Supply an exact timestamp with a UTC offset; it is converted to the site timezone. Only the date is edited. WordPress may publish a scheduled post moved to the past, or return a published post to scheduled status when moved to the future; drafts remain drafts. Normal post saving notifies Cloud, which handles related messages and tasks. The response confirms the saved post date, not completion of related-item scheduling. An unchanged date does not make changes. After an unconfirmed save, inspect the post before retrying.',
				'category'            => 'nelio-content-calendar',
				'input_schema'        => $this->object_schema(
					array(
						'post_id'          => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => 'Existing post enabled in the editorial calendar that the current user may edit. Trash, revisions and auto-drafts are unsupported.',
						),
						'publication_date' => array(
							'type'        => 'string',
							'format'      => 'date-time',
							'maxLength'   => 25,
							'pattern'     => '^\\d{4}-\\d{2}-\\d{2}T\\d{2}:\\d{2}:\\d{2}(Z|[+-]\\d{2}:\\d{2})$',
							'description' => 'Target publication timestamp with seconds and explicit UTC offset, for example 2030-06-15T10:00:00+02:00 or 2030-06-15T08:00:00Z. Years 1000 through 9999 are supported. Fractional seconds, relative dates and timestamps without an offset are unsupported. This changes the saved date and does not promote a draft to scheduled status.',
						),
					),
					array( 'post_id', 'publication_date' ),
					'Saved post identity and an unambiguous target publication timestamp.'
				),
				'output_schema'       => $this->object_schema(
					array(
						'post_id'                   => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => 'ID of the post whose saved publication date was verified.',
						),
						'publication_date'          => array(
							'type'        => 'string',
							'format'      => 'date-time',
							'description' => 'Verified publication timestamp expressed in the site timezone, including its UTC offset.',
						),
						'previous_publication_date' => array(
							'type'        => 'string',
							'description' => 'Previous publication timestamp in the site timezone, or empty if the post had no publication date.',
						),
						'status'                    => array(
							'type'        => 'string',
							'description' => 'Actual WordPress status after saving. It reflects normal publication or scheduling transitions caused by changing the date.',
						),
						'timezone'                  => array(
							'type'        => 'string',
							'description' => 'WordPress site timezone used to express the saved dates.',
						),
						'changed'                   => array(
							'type'        => 'boolean',
							'description' => 'True if the saved publication date changed. False means the requested date was already saved and no update was needed.',
						),
					),
					array( 'post_id', 'publication_date', 'previous_publication_date', 'status', 'timezone', 'changed' ),
					'Verified local publication date and post status. Related social message and task scheduling is handled separately by normal synchronization and is not confirmed by this result.'
				),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $metadata,
			)
		);
	}

	/**
	 * @param TAbility_Input $input Input.
	 * @return bool
	 * @phpstan-impure
	 */
	public function permissions( $input ) {
		if ( ! $this->can_use_plugin( $input ) ) {
			return false;
		}
		$post = isset( $input['post_id'] ) ? get_post( $input['post_id'] ) : null;
		if ( ! $post || ! in_array( $post->post_type, nelio_content_get_post_types( 'calendar' ), true ) || ! current_user_can( 'edit_post', $post->ID ) ) {
			return false;
		}
		$type        = get_post_type_object( $post->post_type );
		$publish_cap = $type ? ( $type->cap->publish_posts ?? null ) : null;
		return $type && ( ! in_array( $post->post_status, array( 'publish', 'future' ), true ) || ( is_string( $publish_cap ) && current_user_can( $publish_cap ) ) );
	}

	/**
	 * @param TAbility_Input $input Input.
	 * @return array<string,mixed>|WP_Error
	 */
	public function execute( $input ) {
		if ( ! $this->permissions( $input ) ) {
			return new WP_Error( 'nelio_content_permission_denied', 'You cannot change the publication date of this post.' );
		}
		$value = $input['publication_date'] ?? '';
		if ( ! preg_match( '/^[1-9]\d{3}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:Z|[+-](?:[01]\d|2[0-3]):[0-5]\d)$/D', $value ) ) {
			return new WP_Error( 'nelio_content_invalid_date', 'Use a full publication timestamp with seconds and an explicit UTC offset.' );
		}
		try {
			$date   = new DateTimeImmutable( $value );
			$errors = DateTimeImmutable::getLastErrors();
			if ( false !== $errors && ( $errors['warning_count'] || $errors['error_count'] ) ) {
				return new WP_Error( 'nelio_content_invalid_date', 'Use a real publication date and time.' );
			}
		} catch ( Exception $e ) {
			return new WP_Error( 'nelio_content_invalid_date', 'Use a real publication date and time.' );
		}
		$post = get_post( $input['post_id'] ?? 0 );
		if ( ! $post || in_array( $post->post_status, array( 'trash', 'inherit', 'auto-draft' ), true ) ) {
			return new WP_Error( 'nelio_content_invalid_post', 'Trash, revisions and auto-drafts cannot be rescheduled.' );
		}
		$previous = $this->publication_date( $post );
		$changed  = $date->setTimezone( wp_timezone() )->format( 'Y-m-d H:i:s' ) !== $post->post_date || $date->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' ) !== $post->post_date_gmt;
		if ( ! $this->permissions( $input ) ) {
			return new WP_Error( 'nelio_content_permission_denied', 'Post editing access is no longer available.' );
		}
		try {
			$saved = Nelio_Content_Post_REST_Controller::instance()->set_post_publication_date( $post->ID, $date );
		} catch ( Throwable $e ) {
			return new WP_Error( 'nelio_content_reschedule_unconfirmed', 'Saving could not be confirmed. The date may already have changed; inspect the post before retrying.' );
		}
		if ( is_wp_error( $saved ) ) {
			return new WP_Error( 'nelio_content_reschedule_unconfirmed', 'The publication date could not be confirmed. Inspect the post before retrying.' );
		}
		return array(
			'post_id'                   => $saved->ID,
			'publication_date'          => $this->publication_date( $saved ),
			'previous_publication_date' => $previous,
			'status'                    => $saved->post_status,
			'timezone'                  => wp_timezone_string(),
			'changed'                   => $changed,
		);
	}

	/**
	 * Uses the saved UTC date to preserve the correct offset during repeated DST hours.
	 *
	 * @param WP_Post $post Post.
	 * @return string
	 */
	private function publication_date( $post ) {
		$date = get_post_datetime( $post, 'date', 'gmt' );
		return $date ? $date->format( DATE_ATOM ) : '';
	}
}
