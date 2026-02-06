<?php
/**
 * This file contains the Reference class.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/extensions
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class represents a reference in a post.
 *
 * References can be links that are included in a post's content, or
 * links that have been suggested by users in WordPress. Regardless of
 * the relationship between a link and a post, the reference will exist
 * as some information about the given link (title, author, and so on).
 */
class Nelio_Content_Reference {

	/**
	 * The reference (post) ID.
	 *
	 * @var int
	 */
	public $ID = 0;

	/**
	 * Stores post data.
	 *
	 * @var stdClass&object{ID:int, post_author:string, post_title:string, post_status:string, post_type:string, post_date:string|null}
	 */
	public $post;

	/**
	 * The URL of the reference.
	 *
	 * @var string
	 */
	private $url;

	/**
	 * The name of the reference's author.
	 *
	 * @var string
	 */
	private $author_name;

	/**
	 * The email of the reference's author.
	 *
	 * @var string
	 */
	private $author_email;

	/**
	 * The Twitter username of the reference's author.
	 *
	 * @var string
	 */
	private $author_twitter;

	/**
	 * Publication date of the reference.
	 *
	 * @var string
	 */
	private $publication_date;

	/**
	 * Whether this reference has to be considered a suggestion (for a certain post) or not.
	 *
	 * @var bool
	 */
	private $is_suggestion;

	/**
	 * Assuming someone suggested this reference, the name of the user who
	 * suggested it.
	 *
	 * @var int
	 */
	private $suggestion_advisor;

	/**
	 * Assuming someone suggested this reference, the date in which the
	 * suggestion was made.
	 *
	 * @var int
	 */
	private $suggestion_date;

	/**
	 * Creates a new instance of this class.
	 *
	 * @param integer|Nelio_Content_Reference|WP_Post $reference Optional. The
	 *                 identifier of a reference in the database, or a WP_Post
	 *                 instance that contains said reference. If no value is
	 *                 given, a reference object that has no counterpart in the
	 *                 database will be created.
	 *
	 * @since  1.0.0
	 */
	public function __construct( $reference = 0 ) {

		if ( $reference instanceof Nelio_Content_Reference ) {

			$this->ID   = absint( $reference->ID );
			$this->post = $reference->post;

		} else {
			$post = $reference instanceof WP_Post ? $reference : get_post( absint( $reference ) );

			$this->ID   = ! empty( $post ) ? $post->ID : 0;
			$this->post = (object) array(
				'ID'          => ! empty( $post ) ? $post->ID : 0,
				'post_author' => ! empty( $post ) ? $post->post_author : '',
				'post_title'  => ! empty( $post ) ? $post->post_title : '',
				'post_status' => ! empty( $post ) ? $post->post_status : 'nc_pending',
				'post_type'   => ! empty( $post ) ? $post->post_type : 'nc_reference',
				'post_date'   => ! empty( $post ) ? $post->post_date : null,
			);
		}

		// Initialize variables.
		$this->build();
	}

	/**
	 * Initializes the private variables.
	 *
	 * @return void
	 *
	 * @since  1.0.0
	 */
	private function build() {

		$this->is_suggestion = false;

		if ( $this->is_external() ) {

			$this->url              = get_post_meta( $this->ID, '_nc_url', true );
			$this->author_name      = get_post_meta( $this->ID, '_nc_author_name', true );
			$this->author_email     = get_post_meta( $this->ID, '_nc_author_email', true );
			$this->author_twitter   = $this->atify( get_post_meta( $this->ID, '_nc_author_twitter', true ) );
			$this->publication_date = get_post_meta( $this->ID, '_nc_publication_date', true );

		} else {

			$url       = get_permalink( $this->ID );
			$this->url = is_string( $url ) ? $url : '';

			$this->author_name    = get_the_author_meta( 'display_name', absint( $this->post->post_author ) );
			$this->author_email   = get_the_author_meta( 'email', absint( $this->post->post_author ) );
			$this->author_twitter = '';

			if ( ! empty( $this->post->post_date ) ) {
				$publication_date       = mysql2date( 'Y-m-d', $this->post->post_date );
				$this->publication_date = is_string( $publication_date ) ? $publication_date : '';
			}
		}
	}

