<?php
/**
 * This file defines a helper class to add react-based components in our settings screen.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/admin/settings
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      2.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Helper class to add react-based components.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/admin/settings
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      2.0.0
 */
abstract class Nelio_Content_Abstract_React_Setting extends Nelio_Content_Abstract_Setting {

	/**
	 * Value.
	 *
	 * @var mixed
	 */
	protected $value;

	/**
	 * JS component.
	 *
	 * @var string
	 */
	protected $component;

	/**
	 * Creates a new instance of this class.
	 *
	 * @param string $name      Field name.
	 * @param string $component JS component.
	 *
	 * @return void
	 */
	public function __construct( $name, $component ) {
		parent::__construct( $name );
		$this->component = $component;
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Sets value.
	 *
	 * @param mixed $value Value.
	 *
	 * @return void
	 */
	public function set_value( $value ) {
		$this->value = $value;
	}

	/**
	 * Callback to enqueue assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {

		$screen = get_current_screen();
		if ( empty( $screen ) || 'nelio-content_page_nelio-content-settings' !== $screen->id ) {
			return;
		}

		$settings = array(
			'component'  => $this->component,
			'id'         => $this->get_field_id(),
			'name'       => $this->option_name . '[' . $this->name . ']',
			'value'      => $this->value,
			'attributes' => $this->get_field_attributes(),
		);

		wp_enqueue_style(
			'nelio-content-settings-page',
			nelio_content()->plugin_url . '/assets/dist/css/settings-page.css',
			array( 'nelio-content-components', 'nelio-content-social-profiles-manager' ),
			nelio_content_get_script_version( 'settings-page' )
		);
		nelio_content_enqueue_script_with_auto_deps( 'nelio-content-settings-page', 'settings-page', true );

		wp_add_inline_script(
			'nelio-content-settings-page',
			sprintf(
				'NelioContent.initField( %s, %s );',
				wp_json_encode( $this->get_field_id() ),
				wp_json_encode( $settings )
			)
		);
	}

	// @Implements
	public function display() {
		printf( '<div id="%s"></div>', esc_attr( $this->get_field_id() ) );
	}

	/**
	 * Gets field ID.
	 *
	 * @return string
	 */
	private function get_field_id() {
		return str_replace( '_', '-', $this->name );
	}

	/**
	 * Gets field attributes.
	 *
	 * @return array<string,mixed>
	 */
	protected function get_field_attributes() {
		return array();
	}
}
