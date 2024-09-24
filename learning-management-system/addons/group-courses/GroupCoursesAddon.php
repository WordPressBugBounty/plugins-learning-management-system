<?php
/**
 * Group Courses Addon for Masteriyo.
 *
 * @since 1.9.0
 */

namespace Masteriyo\Addons\GroupCourses;

use Masteriyo\Addons\GroupCourses\Controllers\GroupsController;
use Masteriyo\Addons\GroupCourses\Emails\GroupCourseEnrollmentEmailToNewMember;
use Masteriyo\Addons\GroupCourses\Emails\GroupJoinedEmailToNewMember;
use Masteriyo\Addons\GroupCourses\Models\Setting;
use Masteriyo\Addons\GroupCourses\PostType\Group;
use Masteriyo\Constants;
use Masteriyo\Enums\OrderStatus;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Roles;

/**
 * Group Courses Addon main class for Masteriyo.
 *
 * @since 1.9.0
 */
class GroupCoursesAddon {

	/**
	 * Initialize.
	 *
	 * @since 1.9.0
	 */
	public function init() {
		$this->init_hooks();
	}

	/**
	 * Initializes hooks for the Group Courses Addon.
	 *
	 * Registers filters and actions related to:
	 * - Adding group course schema to courses.
	 * - Saving group course data when creating/updating courses.
	 * - Appending group course data to course responses.
	 * - Registering group submenu and post type.
	 * - Adding group checkout fields.
	 * - Changing templates for group courses.
	 * - Enqueuing scripts and styles.
	 * - Validating cart items.
	 * - Saving group IDs to order meta.
	 * - Creating group members.
	 * - Enrolling group members.
	 * - Sending emails.
	 * - Updating enrollment status on order/group changes.
	 * - Adjusting pricing.
	 *
	 * @since 1.9.0
	 */
	public function init_hooks() {
		add_filter( 'masteriyo_rest_course_schema', array( $this, 'add_group_courses_schema_to_course' ) );
		add_action( 'masteriyo_new_course', array( $this, 'save_group_courses_data' ), 10, 2 );
		add_action( 'masteriyo_update_course', array( $this, 'save_group_courses_data' ), 10, 2 );
		add_filter( 'masteriyo_rest_response_course_data', array( $this, 'append_group_courses_data_in_response' ), 10, 4 );

		add_filter( 'masteriyo_admin_submenus', array( $this, 'register_groups_submenu' ) );
		add_filter( 'masteriyo_register_post_types', array( $this, 'register_group_post_type' ) );
		add_filter( 'masteriyo_rest_api_get_rest_namespaces', array( $this, 'register_rest_namespaces' ) );

		add_action( 'masteriyo_checkout_form_content', array( $this, 'add_group_checkout_form_content' ), 140, 2 );
		add_action( 'masteriyo_after_single_course_enroll_button_wrapper', array( $this, 'masteriyo_template_group_buy_button_for_new_layout' ), 20, 1 );
		add_action( 'masteriyo_template_enroll_button', array( $this, 'masteriyo_template_group_buy_button' ), 20, 1 );
		add_action( 'wp_footer', array( $this, 'add_group_courses_popup_modal' ) );

		add_filter( 'masteriyo_get_template', array( $this, 'change_template_for_group_courses' ), 10, 5 );

		add_filter( 'masteriyo_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_filter( 'masteriyo_localized_public_scripts', array( $this, 'localize_group_courses_scripts' ) );

		add_filter( 'masteriyo_add_to_cart_validation', array( $this, 'validate_cart_items' ), 10, 4 );
		add_action( 'masteriyo_checkout_set_order_data_from_cart', array( $this, 'save_group_ids_to_order_meta' ), 10, 3 );

		add_action( 'masteriyo_new_group', array( $this, 'create_group_members' ), 10, 2 );
		add_action( 'masteriyo_update_group', array( $this, 'create_group_members' ), 10, 2 );

		add_action( 'masteriyo_checkout_order_created', array( $this, 'enroll_group_members' ), 10, 1 );
		add_filter( 'masteriyo_rest_response_order_data', array( $this, 'append_group_courses_data_in_order_response' ), 10, 4 );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_wp_editor_scripts' ) );
		add_filter( 'masteriyo_enqueue_scripts', array( $this, 'add_group_courses_dependencies_to_account_page' ) );

		add_action( 'masteriyo_group_course_new_user', array( __CLASS__, 'schedule_group_joined_email_to_new_member' ), 10, 3 );
		add_action( 'masteriyo_group_enrollment_course_user_added', array( $this, 'schedule_group_course_enrollment_email_to_new_member' ), 10, 4 );

		add_filter( 'masteriyo_cart_contents_changed', array( $this, 'add_group_course_content_to_cart_contents' ), 10, 1 );
		add_filter( 'masteriyo_add_cart_item_data', array( $this, 'append_group_course_data_in_cart_item' ), 10, 4 );

		add_action( 'masteriyo_after_trash_order', array( $this, 'update_enrollments_status_for_orders_deletion' ), 10, 2 );
		add_action( 'masteriyo_after_restore_order', array( $this, 'update_enrollments_status_for_orders_restoration' ), 10, 2 );
		add_action( 'masteriyo_update_order', array( $this, 'update_enrollments_status_for_orders_update' ), 10, 2 );

		add_action( 'masteriyo_after_trash_group', array( $this, 'update_enrollments_status_for_groups_deletion' ), 10, 2 );
		add_action( 'masteriyo_after_restore_group', array( $this, 'update_enrollments_status_for_groups_restoration' ), 10, 2 );
		add_action( 'masteriyo_update_group', array( $this, 'update_enrollments_status_for_groups_update' ), 10, 2 );

		add_filter( 'masteriyo_checkout_modify_course_details', array( $this, 'adjust_course_for_group_pricing' ), 10, 3 );

		add_filter( 'elementor_course_widgets', array( $this, 'append_custom_course_widgets' ), 10 );

		add_action( 'masteriyo_template_group_btn', array( $this, 'get_group_btn_template' ), 20, 1 );

	}

	/**
	 * Add group course elementor widget.
	 *
	 * @since 1.12.2
	 *
	 * @param array $widgets
	 * @return array
	 */
	public function append_custom_course_widgets( $widgets ) {
		$widgets[] = new GroupCourseMetaWidget();
		return $widgets;
	}

	/**
	 * Adjusts course pricing and name for group courses in the checkout order summary.
	 *
	 * @since 1.9.0
	 *
	 * @param \Masteriyo\Models\Course $course The course object being modified.
	 * @param array $cart_content Current cart item data.
	 * @param \Masteriyo\Cart\Cart $cart The entire cart object.
	 *
	 * @return \Masteriyo\Models\Course The modified course object.
	 */
	public function adjust_course_for_group_pricing( $course, $cart_content, $cart ) {
		if ( isset( $cart_content['group_ids'] ) && is_array( $cart_content['group_ids'] ) && count( $cart_content['group_ids'] ) ) {
			$group_price = get_post_meta( $course->get_id(), '_group_courses_group_price', true );
			$course->set_price( $group_price );

			$group_badge    = ' <span class="masteriyo-badge" style="background-color: green;">' . __( 'Group Course', 'learning-management-system' ) . '</span>';
			$modified_title = $course->get_name() . $group_badge;
			$course->set_name( $modified_title );
		}

		return $course;
	}


	/**
	 * Update enrollments status.
	 *
	 * @since 1.9.0
	 *
	 * @param integer $id The group ID.
	 * @param \Masteriyo\Addons\GroupCourses\Models\Group $group The group object.
	 */
	public function update_enrollments_status_for_groups_deletion( $id, $group ) {
		if ( ! Setting::get( 'deactivate_enrollment_on_status_change' ) || ! $id || ! $group ) {
			return;
		}

		$user_emails = masteriyo_get_members_emails_from_group( $id );

		if ( empty( $user_emails ) ) {
			return;
		}

		masteriyo_update_user_enrollments_status( $id, $user_emails, 'inactive' );
	}

	/**
	 * Update enrollments status.
	 *
	 * @since 1.9.0
	 *
	 * @param integer $id The group ID.
	 * @param \Masteriyo\Addons\GroupCourses\Models\Group $group The group object.
	 */
	public function update_enrollments_status_for_groups_restoration( $id, $group ) {
		if ( ! Setting::get( 'deactivate_enrollment_on_status_change' ) || ! $id || ! $group ) {
			return;
		}

		$user_emails = masteriyo_get_members_emails_from_group( $id );

		if ( empty( $user_emails ) ) {
			return;
		}

		masteriyo_update_user_enrollments_status( $id, $user_emails, 'active' );
	}

	/**
	 * Update enrollments status.
	 *
	 * @since 1.9.0
	 *
	 * @param integer $id The group ID.
	 * @param \Masteriyo\Addons\GroupCourses\Models\Group $group The group object.
	 */
	public function update_enrollments_status_for_groups_update( $id, $group ) {
		if ( ! Setting::get( 'deactivate_enrollment_on_status_change' ) || ! $id || ! $group ) {
			return;
		}

		$user_emails = masteriyo_get_members_emails_from_group( $id );

		if ( empty( $user_emails ) ) {
			return;
		}

		$enrollment_status = OrderStatus::PUBLISH === $group->get_status() ? 'active' : 'inactive';

		masteriyo_update_user_enrollments_status( $id, $user_emails, $enrollment_status );

	}

	/**
	 * Update enrollments status.
	 *
	 * @since 1.9.0
	 *
	 * @param integer $id The order ID.
	 * @param \Masteriyo\Models\Order\Order $order The order object.
	 */
	public function update_enrollments_status_for_orders_deletion( $id, $order ) {
		if ( ! $id || ! $order ) {
			return;
		}

		$group_ids = $order->get_group_ids();

		if ( empty( $group_ids ) ) {
			return;
		}

		foreach ( $group_ids as $group_id ) {

			$user_emails = masteriyo_get_members_emails_from_group( $group_id );

			if ( empty( $user_emails ) ) {
				return;
			}

			masteriyo_update_user_enrollments_status( $group_id, $user_emails, 'inactive' );
		}
	}

	/**
	 * Update enrollments status.
	 *
	 * @since 1.9.0
	 *
	 * @param integer $id The order ID.
	 * @param \Masteriyo\Models\Order\Order $order The order object.
	 */
	public function update_enrollments_status_for_orders_restoration( $id, $order ) {
		if ( ! $id || ! $order ) {
			return;
		}

		if ( OrderStatus::COMPLETED !== $order->get_status() ) {
			return;
		}

		$group_ids = $order->get_group_ids();

		if ( empty( $group_ids ) ) {
			return;
		}

		foreach ( $group_ids as $group_id ) {

			$user_emails = masteriyo_get_members_emails_from_group( $group_id );

			if ( empty( $user_emails ) ) {
				return;
			}

			masteriyo_update_user_enrollments_status( $group_id, $user_emails, 'active' );
		}
	}

	/**
	 * Update enrollments status.
	 *
	 * @since 1.9.0
	 *
	 * @param int $id The order ID.
	 * @param \Masteriyo\Models\Order\Order $order The order object.
	 */
	public function update_enrollments_status_for_orders_update( $id, $order ) {
		if ( ! $id || ! ( $order instanceof \Masteriyo\Models\Order\Order ) ) {
			return;
		}

		$group_ids = $order->get_group_ids();

		if ( empty( $group_ids ) ) {
			return;
		}

		$enrollment_status = OrderStatus::COMPLETED === $order->get_status() ? 'active' : 'inactive';

		foreach ( $group_ids as $group_id ) {

			$user_emails = masteriyo_get_members_emails_from_group( $group_id );

			if ( empty( $user_emails ) ) {
				continue;
			}

			masteriyo_update_user_enrollments_status( $group_id, $user_emails, $enrollment_status );

			$course_data = get_post_meta( $group_id, 'masteriyo_course_data', true );

			if ( empty( $course_data ) || ! is_array( $course_data ) ) {
				continue;
			}

			$new_course_data = array_map(
				function( $data ) use ( $enrollment_status, $order ) {
					if ( ! isset( $data['enrolled_status'] ) || ! isset( $data['order_id'] ) || $data['enrolled_status'] === $enrollment_status || absint( $data['order_id'] ) !== $order->get_id() ) {
						return $data;
					}

					$data['enrolled_status'] = $enrollment_status;

					return $data;

				},
				$course_data
			);

			update_post_meta( $group_id, 'masteriyo_course_data', $new_course_data );
		}
	}

	/**
	 * Appends group-specific data to the cart item.
	 *
	 * This function hooks into `masteriyo_group_cart_item_data` to allow adding or modifying cart item data
	 * based on associated group IDs. It's designed for extensibility and customization of group courses feature.
	 *
	 * @since 1.9.0
	 *
	 * @param array $cart_item_data Cart item data.
	 * @param integer $course_id Course ID.
	 * @param integer $quantity Item quantity.
	 * @param array $group_ids An array of group IDs associated with the course being added. This can be used
	 *
	 * @return array|\WP_Error Modified cart item data with group information or WP Error object.
	 */
	public function append_group_course_data_in_cart_item( $cart_item_data, $course_id, $quantity, $group_ids ) {
		if ( empty( $group_ids ) ) {
			return $cart_item_data;
		}

		$course = masteriyo_get_course( $course_id );

		if ( ! $course ) {
			return $cart_item_data;
		}

		$members = array_sum(
			array_map(
				function( $group_id ) {
					return count( masteriyo_get_members_emails_from_group( $group_id ) );
				},
				$group_ids
			)
		);

		if ( $course->get_enrollment_limit() > 0 && $members > $course->get_available_seats() ) {
			if ( $course->get_available_seats() === 0 ) {
				$error_message = __( 'Sorry, students limit reached. Course closed for enrollment.', 'learning-management-system' );
			} else {
				$error_message = sprintf(
					/* translators: %d: available seats */
					__( 'Sorry, the course has only %d available seat(s), which is less than the total requested seats for your group(s). Please reduce the number of group members or choose another course.', 'learning-management-system' ),
					$course->get_available_seats()
				);
			}

			return new \WP_Error( 'course_enrollment_limit_reached', $error_message );
		}

		$group_price = get_post_meta( $course->get_id(), '_group_courses_group_price', true );
		if ( $group_price > 0 ) {
			$cart_item_data['group_price'] = floatval( $group_price ) * count( $group_ids );
			$cart_item_data['group_ids']   = $group_ids;
		}

		return $cart_item_data;
	}

	/**
	 * Adjusts the price of group courses in the cart.
	 *
	 * @since 1.9.0
	 *
	 * @param array $cart_contents The current contents of the cart.
	 *
	 * @return array Modified cart contents with updated pricing for group courses.
	 */
	public function add_group_course_content_to_cart_contents( $cart_contents ) {
		if ( ! is_array( $cart_contents ) || empty( $cart_contents ) ) {
			return $cart_contents;
		}

		$cart_contents = array_map(
			function ( $cart_item ) {

				if ( isset( $cart_item['group_ids'] ) && isset( $cart_item['group_price'] ) ) {
					$group_price = $cart_item['group_price'];

					if ( ! empty( $cart_item['group_ids'] ) && ! empty( $group_price ) ) {
						$cart_item['data']->set_price( $group_price );
						$cart_item['data']->set_regular_price( $group_price );
					}
				}

				return $cart_item;
			},
			$cart_contents
		);

		return $cart_contents;
	}

	/**
	 * Schedules or directly triggers a group course enrollment email to a new member based on the email scheduling setting.
	 * If email scheduling is enabled, the action is queued. Otherwise, the email is sent immediately.
	 *
	 * @since 1.9.0
	 *
	 * @param int    $user_id   The ID of the user to whom the email will be sent.
	 * @param object $user      The user object of the new member.
	 * @param int    $group_id  The ID of the group the user has been enrolled in.
	 * @param int    $course_id The ID of the course the user has been enrolled in.
	 *
	 * @return void
	 */
	public static function schedule_group_course_enrollment_email_to_new_member( $user_id, $user, $group_id, $course_id ) {
		$email = new GroupCourseEnrollmentEmailToNewMember();

		if ( ! $email->is_enabled() ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action(
				$email->get_schedule_handle(),
				array(
					'id'        => $user->get_id(),
					'group_id'  => $group_id,
					'course_id' => $course_id,
				),
				'masteriyo'
			);
		} else {
			$email->trigger( $user_id, $group_id, $course_id );
		}
	}

	/**
	 * Schedules or directly triggers a group joined email to a new member based on the email scheduling setting.
	 * If email scheduling is enabled, the action is queued. Otherwise, the email is sent immediately.
	 *
	 * @since 1.9.0
	 *
	 * @param int    $user_id  The ID of the user to whom the email will be sent.
	 * @param object $user     The user object of the new member.
	 * @param int    $group_id The ID of the group the user has joined.
	 *
	 * @return void
	 */
	public static function schedule_group_joined_email_to_new_member( $user_id, $user, $group_id ) {
		$email = new GroupJoinedEmailToNewMember();

		if ( ! $email->is_enabled() ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action(
				$email->get_schedule_handle(),
				array(
					'id'       => $user->get_id(),
					'group_id' => $group_id,
				),
				'masteriyo'
			);
		} else {
			$email->trigger( $user_id, $group_id );
		}
	}

	/**
	 * Return true if the action schedule is enabled for Email.
	 *
	 * @since 1.9.0
	 *
	 * @return boolean
	 */
	public static function is_email_schedule_enabled() {
		return masteriyo_is_email_schedule_enabled();
	}

	/**
	 * Adds script dependencies required for group courses on the account page.
	 * This method checks if the current page is the account page and if specific scripts are not already set as dependencies.
	 * It then merges the required dependencies into the scripts array.
	 *
	 * @since 1.9.0
	 *
	 * @param array $scripts Associative array of script handles and their dependencies.
	 *
	 * @return array The modified scripts array with added dependencies for the account page, if applicable.
	 */
	public function add_group_courses_dependencies_to_account_page( $scripts ) {
		if ( masteriyo_is_account_page() ) {
			if ( ! isset( $scripts['account'] ) || ! isset( $scripts['account']['deps'] ) ) {
				return $scripts;
			}

			$scripts['account']['deps'] = array_merge( $scripts['account']['deps'], array( 'wp-editor' ) );
		}

		return $scripts;
	}

	/**
	 * Enqueues WordPress editor scripts on the account page if the current page is identified as the account page.
	 * This method is a helper function to ensure that editor scripts are loaded where necessary for account management.
	 *
	 * @since 1.9.0
	 *
	 * @return void
	 */
	public function enqueue_wp_editor_scripts() {
		if ( masteriyo_is_account_page() ) {
			wp_enqueue_editor();
		}
	}

	/**
	 * Appends the groups data to the order response data.
	 *
	 * @since 1.9.0
	 *
	 * @param array $data Order data.
	 * @param Masteriyo\Models\Order $order Order object.
	 * @param string $context What the value is for. Valid values are view and edit.
	 * @param Masteriyo\RestApi\Controllers\Version1\OrdersController $controller REST Orders controller object.
	 *
	 * @return array $data Order response data.
	 */
	public function append_group_courses_data_in_order_response( $data, $order, $context, $controller ) {
		if ( ! $order || empty( $data['course_lines'] ) ) {
			return $data;
		}

		$group_ids = $order->get_group_ids();

		if ( empty( $group_ids ) ) {
			return $data;
		}

		$groups     = masteriyo_get_groups( $group_ids );
		$group_data = array();

		foreach ( $data['course_lines'] as $course_line ) {
			$course_id = $course_line['course_id'] ?? 0;
			if ( empty( $course_line ) || ! $course_id ) {
				continue;
			}

			foreach ( $groups as $group ) {
				if ( ! $group ) {
					continue;
				}

				$group_data[] = array(
					'id'     => $group->get_id(),
					'title'  => $group->get_title(),
					'emails' => masteriyo_get_enrolled_group_user_emails( $group, $course_id ),
				);
			}
		}

		$data['groups'] = $group_data;

		return $data;
	}

	/**
	 * Enrolls group members into courses associated with the order.
	 *
	 * @since 1.9.0
	 *
	 * @param \Masteriyo\Models\Order\Order $order Order object.
	 */
	public function enroll_group_members( $order ) {
		if ( ! $order instanceof \Masteriyo\Models\Order\Order || ! $order->get_id() ) {
			return;
		}

		$course_ids = $this->get_course_ids_from_order( $order );
		if ( empty( $course_ids ) ) {
			return;
		}

		$group_ids = $order->get_group_ids();
		if ( empty( $group_ids ) ) {
			return;
		}

		foreach ( $group_ids as $group_id ) {
			$members = masteriyo_get_members_emails_from_group( $group_id );
			if ( empty( $members ) ) {
				continue;
			}

			$enrollment_status = OrderStatus::COMPLETED === $order->get_status() ? 'active' : 'inactive';

			$existing_course_data = get_post_meta( $group_id, 'masteriyo_course_data', true );
			if ( ! is_array( $existing_course_data ) ) {
				$existing_course_data = array();
			}

			foreach ( $course_ids as $course_id ) {
				$this->enroll_members_into_course( $members, $course_id, $group_id, $order->get_status() );

				$course_data = array(
					'course_id'       => $course_id,
					'order_id'        => $order->get_id(),
					'enrolled_status' => $enrollment_status,
				);

				$exists = false;
				foreach ( $existing_course_data as &$existing_data ) {
					if ( absint( $existing_data['course_id'] ) === absint( $course_id ) ) {
						$existing_data = $course_data;
						$exists        = true;
						break;
					}
				}

				if ( ! $exists ) {
					$existing_course_data[] = $course_data;
				}
			}

			update_post_meta( $group_id, 'masteriyo_course_data', $existing_course_data );
		}
	}

	/**
	 * Creates group members based on the provided group object.
	 *
	 * This function checks if the group is valid and published, retrieves the emails from the group,
	 * and processes each email to either fetch an existing user or create a new one, assigning them to the group.
	 *
	 * @since 1.9.0
	 *
	 * @param integer $group_id The group ID.
	 * @param \Masteriyo\Addons\GroupCourses\Models\Group $group The group object.
	 *
	 * @return void
	 */
	public function create_group_members( $group_id, $group ) {
		if ( ! $this->is_group_valid_and_published( $group ) ) {
			return;
		}

		$emails = $group->get_emails();
		if ( ! $this->are_emails_valid( $emails ) ) {
			return;
		}

		$this->setup_user_registration_filters();

		foreach ( $emails as $email ) {
			$this->process_email( $email, $group_id );
		}
	}

	/**
	 * Saves group IDs associated with cart items to the order meta.
	 * This function iterates over the contents of the cart and checks for group IDs associated with each item.
	 * If group IDs are found, they are saved to the order meta to establish a connection between the order and the groups.
	 *
	 * @since 1.9.0
	 *
	 * @param \Masteriyo\Models\Order\Order $order    The order object to which group IDs will be saved.
	 * @param \Masteriyo\Checkout $checkout          Checkout object, not directly used but required for method signature consistency.
	 * @param \Masteriyo\Cart\Cart $cart             The cart object containing the items purchased.
	 *
	 * @return void
	 */
	public function save_group_ids_to_order_meta( $order, $checkout, $cart ) {
		if ( ! $order instanceof \Masteriyo\Models\Order\Order || ! $checkout instanceof \Masteriyo\Checkout || ! $cart instanceof \Masteriyo\Cart\Cart ) {
			return;
		}

		if ( ! $cart->is_empty() ) {
			foreach ( $cart->get_cart_contents() as $cart_content ) {
				if ( isset( $cart_content['group_ids'] ) && is_array( $cart_content['group_ids'] ) && count( $cart_content['group_ids'] ) ) {
					$group_ids = $cart_content['group_ids'];

					$order->set_group_ids( $group_ids );
				}
			}
		}

	}

	/**
	 * Validates the cart items, specifically checking if the course exists, is not trashed, and if the group IDs associated with the course are valid.
	 * It ensures the quantity is positive, the course exists and is not trashed, and all provided group IDs are valid for the course.
	 * If any of these checks fail, the method returns false, indicating the cart item is not valid.
	 *
	 * @since 1.9.0
	 *
	 * @param bool  $is_valid   Initially passed in validation flag (true if the cart item is considered valid so far).
	 * @param int   $course_id  The ID of the course being added to the cart.
	 * @param int   $quantity   The quantity of the course being added to the cart.
	 * @param array $group_ids  The group IDs associated with the course in the cart.
	 *
	 * @return bool Returns true if the cart item passes all validation checks; otherwise, false.
	 */
	public function validate_cart_items( $is_valid, $course_id, $quantity, $group_ids ) {

		if ( ! $is_valid ) {
			return $is_valid;
		}

		$course = masteriyo_get_course( $course_id );

		if ( ! $course ) {
			return false;
		}

		if ( $quantity <= 0 || ! $course || PostStatus::TRASH === $course->get_status() ) {
			return false;
		}

		$invalid_group_messages = array();

		if ( is_array( $group_ids ) && count( $group_ids ) ) {
			$valid_group_ids = masteriyo_validate_groups_for_course( $course, $group_ids );

			$invalid_group_ids = array_diff( $group_ids, $valid_group_ids );

			foreach ( $invalid_group_ids as $invalid_group_id ) {
				$group = masteriyo_get_group( $invalid_group_id );

				/* translators: %s: Invalid Group ID */
				$group_name = $group ? $group->get_title() : sprintf( __( 'Group ID %s', 'learning-management-system' ), $invalid_group_id );

				$invalid_group_messages[] = $group_name;
			}
		}

		if ( ! empty( $invalid_group_messages ) ) {
			$invalid_groups_list = implode( ', ', $invalid_group_messages );

			$error_message = sprintf(
			/* translators: %s: Invalid groups list */
				__( 'Invalid group(s): %s', 'learning-management-system' ),
				$invalid_groups_list
			);

			return false;
		}

		return $is_valid;
	}

	/**
	 * Localize single course page scripts.
	 *
	 * @since 1.9.0
	 *
	 * @param array $scripts
	 *
	 * @return array
	 */
	public function localize_group_courses_scripts( $scripts ) {
		$course = ( isset( $GLOBALS['course'] ) && is_a( $GLOBALS['course'], '\Masteriyo\Models\Course' ) ) ? $GLOBALS['course'] : null;

		if ( $course && $course->is_purchasable() && $course->get_price() ) {
			$course_id       = $course->get_id();
			$add_to_cart_url = $course->add_to_cart_url();
			$max_group_size  = absint( get_post_meta( $course_id, '_group_courses_max_group_size', true ) );

			$group_courses_scripts = array(
				'masteriyo-group-courses-single-course' => array(
					'name' => 'MASTERIYO_GROUP_COURSES_DATA',
					'data' => array(
						'wp_rest_nonce'      => wp_create_nonce( 'wp_rest' ),
						'restUrl'            => rest_url( 'masteriyo/v1/groups' ),
						'course_id'          => $course_id,
						'add_to_cart_url'    => $add_to_cart_url,
						'members_text'       => __( 'Members', 'learning-management-system' ),
						'max_group_size'     => $max_group_size,
						'max_group_size_msg' => '',
					),
				),
			);

			if ( $max_group_size ) {
				/* translators: %s: Maximum members */
				$group_courses_scripts['masteriyo-group-courses-single-course']['data']['max_group_size_msg'] = sprintf( __( 'A group must have %d or fewer members to enroll in this course.', 'learning-management-system' ), $max_group_size );
			}

			$scripts = masteriyo_parse_args(
				$scripts,
				$group_courses_scripts
			);

			$scripts['account']['data']['group_courses']['group_limit'] = absint( Setting::get( 'max_members' ) );
		}

		return $scripts;
	}

	/**
	 * Enqueue scripts.
	 *
	 * @since 1.9.0
	 *
	 * @param array $scripts Array of scripts.
	 * @return array
	 */
	public function enqueue_scripts( $scripts ) {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		return masteriyo_parse_args(
			$scripts,
			array(
				'masteriyo-group-courses-single-course' => array(
					'src'      => plugin_dir_url( Constants::get( 'MASTERIYO_GROUP_COURSES_ADDON_FILE' ) ) . 'assets/js/frontend/single-course' . $suffix . '.js',
					'context'  => 'public',
					'callback' => function() {
						return masteriyo_is_single_course_page() && masteriyo_is_course_valid_for_group();
					},
					'deps'     => array( 'jquery' ),
				),
			)
		);
	}

	/**
	 * Renders a popup modal for group courses on single course pages.
	 *
	 * @since 1.9.0
	 *
	 * @return void
	 */
	public function add_group_courses_popup_modal() {
		if ( ! masteriyo_is_single_course_page() ) {
			return;
		}

		$course = $GLOBALS['course'];
		$course = masteriyo_get_course( $course );

		if ( ! $course ) {
			return;
		}

		masteriyo_get_template(
			'group-courses/group-courses-modal.php',
			array(
				'course' => $course,
			)
		);
	}

	/**
	 * Renders the group buy button for a course on single course pages for logged-in users.
	 *
	 * This function checks if the course is purchasable and has a group price, and then renders the group buy button template for the course.
	 *
	 * @since 1.10.0
	 *
	 * @param \Masteriyo\Models\Course $course The course object for which the group buy button is being rendered.
	 */
	public function get_group_btn_template( $course ) {
		$group_price = get_post_meta( $course->get_id(), '_group_courses_group_price', true );

		if ( ! $group_price ) {
			remove_action( 'masteriyo_template_group_btn', array( $this, 'get_group_btn_template' ), 20, 1 );
			return;
		}

		if ( masteriyo_is_courses_page() ) {
			return;
		}

		if ( ! $course || ! $course instanceof \Masteriyo\Models\Course || ! is_user_logged_in() ) {
			return;
		}

		if ( ! $course->is_purchasable() || ! $course->get_price() ) {
			return;
		}

		$group_price = masteriyo_get_group_price( $course->get_id() );

		if ( ! $group_price ) {
			return;
		}

		masteriyo_get_template(
			'group-courses/group-buy-btn.php',
			array(
				'group_price' => $group_price,
				'course_id'   => $course->get_id(),
				'course'      => $course,
			)
		);
	}

	/**
	 * Renders the group buy button for a course on single course pages for logged-in users.
	 *
	 * @since 1.10.0
	 *
	 * @param \Masteriyo\Models\Course $course The course object for which the group buy button is being rendered.
	 *
	 * @return void
	 */
	public function masteriyo_template_group_buy_button_for_new_layout( $course ) {
		$layout = masteriyo_get_setting( 'single_course.layout' ) ?? 'default';

		if ( masteriyo_is_single_course_page() && 'default' === $layout ) {
			return;
		}

		do_action( 'masteriyo_template_group_btn', $course );
	}

	/**
	 * Renders the group buy button for a course on single course pages for logged-in users.
	 *
	 * @since 1.10.0
	 *
	 * @param \Masteriyo\Models\Course $course The course object for which the group buy button is being rendered.
	 *
	 * @return void
	 */
	public function masteriyo_template_group_buy_button( $course ) {
		$layout = masteriyo_get_setting( 'single_course.layout' ) ?? 'default';

		if ( masteriyo_is_single_course_page() && 'layout1' === $layout ) {
			return;
		}

		do_action( 'masteriyo_template_group_btn', $course );
	}

	/**
	 * Changes the template path for specific group courses related templates.
	 *
	 * @since 1.9.0
	 *
	 * @param string $template Template path.
	 * @param string $template_name Template name.
	 * @param array $args Template arguments.
	 * @param string $template_path Template path from function parameter.
	 * @param string $default_path Default templates directory path.
	 *
	 * @return string
	 */
	public function change_template_for_group_courses( $template, $template_name, $args, $template_path, $default_path ) {
		$template_map = array(
			'group-courses/group-courses.php'              => 'group-courses.php',
			'group-courses/group-buy-btn.php'              => 'group-buy-btn.php',
			'group-courses/group-courses-modal.php'        => 'group-courses-modal.php',
			'group-courses/emails/group-joining.php'       => 'emails/group-joining.php',
			'group-courses/emails/group-course-enroll.php' => 'emails/group-course-enroll.php',
		);

		if ( isset( $template_map[ $template_name ] ) ) {
			$new_template = trailingslashit( Constants::get( 'MASTERIYO_GROUP_COURSES_TEMPLATES' ) ) . $template_map[ $template_name ];

			return file_exists( $new_template ) ? $new_template : $template;
		}

		return $template;
	}

	/**
	 * Add the group courses content to the checkout form.
	 *
	 * @since 1.9.0
	 *
	 * @param \Masteriyo\Models\User $user
	 * @param \Masteriyo\Checkout $checkout
	 */
	public function add_group_checkout_form_content( $user, $checkout ) {

		if ( ! $checkout instanceof \Masteriyo\Checkout ) {
			return;
		}

		$cart = masteriyo( 'cart' );
		if ( is_null( $cart ) ) {
			return;
		}

		$groups = array();

		if ( ! $cart->is_empty() ) {
			foreach ( $cart->get_cart_contents() as $cart_content ) {
				if ( isset( $cart_content['group_ids'] ) && is_array( $cart_content['group_ids'] ) && count( $cart_content['group_ids'] ) ) {
					$group_ids = $cart_content['group_ids'];

					$groups = masteriyo_get_groups( $group_ids );
				}
			}
		}

		if ( empty( $groups ) ) {
			return;
		}

		masteriyo_get_template(
			'group-courses/group-courses.php',
			array(
				'user'     => $user,
				'checkout' => $checkout,
				'groups'   => $groups,
			)
		);
	}

	/**
	 * Append group courses to course response.
	 *
	 * @since 1.9.0
	 *
	 * @param array $data Course data.
	 * @param \Masteriyo\Models\Course $course Course object.
	 * @param string $context What the value is for. Valid values are view and edit.
	 * @param \Masteriyo\RestApi\Controllers\Version1\CoursesController $controller REST courses controller object.
	 *
	 * @return array
	 */
	public function append_group_courses_data_in_response( $data, $course, $context, $controller ) {

		if ( $course instanceof \Masteriyo\Models\Course ) {
			$data['group_courses'] = array(
				'group_price'    => get_post_meta( $course->get_id(), '_group_courses_group_price', true ),
				'max_group_size' => get_post_meta( $course->get_id(), '_group_courses_max_group_size', true ),
			);
		}

		return $data;
	}

	/**
	 * Save group courses data.
	 *
	 * @since 1.9.0
	 *
	 * @param integer $id The course ID.
	 * @param \Masteriyo\Models\Course $object The course object.
	 */
	public function save_group_courses_data( $id, $course ) {
		$request = masteriyo_current_http_request();

		if ( null === $request ) {
			return;
		}

		if ( ! isset( $request['group_courses'] ) ) {
			return;
		}

		if ( isset( $request['group_courses']['group_price'] ) ) {
			update_post_meta( $id, '_group_courses_group_price', sanitize_text_field( $request['group_courses']['group_price'] ) );
		}

		if ( isset( $request['group_courses']['max_group_size'] ) ) {
			update_post_meta( $id, '_group_courses_max_group_size', sanitize_text_field( $request['group_courses']['max_group_size'] ) );
		}
	}

	/**
		 * Add group courses fields to course schema.
		 *
		 * @since 1.9.0
		 *
		 * @param array $schema
		 * @return array
		 */
	public function add_group_courses_schema_to_course( $schema ) {
		$schema = wp_parse_args(
			$schema,
			array(
				'group_courses' => array(
					'description' => __( 'Group courses setting', 'learning-management-system' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'group_price'    => array(
								'description' => __( 'Group price.', 'learning-management-system' ),
								'type'        => 'string',
								'default'     => '',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'max_group_size' => array(
								'description' => __( 'Maximum Group Size', 'learning-management-system' ),
								'type'        => 'string',
								'default'     => '',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
			)
		);

		return $schema;
	}

	/**
	 * Register REST API namespaces for the Group Courses.
	 *
	 * @since 1.9.0
	 *
	 * @param array $namespaces Rest namespaces.
	 *
	 * @return array Modified REST namespaces including Group Courses endpoints.
	 */
	public function register_rest_namespaces( $namespaces ) {
		$namespaces['masteriyo/v1']['group-courses'] = GroupsController::class;

		return $namespaces;
	}

	/**
	 * Register group post types.
	 *
	 * @since 1.9.0
	 *
	 * @param string[] $post_types
	 *
	 * @return string[]
	 */
	public function register_group_post_type( $post_types ) {
		$post_types[] = Group::class;

		return $post_types;
	}

	/**
	 * Register group submenu.
	 *
	 * @since 1.9.0
	 *
	 * @param array $submenus Admin submenus.
	 *
	 * @return array
	 */
	public function register_groups_submenu( $submenus ) {
		$submenus['groups'] = array(
			'page_title' => __( 'Groups', 'learning-management-system' ),
			'menu_title' => __( 'Groups', 'learning-management-system' ),
			'position'   => 66,
		);

		return $submenus;
	}

	/*
	|--------------------------------------------------------------------------
	| Private Methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Validates the group object and its status.
	 *
	 * Checks if the group object is valid, not null, not an error, and is published.
	 *
	 * @since 1.9.0
	 *
	 * @param mixed $group The group object to validate.
	 * @return bool True if the group is valid and published, false otherwise.
	 */
	private function is_group_valid_and_published( $group ) {
		return $group instanceof \Masteriyo\Addons\GroupCourses\Models\Group
		&& ! is_null( $group )
		&& ! is_wp_error( $group )
		&& PostStatus::PUBLISH === $group->get_status();

	}

	/**
	 * Validates the emails array.
	 *
	 * Checks if the provided emails array is not empty and is an array.
	 *
	 * @since 1.9.0
	 *
	 * @param array $emails The array of emails to validate.
	 * @return bool True if the emails are valid, false otherwise.
	 */
	private function are_emails_valid( $emails ) {
		return ! empty( $emails ) && is_array( $emails );
	}

	/**
	 * Sets up user registration filters.
	 *
	 * Adds filters to automatically generate passwords and usernames during user registration.
	 *
	 * @since 1.9.0
	 */
	private function setup_user_registration_filters() {
		add_filter( 'masteriyo_registration_is_generate_password', '__return_true' );
		add_filter( 'masteriyo_registration_is_generate_username', '__return_true' );
	}

	/**
	 * Processes each email for group assignment.
	 *
	 * Validates the email, fetches or creates a user based on the email, and assigns the user to the group.
	 *
	 * @since 1.9.0
	 *
	 * @param string $email    The email to process.
	 * @param int    $group_id The ID of the group to assign the user to.
	 */
	private function process_email( $email, $group_id ) {
		if ( ! is_email( $email ) ) {
			return;
		}

		$user_id = $this->get_or_create_user_id_from_email( $email );
		if ( ! $user_id ) {
			return;
		}

		$this->assign_group_to_user( $user_id, $group_id );
	}

	/**
	 * Gets or creates a user ID from an email.
	 *
	 * Checks if a user exists with the given email, and if not, creates a new user. Returns the user ID.
	 *
	 * @since 1.9.0
	 *
	 * @param string $email The email to check or create a user for.
	 * @return mixed The user ID if successful, or false if an error occurred.
	 */
	private function get_or_create_user_id_from_email( $email ) {
		$user = email_exists( $email ) ? get_user_by( 'email', $email ) : masteriyo_create_new_user( $email );

		if ( is_wp_error( $user ) || ! $user ) {
			return false;
		}

		return $user instanceof \WP_User ? $user->ID : ( $user instanceof \Masteriyo\Models\User ? $user->get_id() : ( is_int( $user ) ? $user : false ) );
	}

	/**
	 * Assigns a user to a group.
	 *
	 * Checks if the user is already assigned to the group, and if not, assigns them and updates their role if necessary.
	 *
	 * @since 1.9.0
	 *
	 * @param int $user_id  The ID of the user to assign to the group.
	 * @param int $group_id The ID of the group to assign the user to.
	 */
	private function assign_group_to_user( $user_id, $group_id ) {
		$existing_groups = get_user_meta( $user_id, 'masteriyo_group_ids', true );
		$existing_groups = $existing_groups ? $existing_groups : array();

		if ( in_array( $group_id, $existing_groups, true ) ) {
			return;
		}

		$existing_groups[] = $group_id;
		update_user_meta( $user_id, 'masteriyo_group_ids', $existing_groups );

		$user = new \WP_User( $user_id );

		if ( ! $user || ! isset( $user->ID ) || 0 === $user->ID ) {
			return;
		}

		if (
		! in_array( Roles::ADMIN, (array) $user->roles, true ) &&
		! in_array( Roles::MANAGER, (array) $user->roles, true ) &&
		! in_array( Roles::INSTRUCTOR, (array) $user->roles, true ) &&
		! in_array( Roles::STUDENT, (array) $user->roles, true )
		) {
			$user->add_role( Roles::STUDENT );
		}

		/**
		 * Action: `masteriyo_group_course_new_user`
		 *
		 * Fires after a new user is assigned to a group, allowing additional custom actions to be executed.
		 *
		 * @since 1.9.0
		 *
		 * @param int     $user_id  The ID of the user.
		 * @param \WP_User $user     The user object.
		 * @param int     $group_id The ID of the group the user was added to.
		 */
		do_action( 'masteriyo_group_course_new_user', $user->ID, $user, $group_id );
	}

	/**
	 * Extracts course IDs from order items.
	 *
	 * @since 1.9.0
	 *
	 * @param \Masteriyo\Models\Order\Order $order Order object.
	 * @return array An array of course IDs.
	 */
	private function get_course_ids_from_order( $order ) {
		return array_filter(
			array_map(
				function( $item ) {
					return 'course' === $item->get_type() ? $item->get_course_id() : null;
				},
				$order->get_items()
			)
		);
	}

	/**
	 * Enrolls members into a specified course.
	 *
	 * @since 1.9.0
	 *
	 * @param array $members   An array of members' emails.
	 * @param int   $course_id The course ID.
	 * @param int   $group_id  The group ID.
	 * @param string $order_status The order status.
	 */
	private function enroll_members_into_course( $members, $course_id, $group_id, $order_status ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'masteriyo_user_items';

		$status = OrderStatus::COMPLETED === $order_status ? 'active' : 'inactive';

		foreach ( $members as $member ) {
			$user = get_user_by( 'email', $member );
			if ( ! $user || masteriyo_is_user_already_enrolled( $user->ID, $course_id ) ) {
				continue;
			}

			$user_items_data = array(
				'user_id'    => $user->ID,
				'item_id'    => $course_id,
				'item_type'  => 'user_course',
				'status'     => $status,
				'parent_id'  => 0,
				'date_start' => current_time( 'mysql' ),
			);

			if ( $wpdb->insert( $table_name, $user_items_data ) ) {
				/**
				 * Fires after a user is successfully enrolled into a course as part of a group.
				 *
				 * @since 1.9.0
				 *
				 * @param int     $user_id   The ID of the enrolled user.
				 * @param WP_User $user      The WP_User object of the enrolled user.
				 * @param int     $group_id The ID of the group the user was added to.
				 * @param int     $course_id The ID of the course the user was enrolled into.
				 */
				do_action( 'masteriyo_group_enrollment_course_user_added', $user->ID, $user, $group_id, $course_id );
			}
		}
	}
}
