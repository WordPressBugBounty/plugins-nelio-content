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

	// @Implements
	public function display() {
		printf( '<div id="%s"></div>', esc_attr( $this->get_field_id() ) );

		$settings = array(
			'component'  => $this->component,
			'id'         => $this->get_field_id(),
			'name'       => $this->option_name . '[' . $this->name . ']',
			'value'      => $this->value,
			'attributes' => $this->get_field_attributes(),
		);

		printf(
			'<script type="text/javascript">NelioContent.initField( %s, %s )</script>',
			wp_json_encode( $this->get_field_id() ),
			wp_json_encode( $settings )
		);
	}

	/**
	 * Gets field ID.
	 *
	 * @return string
	 */
	private function get_field_id() {
		return $this->option_name . '_' . str_replace( '_', '-', $this->name );
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
