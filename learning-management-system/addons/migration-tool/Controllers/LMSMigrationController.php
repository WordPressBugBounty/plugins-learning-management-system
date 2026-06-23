<?php

/**
 * LMSMigrationController Class.
 *
 * Handles the migration of data from other WordPress LMS plugins to Masteriyo.
 *
 * @since 1.8.0
 * @package Masteriyo\Addons\MigrationTool\Controllers
 */

namespace Masteriyo\Addons\MigrationTool\Controllers;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Addons\MigrationTool\MigratorRegistry;
use Masteriyo\Helper\Permission;
use Masteriyo\PostType\PostType;
use Masteriyo\Addons\MigrationTool\Jobs\MigrationProcessJob;
use Masteriyo\Addons\MigrationTool\MigrationSession;
use Masteriyo\RestApi\Controllers\Version1\RestController;
use WP_Error;

/**
 * LMSMigrationController class.
 *
 * Resolves the requested LMS migrator from the MigratorRegistry and delegates
 * all migration work to it. No LMS-specific logic lives here.
 *
 * @since 1.8.0
 */
class LMSMigrationController extends RestController {

	/**
	 * Endpoint namespace.
	 *
	 * @since 1.8.0
	 *
	 * @var string
	 */
	protected $namespace = 'masteriyo/v1';

	/**
	 * Route base.
	 *
	 * @since 1.8.0
	 *
	 * @var string
	 */
	protected $rest_base = 'migrations';

	/**
	 * Permission class.
	 *
	 * @since 1.8.0
	 *
	 * @var \Masteriyo\Helper\Permission
	 */
	protected $permission = null;

	/**
	 * Migrator registry.
	 *
	 * @since x.x.x
	 *
	 * @var MigratorRegistry
	 */
	protected $registry;

	/**
	 * Constructor.
	 *
	 * @since 1.8.0
	 *
	 * @param Permission       $permission
	 * @param MigratorRegistry $registry
	 */
	public function __construct( Permission $permission, MigratorRegistry $registry ) {
		$this->permission = $permission;
		$this->registry   = $registry;
	}

