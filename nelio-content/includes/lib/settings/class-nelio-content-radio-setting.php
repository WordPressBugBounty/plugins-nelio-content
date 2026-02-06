<?php
/**
 * This file contains the Radio Setting class.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/lib/settings
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class represents a Radio setting.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/lib/settings
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      1.0.0
 */
class Nelio_Content_Radio_Setting extends Nelio_Content_Abstract_Setting {

	/**
	 * The currently-selected value of this radio.
	 *
	 * @since  1.0.0
	 * @var    string
	 */
	protected $value;

	/**
	 * The list of options.
	 *
	 * @since  1.0.0
	 * @var    list<array{value:string,label:string,desc?:string}>
	 */
	protected $options;

	/**
	 * Creates a new instance of this class.
	 *
	 * @param string                                              $name    The name that identifies this setting.
	 * @param string                                              $desc    A text that describes this field.
	 * @param string                                              $more    A link pointing to more information about this field.
	 * @param list<array{value:string,label:string,desc?:string}> $options The list of options.
	 *
	 * @since  1.0.0
	 */
	public function __construct( $name, $desc, $more, $options ) {

		parent::__construct( $name, $desc, $more );
		$this->options = $options;
	}

	/**
	 * Specifies which option is selected.
	 *
	 * @param string $value The currently-selected value of this radio.
	 *
	 * @since  1.0.0
	 */
	public function set_value( $value ) {
		$this->value = $value;
	}

	// @Implements
	public function display() {

		// Preparing data for the partial.
		$id      = str_replace( '_', '-', $this->name );
		$name    = $this->option_name . '[' . $this->name . ']';
		$value   = $this->value;
		$options = $this->options;
		$desc    = $this->desc;
		$more    = $this->more;
		include nelio_content()->plugin_path . '/includes/lib/settings/partials/nelio-content-radio-setting.php';
	}

	// @Implements
	public function sanitize( $input ) {
		if ( ! isset( $input[ $this->name ] ) ) {
			$input[ $this->name ] = $this->value;
		}
		$is_value_correct = false;
		foreach ( $this->options as $option ) {
			if ( $option['value'] === $input[ $this->name ] ) {
				$is_value_correct = true;
			}
		}
		if ( ! $is_value_correct ) {
			$input[ $this->name ] = $this->value;
		}
		return $input;
	}
}
