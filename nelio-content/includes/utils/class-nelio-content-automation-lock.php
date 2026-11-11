<?php
/**
 * Coordinates cron and interactive executions of social automation operations.
 */

defined( 'ABSPATH' ) || exit;

/** Owns an expiring option lock; stale owners cannot delete a replacement lock. */
class Nelio_Content_Automation_Lock {

	/** @var string */
	private $name;

	/** @var string */
	private $value;

	/**
	 * Acquires the lock without waiting or retrying a Cloud operation.
	 *
	 * @param string $operation Operation identifier.
	 * @return self|WP_Error
	 */
	public static function acquire( $operation ) {
		$lock        = new self();
		$lock->name  = 'nc_automation_lock_' . md5( $operation );
		$lock->value = ( time() + HOUR_IN_SECONDS ) . ':' . wp_generate_uuid4();
		$previous    = get_option( $lock->name );
		if ( is_string( $previous ) && (int) $previous < time() ) {
			$lock->delete_value( $previous );
		}
		if ( ! add_option( $lock->name, $lock->value, '', false ) ) {
			return new WP_Error( 'nelio_content_automation_busy', _x( 'This social automation is already running. Inspect the calendar before trying again later.', 'user', 'nelio-content' ) );
		}
		return $lock;
	}

	/**
	 * Releases only the lock owned by this execution.
	 *
	 * @return void
	 */
	public function release() {
		$this->delete_value( $this->value );
	}

	/**
	 * Compares and deletes atomically, including during stale lock recovery.
	 *
	 * @param string $value Expected value.
	 * @return void
	 */
	private function delete_value( $value ) {
		/** @var wpdb $wpdb */
		global $wpdb;
		// An atomic comparison prevents an expired execution from releasing a newer lock.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$wpdb->options,
			array(
				'option_name'  => $this->name,
				'option_value' => $value,
			)
		);
		wp_cache_delete( $this->name, 'options' );
	}
}
