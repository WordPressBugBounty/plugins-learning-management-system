<?php
/**
 * Addons integration.
 *
 * Wires the addon framework into the rest of the plugin: the addon data both
 * script bundles are localised with, the Addons admin submenu, and the
 * sequential course-flow lock on course progress items.
 *
 * Both products need every one of those, so it lives in core. Pro adds its
 * licence- and subscription-specific behaviour on top through the same hooks,
 * from its own class.
 *
 * @since 2.0.0
 * @since 1.6.11
 * @package Masteriyo\AddonsFramework
 */

namespace Masteriyo\AddonsFramework;

defined( 'ABSPATH' ) || exit;


use Masteriyo\Enums\CourseFlow;
use Masteriyo\Enums\CourseChildrenPostType;
use Masteriyo\Query\CourseProgressItemQuery;

/**
 * Addons integration class.
 *
 * @since 1.6.11
*/
class AddonsIntegration {

	/**
	 *
	 * Instance of addons class.
	 *
	 * @since 2.2.0
	 *
	 * @var \Masteriyo\AddonsFramework\Addons
	 * @since 1.6.11
	 *
	 * @var Masteriyo\Addons
	 *
	 */
	public $addons;

	/**
	 * Constructor.
	 *
	 * @since 2.2.0
	 *
	 * @param \Masteriyo\AddonsFramework\Addons $addons
	 */
	public function __construct( Addons $addons ) {
		$this->addons = $addons;
	}

	/**
	 * Initialize.
	 *
	 * @since 2.0.0
	 * @since 1.6.11
	 */
	public function init() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 2.0.0
	 * @since 1.6.11
	 */
	public function init_hooks() {
		add_filter( 'masteriyo_localized_admin_scripts', array( $this, 'localize_addons_data' ) );
		add_filter( 'masteriyo_localized_public_scripts', array( $this, 'localize_public_scripts' ) );
		add_filter( 'masteriyo_admin_submenus', array( $this, 'register_submenus' ) );
		add_filter( 'masteriyo_course_progress_item_data', array( $this, 'add_locked_status_to_course_progress_item' ), 10, 3 );

		// The lock above is a display cue. These two are the writes it stands in front of.
		add_filter( 'masteriyo_rest_check_course_progress_item_permission', array( $this, 'restrict_sequentially_locked_progress_item' ), 10, 5 );
		add_filter( 'masteriyo_rest_check_quiz_start_permission', array( $this, 'restrict_sequentially_locked_quiz_start' ), 10, 2 );
	}

	/**
	 * Localize addons data.
	 *
	 * @since 2.2.0
	 * @since 1.6.11
	 *
	 * @param array $data Localized data.
	 */
	public function localize_addons_data( $data ) {
		$addons_data = array_map(
			function( $slug ) {
				return $this->addons->get_data( $slug, true );
			},
			array_keys( $this->addons->get_all_addons() )
		);

		$addons_data = array_map(
			function( $addon ) {
				$addon_plan      = masteriyo_array_get( $addon, 'plan', '' );
				$locked          = empty( $addon ) ? false : ! $this->addons->is_allowed( masteriyo_array_get( $addon, 'slug' ) );
				$addon['locked'] = $locked;

				return $addon;
			},
			$addons_data
		);

		$addons_keys = wp_list_pluck( $addons_data, 'slug' );
		$addons_data = array_combine( $addons_keys, $addons_data );

		// Move content drip addon to the end of the array.
		// TODO Remove after number of addons are introduced.
		$addons_data += array_splice( $addons_data, array_search( 'content-drip', array_keys( $addons_data ), true ), 1 );
		$addons_data  = array_values( $addons_data );

		$active_integration_addons = array_filter(
			$addons_data,
			function( $addon ) {
				return $addon['active'] && 'integration' === $addon['addon_type'];
			}
		);

		if ( isset( $data['backend'] ) ) {
			$data['backend']['data']['addons']       = $addons_data;
			$data['backend']['data']['integrations'] = masteriyo_bool_to_string( ! empty( $active_integration_addons ) );
		}

		return $data;
	}

	/**
	 * Localize public scripts
	 *
	 * @since 2.3.5
	 * @since 1.6.11
	 *
	 * @param array $data Localized data.
	 */
	public function localize_public_scripts( $data ) {
		$addons_data = array_map(
			function( $slug ) {
				return $this->addons->get_data( $slug, true );
			},
			array_keys( $this->addons->get_all_addons() )
		);

		$active_integration_addons = array_filter(
			$addons_data,
			function( $addon ) {
				return $addon['active'] && 'integration' === $addon['addon_type'];
			}
		);

		$data = masteriyo_parse_args(
			$data,
			array(
				'learn'   => array(
					'data' => array(
						'addons'       => $addons_data,
						'integrations' => masteriyo_bool_to_string( ! empty( $active_integration_addons ) ),
					),
				),
				'account' => array(
					'data' => array(
						'addons'       => $addons_data,
						'integrations' => masteriyo_bool_to_string( ! empty( $active_integration_addons ) ),
					),
				),
			)
		);

		return $data;
	}

	/**
	 * Register sub menus.
	 *
	 * @since 2.3.3
	 * @since 1.6.11
	 *
	 * @param array $submenus
	 * @return array
	 */
	public function register_submenus( $submenus ) {
		$submenus['add-ons'] = array(
			'page_title' => esc_html__( 'Addons', 'learning-management-system' ),
			'menu_title' => esc_html__( 'Addons', 'learning-management-system' ),
			'position'   => 95,
		);

		return $submenus;
	}

