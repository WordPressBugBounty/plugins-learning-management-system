<?php
/**
 * Manages migration session lifecycle and per-step progress state.
 *
 * @since x.x.x
 * @package Masteriyo\Addons\MigrationTool
 */

namespace Masteriyo\Addons\MigrationTool;

defined( 'ABSPATH' ) || exit;

class MigrationSession {

	const SESSION_PREFIX    = 'masteriyo_migration_session_';
	const STEP_STATE_PREFIX = 'masteriyo_migration_step_';

	/**
	 * Create a new migration session. Returns the session ID.
	 *
	 * Prunes old terminal sessions before inserting so wp_options does not
	 * accumulate unbounded rows across repeated migration runs.
	 *
	 * @since x.x.x
	 * @param string   $lms_slug LMS plugin slug.
	 * @param string[] $steps    Ordered step names.
	 * @return string Session ID.
	 */
	public static function create( string $lms_slug, array $steps ): string {
		static::cleanup_terminal_sessions();

		$session_id = substr( md5( uniqid( '', true ) ), 0, 16 );

		add_option(
			self::SESSION_PREFIX . $session_id,
			array(
				'lms_slug'     => $lms_slug,
				'status'       => 'running',
				'current_step' => $steps[0],
				'steps'        => $steps,
				'started_at'   => time(),
				'completed_at' => null,
			),
			'',
			'no' // NOT autoloaded
		);

		return $session_id;
	}

	/**
	 * Retrieve session data by ID.
	 *
	 * Always bypasses the WordPress object cache before reading so that
	 * a running background job immediately sees status changes (e.g.
	 * cancel) written by a concurrent HTTP request.
	 *
	 * @since x.x.x
	 * @param string $session_id Session ID.
	 * @return array|null
	 */
	public static function get( string $session_id ): ?array {
		wp_cache_delete( self::SESSION_PREFIX . $session_id, 'options' );
		$data = get_option( self::SESSION_PREFIX . $session_id, null );
		return is_array( $data ) ? $data : null;
	}

	/**
	 * Merge changes into an existing session.
	 *
	 * @since x.x.x
	 * @param string $session_id Session ID.
	 * @param array  $changes    Key-value pairs to update.
	 */
	public static function update( string $session_id, array $changes ): void {
		$data = static::get( $session_id );
		if ( $data ) {
			update_option( self::SESSION_PREFIX . $session_id, array_merge( $data, $changes ) );
		}
	}

	/**
	 * Get per-step cursor/progress state.
	 *
	 * Step states are stored inline in the session option under `step_states` to avoid
	 * one get_option() DB call per step on every status poll. Falls back to the legacy
	 * per-step option key so sessions created before this change continue to work.
	 *
	 * @since x.x.x
	 * @param string $session_id Session ID.
	 * @param string $step       Step name.
	 * @return array{ total: int, last_cursor: int, completed: int, failed: int[] }
	 */
	public static function get_step_state( string $session_id, string $step ): array {
		$default = array(
			'total'       => 0,
			'last_cursor' => 0,
			'completed'   => 0,
			'failed'      => array(),
		);

		$session = static::get( $session_id );
		if ( $session && isset( $session['step_states'][ $step ] ) ) {
			return $session['step_states'][ $step ];
		}

		// Legacy fallback: sessions created before step states were merged into the session option.
		$legacy = get_option( self::STEP_STATE_PREFIX . $session_id . '_' . $step, null );
		return is_array( $legacy ) ? $legacy : $default;
	}

	/**
	 * Save per-step cursor/progress state into the session option (single DB write).
	 *
	 * Stores all step states under session['step_states'] so get_status() reads the
	 * entire state in one get_option() call instead of one per step.
	 *
	 * @since x.x.x
	 * @param string $session_id Session ID.
	 * @param string $step       Step name.
	 * @param array  $state      State array to persist.
	 */
	public static function save_step_state( string $session_id, string $step, array $state ): void {
		$session = static::get( $session_id );
		if ( ! $session ) {
			return;
		}

		$session['step_states'][ $step ] = $state;
		update_option( self::SESSION_PREFIX . $session_id, $session );
	}

	/**
	 * Find the most recent completed session.
	 *
	 * @since x.x.x
	 * @return array{ session_id: string, session: array }|null
	 */
	public static function get_last_completed(): ?array {
		global $wpdb;

		$prefix = $wpdb->esc_like( self::SESSION_PREFIX ) . '%';
		$rows   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_id DESC",
				$prefix
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		foreach ( $rows as $row ) {
			$data = maybe_unserialize( $row['option_value'] );
			if ( ! is_array( $data ) || 'completed' !== ( $data['status'] ?? '' ) ) {
				continue;
			}
			$session_id = substr( $row['option_name'], strlen( self::SESSION_PREFIX ) );
			return array(
				'session_id' => $session_id,
				'session'    => $data,
			);
		}

		return null;
	}

	/**
	 * Find the most recent non-terminal session (running or paused).
	 *
	 * Scans wp_options for session records and returns the newest one whose
	 * status is not completed, failed, or cancelled. Returns null when none exist.
	 *
	 * @since x.x.x
	 * @return array{ session_id: string, session: array }|null
	 */
	public static function get_active(): ?array {
		global $wpdb;

		$prefix = $wpdb->esc_like( self::SESSION_PREFIX ) . '%';
		$rows   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_id DESC",
				$prefix
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( empty( $rows ) ) {
			return null;
		}

		$terminal = array( 'completed', 'failed', 'cancelled' );

		foreach ( $rows as $row ) {
			$data = maybe_unserialize( $row['option_value'] );
			if ( ! is_array( $data ) ) {
				continue;
			}
			if ( in_array( $data['status'] ?? '', $terminal, true ) ) {
				continue;
			}
			$session_id = substr( $row['option_name'], strlen( self::SESSION_PREFIX ) );
			return array(
				'session_id' => $session_id,
				'session'    => $data,
			);
		}

		return null;
	}

	/**
	 * Delete all terminal sessions except the single most-recent completed one.
	 *
	 * Called on every session create so wp_options never grows unboundedly.
	 * We keep ONE completed session because the frontend banner reads it to show
	 * "last migration completed on …". Failed and cancelled sessions are always
	 * removed entirely since they carry no useful history.
	 *
	 * @since x.x.x
	 */
	private static function cleanup_terminal_sessions(): void {
		global $wpdb;

		$prefix = $wpdb->esc_like( self::SESSION_PREFIX ) . '%';
		$rows   = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_id DESC",
				$prefix
			),
			ARRAY_A
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$terminal       = array( 'completed', 'failed', 'cancelled' );
		$kept_completed = false;

		foreach ( $rows as $row ) {
			$data   = maybe_unserialize( $row['option_value'] );
			$status = is_array( $data ) ? ( $data['status'] ?? '' ) : '';

			if ( ! in_array( $status, $terminal, true ) ) {
				continue; // Active or paused — never touch.
			}

			// Preserve the newest completed session for the "last migration" banner.
			if ( 'completed' === $status && ! $kept_completed ) {
				$kept_completed = true;
				continue;
			}

			$session_id = substr( $row['option_name'], strlen( self::SESSION_PREFIX ) );
			static::cleanup( $session_id );
		}
	}

	/**
	 * Delete all options for a session (session record + all step states).
	 *
	 * @since x.x.x
	 * @param string $session_id Session ID.
	 */
	public static function cleanup( string $session_id ): void {
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$wpdb->esc_like( self::SESSION_PREFIX . $session_id ),
				$wpdb->esc_like( self::STEP_STATE_PREFIX . $session_id . '_' ) . '%'
			)
		); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}