	/**
	 * Whether this reference is external (points to an external page) or not
	 * (points to a post in this WordPress).
	 *
	 * @return boolean Whether this reference is external or not.
	 *
	 * @since  1.0.0
	 */
	public function is_external() {

		return 'nc_reference' === $this->post->post_type;
	}

	/**
	 * Returns the title of the reference.
	 *
	 * @return string The title of the reference.
	 *
	 * @since  1.0.0
	 */
	public function get_title() {

		return $this->post->post_title;
	}

	/**
	 * Sets the title of the reference to the given title.
	 *
	 * This function only works for external references.
	 *
	 * @param string $title the new title of the reference.
	 *
	 * @return void
	 *
	 * @since  1.0.0
	 */
	public function set_title( $title ) {
		if ( ! $this->is_external() || 0 === $this->ID ) {
			return;
		}

		$this->post->post_title = trim( $title );
		if ( ! $this->maybe_update_status() ) {
			$this->update_post();
		}
	}

	/**
	 * Returns the URL of this reference.
	 *
	 * @return string the URL of this reference.
	 *
	 * @since  1.0.0
	 */
	public function get_url() {
		return $this->url;
	}

	/**
	 * Sets the URL of this reference to the given URL.
	 *
	 * This function only works for external references.
	 *
	 * @param string $url the new URL of this reference.
	 *
	 * @return void
	 *
	 * @since  1.0.0
	 */
	public function set_url( $url ) {
		$this->url = $url;
		if ( $this->ID > 0 ) {
			update_post_meta( $this->ID, '_nc_url', $url );
		}
		$this->maybe_update_status();
	}

	/**
	 * Returns the name of the author of this reference.
	 *
	 * @return string the name of the author of this reference.
	 *
	 * @since  1.0.0
	 */
	public function get_author_name() {
		return $this->author_name;
	}

	/**
	 * Sets the name of the author to the given name.
	 *
	 * This function only works for external references.
	 *
	 * @param string $author_name the new name of the author.
	 *
	 * @return void
	 *
	 * @since  1.0.0
	 */
	public function set_author_name( $author_name ) {
		if ( $this->is_external() ) {
			$this->author_name = $author_name;
			if ( $this->ID > 0 ) {
				update_post_meta( $this->ID, '_nc_author_name', $author_name );
			}
			$this->maybe_update_status();
		}
	}

	/**
	 * Returns the email of the author of this reference.
	 *
	 * @return string the author's email.
	 *
	 * @since  1.0.0
	 */
	public function get_author_email() {

		return $this->author_email;
	}

	/**
	 * Sets the author's email to the given email.
	 *
	 * This function only works for external references.
	 *
	 * @param string $author_email the new email address.
	 *
	 * @return void
	 *
	 * @since  1.0.0
	 */
	public function set_author_email( $author_email ) {
		if ( $this->is_external() ) {
			$this->author_email = $author_email;
			if ( $this->ID > 0 ) {
				update_post_meta( $this->ID, '_nc_author_email', $author_email );
			}
			$this->maybe_update_status();
		}
	}

	/**
	 * Returns the author's Twitter username.
	 *
	 * @return string the author's Twitter username.
	 *
	 * @since  1.0.0
	 */
	public function get_author_twitter() {
		return $this->author_twitter;
	}

	/**
	 * Sets the author's Twitter to the given username.
	 *
	 * This function only works for external references.
	 *
	 * @param string $author_twitter the new author's Twitter username.
	 *
	 * @return void
	 *
	 * @since  1.0.0
	 */
	public function set_author_twitter( $author_twitter ) {
		if ( ! $this->is_external() ) {
			return;
		}

		$this->author_twitter = $this->atify( $author_twitter );
		if ( $this->ID > 0 ) {
			update_post_meta( $this->ID, '_nc_author_twitter', $author_twitter );
		}
		$this->maybe_update_status();
	}

	/**
	 * Returns the publication date of this reference.
	 *
	 * @return string The publication date following the format YYYY-MM-DD.
	 *
	 * @since  1.0.0
	 */
	public function get_publication_date() {
		return $this->publication_date;
	}

	/**
	 * Sets the publication date of this reference to the given date.
	 *
	 * This function only works for external references.
	 *
	 * @param string $publication_date the new publication date.
	 *
	 * @return void
	 *
	 * @since  1.0.0
	 */
	public function set_publication_date( $publication_date ) {
		if ( $this->is_external() ) {
			$this->publication_date = $publication_date;
			if ( $this->ID > 0 ) {
				update_post_meta( $this->ID, '_nc_publication_date', $publication_date );
			}
			$this->maybe_update_status();
		}
	}

