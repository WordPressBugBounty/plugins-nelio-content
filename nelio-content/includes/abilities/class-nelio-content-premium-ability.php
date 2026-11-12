<?php
/**
 * Discoverable Premium ability contracts and runtime dispatch in the Free plugin.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Keeps discovery public to authorized users while Premium supplies the implementation.
 *
 * @template TInput of TAbility_Input
 * @extends Nelio_Content_Ability<TInput>
 */
abstract class Nelio_Content_Premium_Ability extends Nelio_Content_Ability {

	/**
	 * Identifies the paid implementation associated with this contract.
	 *
	 * @return string
	 */
	abstract protected function get_premium_ability_name();

	/**
	 * Reports installation availability independently of the current user's permissions.
	 *
	 * @return bool
	 */
	public function is_available() {
		return null === $this->get_premium_requirement_error() && null !== $this->get_premium_handler();
	}

	/**
	 * Checks user authorization; availability errors are returned from execution.
	 *
	 * @param TInput $input Validated ability input.
	 * @return bool|WP_Error
	 */
	public function permissions( $input ) {
		if ( ! $this->can_use_plugin( $input ) || ! $this->check_operation_permissions( $input ) ) {
			return false;
		}
		// Core hides permission errors. Authorized users enter execute() for availability guidance.
		if ( null !== $this->get_premium_requirement_error() ) {
			return true;
		}
		$handler = $this->get_premium_handler();
		return $handler ? $handler->permissions( $input ) : true;
	}

	/**
	 * Delegates only after all access checks succeed, including direct PHP calls.
	 *
	 * @param TInput $input Validated ability input.
	 * @return array<string,mixed>|WP_Error
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( true !== $allowed ) {
			return is_wp_error( $allowed ) ? $allowed : new WP_Error( 'nelio_content_permission_denied', 'You cannot execute this ability.' );
		}
		$error = $this->get_premium_requirement_error();
		if ( $error ) {
			return $error;
		}
		$handler = $this->get_premium_handler();
		return $handler ? $handler->execute( $input ) : $this->get_activation_error();
	}

	/**
	 * Rechecks access and requirements for both the contract and its Premium handler.
	 *
	 * @param TInput $input Validated ability input.
	 * @return bool|WP_Error
	 */
	protected function can_use_premium( $input ) {
		if ( ! $this->can_use_plugin( $input ) || ! $this->check_operation_permissions( $input ) ) {
			return false;
		}
		return $this->get_premium_requirement_error() ?? true;
	}

	/**
	 * Checks permissions specific to the operation before showing Premium guidance.
	 *
	 * @param TInput $input Validated ability input.
	 * @return bool
	 */
	protected function check_operation_permissions( $input ) {
		return true;
	}

	/**
	 * Adds a stable Premium requirement to the contract's discovery metadata.
	 *
	 * @param bool $is_readonly Whether execution only reads data.
	 * @return array<string,mixed>
	 */
	protected function get_premium_ability_metadata( $is_readonly = true ) {
		return array_merge(
			$this->get_ability_metadata( $is_readonly ),
			array(
				'nelio_content' => array(
					'requires_premium' => true,
					'upgrade_url'      => $this->get_upgrade_url(),
				),
			)
		);
	}

	/**
	 * Looks up a compatible subclass supplied by the Premium plugin at call time.
	 *
	 * @return static|null
	 */
	private function get_premium_handler() {
		$handler = apply_filters( 'nelio_content_premium_ability_handler', null, $this->get_premium_ability_name() );
		if ( ! $handler instanceof static ) {
			return null;
		}
		// A provider must override both dispatch methods; returning a contract would recurse.
		foreach ( array( 'permissions', 'execute' ) as $method ) {
			$implementation = new ReflectionMethod( $handler, $method );
			if ( self::class === $implementation->getDeclaringClass()->getName() ) {
				return null;
			}
		}
		return $handler;
	}

	/**
	 * Distinguishes subscription, compatibility and activation problems.
	 * The URL is also included in the message because MCP may omit WP_Error data.
	 *
	 * @return WP_Error|null
	 */
	private function get_premium_requirement_error() {
		if ( ! nelio_content_is_subscribed() ) {
			$url = $this->get_upgrade_url();
			return new WP_Error(
				'nelio_content_premium_required',
				sprintf( 'This ability requires Nelio Content Premium. Subscribe at %s.', $url ),
				array(
					'status'      => 403,
					'reason'      => 'subscription_required',
					'upgrade_url' => $url,
				)
			);
		}
		if ( function_exists( 'nelio_content_premium_is_version_valid' ) && ! nelio_content_premium_is_version_valid() ) {
			return new WP_Error(
				'nelio_content_premium_update_required',
				'Update Nelio Content and Nelio Content Premium to matching versions to use this ability. Ask your site administrator if you cannot update plugins.',
				array(
					'status' => 403,
					'reason' => 'incompatible_versions',
				)
			);
		}
		if ( ! function_exists( 'nelio_content_premium_can_init' ) || ! nelio_content_premium_can_init() ) {
			return $this->get_activation_error();
		}
		return null;
	}

	/**
	 * Explains how an existing subscriber can enable the paid implementation.
	 *
	 * @return WP_Error
	 */
	private function get_activation_error() {
		return new WP_Error(
			'nelio_content_premium_activation_required',
			'Your subscription is active. Install and activate the matching Nelio Content Premium plugin to use this ability. Ask your site administrator if you cannot manage plugins.',
			array(
				'status'     => 403,
				'reason'     => 'activate_premium',
				'action_url' => admin_url( 'admin.php?page=nelio-content-account' ),
			)
		);
	}

	/**
	 * Builds the subscription link used by discovery and execution errors.
	 *
	 * @return string
	 */
	private function get_upgrade_url() {
		return add_query_arg(
			array(
				'utm_source'   => 'nelio-content',
				'utm_medium'   => 'mcp',
				'utm_campaign' => 'premium-abilities',
			),
			'https://neliosoftware.com/content/pricing/'
		);
	}
}
