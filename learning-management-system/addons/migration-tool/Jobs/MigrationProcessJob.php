<?php
/**
 * Background batch processor for LMS migration.
 * Self-queues via Action Scheduler until a step is complete, then advances.
 *
 * @since x.x.x
 * @package Masteriyo\Addons\MigrationTool
 */

namespace Masteriyo\Addons\MigrationTool\Jobs;

use Masteriyo\Addons\MigrationTool\MigrationSession;

defined( 'ABSPATH' ) || exit;

class MigrationProcessJob {

	/**
	 * Action Scheduler hook name for this job.
	 *
	 * @since x.x.x
	 */
	const HOOK = 'masteriyo/migration/process';

	/**
	 * Items processed per batch. Balances throughput against per-item memory cost.
	 *
	 * @since x.x.x
	 */
	const BATCH_SIZE = 100;

	/**
	 * Register the Action Scheduler hook.
	 *
	 * @since x.x.x
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( self::HOOK, array( $this, 'handle' ), 10, 4 );
		add_action( 'action_scheduler_failed_action', array( $this, 'handle_failed_action' ), 10, 2 );
	}

	/**
	 * Re-queue a migration job when AS marks it as failed (e.g. 600-second hard timeout).
	 *
	 * Without this, a timed-out job leaves the session permanently stuck in 'running'
	 * with no pending AS action to continue it.
	 *
	 * @since x.x.x
	 * @param int $action_id Failed AS action ID.
	 * @param int $timeout   Timeout value (seconds) that caused the failure.
	 *
	 * @return void
	 */
	public function handle_failed_action( int $action_id, int $timeout ): void {
		$action = \ActionScheduler::store()->fetch_by_id( $action_id );
		if ( ! $action || $action->get_hook() !== self::HOOK ) {
			return;
		}

		$args       = $action->get_args();
		$session_id = $args[0] ?? '';
		$lms_slug   = $args[1] ?? '';
		$step       = $args[2] ?? '';

		if ( ! $session_id || ! $lms_slug || ! $step ) {
			return;
		}

		$session = MigrationSession::get( $session_id );
		if ( ! $session || 'running' !== $session['status'] ) {
			return;
		}

		// Use the last successfully saved cursor — may be ahead of the failed job's cursor.
		$state  = MigrationSession::get_step_state( $session_id, $step );
		$cursor = $state['last_cursor'] ?? 0;

		masteriyo_get_logger()->warning(
			sprintf(
				'Migration [%s] step "%s": AS job %d failed after %ds — re-queuing from cursor %d.',
				$lms_slug,
				$step,
				$action_id,
				$timeout,
				$cursor
			),
			array( 'source' => 'migration-tool' )
		);

		as_enqueue_async_action(
			self::HOOK,
			array( $session_id, $lms_slug, $step, $cursor ),
			'masteriyo-migration-' . $session_id,
			true // prevent a duplicate recovery action if one is already pending
		);
	}

	/**
	 * Process all batches for a step within this AS job execution.
	 *
	 * Loops through batches until the step is exhausted OR a PHP resource limit
	 * (time/memory) is hit. On limit: re-queues itself via AS so the next cron
	 * cycle continues from the saved cursor. On exhaustion: advances to the next
	 * step via a new AS action so each step runs in its own PHP process (prevents
	 * recursive call chains). On loopback-blocked hosts (e.g. LocalWP), the
	 * spawn_cron() call in get_status() nudges the AS runner for each subsequent step.
	 *
	 * @since x.x.x
	 * @param string $session_id Session ID.
	 * @param string $lms_slug   LMS plugin slug.
	 * @param string $step       Current step name.
	 * @param int    $cursor     Last processed ID (0 = first batch).
	 *
	 * @return void
	 */
	public function handle( string $session_id, string $lms_slug, string $step, int $cursor = 0 ): void {
		global $wpdb;

		// Non-blocking per-session mutex; MySQL auto-releases on connection close.
		$lock = 'masteriyo_mig_' . $session_id;
		if ( ! (int) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 0)', $lock ) ) ) {
			return; // Another process is already handling this session.
		}

