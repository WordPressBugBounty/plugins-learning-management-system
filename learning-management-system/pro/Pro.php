<?php
/**
 * Masteriyo Pro class.
 *
 * @since 1.6.11
 * @package Masteriyo\Pro
 */

namespace Masteriyo\Pro;

defined( 'ABSPATH' ) || exit;


use Masteriyo\Enums\CourseFlow;
use Masteriyo\Enums\CourseChildrenPostType;
use Masteriyo\Query\CourseProgressItemQuery;

/**
 * Masteriyo pro class.
 *
 * @since 1.6.11
*/
class Pro {

	/**
	 *
	 * Instance of addons class.
	 *
	 * @since 1.6.11
	 *
	 * @var Masteriyo\Addons
	 *
	 */
	public $addons;

	/**
	 * Constructor.
	 *
	 * @since 1.6.11
	 *
	 * @param \Masteriyo\Pro\Addons $addons
	 */
	public function __construct( Addons $addons ) {
		$this->addons = $addons;
	}

	/**
	 * Initialize.
	 *
	 * @since 1.6.11
	 */
	public function init() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.6.11
	 */
	public function init_hooks() {
		add_filter( 'masteriyo_localized_admin_scripts', array( $this, 'localize_addons_data' ) );
		add_filter( 'masteriyo_localized_public_scripts', array( $this, 'localize_public_scripts' ) );
		add_filter( 'masteriyo_admin_submenus', array( $this, 'register_submenus' ) );
		add_filter( 'masteriyo_course_progress_item_data', array( $this, 'add_locked_status_to_course_progress_item' ), 10, 3 );
	}

	/**
	 * Localize addons data.
	 *
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
	 * @since 1.6.11
	 *
	 * @param array $submenus
	 * @return array
	 */
	public function register_submenus( $submenus ) {
		$submenus['add-ons'] = array(
			'page_title' => esc_html__( 'Addons', 'learning-management-system' ),
			'menu_title' => esc_html__( 'Addons', 'learning-management-system' ),
			'position'   => 61,
		);

		return $submenus;
	}

	/**
	 * Add locked status to course progress item like (lesson and quiz) for sequential.
	 *
	 * @since 1.15.0
	 *
	 * @param array $data The course progress item data.
	 * @param \Masteriyo\Models\CourseProgressItem $course_progress_item Course progress item object.
	* @param string $context Context.
	 */
	public function add_locked_status_to_course_progress_item( $data, $course_progress_item, $context ) {
		$locked = false;
		$course = masteriyo_get_course( $course_progress_item->get_course_id() );

		if ( $course && CourseFlow::SEQUENTIAL === $course->get_flow() ) {
			$current_index = 0;
			$contents      = masteriyo_get_course_contents( $course );

			$contents = array_values(
				array_filter(
					$contents,
					function( $content ) {
						return CourseChildrenPostType::SECTION !== $content->get_post_type();
					}
				)
			);

			foreach ( $contents as $index => $content ) {
				if ( $content->get_id() === $course_progress_item->get_item_id() ) {
					$current_index = $index;
					break;
				}
			}

			if ( $current_index > 0 ) {
				$previous_content = $contents[ $current_index - 1 ];

				if ( is_user_logged_in() ) {
					$query = new CourseProgressItemQuery(
						array(
							'item_id' => $previous_content->get_id(),
							'user_id' => masteriyo_get_current_user_id(),
							'limit'   => 1,
						)
					);

					$previous_course_progress_item = current( $query->get_course_progress_items() );
				} else {
					$session = masteriyo( 'session' );

					$previous_course_progress_items = $session->get( 'course_progress_items', array() );

					if ( isset( $previous_course_progress_items[ $previous_content->get_id() ] ) ) {
						$previous_course_progress_item = masteriyo( 'course-progress-item' );
						$previous_course_progress_item->set_item_id( $previous_content->get_id() );
						$previous_course_progress_item->set_item_type( str_replace( 'mto-', '', $previous_content->get_post_type() ) );
						$previous_course_progress_item->set_completed( $previous_course_progress_items[ $previous_content->get_id() ]['completed'] );
					}
				}

				$locked = empty( $previous_course_progress_item ) || ( $previous_course_progress_item && ! $previous_course_progress_item->get_completed() );
			}
		}

		$data['locked'] = $locked;

		return $data;
	}
}