	/**
	 * Add locked status to course progress item like (lesson and quiz) for sequential.
	 *
	 * @since 2.5.6
	 *
	 * @param array $data The course progress item data.
	 * @param \Masteriyo\Models\CourseProgressItem $course_progress_item Course progress item object.
	* @param string $context Context.
	 */
	public function add_locked_status_to_course_progress_item( $data, $course_progress_item, $context ) {
		$data['locked'] = $this->is_sequentially_locked( $course_progress_item->get_course_id(), $course_progress_item->get_item_id() );

		return $data;
	}

	/**
	 * Whether a sequential course holds this item back because the one before it is incomplete.
	 *
	 * @since 3.4.3
	 *
	 * @param int $course_id Course ID.
	 * @param int $item_id Lesson, quiz or assignment ID.
	 * @param int $user_id Whose progress decides the lock; 0 means the current user.
	 *
	 * @return bool
	 */
	public function is_sequentially_locked( $course_id, $item_id, $user_id = 0 ) {
		$course = masteriyo_get_course( $course_id );

		if ( ! $course || CourseFlow::SEQUENTIAL !== $course->get_flow() ) {
			return false;
		}

		// Only allow bypass when in preview mode AND user may preview the course.
		$current_request = masteriyo_current_http_request();

		if ( masteriyo_is_course_preview_request(
			array(
				'mto-preview' => isset( $current_request['mto-preview'] ) ? $current_request['mto-preview'] : false,
				'course_id'   => $course_id,
			)
		) ) {
			return false;
		}

		$contents = array_values(
			array_filter(
				masteriyo_get_course_contents( $course ),
				function( $content ) {
					return CourseChildrenPostType::SECTION !== $content->get_post_type();
				}
			)
		);

		$current_index = null;

		foreach ( $contents as $index => $content ) {
			if ( $content->get_id() === absint( $item_id ) ) {
				$current_index = $index;
				break;
			}
		}

		// An item the published curriculum does not list — a draft, or one belonging to
		// another course — has no predecessor to measure against. It is held rather than
		// waved through, so knowing a draft item's ID does not pre-complete it. Items of a
		// deactivated addon never reach here: validate_course_progress_item() rejects them.
		if ( is_null( $current_index ) ) {
			return true;
		}

		if ( 0 === $current_index ) {
			return false;
		}

		$previous_content = $contents[ $current_index - 1 ];

		if ( is_user_logged_in() ) {
			$query = new CourseProgressItemQuery(
				array(
					'item_id' => $previous_content->get_id(),
					'user_id' => $user_id ? absint( $user_id ) : masteriyo_get_current_user_id(),
					'limit'   => 1,
				)
			);

			$previous_course_progress_item = current( $query->get_course_progress_items() );
		} else {
			/** @var \Masteriyo\Session\Session $session */
			$session = masteriyo( 'session' );

			$previous_course_progress_items = $session->get( 'course_progress_items', array() );

			if ( isset( $previous_course_progress_items[ $previous_content->get_id() ] ) ) {
				/** @var \Masteriyo\Models\CourseProgressItem $previous_course_progress_item */
				$previous_course_progress_item = masteriyo( 'course-progress-item' );
				$previous_course_progress_item->set_item_id( $previous_content->get_id() );
				$previous_course_progress_item->set_item_type( str_replace( 'mto-', '', $previous_content->get_post_type() ) );
				$previous_course_progress_item->set_completed( $previous_course_progress_items[ $previous_content->get_id() ]['completed'] );
			}
		}

		return empty( $previous_course_progress_item ) || ! $previous_course_progress_item->get_completed();
	}

	/**
	 * Refuse progress on a course item a sequential course has not released yet.
	 *
	 * The player draws the lock from serialized progress, so a request sent straight to
	 * the endpoint walked past it and completed a later quiz first (issue #970).
	 *
	 * @since 3.4.3
	 *
	 * @param bool|\WP_Error $can Whether the progress item may be recorded.
	 * @param int $item_id Lesson, quiz or assignment ID.
	 * @param \WP_REST_Request|null $request Request object.
	 * @param bool|null $completed The completion the write will record.
	 * @param int $user_id Whose progress the write records.
	 *
	 * @return bool|\WP_Error
	 */
	public function restrict_sequentially_locked_progress_item( $can, $item_id, $request = null, $completed = null, $user_id = 0 ) {
		if ( true !== $can || $item_id <= 0 ) {
			return $can;
		}

		// Staff completing a quiz on a learner's behalf set the order aside, the same way
		// they set the attempt requirement aside, and the same way an administrator already
		// does by never reaching this filter. The predicate checks the quiz is theirs.
		if ( $request && masteriyo_check_manual_quiz_attempt( $request ) ) {
			return $can;
		}

		$course_id = get_post_meta( absint( $item_id ), '_course_id', true );

		if ( ! $this->is_sequentially_locked( $course_id, $item_id, $user_id ) ) {
			return $can;
		}

		return new \WP_Error(
			'masteriyo_rest_course_flow_locked',
			__( 'Complete the previous course item first.', 'learning-management-system' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * Refuse to start a quiz a sequential course has not released yet.
	 *
	 * @since 3.4.3
	 *
	 * @param bool|\WP_Error $can_start Whether the quiz can be started.
	 * @param \Masteriyo\Models\Quiz $quiz Quiz object.
	 *
	 * @return bool|\WP_Error
	 */
	public function restrict_sequentially_locked_quiz_start( $can_start, $quiz ) {
		if ( true !== $can_start ) {
			return $can_start;
		}

		if ( ! $this->is_sequentially_locked( $quiz->get_course_id(), $quiz->get_id() ) ) {
			return $can_start;
		}

		return new \WP_Error(
			'masteriyo_rest_course_flow_locked',
			__( 'Complete the previous course item first.', 'learning-management-system' ),
			array( 'status' => 403 )
		);
	}
}