		try {
			$this->run_handle( $session_id, $lms_slug, $step, $cursor );
		} finally {
			$wpdb->get_var( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
		}
	}

	/**
	 * Inner implementation of handle() — called exclusively through the GET_LOCK wrapper.
	 *
	 * @since x.x.x
	 * @param string $session_id Session ID.
	 * @param string $lms_slug   LMS plugin slug.
	 * @param string $step       Current step name.
	 * @param int    $cursor     Last processed ID (0 = first batch).
	 *
	 * @return void
	 */
	private function run_handle( string $session_id, string $lms_slug, string $step, int $cursor = 0 ): void {
		global $wpdb;

		$session = MigrationSession::get( $session_id );
		if ( ! $session || 'running' !== $session['status'] ) {
			return; // Cancelled or not running — stop without re-queuing.
		}

		$state = MigrationSession::get_step_state( $session_id, $step );
		// DB is authoritative: a stale pending action may carry an older cursor in its args.
		// Advancing to last_cursor here matches what handle_failed_action() already does.
		$cursor   = max( $cursor, $state['last_cursor'] );
		$migrator = masteriyo( 'migration-tool.registry' )->get( $lms_slug );

		// Remove duplicate failed IDs and build a skip-set.
		// Self-cleaning steps ignore $cursor, so a failed item stays in the source table and
		// reappears every batch — the skip-set prevents re-recording the same failure forever.
		$state['failed'] = array_values( array_unique( (array) $state['failed'] ) );
		$failed_set      = array_fill_keys( $state['failed'], true );

		// Count now if not pre-seeded at session creation (legacy sessions).
		if ( 0 === $state['total'] && 0 === $cursor && 0 === $state['completed'] ) {
			$state['total'] = $migrator->count_source_items( $step );
			MigrationSession::save_step_state( $session_id, $step, $state );

			if ( 0 === $state['total'] ) {
				masteriyo_get_logger()->info(
					sprintf( 'Migration [%s] step "%s": skipped — no source items found.', $lms_slug, $step ),
					array( 'source' => 'migration-tool' )
				);
				$this->advance_step( $session_id, $lms_slug, $step, $migrator );
				return;
			}
		}

		if ( 0 === $cursor && 0 === $state['completed'] ) {
			masteriyo_get_logger()->info(
				sprintf( 'Migration [%s] step "%s": started — %d item(s) to migrate.', $lms_slug, $step, $state['total'] ),
				array( 'source' => 'migration-tool' )
			);
			$this->maybe_activate_equivalent_addons( $migrator, $step );
		}

		$start         = microtime( true );
		$memory_limit  = wp_convert_hr_to_bytes( ini_get( 'memory_limit' ) ?? '256M' );
		$php_max_exec  = (int) ini_get( 'max_execution_time' ); // 0 = unlimited (AS called set_time_limit(0))
		$as_time_limit = (int) apply_filters( 'action_scheduler_queue_runner_time_limit', 30 );
		// Use the tighter of the two limits so we always re-queue before PHP or AS kills the process.
		$time_limit = (int) apply_filters(
			'masteriyo_migration_run_time_limit',
			$php_max_exec > 0
				? max( min( $as_time_limit - 5, $php_max_exec - 10 ), 20 )
				: max( $as_time_limit - 5, 25 )
		);
		$limit_hit  = false;

		while ( true ) {
			$batch_size = (int) apply_filters( 'masteriyo_migration_batch_size', self::BATCH_SIZE );
			$ids        = $migrator->get_source_ids( $step, $batch_size, $cursor, array_keys( $failed_set ) );

			if ( empty( $ids ) ) {
				break; // Source exhausted — step complete.
			}

			// All returned IDs are already in the skip-set — nothing left to process.
			// Save state first so advance_step() logs an accurate failed count.
			$processable = array_filter( $ids, fn( $id ) => ! isset( $failed_set[ (int) $id ] ) );
			if ( empty( $processable ) ) {
				MigrationSession::save_step_state( $session_id, $step, $state );
				break;
			}

			$wpdb->query( 'START TRANSACTION' );

			foreach ( $ids as $id ) {
				// Skip previously failed items without retrying.
				if ( isset( $failed_set[ (int) $id ] ) ) {
					$cursor = (int) $id;
					continue;
				}

				// Check BEFORE each item so a slow batch never runs past the time window.
				if ( memory_get_usage( true ) / $memory_limit > (float) apply_filters( 'masteriyo_migration_memory_threshold', 0.85 ) ||
					( microtime( true ) - $start ) > $time_limit ) {
					$limit_hit = true;
					break;
				}

				$savepoint = 'sp_' . (int) $id;
				$wpdb->query( "SAVEPOINT {$savepoint}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

				try {
					$migrator->migrate_item( $step, (int) $id );
					++$state['completed'];
					$wpdb->query( "RELEASE SAVEPOINT {$savepoint}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				} catch ( \Throwable $e ) {
					$wpdb->query( "ROLLBACK TO SAVEPOINT {$savepoint}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$state['failed'][]       = (int) $id;
					$failed_set[ (int) $id ] = true;
					masteriyo_get_logger()->error(
						sprintf( 'Migration [%s] step "%s" item %d: %s', $lms_slug, $step, $id, $e->getMessage() ),
						array( 'source' => 'migration-tool' )
					);
				}

				$cursor = (int) $id;
			}

			$wpdb->query( 'COMMIT' );

			$state['last_cursor'] = $cursor;
			MigrationSession::save_step_state( $session_id, $step, $state );

			// Respect pause/cancel between batches.
			$session = MigrationSession::get( $session_id );
			if ( ! $session || 'running' !== $session['status'] ) {
				return;
			}

			if ( $limit_hit ) {
				break;
			}
		}

		if ( $limit_hit ) {
			// Hit resource limits mid-step — re-queue to continue from current cursor.
			// $unique = false: the current action is still "running" (same hook+group),
			// and AS uniqueness checks hook+group only — passing true would block this enqueue.
			// GET_LOCK in handle() prevents concurrent duplicate processing.
			as_enqueue_async_action(
				self::HOOK,
				array( $session_id, $lms_slug, $step, $cursor ),
				'masteriyo-migration-' . $session_id
			);
		} else {
			// Step fully processed — run bulk post-step SQL (e.g. status recalculation).
			try {
				$migrator->finalize_step( $step );
			} catch ( \Throwable $e ) {
				masteriyo_get_logger()->warning(
					sprintf( 'Migration [%s] step "%s" finalize_step: %s', $lms_slug, $step, $e->getMessage() ),
					array( 'source' => 'migration-tool' )
				);
			}
			$this->advance_step( $session_id, $lms_slug, $step, $migrator );
		}
	}

	/**
	 * Activate Masteriyo Pro addons equivalent to active source LMS addons for the given step.
	 *
	 * No-op when Masteriyo Pro is not active — \Masteriyo\Pro\Addons won't exist in that case.
	 * Silently skips slugs that are already active or not installed in this Pro build.
	 *
	 * @since x.x.x
	 * @param \Masteriyo\Addons\MigrationTool\Contracts\MigratorInterface $migrator Resolved migrator.
	 * @param string                                                        $step     Current step name.
	 * @return void
	 */
	private function maybe_activate_equivalent_addons( $migrator, string $step ): void {
		$slugs = $migrator->get_addons_to_activate( $step );

		if ( empty( $slugs ) || ! class_exists( '\Masteriyo\Pro\Addons' ) ) {
			return;
		}

		$addons = new \Masteriyo\Pro\Addons();

		foreach ( $slugs as $slug ) {
			if ( ! $addons->is_addon( $slug ) ) {
				masteriyo_get_logger()->warning(
					sprintf( 'Migration: cannot activate addon "%s" — not installed in this build.', $slug ),
					array( 'source' => 'migration-tool' )
				);
				continue;
			}

			if ( $addons->is_active( $slug ) ) {
				continue;
			}

			$addons->set_active( $slug );

			masteriyo_get_logger()->info(
				sprintf( 'Migration: auto-activated Masteriyo addon "%s" for step "%s".', $slug, $step ),
				array( 'source' => 'migration-tool' )
			);
		}
	}

	/**
	 * Advance to the next step with items, or mark session complete.
	 *
	 * Non-empty steps are queued as a new AS action so each step runs in its own
	 * PHP process (prevents recursive call chains that can be killed mid-flight).
	 * Empty steps are skipped synchronously so they require no extra cron tick.
	 *
	 * @since x.x.x
	 * @param string                                                           $session_id Session ID.
	 * @param string                                                           $lms_slug   LMS plugin slug.
	 * @param string                                                           $step       Step that just finished.
	 * @param \Masteriyo\Addons\MigrationTool\Contracts\MigratorInterface|null $migrator   Resolved migrator (avoids extra container lookup).
	 *
	 * @return void
	 */
	private function advance_step( string $session_id, string $lms_slug, string $step, $migrator = null ): void {
		$session = MigrationSession::get( $session_id );
		$steps   = $session['steps'];
		$idx     = array_search( $step, $steps, true );

		// array_search returns false (not 0) when the step is not found.
		// false + 1 = 1, which would silently skip the first step instead of completing.
		if ( false === $idx ) {
			MigrationSession::update(
				$session_id,
				array(
					'status'       => 'completed',
					'completed_at' => time(),
				)
			);
			masteriyo_get_logger()->warning(
				sprintf( 'Migration session %s: step "%s" not found in steps list — marking complete.', $session_id, $step ),
				array( 'source' => 'migration-tool' )
			);
			return;
		}

		$state        = MigrationSession::get_step_state( $session_id, $step );
		$failed_count = count( $state['failed'] );

		masteriyo_get_logger()->info(
			sprintf(
				'Migration [%s] step "%s": complete — %d succeeded, %d failed.',
				$lms_slug,
				$step,
				$state['completed'],
				$failed_count
			),
			array( 'source' => 'migration-tool' )
		);

		if ( null === $migrator ) {
			$migrator = masteriyo( 'migration-tool.registry' )->get( $lms_slug );
		}

		// Walk forward through remaining steps. Totals were pre-saved at session
		// creation so we read from the DB instead of re-counting — no redundant
		// COUNT queries during job execution.
		$next_idx = $idx + 1;
		while ( isset( $steps[ $next_idx ] ) ) {
			$next       = $steps[ $next_idx ];
			$next_state = MigrationSession::get_step_state( $session_id, $next );
			$next_count = $next_state['total'];

			if ( $next_count > 0 ) {
				MigrationSession::update( $session_id, array( 'current_step' => $next ) );
				// $unique = false: the completing action is still "running" (same hook+group),
				// and AS uniqueness is hook+group — true would block scheduling the next step.
				as_enqueue_async_action(
					self::HOOK,
					array( $session_id, $lms_slug, $next, 0 ),
					'masteriyo-migration-' . $session_id
				);
				return;
			}

			// Zero-count step — already persisted at session creation; just advance.
			MigrationSession::update( $session_id, array( 'current_step' => $next ) );

			masteriyo_get_logger()->info(
				sprintf( 'Migration [%s] step "%s": skipped — no source items found.', $lms_slug, $next ),
				array( 'source' => 'migration-tool' )
			);

			++$next_idx;
		}

		// All remaining steps were empty (or there were none) — session is done.
		MigrationSession::update(
			$session_id,
			array(
				'status'       => 'completed',
				'completed_at' => time(),
			)
		);

		masteriyo_get_logger()->info(
			sprintf( 'Migration session %s [%s]: all steps complete.', $session_id, $lms_slug ),
			array( 'source' => 'migration-tool' )
		);
	}
}
