<?php
/** Local installation diagnostics for users already authorized to use Nelio Content. */

use function Nelio_Content\Networks\is_text_validation_available;

defined( 'ABSPATH' ) || exit;

/**
 * Shares the normal ability lifecycle and exposes only an explicit local projection.
 *
 * @extends Nelio_Content_Ability<TAbility_Input>
 */
class Nelio_Content_Get_Site_Status_Ability extends Nelio_Content_Ability {

	/** @return void */
	public function register() {
		wp_register_ability(
			'nelio-content/get-site-status',
			array(
				'label'               => _x( 'Get site status', 'command', 'nelio-content' ),
				'description'         => _x( 'Inspect local Nelio Content readiness, site timezone, current-user permissions and feature requirements without contacting Cloud, social networks or billing providers. Available in Free and Premium through the normal configured-plugin lifecycle. Features may be available, blocked or conditional on checks for a specific post, profile, cached metric or confirmation. Subscription and Premium installation/version details are included only for users who may manage Nelio Content. Recorded configuration does not prove remote connectivity, current billing validity or provider acceptance. Use profile status and the relevant operation for object-specific checks.', 'text', 'nelio-content' ),
				'category'            => 'nelio-content-site',
				// No properties map: an empty PHP array would become an invalid JSON Schema [].
				'input_schema'        => array(
					'type'                 => 'object',
					'description'          => _x( 'No input is required. Diagnostics always describe this installation and the authenticated current user.', 'text', 'nelio-content' ),
					'additionalProperties' => false,
				),
				'output_schema'       => $this->status_schema(),
				'execute_callback'    => array( $this, 'execute' ),
				'permission_callback' => array( $this, 'permissions' ),
				'meta'                => $this->get_ability_metadata(),
			)
		);
	}

	/**
	 * @param TAbility_Input $input Input.
	 * @return bool
	 */
	public function permissions( $input ) {
		return $this->can_use_plugin( $input );
	}

