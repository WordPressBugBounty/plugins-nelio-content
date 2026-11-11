<?php
/**
 * Coordinates Nelio Content ability categories, registrations and MCP server.
 */

defined( 'ABSPATH' ) || exit;

/** Registers the shared abilities through the WordPress lifecycle. */
class Nelio_Content_Abilities {

	/**
	 * Initializes the integration only when WordPress provides the Abilities API.
	 *
	 * @return void
	 */
	public function init() {
		if ( ! function_exists( 'wp_register_ability' ) || ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}
		require_once __DIR__ . '/helpers.php';
		require_once __DIR__ . '/../networks/helpers.php';
		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_categories' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
		add_action( 'mcp_adapter_init', array( $this, 'register_server' ) );
	}

	/**
	 * Registers categories before their abilities. Premium can reuse these categories.
	 *
	 * @return void
	 */
	public function register_categories() {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}
		$categories = array(
			'calendar'       => array(
				'label'       => _x( 'Editorial Calendar', 'text', 'nelio-content' ),
				'description' => _x( 'Retrieve WordPress posts and social messages scheduled in the editorial calendar, change post publication dates, and create draft copies with the required editing, creation and publishing permissions.', 'text', 'nelio-content' ),
			),
			'profiles'       => array(
				'label'       => _x( 'Social Profiles', 'text', 'nelio-content' ),
				'description' => _x( 'Discover connected social profiles, their labels, and connection status to select publishing accounts and identify profiles needing attention.', 'text', 'nelio-content' ),
			),
			'messages'       => array(
				'label'       => _x( 'Social Messages', 'text', 'nelio-content' ),
				'description' => _x( 'Inspect social messages, scheduled timelines, and publishing failures, schedule and update messages, delete unpublished messages, retry failed publications, inspect automation settings and templates, and generate timelines when the required features and permissions are available.', 'text', 'nelio-content' ),
			),
			'tasks'          => array(
				'label'       => _x( 'Editorial Tasks', 'text', 'nelio-content' ),
				'description' => _x( 'Retrieve editorial task assignments, due dates, and completion status to coordinate content preparation and review.', 'text', 'nelio-content' ),
			),
			'comments'       => array(
				'label'       => _x( 'Editorial Comments', 'text', 'nelio-content' ),
				'description' => _x( 'Read internal post discussions and add editorial comments to coordinate content preparation, with post editing permissions and normal author and follower notifications.', 'text', 'nelio-content' ),
			),
			'references'     => array(
				'label'       => _x( 'Editorial References', 'text', 'nelio-content' ),
				'description' => _x( 'Inspect saved links used as editorial sources and inspiration, their relationships to editable posts and their bibliographic information, suggest individual links discard suggestions and edit shared bibliography without changing post content, with access checks for internal content.', 'text', 'nelio-content' ),
			),
			'future-actions' => array(
				'label'       => _x( 'Future Actions', 'text', 'nelio-content' ),
				'description' => _x( 'Inspect saved post changes planned for the future, their authorized configuration and scheduling state without executing or rescheduling them or inferring successful execution.', 'text', 'nelio-content' ),
			),
			'series'         => array(
				'label'       => _x( 'Content Series', 'text', 'nelio-content' ),
				'description' => _x( 'Discover and manage content series, read saved descriptions and archive links, add or remove editable posts and reorder complete authorized collections using the configured taxonomy, without exposing inaccessible members or totals.', 'text', 'nelio-content' ),
			),
			'site'           => array(
				'label'       => _x( 'Site Status', 'text', 'nelio-content' ),
				'description' => _x( 'Inspect local installation readiness, current-user permissions and feature requirements to explain operational blockers without exposing credentials or contacting providers.', 'text', 'nelio-content' ),
			),
			'analytics'      => array(
				'label'       => _x( 'Analytics', 'text', 'nelio-content' ),
				'description' => _x( 'Retrieve available post engagement and pageview metrics to assess content performance and inform editorial planning.', 'text', 'nelio-content' ),
			),
		);
		foreach ( $categories as $slug => $category ) {
			wp_register_ability_category( 'nelio-content-' . $slug, $category );
		}
	}

	/**
	 * Registers every discoverable contract explicitly.
	 *
	 * @return void
	 */
	public function register_abilities() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}
		( new Nelio_Content_Get_Site_Status_Ability() )->register();
		( new Nelio_Content_List_Series_Ability() )->register();
		( new Nelio_Content_Create_Series_Ability() )->register();
		( new Nelio_Content_Update_Series_Ability() )->register();
		( new Nelio_Content_Add_Post_To_Series_Ability() )->register();
		( new Nelio_Content_Remove_Post_From_Series_Ability() )->register();
		( new Nelio_Content_Reorder_Series_Posts_Ability() )->register();
		( new Nelio_Content_List_Future_Actions_Ability() )->register();
		( new Nelio_Content_Get_Future_Action_Ability() )->register();
		( new Nelio_Content_Create_Future_Action_Ability() )->register();
		( new Nelio_Content_Update_Future_Action_Ability() )->register();
		( new Nelio_Content_Delete_Future_Action_Ability() )->register();
		( new Nelio_Content_Get_Series_Ability() )->register();
		( new Nelio_Content_List_Series_Posts_Ability() )->register();
		( new Nelio_Content_List_Reshare_Candidates_Ability() )->register();
		( new Nelio_Content_Reschedule_Content_Ability() )->register();
		( new Nelio_Content_Duplicate_Content_Ability() )->register();
		( new Nelio_Content_List_Unscheduled_Posts_Ability() )->register();
		( new Nelio_Content_Generate_Social_Timeline_Ability() )->register();
		( new Nelio_Content_Generate_Reshare_Timeline_Ability() )->register();
		( new Nelio_Content_List_Automation_Groups_Ability() )->register();
		( new Nelio_Content_Get_Automation_Group_Ability() )->register();
		( new Nelio_Content_Create_Automation_Group_Ability() )->register();
		( new Nelio_Content_Update_Automation_Group_Ability() )->register();
		( new Nelio_Content_Delete_Automation_Group_Ability() )->register();
		( new Nelio_Content_Get_Automation_Settings_Ability() )->register();
		( new Nelio_Content_Get_Social_Templates_Ability() )->register();
		( new Nelio_Content_Get_Social_Network_Requirements_Ability() )->register();
		( new Nelio_Content_List_Social_Profiles_Ability() )->register();
		( new Nelio_Content_Update_Social_Profile_Frequencies_Ability() )->register();
		( new Nelio_Content_List_Social_Profile_Targets_Ability() )->register();
		( new Nelio_Content_Get_Social_Profile_Status_Ability() )->register();
		( new Nelio_Content_List_Social_Messages_Ability() )->register();
		( new Nelio_Content_Get_Social_Message_Ability() )->register();
		( new Nelio_Content_Get_Post_Social_Status_Ability() )->register();
		( new Nelio_Content_Get_Social_Timeline_Ability() )->register();
		( new Nelio_Content_Get_Social_Publishing_Errors_Ability() )->register();
		( new Nelio_Content_Get_Calendar_Ability() )->register();
		( new Nelio_Content_List_Top_Performing_Posts_Ability() )->register();
		( new Nelio_Content_Get_Post_Analytics_Ability() )->register();
		( new Nelio_Content_List_Editorial_References_Ability() )->register();
		( new Nelio_Content_Suggest_Editorial_Reference_Ability() )->register();
		( new Nelio_Content_Discard_Editorial_Reference_Ability() )->register();
		( new Nelio_Content_Update_Editorial_Reference_Ability() )->register();
		( new Nelio_Content_Get_Editorial_Status_Ability() )->register();
		( new Nelio_Content_Get_Editorial_Task_Ability() )->register();
		( new Nelio_Content_Create_Editorial_Task_Ability() )->register();
		( new Nelio_Content_Update_Editorial_Task_Ability() )->register();
		( new Nelio_Content_Complete_Editorial_Task_Ability() )->register();
		( new Nelio_Content_List_Editorial_Comments_Ability() )->register();
		( new Nelio_Content_Create_Editorial_Comment_Ability() )->register();
		( new Nelio_Content_List_Editorial_Tasks_Ability() )->register();
		( new Nelio_Content_Create_Social_Message_Ability() )->register();
		( new Nelio_Content_Update_Social_Message_Ability() )->register();
		( new Nelio_Content_Delete_Social_Message_Ability() )->register();
		( new Nelio_Content_Retry_Social_Message_Ability() )->register();
	}

	/**
	 * Registers the dedicated server with the complete discoverable contract catalog.
	 *
	 * @param \WP\MCP\Core\McpAdapter $adapter Initialized MCP Adapter.
	 * @return void
	 */
	public function register_server( $adapter ) {
		if ( ! function_exists( 'wp_get_ability' ) || ! defined( 'WP_MCP_VERSION' ) || ! is_string( WP_MCP_VERSION ) || version_compare( WP_MCP_VERSION, '0.7.1', '<' ) ) {
			return;
		}
		$tools = array(
			'nelio-content/get-site-status',
			'nelio-content/list-series',
			'nelio-content/create-series',
			'nelio-content/update-series',
			'nelio-content/add-post-to-series',
			'nelio-content/remove-post-from-series',
			'nelio-content/reorder-series-posts',
			'nelio-content/list-future-actions',
			'nelio-content/get-future-action',
			'nelio-content/create-future-action',
			'nelio-content/update-future-action',
			'nelio-content/delete-future-action',
			'nelio-content/get-series',
			'nelio-content/list-series-posts',
			'nelio-content/list-reshare-candidates',
			'nelio-content/reschedule-content',
			'nelio-content/duplicate-content',
			'nelio-content/list-unscheduled-posts',
			'nelio-content/generate-social-timeline',
			'nelio-content/generate-reshare-timeline',
			'nelio-content/list-automation-groups',
			'nelio-content/get-automation-group',
			'nelio-content/create-automation-group',
			'nelio-content/update-automation-group',
			'nelio-content/delete-automation-group',
			'nelio-content/get-automation-settings',
			'nelio-content/get-social-templates',
			'nelio-content/get-social-network-requirements',
			'nelio-content/list-social-profiles',
			'nelio-content/update-social-profile-frequencies',
			'nelio-content/list-social-profile-targets',
			'nelio-content/get-social-profile-status',
			'nelio-content/list-social-messages',
			'nelio-content/get-social-message',
			'nelio-content/get-post-social-status',
			'nelio-content/get-social-timeline',
			'nelio-content/get-social-publishing-errors',
			'nelio-content/get-calendar',
			'nelio-content/list-top-performing-posts',
			'nelio-content/get-post-analytics',
			'nelio-content/list-editorial-references',
			'nelio-content/suggest-editorial-reference',
			'nelio-content/discard-editorial-reference',
			'nelio-content/update-editorial-reference',
			'nelio-content/get-editorial-status',
			'nelio-content/get-editorial-task',
			'nelio-content/create-editorial-task',
			'nelio-content/update-editorial-task',
			'nelio-content/complete-editorial-task',
			'nelio-content/list-editorial-comments',
			'nelio-content/create-editorial-comment',
			'nelio-content/list-editorial-tasks',
			'nelio-content/create-social-message',
			'nelio-content/update-social-message',
			'nelio-content/delete-social-message',
			'nelio-content/retry-social-message',
		);
		// All contracts live in Free; Premium supplies only their execution handlers.
		$tools = array_values( array_filter( $tools, fn( $name ) => null !== wp_get_ability( $name ) ) );
		$adapter->create_server(
			'nelio-content',
			'nelio-content/v1',
			'mcp',
			_x( 'Nelio Content', 'text', 'nelio-content' ),
			_x( 'Access editorial calendars, social profiles, messages and analytics. Update and delete unpublished messages or explicitly retry failed publications. Schedule new social messages, inspect automations, generate timelines and review editorial tasks when Premium is available.', 'text', 'nelio-content' ),
			'1.0.0',
			array( \WP\MCP\Transport\HttpTransport::class ),
			null,
			null,
			$tools,
			array(),
			array(),
			fn() => is_user_logged_in() && nelio_content()->is_ready() && nelio_content_can_current_user_use_plugin()
		);
	}
}