	/**
	 * Register routes.
	 *
	 * @since 1.8.0
	 *
	 * @return void
	 */
	public function register_routes() {
		// POST /migrations/start — kick off a new background migration session.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/start',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'start_migration' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
				'args'                => array(
					'lms_name' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// GET /migrations/{session_id} — read-only status poll.
		// DELETE /migrations/{session_id} — cancel a running session.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<session_id>[a-f0-9]{16})',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_status' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'cancel_migration' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/lms',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_other_LMSs' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
				),
			)
		);

		// GET /migrations/active — returns the most recent non-terminal session or null.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/active',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_active_session' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
			)
		);
	}

	/**
	 * Checks if the user has permission to import items.
	 *
	 * @since 1.8.0
	 *
	 * @param  \WP_REST_Request $request Full details about the request.
	 * @return \WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {
		if ( is_null( $this->permission ) ) {
			return new \WP_Error(
				'masteriyo_null_permission',
				__( 'Sorry, the permission object for this resource is null.', 'learning-management-system' )
			);
		}

		$instructor = masteriyo_get_current_instructor();
		if ( $instructor && ! $instructor->is_active() ) {
			return new \WP_Error(
				'masteriyo_rest_user_not_approved',
				__( 'Sorry, you are not approved by the manager.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		if ( ! $this->permission->rest_check_post_permissions( PostType::COURSE, 'create' ) ) {
			return new \WP_Error(
				'masteriyo_rest_cannot_create',
				__( 'Sorry, you are not allowed to import courses.', 'learning-management-system' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		return true;
	}

	/**
	 * POST /migrations/start — create a new migration session and enqueue the first batch.
	 *
	 * @since x.x.x
	 * @param \WP_REST_Request $request
	 * @return WP_Error|\WP_REST_Response
	 */
	public function start_migration( $request ) {
		$lms_slug = sanitize_text_field( $request->get_param( 'lms_name' ) );

		if ( empty( $lms_slug ) || ! $this->registry->has( $lms_slug ) ) {
			return new WP_Error(
				'migration_invalid_lms',
				__( 'Please select a valid LMS.', 'learning-management-system' ),
				array( 'status' => 400 )
			);
		}

		$active = MigrationSession::get_active();
		if ( $active ) {
			return new WP_Error(
				'migration_already_running',
				__( 'A migration is already in progress. Please wait for it to finish or cancel it before starting a new one.', 'learning-management-system' ),
				array( 'status' => 409 )
			);
		}

		$migrator   = $this->registry->get( $lms_slug );
		$steps      = $migrator->get_steps();
		$session_id = MigrationSession::create( $lms_slug, $steps );

		// Pre-count every step and persist the states immediately so the status
		// endpoint returns correct values from the very first poll — even on page
		// refresh before the background job has run. Zero-count steps are already
		// marked as skipped; the AS job is queued only for the first step with data.
		$first_nonempty = null;
		foreach ( $steps as $step ) {
			try {
				$count = $migrator->count_source_items( $step );
			} catch ( \Throwable $e ) {
				$count = 0;
			}
			MigrationSession::save_step_state(
				$session_id,
				$step,
				array(
					'total'       => $count,
					'last_cursor' => 0,
					'completed'   => 0,
					'failed'      => array(),
				)
			);
			if ( null === $first_nonempty && $count > 0 ) {
				$first_nonempty = $step;
			}
		}

		// Nothing to migrate — complete immediately without touching the job queue.
		if ( null === $first_nonempty ) {
			MigrationSession::update(
				$session_id,
				array(
					'status'       => 'completed',
					'completed_at' => time(),
				)
			);
			masteriyo_get_logger()->info(
				sprintf( 'Migration session %s [%s]: no source data found — completed immediately.', $session_id, $migrator->get_label() ),
				array( 'source' => 'migration-tool' )
			);
			return rest_ensure_response(
				array(
					'session_id' => $session_id,
					'status'     => 'completed',
				)
			);
		}

		// Advance current_step past any leading zero-count steps.
		MigrationSession::update( $session_id, array( 'current_step' => $first_nonempty ) );

		masteriyo_get_logger()->info(
			sprintf(
				'Migration session %s started for "%s" — first step with data: "%s".',
				$session_id,
				$migrator->get_label(),
				$first_nonempty
			),
			array( 'source' => 'migration-tool' )
		);

		as_enqueue_async_action(
			MigrationProcessJob::HOOK,
			array( $session_id, $lms_slug, $first_nonempty, 0 ),
			'masteriyo-migration-' . $session_id,
			true
		);

		return rest_ensure_response(
			array(
				'session_id' => $session_id,
				'status'     => 'running',
			)
		);
	}

	/**
	 * GET /migrations/{session_id} — return current session state and per-step progress.
	 *
	 * @since x.x.x
	 * @param \WP_REST_Request $request
	 * @return WP_Error|\WP_REST_Response
	 */
	public function get_status( $request ) {
		$session_id = sanitize_text_field( $request->get_param( 'session_id' ) );
		$session    = MigrationSession::get( $session_id );

		if ( ! $session ) {
			return new WP_Error(
				'migration_session_not_found',
				__( 'Migration session not found.', 'learning-management-system' ),
				array( 'status' => 404 )
			);
		}

		$step_data       = array();
		$total_items     = 0;
		$total_done      = 0;
		$total_failed    = 0;
		$step_weight_sum = 0.0;
		$non_empty_steps = 0;

		$is_completed     = 'completed' === $session['status'];
		$current_step_idx = array_search( $session['current_step'], $session['steps'], true );
		if ( false === $current_step_idx ) {
			$current_step_idx = count( $session['steps'] );
		}

		$default_step_state = array(
			'total'       => 0,
			'last_cursor' => 0,
			'completed'   => 0,
			'failed'      => array(),
		);

		foreach ( $session['steps'] as $step_idx => $step ) {
			// Prefer step states embedded in the session option (new format — 1 DB read total).
			// Fall back to per-step option lookup for legacy in-flight sessions.
			if ( isset( $session['step_states'][ $step ] ) ) {
				$state = $session['step_states'][ $step ];
			} else {
				$state = MigrationSession::get_step_state( $session_id, $step );
			}
			$step_passed = $is_completed || ( $step_idx < $current_step_idx );

			if ( $state['total'] > 0 ) {
				$safe_completed = min( $state['completed'], $state['total'] );
				// Passed steps contribute their full weight; current/future steps use actual completed count.
				$pct              = $step_passed ? 100 : (int) round( $safe_completed / $state['total'] * 100 );
				$step_weight_sum += $step_passed ? 1.0 : $safe_completed / $state['total'];
				++$non_empty_steps;
			} else {
				$pct = $step_passed ? 100 : 0; // zero-item steps don't affect overall %.
			}

			$step_data[ $step ] = array(
				'total'     => $state['total'],
				'completed' => min( $state['completed'], $state['total'] ),
				'failed'    => count( $state['failed'] ),
				'offset'    => $state['completed'], // kept for frontend compat; reflects items processed
				'pct'       => $pct,
			);

			$total_items  += $state['total'];
			$total_done   += $state['completed'];
			$total_failed += count( $state['failed'] );
		}

		// Step-equal weighting: each non-empty step contributes offset/total equally
		// so large steps (e.g. 50 000 orders) can't dominate the progress bar.
		$overall_pct = $non_empty_steps > 0
			? (int) floor( $step_weight_sum / $non_empty_steps * 100 )
			: ( $is_completed ? 100 : 0 );

		// Never show 100 % until the session is truly completed.
		if ( ! $is_completed && $overall_pct >= 100 ) {
			$overall_pct = 99;
		}
		$elapsed = $session['started_at'] ? ( time() - (int) $session['started_at'] ) : 0;

		// Nudge WP-Cron so pending AS jobs start promptly on hosts where loopback
		// requests are throttled (e.g. TasteWP). Non-blocking — adds no latency.
		if ( 'running' === $session['status'] ) {
			spawn_cron();
		}

		return rest_ensure_response(
			array(
				'session_id'      => $session_id,
				'status'          => $session['status'],
				'lms_slug'        => $session['lms_slug'],
				'current_step'    => $session['current_step'],
				'elapsed_seconds' => $elapsed,
				'steps'           => $step_data,
				'overall_pct'     => $overall_pct,
				'failed_total'    => $total_failed,
			)
		);
	}

	/**
	 * DELETE /migrations/{session_id} — mark the session cancelled; job engine stops at next status check.
	 *
	 * @since x.x.x
	 * @param \WP_REST_Request $request
	 * @return WP_Error|\WP_REST_Response
	 */
	public function cancel_migration( $request ) {
		$session_id = sanitize_text_field( $request->get_param( 'session_id' ) );
		$session    = MigrationSession::get( $session_id );

		if ( ! $session ) {
			return new WP_Error(
				'migration_session_not_found',
				__( 'Migration session not found.', 'learning-management-system' ),
				array( 'status' => 404 )
			);
		}

		$terminal = array( 'completed', 'failed', 'cancelled' );
		if ( in_array( $session['status'] ?? '', $terminal, true ) ) {
			return new WP_Error(
				'migration_already_terminal',
				__( 'This migration is already completed, failed, or cancelled.', 'learning-management-system' ),
				array( 'status' => 400 )
			);
		}

		// Cancel any pending AS actions for this session immediately.
		as_unschedule_all_actions( '', array(), 'masteriyo-migration-' . $session_id );

		MigrationSession::update( $session_id, array( 'status' => 'cancelled' ) );

		// Cancel any additional active sessions left from previous aborted runs.
		$limit = 10;
		while ( $limit-- > 0 ) {
			$extra = MigrationSession::get_active();
			if ( ! $extra ) {
				break;
			}
			MigrationSession::update( $extra['session_id'], array( 'status' => 'cancelled' ) );
		}

		return rest_ensure_response( array( 'status' => 'cancelled' ) );
	}

	/**
	 * Retrieves a list of installed LMS plugins available for migration.
	 *
	 * Returns only LMS plugins that are currently active, using is_plugin_active()
	 * so network-activated plugins on Multisite are detected correctly.
	 *
	 * @since 1.8.0
	 *
	 * @param \WP_REST_Request $request The request object.
	 * @return \WP_REST_Response
	 */
	public function get_other_LMSs( $request ) {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$data = array();

		foreach ( $this->registry->all() as $migrator ) {
			$data[] = array(
				'name'   => $migrator->get_slug(),
				'label'  => $migrator->get_label(),
				'steps'  => $migrator->get_steps(),
				'active' => is_plugin_active( $migrator->get_plugin_file() ),
			);
		}

		return rest_ensure_response( array( 'data' => $data ) );
	}

	/**
	 * GET /migrations/active — return the most recent non-terminal session with full progress.
	 *
	 * Returns HTTP 200 with `data: null` when no active session exists so the
	 * frontend can distinguish "no session" from an API error.
	 *
	 * @since x.x.x
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function get_active_session( $request ) {
		$active_data = null;
		$active      = MigrationSession::get_active();

		if ( $active ) {
			$status_request = new \WP_REST_Request( 'GET' );
			$status_request->set_param( 'session_id', $active['session_id'] );
			$active_data = $this->get_status( $status_request )->get_data();
		}

		$last_completed_data = null;
		$last                = MigrationSession::get_last_completed();

		if ( $last ) {
			$lms_slug  = $last['session']['lms_slug'] ?? '';
			$lms_label = '';

			if ( $lms_slug && $this->registry->has( $lms_slug ) ) {
				$lms_label = $this->registry->get( $lms_slug )->get_label();
			}

			$last_completed_data = array(
				'lms_slug'     => $lms_slug,
				'lms_label'    => $lms_label,
				'completed_at' => $last['session']['completed_at'] ?? null,
			);
		}

		return rest_ensure_response(
			array(
				'data'           => $active_data,
				'last_completed' => $last_completed_data,
			)
		);
	}
}
