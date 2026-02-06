<?php
/**
 * This file has the Settings class, which defines and registers Nelio Content's Settings.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/utils
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The Settings class, responsible of defining, registering, and providing access to all Nelio Content's settings.
 */
class Nelio_Content_Settings extends Nelio_Content_Abstract_Settings {

	/**
	 * This instance.
	 *
	 * @var Nelio_Content_Settings|null
	 */
	private static $instance;

	/**
	 * Initialize the class, set its properties, and add the proper hooks.
	 *
	 * @since  1.0.0
	 */
	protected function __construct() {

		parent::__construct( 'nelio-content', 'nelio-content-settings' );
	}

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_Content_Settings
	 *
	 * @since  1.0.0
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/** . @Implements */
	public function set_tabs() {

		// Add as many tabs as you want. If you have one tab only, no tabs will be shown at all.
		$tabs = array(

			array(
				'name'  => 'social',
				'label' => _x( 'Social Media', 'text', 'nelio-content' ),
				'pages' => array(
					array(
						'name'   => 'profiles',
						'label'  => _x( 'Profiles', 'text', 'nelio-content' ),
						'custom' => true,
					),
					array(
						'name'   => 'automations',
						'label'  => _x( 'Automations', 'text', 'nelio-content' ),
						'custom' => true,
					),
					array(
						'name'   => 'advanced',
						'label'  => _x( 'Advanced', 'text', 'nelio-content' ),
						'fields' => $this->get_fields( 'social-settings' ),
					),
				),
			),

			array(
				'name'  => 'content',
				'label' => _x( 'Content', 'text', 'nelio-content' ),
				'pages' => array(
					array(
						'name'   => 'basic',
						'label'  => _x( 'Basic', 'text', 'nelio-content' ),
						'fields' => $this->get_fields( 'content-settings' ),
					),
				),
			),

			array(
				'name'  => 'tools',
				'label' => _x( 'Editorial Tools', 'text', 'nelio-content' ),
				'pages' => array(
					array(
						'name'   => 'basic',
						'label'  => _x( 'Basic', 'text', 'nelio-content' ),
						'fields' => $this->get_fields( 'tools-settings' ),
					),
					array(
						'name'   => 'custom-statuses',
						'label'  => _x( 'Custom Statuses', 'text', 'nelio-content' ),
						'custom' => true,
					),
					array(
						'name'   => 'task-presets',
						'label'  => _x( 'Task Presets', 'text', 'nelio-content' ),
						'custom' => true,
					),
					array(
						'name'   => 'series',
						'label'  => _x( 'Series', 'text', 'nelio-content' ),
						'fields' => $this->get_fields( 'series-settings' ),
					),
				),
			),

			array(
				'name'  => 'others',
				'label' => _x( 'Extra', 'text', 'nelio-content' ),
				'pages' => array(
					array(
						'name'   => 'basic',
						'label'  => _x( 'Basic', 'text', 'nelio-content' ),
						'fields' => $this->get_fields( 'others-settings' ),
					),
				),
			),

		);

		$this->do_set_tabs( $tabs );
	}

	/**
	 * Gets list of fields.
	 *
	 * @param string $name Name.
	 *
	 * @return list<TSettings_Section | TSettings_Field>
	 */
	private function get_fields( $name ) {
		/** @var list<TSettings_Section | TSettings_Field> */
		return include nelio_content()->plugin_path . "/includes/data/{$name}.php";
	}
}