	/**
	 * @param TAbility_Input $input Input.
	 * @return array<string,mixed>|WP_Error
	 */
	public function execute( $input ) {
		if ( ! $this->permissions( $input ) ) {
			return new WP_Error( 'nelio_content_permission_denied', _x( 'You cannot inspect Nelio Content status.', 'user', 'nelio-content' ) );
		}
		$manager           = nelio_content_can_current_user_manage_plugin();
		$social            = nelio_content_get_social_editor_permission();
		$tasks             = Nelio_Content_Admin::instance()->get_task_editor_permission();
		$text              = is_text_validation_available();
		$ga                = Nelio_Content_Settings::instance()->get( 'google_analytics_data' );
		$analytics         = ! empty( nelio_content_get_post_types( 'analytics' ) );
		$premium           = ( new Nelio_Content_Create_Social_Message_Ability() )->is_available();
		$adapter_version   = defined( 'WP_MCP_VERSION' ) ? constant( 'WP_MCP_VERSION' ) : '';
		$adapter_version   = is_string( $adapter_version ) ? $adapter_version : '';
		$adapter_loaded    = defined( 'WP_MCP_VERSION' ) && class_exists( '\\WP\\MCP\\Core\\McpAdapter' );
		$adapter_supported = $adapter_loaded && '' !== $adapter_version && version_compare( $adapter_version, '0.7.1', '>=' );
		$features          = array(
			$this->feature( 'calendar', empty( nelio_content_get_post_types( 'calendar' ) ) ? 'blocked' : 'available', 'calendar_disabled', _x( 'No post types are enabled in the editorial calendar.', 'text', 'nelio-content' ), _x( 'Ask the administrator to enable calendar post types.', 'command', 'nelio-content' ) ),
			$this->feature( 'analytics', $analytics ? 'conditional' : 'blocked', 'analytics_disabled', _x( 'No post types have analytics enabled.', 'text', 'nelio-content' ), _x( 'Ask the administrator to enable analytics for the required post types.', 'command', 'nelio-content' ) ),
			$this->feature( 'pageview_rankings', $analytics && ! empty( $ga['id'] ) ? 'conditional' : 'blocked', 'pageviews_unavailable', _x( 'Pageview rankings require analytics-enabled post types and a configured Google Analytics property.', 'text', 'nelio-content' ), _x( 'Ask the administrator to configure analytics. Stored counters are also required.', 'command', 'nelio-content' ) ),
			$this->feature( 'social_message_delete_retry', 'none' !== $social ? 'conditional' : 'blocked', 'social_editing_denied', _x( 'Social editing is unavailable for this user or environment.', 'text', 'nelio-content' ), _x( 'Ask the administrator to review social editing permissions and staging restrictions.', 'command', 'nelio-content' ) ),
			$this->feature( 'social_message_updates', 'none' !== $social && $text ? 'conditional' : 'blocked', $text ? 'social_editing_denied' : 'text_validation_unavailable', _x( 'Updating message content requires social editing access and text validation support.', 'text', 'nelio-content' ), _x( 'Ask the administrator to review social editing access and enable text validation support.', 'command', 'nelio-content' ) ),
			$this->feature( 'social_message_creation', $premium && 'none' !== $social && $text ? 'conditional' : 'blocked', 'creation_requirements_unmet', _x( 'Creating messages requires Premium availability, social editing access and text validation support.', 'text', 'nelio-content' ), _x( 'Ask the administrator to review Premium availability, social editing and text validation support.', 'command', 'nelio-content' ) ),
			$this->feature( 'automation_discovery', ( new Nelio_Content_Get_Automation_Settings_Ability() )->is_available() && 'none' !== $social ? 'conditional' : 'blocked', 'automation_requirements_unmet', _x( 'Automation discovery requires Premium availability and social editing access.', 'text', 'nelio-content' ), _x( 'Ask the administrator to review Premium availability and social editing access.', 'command', 'nelio-content' ) ),
			$this->feature( 'timeline_generation', ( new Nelio_Content_Generate_Social_Timeline_Ability() )->is_available() && ( new Nelio_Content_Generate_Reshare_Timeline_Ability() )->is_available() && $manager && 'all' === $social ? 'conditional' : 'blocked', 'generation_requirements_unmet', _x( 'Timeline generation requires Premium availability, plugin management and full social editing.', 'text', 'nelio-content' ), _x( 'Ask the administrator to review generation permissions. Access to every connected profile and explicit confirmation are checked when generating.', 'command', 'nelio-content' ) ),
			$this->feature( 'editorial_tasks', ( new Nelio_Content_List_Editorial_Tasks_Ability() )->is_available() && 'none' !== $tasks ? 'conditional' : 'blocked', 'task_requirements_unmet', _x( 'Editorial tasks require Premium availability and task access.', 'text', 'nelio-content' ), _x( 'Ask the administrator to review Premium availability and task permissions.', 'command', 'nelio-content' ) ),
			$this->feature( 'editorial_comments', ( new Nelio_Content_List_Editorial_Comments_Ability() )->is_available() && ! empty( nelio_content_get_post_types( 'comments' ) ) ? 'conditional' : 'blocked', 'comment_requirements_unmet', _x( 'Editorial comments require Premium availability and comments enabled for a post type.', 'text', 'nelio-content' ), _x( 'Ask the administrator to review Premium availability and enabled comment post types. Editing the selected post is also required.', 'command', 'nelio-content' ) ),
			$this->feature( 'mcp', $adapter_supported ? 'conditional' : 'blocked', $adapter_loaded ? 'mcp_adapter_unsupported' : 'mcp_adapter_missing', _x( 'MCP connections require a loaded compatible WordPress MCP Adapter.', 'text', 'nelio-content' ), _x( 'Ask the administrator to activate MCP Adapter 0.7.1 or newer. Connection and authentication must be checked separately.', 'command', 'nelio-content' ) ),
		);
		$issues            = array_values( array_filter( array_map( fn( $feature ) => $feature['issue'], $features ) ) );
		$result            = array(
			'site'                  => array(
				'ready'    => nelio_content()->is_ready(),
				'version'  => $this->plugin_version(),
				'timezone' => wp_timezone_string(),
				'staging'  => nelio_content_is_staging(),
			),
			'permissions'           => array(
				'manage_plugin' => $manager,
				'social_editor' => $social,
				'task_editor'   => $tasks,
			),
			'features'              => array_map( fn( $feature ) => array_diff_key( $feature, array( 'issue' => true ) ), $features ),
			'issues'                => $issues,
			'observed_at'           => gmdate( DATE_ATOM ),
			'provider_connectivity' => 'not_checked',
		);
		if ( $manager ) {
			$result['administration'] = $this->administration( $premium, $adapter_loaded, $adapter_supported, $adapter_version, ! empty( $ga['id'] ), $text );
		}
		return $result;
	}

