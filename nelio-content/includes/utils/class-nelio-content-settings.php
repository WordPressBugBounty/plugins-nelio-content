<?php
/**
 * This file has the Settings class, which defines and registers Nelio Content's Settings.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/utils
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}//end if

/**
 * The Settings class, responsible of defining, registering, and providing access to all Nelio Content's settings.
 */
class Nelio_Content_Settings extends Nelio_Content_Abstract_Settings {

	private static $instance;

	/**
	 * Initialize the class, set its properties, and add the proper hooks.
	 *
	 * @since  1.0.0
	 * @access protected
	 */
	protected function __construct() {

		parent::__construct( 'nelio-content', 'nelio-content-settings' );

	}//end __construct()

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_Content_Settings the single instance of this class.
	 *
	 * @since  1.0.0
	 * @access public
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}//end if
		return self::$instance;

	}//end instance()

	/** . @Implements */
	public function set_tabs() { // phpcs:ignore

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
						'fields' => include nelio_content()->plugin_path . '/includes/data/social-settings.php',
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
						'fields' => include nelio_content()->plugin_path . '/includes/data/content-settings.php',
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
						'fields' => include nelio_content()->plugin_path . '/includes/data/tools-settings.php',
					),
					array(
						'name'   => 'task-presets',
						'label'  => _x( 'Task Presets', 'text', 'nelio-content' ),
						'custom' => true,
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
						'fields' => include nelio_content()->plugin_path . '/includes/data/others-settings.php',
					),
				),
			),

		);

		$this->do_set_tabs( $tabs );

	}//end set_tabs()

}//end class
