<?php
/**
 * Abstract class that implements the `register` method of the `Nelio_Content_Setting` interface.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/lib/settings
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * A class that represents a Nelio_Content Setting.
 *
 * It only implements the `register` method, which will be common among all
 * Nelio Content Testing's settings.
 *
 * @package    Nelio_Content
 * @subpackage Nelio_Content/includes/lib/settings
 * @author     David Aguilera <david.aguilera@neliosoftware.com>
 * @since      1.0.0
 */
abstract class Nelio_Content_Abstract_Setting implements Nelio_Content_Setting {

	/**
	 * The label associated to this setting.
	 *
	 * @since  1.0.0
	 * @var    string
	 */
	protected $label;

	/**
	 * The name that identifies this setting.
	 *
	 * @since  1.0.0
	 * @var    string
	 */
	protected $name;

	/**
	 * A text that describes this field.
	 *
	 * @since  1.0.0
	 * @var    string
	 */
	protected $desc;

	/**
	 * A link pointing to more information about this field.
	 *
	 * @since  1.0.0
	 * @var    string
	 */
	protected $more;

	/**
	 * The option name in which this setting will be stored.
	 *
	 * @since  1.0.0
	 * @var    string
	 */
	protected $option_name;

	/**
	 * The default value.
	 *
	 * @since  3.7.0
	 * @var    mixed
	 */
	protected $default_value = null;

	/**
	 * Creates a new instance of this class.
	 *
	 * @param string $name The name that identifies this setting.
	 * @param string $desc Optional. A text that describes this field.
	 *                     Default: the empty string.
	 * @param string $more Optional. A link pointing to more information about this field.
	 *                     Default: the empty string.
	 *
	 * @since  1.0.0
	 */
	public function __construct( $name, $desc = '', $more = '' ) {

		$this->name = $name;
		$this->desc = $desc;
		$this->more = $more;
	}

	/**
	 * Returns the name that identifies this setting.
	 *
	 * @return string The name that identifies this setting.
	 *
	 * @since  1.0.0
	 */
	public function get_name() {
		return $this->name;
	}

	// @Implements
	public function register( $label, $page, $section, $option_group, $option_name ) {

		$this->label       = $label;
		$this->option_name = $option_name;

		register_setting(
			$option_group,
			$option_name,
			array( $this, 'sanitize' ) // Sanitization function.
		);

		$label = $this->generate_label();
		add_settings_field(
			$this->name,  // The ID of the settings field.
			$label,       // The name of the field of setting(s).
			array( $this, 'display' ),
			$page,
			$section,
			empty( $label ) ? array( 'class' => 'nelio-content-reduce-top-margin' ) : array()
		);
	}

	// @Implements
	public function set_default_value( $value ) {
		$this->default_value = $value;
	}

	/**
	 * Prints the description by properly escaping it.
	 *
	 * @param string $html Text with HTML code. Only some tags are supported.
	 *
	 * @return void
	 */
	public function print_html( $html ) {
		$tags = array(
			'<code>'    => '%1$s',
			'</code>'   => '%2$s',
			'<strong>'  => '%3$s',
			'</strong>' => '%4$s',
		);

		foreach ( $tags as $tag => $placeholder ) {
			$html = str_replace( $tag, $placeholder, $html );
		}

		printf(
			esc_html( $html ),
			'<code>',
			'</code>',
			'<strong>',
			'</strong>'
		);
	}

	/**
	 * This function generates a label for this field.
	 *
	 * In particular, it adds the `label` tag and a help icon (if a description
	 * was provided).
	 *
	 * @return string the label for this field.
	 *
	 * @since  1.0.0
	 */
	protected function generate_label() {

		$label = '<label for="' . $this->option_name . '">' . $this->label . '</label>';

		if ( ! empty( $this->desc ) ) {
			$img    = nelio_content()->plugin_url . '/includes/lib/settings/assets/images/help.png';
			$label .= '<img class="nelio-settings-help" style="float:right;margin-right:-15px;cursor:pointer;" src="' . $img . '" height="16" width="16" />'; //phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
		}

		return $label;
	}
}