	/**
	 * Conditional means local prerequisites pass but object/data checks remain necessary.
	 *
	 * @param string $name Feature.
	 * @param string $state Local state.
	 * @param string $code Blocker code.
	 * @param string $message Human-readable blocker.
	 * @param string $action Suggested action.
	 * @return array{name:string,state:string,reason:string,issue:array{feature:string,code:string,message:string,action:string}|null}
	 */
	private function feature( $name, $state, $code, $message, $action ) {
		return array(
			'name'   => $name,
			'state'  => $state,
			'reason' => 'blocked' === $state ? $code : ( 'conditional' === $state ? 'operation_checks_required' : 'local_prerequisites_met' ),
			'issue'  => 'blocked' === $state ? array(
				'feature' => $name,
				'code'    => $code,
				'message' => $message,
				'action'  => $action,
			) : null,
		);
	}

	/**
	 * Reads only explicit diagnostics; never returns plan identifiers or account data.
	 *
	 * @param bool   $premium_available Whether a representative paid handler can execute locally.
	 * @param bool   $adapter_loaded Adapter loaded.
	 * @param bool   $adapter_supported Supported adapter.
	 * @param string $adapter_version Adapter version.
	 * @param bool   $ga_configured GA property configured.
	 * @param bool   $text_available Text validation available.
	 * @return array<string,mixed>
	 */
	private function administration( $premium_available, $adapter_loaded, $adapter_supported, $adapter_version, $ga_configured, $text_available ) {
		$loaded    = function_exists( 'nelio_content_premium_version' );
		$version   = $loaded ? nelio_content_premium_version() : '';
		$installed = $loaded;
		if ( ! $loaded ) {
			nelio_content_require_wp_file( '/wp-admin/includes/plugin.php' );
			$plugins   = get_plugins();
			$plugin    = $plugins['nelio-content-premium/nelio-content-premium.php'] ?? null;
			$installed = null !== $plugin;
			$version   = $plugin['Version'] ?? '';
		}
		$version    = is_string( $version ) ? $version : '';
		$compatible = '' === $version ? 'unknown' : ( version_compare( $this->plugin_version(), $version, '=' ) ? 'yes' : 'no' );
		if ( $loaded && function_exists( 'nelio_content_premium_is_version_valid' ) ) {
			$compatible = nelio_content_premium_is_version_valid() ? 'yes' : 'no';
		}
		$subscription = nelio_content_is_subscribed();
		$reason       = $premium_available ? 'available' : ( ! $subscription ? 'subscription_required' : ( 'no' === $compatible ? 'incompatible_versions' : ( ! $loaded ? 'activation_required' : 'implementation_unavailable' ) ) );
		$actions      = array(
			'available'                  => _x( 'No Premium installation action is required locally; operation-specific checks still apply.', 'text', 'nelio-content' ),
			'subscription_required'      => _x( 'Subscribe to Nelio Content Premium or review the locally recorded subscription in the account page.', 'command', 'nelio-content' ),
			'incompatible_versions'      => _x( 'Update Nelio Content and Nelio Content Premium to matching versions.', 'command', 'nelio-content' ),
			'activation_required'        => _x( 'Install and activate the matching Nelio Content Premium plugin.', 'command', 'nelio-content' ),
			'implementation_unavailable' => _x( 'Review Premium initialization and availability of its abilities with the site administrator.', 'command', 'nelio-content' ),
		);
		return array(
			'subscription_recorded'       => $subscription,
			'premium_plugin_installed'    => $installed,
			'premium_plugin_loaded'       => $loaded,
			'premium_version'             => $version,
			'premium_versions_compatible' => $compatible,
			'premium_available'           => $premium_available,
			'premium_reason'              => $reason,
			'premium_action'              => $actions[ $reason ],
			'google_analytics_configured' => $ga_configured,
			'text_validation_available'   => $text_available,
			'mcp_adapter_loaded'          => $adapter_loaded,
			'mcp_adapter_supported'       => $adapter_supported,
			'mcp_adapter_version'         => $adapter_version,
		);
	}

