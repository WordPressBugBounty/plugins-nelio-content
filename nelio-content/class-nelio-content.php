<?php

defined( 'ABSPATH' ) || exit;

/**
 * Main class.
 */
class Nelio_Content {

	/**
	 * This instance.
	 *
	 * @var Nelio_Content|null
	 */
	private static $instance;

	/**
	 * Plugin’s main file.
	 *
	 * @var string
	 */
	public $plugin_file;

	/**
	 * Plugin name.
	 *
	 * @var string
	 */
	public $plugin_name;

	/**
	 * Plugin path.
	 *
	 * @var string
	 */
	public $plugin_path;

	/**
	 * Plugin slug.
	 *
	 * @var string
	 */
	public $plugin_slug;

	/**
	 * Plugin URL.
	 *
	 * @var string
	 */
	public $plugin_url;

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public $plugin_version;

	/**
	 * Plugin’s REST namespace.
	 *
	 * @var string
	 */
	public $rest_namespace;

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_Content
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::$instance->load_dependencies();
			self::$instance->install();
			self::$instance->init();
		}

		return self::$instance;
	}

	/**
	 * Loads plugin’s basic dependencies.
	 *
	 * This includes the autoloader, helper functions, and all hooks.
	 *
	 * @return void
	 */
	private function load_dependencies() {

		$this->plugin_path    = untrailingslashit( plugin_dir_path( __FILE__ ) );
		$this->plugin_url     = untrailingslashit( plugin_dir_url( __FILE__ ) );
		$this->plugin_file    = 'nelio-content/nelio-content.php';
		$this->rest_namespace = 'nelio-content/v1';

		require_once $this->plugin_path . '/vendor/autoload.php';
		require_once $this->plugin_path . '/includes/lib/nelio/helpers/index.php';
		require_once $this->plugin_path . '/includes/lib/nelio/zod/index.php';
		require_once $this->plugin_path . '/includes/utils/functions/index.php';
	}

	/**
	 * Initializes main classes, regardless of plugin’s status.
	 *
	 * @return void
	 */
	private function install() {

		add_action( 'plugins_loaded', array( $this, 'plugin_data_init' ), 1 );

		if ( nelio_content_is_staging() ) {
			add_action( 'after_plugin_row_nelio-content/nelio-content.php', array( $this, 'add_staging_warning' ) );
			add_action( 'nelio_content_after_settings_title', array( $this, 'add_staging_warning' ) );
		}

		$aux = Nelio_Content_Install::instance();
		$aux->init();

		$aux = Nelio_Content_Settings::instance();
		$aux->init();

		$aux = Nelio_Content_Admin::instance();
		$aux->init();

		$aux = Nelio_Content_Account_REST_Controller::instance();
		$aux->init();

		$aux = Nelio_Content_External_Featured_Image_Admin::instance();
		$aux->init();

		$aux = Nelio_Content_Plugin_REST_Controller::instance();
		$aux->init();

		if ( is_admin() ) {
			$aux = Nelio_Content_Overview_Widget::instance();
			$aux->init();
		}
	}

	/**
	 * Loads remaining dependencies, if plugin is ready.
	 *
	 * @return void
	 */
	private function init() {

		if ( ! $this->is_ready() ) {
			return;
		}

		$this->init_common_helpers();
		$this->init_rest_controllers();
		$this->init_compat_fixes();
		$this->register_post_types();

		if ( ! is_admin() ) {
			$aux = Nelio_Content_Public::instance();
			$aux->init();

			$aux = Nelio_Content_Meta_Tags::instance();
			$aux->init();
		}

		$aux = Nelio_Content_External_Featured_Image_Public::instance();
		$aux->init();
	}

	/**
	 * Returns whether the plugin is properly configured or not (i.e. it has a site id).
	 *
	 * @return boolean
	 */
	public function is_ready() {

		return ! empty( nelio_content_get_site_id() );
	}

	/**
	 * Returns whether the wizard is requested.
	 *
	 * @return boolean
	 */
	public function is_wizard_requested() {

		return ! empty( get_option( 'nc_wizard_requested', '' ) );
	}

	/**
	 * Finishes the wizard.
	 *
	 * @return void
	 */
	public function finish_wizard() {
		delete_option( 'nc_wizard_requested' );
	}

	/**
	 * Inits all common helpers.
	 *
	 * @return void
	 */
	private function init_common_helpers() {

		$aux = Nelio_Content_Classic_Editor::instance();
		$aux->init();

		$aux = Nelio_Content_Gutenberg::instance();
		$aux->init();

		$aux = Nelio_Content_Analytics_Helper::instance();
		$aux->init();

		$aux = Nelio_Content_Auto_Sharer::instance();
		$aux->init();

		$aux = Nelio_Content_Cloud::instance();
		$aux->init();

		$aux = Nelio_Content_Post_Saving::instance();
		$aux->init();

		$aux = Nelio_Content_Notifications::instance();
		$aux->init();

		$aux = Nelio_Content_Missed_Schedule_Handler::instance();
		$aux->init();

		$aux = Nelio_Content_Ics_Calendar::instance();
		$aux->init();
	}

	/**
	 * Inits REST controllers.
	 *
	 * @return void
	 */
	private function init_rest_controllers() {

		$aux = Nelio_Content_Analytics_REST_Controller::instance();
		$aux->init();

		$aux = Nelio_Content_Author_REST_Controller::instance();
		$aux->init();

		$aux = Nelio_Content_External_Calendar_REST_Controller::instance();
		$aux->init();

		$aux = Nelio_Content_Feed_REST_Controller::instance();
		$aux->init();

		$aux = Nelio_Content_Generic_REST_Controller::instance();
		$aux->init();

		$aux = Nelio_Content_Internal_Events_REST_Controller::instance();
		$aux->init();

		$aux = Nelio_Content_Placeholders_REST_Controller::instance();
		$aux->init();

		$aux = Nelio_Content_Post_REST_Controller::instance();
		$aux->init();

		$aux = Nelio_Content_Reference_REST_Controller::instance();
		$aux->init();

		$aux = Nelio_Content_Reusable_Message_REST_Controller::instance();
		$aux->init();

		$aux = Nelio_Content_Shared_Link_REST_Controller::instance();
		$aux->init();

		$aux = Nelio_Content_Statuses_REST_Controller::instance();
		$aux->init();

		$aux = Nelio_Content_Task_Presets_REST_Controller::instance();
		$aux->init();
	}


	/**
	 * Registers post types.
	 *
	 * @return void
	 */
	private function register_post_types() {
		$aux = Nelio_Content_Reference_Post_Type_Register::instance();
		$aux->init();

		$aux = Nelio_Content_Reusable_Message_Post_Type_Register::instance();
		$aux->init();

		$aux = Nelio_Content_Task_Preset_Post_Type_Register::instance();
		$aux->init();
	}


	/**
	 * Initializes compatibility fixes.
	 *
	 * @return void
	 */
	private function init_compat_fixes() {

		require_once nelio_content()->plugin_path . '/includes/compat/index.php';
	}

	/**
	 * Callback to initialize plugin data.
	 *
	 * @return void
	 */
	public function plugin_data_init() {

		$data = get_file_data( untrailingslashit( __DIR__ ) . '/nelio-content.php', array( 'Plugin Name', 'Version' ), 'plugin' );

		$this->plugin_name    = $data[0];
		$this->plugin_version = $data[1];
		$this->plugin_slug    = plugin_basename( __FILE__ );
	}

	/**
	 * Callback to add a warning when plugin is being used in a staging site.
	 *
	 * @return void
	 */
	public function add_staging_warning() {
		echo '<tr class="plugin-update-tr active" id="nelio-content-staging-warning" data-slug="nelio-content" data-plugin="nelio-content.php">';
		echo '<td colspan="4" class="plugin-update colspanchange">';
		echo '<div class="notice inline notice-warning notice-alt">';
		echo '<p>';

		printf(
			wp_kses(
				/* translators: %s: URL. */
				_x( '<strong>Warning!</strong> This site has been identified as a <strong>staging site</strong> and, as a result, you can’t use any of Nelio Content’s social sharing features. If this is not correct and you want to use Nelio Content normally, please <a href="%s" target="_blank">follow these instructions</a>.', 'user', 'nelio-content' ),
				array(
					'strong' => array(),
					'a'      => array(
						'href'   => true,
						'target' => true,
					),
				)
			),
			esc_url(
				add_query_arg(
					array(
						'utm_source'   => 'nelio-content',
						'utm_medium'   => 'plugin',
						'utm_campaign' => 'support',
						'utm_content'  => 'staging-warning',
					),
					'https://neliosoftware.com/content/help/modify-list-of-staging-urls/'
				)
			)
		);

		echo '</p></div></td></tr>';
		echo '<script>(function(){document.getElementById("nelio-content-staging-warning").previousElementSibling.classList.add("update");})();</script>';
	}
}
