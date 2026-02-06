<?php
/**
 * This file contains the Input Setting class.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/lib/settings
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class represents an input setting.
 *
 * There are different type of inputs:
 *  * Text,
 *  * Email,
 *  * Number, and
 *  * Password.
 *
 * Depending on the specific type used, the user interface may vary a
 * little bit and will only accept a certain type of data. Moreover,
 * the specific type also modifies the sanitization function.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/lib/settings
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      1.0.0
 */
class Nelio_Content_Input_Setting extends Nelio_Content_Abstract_Setting {

	/**
	 * The specific type of this HTML input element.
	 *
	 * @since  1.0.0
	 * @var    string
	 */
	protected $type;

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
	 * @param string $type        The specific type of this input.
	 *                            It can be `text`, `email`, `number`, and `password`.
	 * @param string $placeholder A placeholder text to be displayed when the field is empty.
	 *
	 * @since  1.0.0
	 */
	public function __construct( $name, $desc, $more, $type, $placeholder = '' ) {
		parent::__construct( $name, $desc, $more );
		$this->type        = $type;
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
		$type        = $this->type;
		$placeholder = $this->placeholder;
		include nelio_content()->plugin_path . '/includes/lib/settings/partials/nelio-content-input-setting.php';
	}

	// @Implements
	public function sanitize( $input ) {

		if ( ! isset( $input[ $this->name ] ) ) {
			$input[ $this->name ] = $this->value;
		}

		$value = is_string( $input[ $this->name ] ) ? $input[ $this->name ] : $this->value;
		switch ( $this->type ) {
			case 'text':
				$value = $this->sanitize_text( $value );
				break;
			case 'password':
				$value = $this->sanitize_password( $value );
				break;
			case 'email':
				$value = sanitize_email( $value );
				break;
			case 'number':
				$value = $this->sanitize_number( $value );
				break;
		}

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
		return sanitize_text_field( wp_unslash( $value ) );
	}

	/**
	 * This function checks that the password is strong enough and sanitizes the value.
	 *
	 * @param string $value The current value that has to be sanitized.
	 *
	 * @return string The input text properly sanitized.
	 *
	 * @since  1.0.0
	 */
	private function sanitize_password( $value ) {
		return $this->sanitize_text( $value );
	}

	/**
	 * This function checks that the input value is a number and converts it to an actual integer.
	 *
	 * @param string $value The current value that has to be sanitized.
	 *
	 * @return int The input text converted into a number.
	 *
	 * @since  1.0.0
	 */
	private function sanitize_number( $value ) {
		return intval( $value );
	}
}