	/** @return string */
	private function plugin_version() {
		$data = get_file_data( dirname( __DIR__, 2 ) . '/nelio-content.php', array( 'Version' ), 'plugin' );
		return $data[0];
	}

	/** @return array<string,mixed> */
	private function status_schema() {
		$feature     = $this->object_schema(
			array(
				'name'   => array(
					'type'        => 'string',
					'enum'        => array( 'calendar', 'analytics', 'pageview_rankings', 'social_message_delete_retry', 'social_message_updates', 'social_message_creation', 'automation_discovery', 'timeline_generation', 'editorial_tasks', 'editorial_comments', 'mcp' ),
					'description' => _x( 'Function whose local requirements are being reported.', 'text', 'nelio-content' ),
				),
				'state'  => array(
					'type'        => 'string',
					'enum'        => array( 'available', 'conditional', 'blocked' ),
					'description' => _x( 'available means local prerequisites pass. conditional means operation-specific post/profile access, data, confirmation or transport checks remain. blocked means at least one local requirement fails. None of these states confirms provider connectivity or successful execution.', 'text', 'nelio-content' ),
				),
				'reason' => array(
					'type'        => 'string',
					'description' => _x( 'Stable local reason code. A blocked feature has a corresponding issue with an explanation and suggested action.', 'text', 'nelio-content' ),
				),
			),
			array( 'name', 'state', 'reason' ),
			_x( 'Preliminary availability of one function for this user, without inspecting individual posts or profiles.', 'text', 'nelio-content' )
		);
		$issue       = $this->object_schema(
			array(
				'feature' => array(
					'type'        => 'string',
					'description' => _x( 'Function affected by this local blocker.', 'text', 'nelio-content' ),
				),
				'code'    => array(
					'type'        => 'string',
					'description' => _x( 'Stable code identifying the blocked requirement.', 'text', 'nelio-content' ),
				),
				'message' => array(
					'type'        => 'string',
					'description' => _x( 'Human-readable explanation based on local configuration and permissions.', 'text', 'nelio-content' ),
				),
				'action'  => array(
					'type'        => 'string',
					'description' => _x( 'Suggested next step. It is guidance, not an action already performed.', 'text', 'nelio-content' ),
				),
			),
			array( 'feature', 'code', 'message', 'action' ),
			_x( 'One local prerequisite blocking a function. Optional features can be blocked while the plugin itself is ready.', 'text', 'nelio-content' )
		);
		$site        = $this->object_schema(
			array(
				'ready'    => array(
					'type'        => 'boolean',
					'description' => _x( 'Whether the configured plugin is locally ready. This ability uses the normal ready-plugin access checks and is unavailable when they fail.', 'text', 'nelio-content' ),
				),
				'version'  => array(
					'type'        => 'string',
					'description' => _x( 'Installed Nelio Content version.', 'text', 'nelio-content' ),
				),
				'timezone' => array(
					'type'        => 'string',
					'description' => _x( 'WordPress site timezone used to interpret editorial dates.', 'text', 'nelio-content' ),
				),
				'staging'  => array(
					'type'        => 'boolean',
					'description' => _x( 'Whether Nelio Content treats this site as a staging environment. Social editing and synchronization can be restricted in this environment.', 'text', 'nelio-content' ),
				),
			),
			array( 'ready', 'version', 'timezone', 'staging' ),
			_x( 'Safe local installation identity and environment information.', 'text', 'nelio-content' )
		);
		$mode        = array(
			'type'        => 'string',
			'enum'        => array( 'none', 'post-type', 'all' ),
			'description' => _x( 'General editor permission: none denies access; post-type limits operations to permitted posts or assignments; all gives broad editor access. Individual post/profile requirements still apply.', 'text', 'nelio-content' ),
		);
		$permissions = $this->object_schema(
			array(
				'manage_plugin' => array(
					'type'        => 'boolean',
					'description' => _x( 'Whether the current user can manage Nelio Content and receive administrative diagnostics.', 'text', 'nelio-content' ),
				),
				'social_editor' => $mode,
				'task_editor'   => $mode,
			),
			array( 'manage_plugin', 'social_editor', 'task_editor' ),
			_x( 'General permissions of the authenticated current user, not grants for every post or social profile.', 'text', 'nelio-content' )
		);
		$admin       = array();
		foreach ( array(
			'subscription_recorded'       => _x( 'Whether a subscription is recorded locally. This does not verify current billing validity with the provider.', 'text', 'nelio-content' ),
			'premium_plugin_installed'    => _x( 'Whether the loaded Premium plugin or its standard installed plugin entry was detected locally.', 'text', 'nelio-content' ),
			'premium_plugin_loaded'       => _x( 'Whether the Premium plugin bootstrap is loaded. Loaded does not mean its paid features initialized successfully.', 'text', 'nelio-content' ),
			'premium_available'           => _x( 'Whether local subscription, compatibility and runtime implementation checks pass for Premium scheduling. Each feature also checks its own handler and permissions.', 'text', 'nelio-content' ),
			'google_analytics_configured' => _x( 'Whether a Google Analytics property is selected locally. This does not verify its connection or available cached counters.', 'text', 'nelio-content' ),
			'text_validation_available'   => _x( 'Whether this installation has the text validation support required for scheduling and updating social message content.', 'text', 'nelio-content' ),
			'mcp_adapter_loaded'          => _x( 'Whether the WordPress MCP Adapter is loaded in this request.', 'text', 'nelio-content' ),
			'mcp_adapter_supported'       => _x( 'Whether the loaded adapter meets the locally supported minimum version. Server registration, client connectivity and authentication are not verified.', 'text', 'nelio-content' ),
		) as $name => $description ) {
			$admin[ $name ] = array(
				'type'        => 'boolean',
				'description' => $description,
			); }
		$admin['premium_version']             = array(
			'type'        => 'string',
			'description' => _x( 'Detected Premium plugin version, or empty when unavailable.', 'text', 'nelio-content' ),
		);
		$admin['premium_versions_compatible'] = array(
			'type'        => 'string',
			'enum'        => array( 'yes', 'no', 'unknown' ),
			'description' => _x( 'Whether detected Free and Premium versions match. unknown means no Premium version was detected.', 'text', 'nelio-content' ),
		);
		$admin['premium_reason']              = array(
			'type'        => 'string',
			'enum'        => array( 'available', 'subscription_required', 'incompatible_versions', 'activation_required', 'implementation_unavailable' ),
			'description' => _x( 'Local Premium availability reason. Subscription, compatibility, activation and runtime implementation are distinct requirements.', 'text', 'nelio-content' ),
		);
		$admin['premium_action']              = array(
			'type'        => 'string',
			'description' => _x( 'Suggested administrative next step for the local Premium state. This does not perform activation, updates or subscription changes.', 'text', 'nelio-content' ),
		);
		$admin['mcp_adapter_version']         = array(
			'type'        => 'string',
			'description' => _x( 'Loaded MCP Adapter version, or empty when unknown.', 'text', 'nelio-content' ),
		);
		$features                             = $this->array_schema( $feature, _x( 'Local function availability for the current user, without a guarantee that a particular operation will succeed.', 'text', 'nelio-content' ) );
		$features['maxItems']                 = 11;
		$issues                               = $this->array_schema( $issue, _x( 'Local blockers and suggested actions. The array can be empty without proving remote services are healthy.', 'text', 'nelio-content' ) );
		$issues['maxItems']                   = 11;
		return $this->object_schema(
			array(
				'site'                  => $site,
				'permissions'           => $permissions,
				'features'              => $features,
				'issues'                => $issues,
				'observed_at'           => array(
					'type'        => 'string',
					'format'      => 'date-time',
					'description' => _x( 'UTC timestamp when these local diagnostics were assembled. It is not a provider verification time.', 'text', 'nelio-content' ),
				),
				'provider_connectivity' => array(
					'type'        => 'string',
					'enum'        => array( 'not_checked' ),
					'description' => _x( 'Remote provider connectivity is not checked by this local diagnostic.', 'text', 'nelio-content' ),
				),
				'administration'        => $this->object_schema( $admin, array_keys( $admin ), _x( 'Explicit administrative diagnostics. This entire object is omitted unless the current user may manage Nelio Content. Account identifiers, plan identifiers, billing links, credentials, property identifiers and arbitrary settings are excluded.', 'text', 'nelio-content' ) ),
			),
			array( 'site', 'permissions', 'features', 'issues', 'observed_at', 'provider_connectivity' ),
			_x( 'Local readiness and preliminary feature availability for an already configured installation and authorized user. This result does not confirm external service health.', 'text', 'nelio-content' )
		);
	}
}
