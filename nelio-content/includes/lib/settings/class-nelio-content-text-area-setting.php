<?php
/**
 * This file contains the Text Area Setting class.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/lib/settings
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class represents a text area setting.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/lib/settings
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      1.0.0
 */
class Nelio_Content_Text_Area_Setting extends Nelio_Content_Abstract_Setting {

	/**
	 * The concrete value of this field.
	 *
	 * @since  1.0.0
	 * @var    string
	 */
	protected $value;

	/**
	 * A placeholder text to be displayed when the field is empty.
	 *
	 * @since  1.0.0
	 * @var    string
	 */
	protected $placeholder;

	/**
	 * Creates a new instance of this class.
	 *
	 * @param string $name        The name that identifies this setting.
	 * @param string $desc        A text that describes this field.
	 * @param string $more        A link pointing to more information about this field.
	 * @param string $placeholder A placeholder text to be displayed when the field is empty.
	 *
	 * @since  1.0.0
	 */
	public function __construct( $name, $desc, $more, $placeholder = '' ) {
		parent::__construct( $name, $desc, $more );
		$this->placeholder = $placeholder;
	}

	/**
	 * Sets the value of this field to the given string.
	 *
	 * @param string $value The value of this field.
	 *
	 * @since  1.0.0
	 */
	public function set_value( $value ) {
		$this->value = $value;
	}

	// @Implements
	/** . @SuppressWarnings( PHPMD.UnusedLocalVariable, PHPMD.ShortVariableName ) */
	public function display() {

		// Preparing data for the partial.
		$id          = str_replace( '_', '-', $this->name );
		$name        = $this->option_name . '[' . $this->name . ']';
		$desc        = $this->desc;
		$more        = $this->more;
		$value       = $this->value;
		$placeholder = $this->placeholder;
		include nelio_content()->plugin_path . '/includes/lib/settings/partials/nelio-content-textarea-setting.php';
	}

	// @Implements
	public function sanitize( $input ) {

		if ( ! isset( $input[ $this->name ] ) ) {
			$input[ $this->name ] = $this->value;
		}

		$value                = is_string( $input[ $this->name ] ) ? $input[ $this->name ] : $this->value;
		$value                = $this->sanitize_text( $value );
		$input[ $this->name ] = $value;

		return $input;
	}

	/**
	 * This function sanitizes the input value.
	 *
	 * @param string $value The current value that has to be sanitized.
	 *
	 * @return string The input text properly sanitized.
	 *
	 * @see    sanitize_text_field
	 * @since  1.0.0
	 */
	private function sanitize_text( $value ) {
		return sanitize_textarea_field( wp_unslash( $value ) );
	}
}