	/**
	 * Returns the status of this reference.
	 *
	 * (See Reference status in the Register class).
	 *
	 * @return 'pending'|'improvable'|'complete'|'broken'|'check' The status of this reference. If the reference is internal, its status is always `complete`.
	 *
	 * @since  1.0.0
	 */
	public function get_status() {
		if ( $this->is_external() ) {
			$status = str_replace( 'nc_', '', $this->post->post_status );
			switch ( $status ) {
				case 'pending':
				case 'improvable':
				case 'complete':
				case 'broken':
				case 'check':
					return $status;
				default:
					return 'pending';
			}
		} else {
			return 'complete';
		}
	}

	/**
	 * Marks this concrete instance of a reference as suggested by someone on
	 * some date.
	 *
	 * @param integer $advisor ID of the user who suggested this reference.
	 * @param integer $date    UNIX timestamp in which the suggestion was made.
	 *
	 * @return void
	 *
	 * @since  1.0.0
	 */
	public function mark_as_suggested( $advisor, $date ) {
		$this->is_suggestion      = true;
		$this->suggestion_advisor = $advisor;
		$this->suggestion_date    = $date;
	}

	/**
	 * This function updates the status of this reference based on the amount of
	 * information it contains. Thus, for example, a reference is complete if all
	 * data is properly set, improvable if some data is missing, or pending if
	 * noone has ever set any field of this reference.
	 *
	 * @return boolean Whether the status has been updated to a new value or not.
	 *
	 * @since  1.0.0
	 */
	private function maybe_update_status() {
		// Only external references may have a status different than "complete".
		if ( ! $this->is_external() ) {
			return false;
		}

		// If the post status is "broken", we shouldn't update it.
		$status = $this->get_status();
		if ( 'broken' === $status || 'check' === $status ) {
			return false;
		}

		// Finally, we simply need to set the reference to "complete" or "improvable".
		$values = array(
			$this->get_title(),
			$this->get_url(),
			$this->get_author_name(),
			$this->get_author_email(),
			$this->get_author_twitter(),
			$this->get_publication_date(),
		);

		// If one of the values is empty, the reference is "improvable".
		if ( in_array( '', $values, true ) ) {
			$new_status = 'improvable';
		} else {
			$new_status = 'complete';
		}

		$old_status = $this->get_status();
		if ( $new_status === $old_status ) {
			return false;
		}

		$this->post->post_status = "nc_{$new_status}";
		if ( 0 === $this->ID ) {
			return false;
		}

		$this->update_post();
		return true;
	}

	/**
	 * Returns a JSON encoded version of this reference.
	 *
	 * @return TEditorial_Reference
	 *
	 * @since  1.0.0
	 */
	public function json_encode() {

		$result = array(
			'id'           => $this->ID,
			'author'       => $this->get_author_name(),
			'date'         => $this->get_publication_date(),
			'email'        => $this->get_author_email(),
			'isExternal'   => $this->is_external(),
			'isSuggestion' => $this->is_suggestion,
			'status'       => $this->get_status(),
			'title'        => $this->get_title(),
			'twitter'      => $this->get_author_twitter(),
			'url'          => $this->get_url(),
		);

		if ( $this->is_suggestion ) {
			$result['suggestionAdvisorId'] = $this->suggestion_advisor;
			$result['suggestionDate']      = $this->suggestion_date . 'T00:00:00';
		}

		return $result;
	}

	/**
	 * Adds at symbol to the value.
	 *
	 * @param string $value Value.
	 *
	 * @return string
	 */
	private function atify( $value ) {
		if ( mb_strlen( $value ) && '@' !== mb_substr( $value, 0, 1 ) ) {
			$value = '@' . $value;
		}
		return $value;
	}

	/**
	 * Updates post.
	 *
	 * @return void
	 */
	private function update_post() {
		wp_update_post(
			array(
				'ID'          => $this->post->ID,
				'post_author' => absint( $this->post->post_author ),
				'post_title'  => $this->post->post_title,
				'post_status' => $this->post->post_status,
				'post_type'   => $this->post->post_type,
			)
		);
	}
}
