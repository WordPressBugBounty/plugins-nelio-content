<?php
/**
 * This file contains the task preset class.
 *
 * @since 3.2.0
 */

defined( 'ABSPATH' ) || exit;

use Nelio_Content\Zod\Schema;
use Nelio_Content\Zod\Zod as Z;

class Nelio_Content_Task_Preset {

	/**
	 * The task preset (post) ID.
	 *
	 * @var int
	 */
	public $ID = 0;

	/**
	 * Task preset name.
	 *
	 * @var string
	 */
	private $name = '';

	/**
	 * Tasks in this preset.
	 *
	 * @var list<TTask_Template>
	 */
	private $tasks = array();

	/**
	 * Maximum number of task presets.
	 *
	 * @var int
	 */
	public const MAX_PRESETS = 50;

	/**
	 * Creates a new instance of this class.
	 *
	 * @param integer|Nelio_Content_Task_Preset|WP_Post $preset Optional. The
	 *                 identifier of a task prset in the database, or a WP_Post
	 *                 instance that contains said preset. If no value is
	 *                 given, a task preset object that has no counterpart in the
	 *                 database will be created.
	 *
	 * @since 3.2.0
	 */
	public function __construct( $preset = 0 ) {
		$preset = $preset instanceof Nelio_Content_Task_Preset ? $preset->ID : $preset;
		$preset = $preset instanceof WP_Post ? $preset->ID : $preset;
		$preset = absint( $preset );
		$preset = get_post( $preset );
		$preset = $preset instanceof WP_Post ? $preset : false;

		if ( ! empty( $preset ) ) {
			$this->ID    = $preset->ID;
			$this->name  = $preset->post_title;
			$this->tasks = array();

			$content     = json_decode( base64_decode( $preset->post_content ) );
			$parsed_data = Z::array( self::task_schema() )->safe_parse( $content );
			if ( true === $parsed_data['success'] ) {
				/** @var list<TTask_Template> */
				$parsed_data = $parsed_data['data'];
				$this->tasks = $parsed_data;
			}
		}
	}

	/**
	 * Parses the given JSON and converts it into an instance of this class.
	 *
	 * @param string|array<mixed> $json Task preset as JSON.
	 *
	 * @return Nelio_Content_Task_Preset|WP_Error an instance of this class or error.
	 *
	 * @since 3.2.0
	 */
	public static function parse( $json ) {
		$json = is_string( $json ) ? json_decode( $json, true ) : $json;
		$json = is_array( $json ) ? $json : array();

		$parsed = self::schema()->safe_parse( $json );
		if ( false === $parsed['success'] ) {
			return new WP_Error( 'parsing-error', $parsed['error'] );
		}

		/** @var TTask_Preset $parsed */
		$parsed = $parsed['data'];
		if ( 0 < $parsed['id'] && 'nc_task_preset' !== get_post_type( $parsed['id'] ) ) {
			return new WP_Error( 'invalid-id', sprintf( 'Post %d is not a Task Preset', $parsed['id'] ) );
		}

		$result        = new self();
		$result->ID    = $parsed['id'] < 0 ? 0 : $parsed['id'];
		$result->name  = $parsed['name'];
		$result->tasks = $parsed['tasks'];
		return $result;
	}

	/**
	 * Saves the task preset to the database.
	 *
	 * @return Nelio_Content_Task_Preset|WP_Error the task preset ID or an error if something went wrong.
	 *
	 * @since 3.2.0
	 */
	public function save() {
		$tasks = wp_json_encode( $this->tasks );
		if ( false === $tasks ) {
			return new WP_Error( 'encoding-error', _x( 'Encoding error', 'text', 'nelio-content' ) );
		}

		$args = array(
			'post_title'   => $this->name,
			'post_content' => base64_encode( $tasks ),
			'post_type'    => 'nc_task_preset',
			'post_status'  => 'draft',
		);

		$result = empty( $this->ID )
			? wp_insert_post( $args, true )
			: wp_update_post( array_merge( $args, array( 'ID' => $this->ID ) ), true );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$this->ID = $result;
		return $this;
	}

	/**
	 * Converts this class into JSON.
	 *
	 * @return TTask_Preset this class into JSON.
	 *
	 * @since 3.2.0
	 */
	public function json() {
		return array(
			'id'    => $this->ID,
			'name'  => $this->name,
			'tasks' => $this->tasks,
		);
	}

	/**
	 * Returns the schema.
	 *
	 * @return Nelio_Content\Zod\Schema
	 */
	public static function schema(): Schema {
		/** @var Nelio_Content\Zod\Schema|null $schema */
		static $schema;
		if ( empty( $schema ) ) {
			$schema = Z::object(
				array(
					'id'    => Z::number(),
					'name'  => Z::string()->trim()->min( 1 ),
					'tasks' => Z::array( self::task_schema() )->min( 1 )->max( self::MAX_PRESETS ),
				)
			);
		}
		return $schema;
	}

	/**
	 * Returns the task schema.
	 *
	 * @return Nelio_Content\Zod\Schema
	 */
	private static function task_schema() {
		/** @var Nelio_Content\Zod\Schema|null $task_schema */
		static $task_schema;
		if ( empty( $task_schema ) ) {
			$task_schema = Z::object(
				array(
					'assigneeId' => Z::number()->optional(),
					'color'      => Z::enum(
						array(
							'none',
							'red',
							'orange',
							'yellow',
							'green',
							'cyan',
							'blue',
							'purple',
						)
					),
					'dateType'   => Z::enum(
						array(
							'predefined-offset',
							'positive-days',
							'negative-days',
						)
					),
					'dateValue'  => Z::string()->regex( '/-?[0-9]+/' ),
					'task'       => Z::string()->trim()->min( 1 ),
				)
			);
		}
		return $task_schema;
	}
}
