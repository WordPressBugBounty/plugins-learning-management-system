<?php
/**
 * Group Courses Addon for Masteriyo.
 *
 * @since 1.9.0
 */

namespace Masteriyo\Addons\GroupCourses;

use Masteriyo\Addons\GroupCourses\Controllers\GroupsController;
use Masteriyo\Addons\GroupCourses\Emails\EmailScheduleActions;
use Masteriyo\Addons\GroupCourses\Emails\GroupCourseEnrollmentEmailToNewMember;
use Masteriyo\Addons\GroupCourses\Emails\GroupJoinedEmailToNewMember;
use Masteriyo\Addons\GroupCourses\Emails\GroupPublishedEmailToAuthor;
use Masteriyo\Addons\GroupCourses\Models\Setting;
use Masteriyo\Addons\GroupCourses\PostType\Group;
use Masteriyo\Constants;
use Masteriyo\Enums\OrderStatus;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Enums\UserCourseStatus;
use Masteriyo\PostType\PostType;
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

		add_action( 'masteriyo_after_single_course_enroll_button_wrapper', array( $this, 'masteriyo_template_group_buy_button_for_new_layout' ), 20, 1 );
		add_action( 'masteriyo_template_enroll_button', array( $this, 'masteriyo_template_group_buy_button' ), 20, 1 );

		add_filter( 'masteriyo_get_template', array( $this, 'change_template_for_group_courses' ), 10, 5 );

		add_filter( 'masteriyo_localized_public_scripts', array( $this, 'localize_group_courses_scripts' ) );

		add_action( 'masteriyo_checkout_set_order_data_from_cart', array( $this, 'save_group_ids_to_order_meta' ), 10, 3 );

		add_action( 'masteriyo_new_group', array( $this, 'create_group_members' ), 10, 2 );
		add_action( 'masteriyo_update_group', array( $this, 'create_group_members' ), 10, 2 );

		add_action( 'masteriyo_checkout_order_created', array( $this, 'enroll_group_members' ), 10, 1 );
		add_action( 'masteriyo_checkout_order_created', array( $this, 'create_group_on_order_creation' ), 20, 1 );
		add_action( 'masteriyo_order_status_changed', array( $this, 'update_group_status_on_order_change' ), 10, 3 );
		add_action( 'masteriyo_after_trash_order', array( $this, 'set_group_to_draft_on_order_trash' ), 10, 2 );
		add_action( 'masteriyo_before_delete_order', array( $this, 'set_group_to_draft_on_order_delete' ), 10, 2 );
		add_action( 'masteriyo_after_restore_order', array( $this, 'restore_group_status_on_order_restore' ), 10, 2 );
		add_filter( 'masteriyo_rest_response_order_data', array( $this, 'append_group_courses_data_in_order_response' ), 10, 4 );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_wp_editor_scripts' ) );
		add_filter( 'masteriyo_enqueue_scripts', array( $this, 'add_group_courses_dependencies_to_account_page' ) );

		add_action( 'masteriyo_group_course_new_user', array( __CLASS__, 'schedule_group_joined_email_to_new_member' ), 10, 3 );
		add_action( 'masteriyo_group_enrollment_course_user_added', array( $this, 'schedule_group_course_enrollment_email_to_new_member' ), 10, 5 );
		add_action( 'masteriyo_update_group', array( $this, 'schedule_group_published_email_to_author' ), 10, 2 );

		// Initialize email schedule actions.
		EmailScheduleActions::init();

		add_filter( 'masteriyo_cart_contents_changed', array( $this, 'add_group_course_content_to_cart_contents' ), 10, 1 );
		add_filter( 'masteriyo_add_cart_item_data', array( $this, 'append_group_course_data_in_cart_item' ), 10, 1 );

		add_action( 'masteriyo_after_trash_order', array( $this, 'update_enrollments_status_for_orders_deletion' ), 10, 2 );
		add_action( 'masteriyo_after_restore_order', array( $this, 'update_enrollments_status_for_orders_restoration' ), 10, 2 );
		add_action( 'masteriyo_update_order', array( $this, 'update_enrollments_status_for_orders_update' ), 10, 2 );

		add_action( 'masteriyo_after_trash_group', array( $this, 'update_enrollments_status_for_groups_deletion' ), 10, 2 );
		add_action( 'masteriyo_after_restore_group', array( $this, 'update_enrollments_status_for_groups_restoration' ), 10, 2 );
		add_action( 'masteriyo_update_group', array( $this, 'update_enrollments_status_for_groups_update' ), 10, 2 );

		add_filter( 'masteriyo_checkout_modify_course_details', array( $this, 'adjust_course_for_group_pricing' ), 11, 3 );

		add_filter( 'elementor_course_widgets', array( $this, 'append_custom_course_widgets' ), 10 );

		add_action( 'masteriyo_template_group_btn', array( $this, 'get_group_btn_template' ), 20, 1 );

		add_action( 'masteriyo_checkout_after_order_summary', array( $this, 'display_group_details_section' ), 10, 1 );

		add_filter( 'masteriyo_invoice_data', array( $this, 'add_group_info_to_invoice_data' ), 10, 2 );
		add_action( 'masteriyo_invoice_after_customer_details', array( $this, 'display_group_info_in_invoice' ), 10, 1 );
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
		// Handle new group purchase flow
		if ( isset( $cart_content['group_purchase'] ) && $cart_content['group_purchase'] ) {
			$group_price = get_post_meta( $course->get_id(), '_group_courses_group_price', true );

			if ( function_exists( 'masteriyo_get_currency_and_pricing_zone_based_on_course' ) ) {
				list( $currency, $pricing_zone ) = masteriyo_get_currency_and_pricing_zone_based_on_course( $course->get_id() );

				if ( ! empty( $currency ) ) {
					$modified_group_price = masteriyo_get_country_based_group_course_price( $course->get_id(), $group_price, $pricing_zone );
					$group_price          = $modified_group_price ? $modified_group_price : $group_price;
				}
			}

			$course->set_price( $group_price );

			$group_badge    = ' <span class="masteriyo-badge" style="background-color: green;">' . __( 'Group', 'learning-management-system' ) . '</span>';
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

		masteriyo_update_user_enrollments_status( $id, $user_emails, UserCourseStatus::INACTIVE );
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

		masteriyo_update_user_enrollments_status( $id, $user_emails, UserCourseStatus::ACTIVE );
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

		$enrollment_status = PostStatus::PUBLISH === $group->get_status() ? UserCourseStatus::ACTIVE : UserCourseStatus::INACTIVE;

		masteriyo_update_user_enrollments_status( $id, $user_emails, $enrollment_status );
	}

	/**
	 * Update enrollments status  when order is deleted.
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

		$group_id  = get_post_meta( $id, '_created_group_id', true ); // New group purchase flow.
		$group_ids = $group_id ? array( $group_id ) : $order->get_group_ids();

		if ( empty( $group_ids ) ) {
			return;
		}

		foreach ( $group_ids as $group_id ) {

			$user_emails = masteriyo_get_members_emails_from_group( $group_id );

			if ( empty( $user_emails ) ) {
				return;
			}

			masteriyo_update_user_enrollments_status( $group_id, $user_emails, UserCourseStatus::INACTIVE );
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

		$group_id  = get_post_meta( $id, '_created_group_id', true ); // New group purchase flow.
		$group_ids = $group_id ? array( $group_id ) : $order->get_group_ids();

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

		$group_id  = get_post_meta( $id, '_created_group_id', true ); // New group purchase flow.
		$group_ids = $group_id ? array( $group_id ) : $order->get_group_ids();

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
	 *
	 * @return array|\WP_Error Modified cart item data with group information or WP Error object.
	 */
	public function append_group_course_data_in_cart_item( $cart_item_data ) {
		// Check if this is a group purchase from the new flow
		if ( isset( $_GET['group_purchase'] ) && 'yes' === $_GET['group_purchase'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			$cart_item_data['group_purchase'] = true;
			return $cart_item_data;
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
				// Handle new group purchase flow.
				if ( isset( $cart_item['group_purchase'] ) && $cart_item['group_purchase'] ) {
					$course = $cart_item['data'];
					if ( $course ) {
						$group_price = get_post_meta( $course->get_id(), '_group_courses_group_price', true );

						if ( function_exists( 'masteriyo_get_currency_and_pricing_zone_based_on_course' ) ) {
							list( $currency, $pricing_zone ) = masteriyo_get_currency_and_pricing_zone_based_on_course( $course->get_id() );

							if ( ! empty( $currency ) ) {
								$modified_group_price = masteriyo_get_country_based_group_course_price( $course->get_id(), $group_price, $pricing_zone );
								$group_price          = $modified_group_price ? $modified_group_price : $group_price;
							}
						}

						if ( $group_price ) {
							$cart_item['data']->set_price( $group_price );
							$cart_item['data']->set_regular_price( $group_price );
						}
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
	 * @param string $enrollment_status The enrollment status of the user.
	 *
	 * @return void
	 */
	public static function schedule_group_course_enrollment_email_to_new_member( $user_id, $user, $group_id, $course_id, $enrollment_status ) {
		// Check if this user is the group author - don't send email to group author
		$group_author_id = get_post_field( 'post_author', $group_id );
		if ( $group_author_id && absint( $group_author_id ) === $user_id ) {
			return;
		}

		$email = new GroupCourseEnrollmentEmailToNewMember();

		if ( ! $email->is_enabled() || UserCourseStatus::ACTIVE !== $enrollment_status ) {
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
		// Check if this user is the group author - don't send email to group author
		$group_author_id = get_post_field( 'post_author', $group_id );
		if ( $group_author_id && absint( $group_author_id ) === $user_id ) {
			return;
		}

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
		 * Schedules or directly triggers a group published email to the group author when group status changes to published.
		 * This email is sent only once when the group is first published, not on subsequent updates.
		 *
		 * @since 1.20.0
		 *
		 * @param int $group_id The ID of the group that was updated.
		 * @param \Masteriyo\Addons\GroupCourses\Models\Group $group The group object.
		 *
		 * @return void
		 */
	public function schedule_group_published_email_to_author( $group_id, $group ) {
		if ( ! $group || ! $group_id ) {
			return;
		}

		// Only send email when group status changes to published
		if ( PostStatus::PUBLISH !== $group->get_status() ) {
			return;
		}

		// Check if this email has already been sent to prevent duplicates
		$email_sent = get_post_meta( $group_id, '_group_published_email_sent', true );
		if ( $email_sent ) {
			return;
		}

		$group_author_id = $group->get_author_id();
		if ( ! $group_author_id ) {
			return;
		}

		$group_author = get_userdata( $group_author_id );
		if ( ! $group_author ) {
			return;
		}

		$email = new GroupPublishedEmailToAuthor();

		if ( ! $email->is_enabled() ) {
			return;
		}

		if ( self::is_email_schedule_enabled() ) {
			as_enqueue_async_action(
				$email->get_schedule_handle(),
				array(
					'author_id' => $group_author_id,
					'group_id'  => $group_id,
				),
				'masteriyo'
			);
		} else {
			$email->trigger( $group_author_id, $group_id );
		}

		// Mark email as sent to prevent duplicates
		update_post_meta( $group_id, '_group_published_email_sent', true );
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

		$group_id  = get_post_meta( $order->get_id(), '_created_group_id', true ); // New group purchase flow.
		$group_ids = $group_id ? array( $group_id ) : $order->get_group_ids();

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

		// Enroll members in associated courses
		$this->enroll_group_members_in_courses( $group_id );
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
				// Handle new group purchase flow - mark order for group creation
				if ( isset( $cart_content['group_purchase'] ) && $cart_content['group_purchase'] ) {
					// Get course ID from the cart item data object
					$course_id = isset( $cart_content['course_id'] ) ? $cart_content['course_id'] :
								( isset( $cart_content['data'] ) && is_object( $cart_content['data'] ) ? $cart_content['data']->get_id() : 0 );

					if ( $course_id ) {
						// Store flag to create group after order completion
						$order->update_meta_data( '_create_group_after_completion', 'yes' );
						$order->update_meta_data( '_group_course_id', $course_id );
						$order->save_meta_data();
					}
				}
			}
		}
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
		// Add currentUserHasGroups to account data
		if ( isset( $scripts['account']['data'] ) && is_user_logged_in() ) {
			$user_id = get_current_user_id();

			// Query groups where the current user is the author
			$args = array(
				'post_type'      => PostType::GROUP,
				'post_status'    => array( PostStatus::PUBLISH, PostStatus::DRAFT ),
				'author'         => $user_id,
				'posts_per_page' => 1,
				'fields'         => 'ids',
			);

			$query = new \WP_Query( $args );

			// Check if user has any groups as author
			$scripts['account']['data']['currentUserHasGroups'] = $query->have_posts() ? 'yes' : 'no';
		}

		return $scripts;
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
		// Check if group selling is enabled for this course (default to true if not set)
		$group_enabled_meta = get_post_meta( $course->get_id(), '_group_courses_enabled', true );
		// Only hide if explicitly set to 'no'
		if ( 'no' === $group_enabled_meta ) {
			remove_action( 'masteriyo_template_group_btn', array( $this, 'get_group_btn_template' ), 20, 1 );
			return;
		}

		$group_price = get_post_meta( $course->get_id(), '_group_courses_group_price', true );

		if ( ! $group_price ) {
			remove_action( 'masteriyo_template_group_btn', array( $this, 'get_group_btn_template' ), 20, 1 );
			return;
		}

		if ( masteriyo_is_courses_page() ) {
			return;
		}

		if ( ! $course || ! $course instanceof \Masteriyo\Models\Course ) {
			return;
		}

		if ( ! $course->is_purchasable() || ! $course->get_price() ) {
			return;
		}

		$group_price = floatval( get_post_meta( $course->get_id(), '_group_courses_group_price', true ) );

		if ( ! $group_price ) {
			return;
		}

		/**
		 * Filter the price for the group buy button.
		 *
		 * @since 1.17.1
		 *
		 * @param int    $group_price The group price for the course.
		 * @param int    $course_id   The course ID.
		 *
		 * @return int The filtered group price.
		 */
		$group_price = apply_filters( 'masteriyo_group_buy_btn_price', $group_price, $course->get_id() );

		$currency = '';

		if ( function_exists( 'masteriyo_get_currency_and_pricing_zone_based_on_course' ) ) {
			list( $currency,  ) = masteriyo_get_currency_and_pricing_zone_based_on_course( $course->get_id() );
		}

		$max_group_size = absint( get_post_meta( $course->get_id(), '_group_courses_max_group_size', true ) );

		masteriyo_get_template(
			'group-courses/group-buy-btn.php',
			array(
				'group_price'    => masteriyo_price( $group_price, array( 'currency' => $currency ) ),
				'course_id'      => $course->get_id(),
				'course'         => $course,
				'max_group_size' => $max_group_size,
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
		$layout = masteriyo_get_setting( 'single_course.display.template.layout' ) ?? 'default';

		if ( masteriyo_is_single_course_page() && 'default' === $layout ) {
			return;
		}

		// Exit early if the user already has a group for this course.
		if ( $this->user_has_a_group_for_this_course( $course->get_id() ) ) {
			return;
		}

		/**
		 * Hook: masteriyo_template_group_btn
		 *
		 * Fires to display the group purchase button template for a course.
		 *
		 * @since 1.9.0
		 *
		 * @param \Masteriyo\Models\Course $course The course object for which to display the group buy button.
		 */
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
		$layout = masteriyo_get_setting( 'single_course.display.template.layout' ) ?? 'default';

		if ( masteriyo_is_single_course_page() && 'layout1' === $layout ) {
			return;
		}

		// Exit early if the user already has a group for this course.
		if ( $this->user_has_a_group_for_this_course( $course->get_id() ) ) {
			return;
		}

		/**
		 * Hook: masteriyo_template_group_btn
		 *
		 * Fires to display the group purchase button template for a course.
		 *
		 * @since 1.9.0
		 *
		 * @param \Masteriyo\Models\Course $course The course object for which to display the group buy button.
		 */
		do_action( 'masteriyo_template_group_btn', $course );
	}

	/**
	 * Display group details section after order summary.
	 *
	 * @since 1.20.0
	 *
	 * @param \Masteriyo\Models\Order\Order $order Order object.
	 */
	public function display_group_details_section( $order ) {
		if ( ! $order || ! $order instanceof \Masteriyo\Models\Order\Order ) {
			return;
		}

		$created_group_id = get_post_meta( $order->get_id(), '_created_group_id', true ) ?? get_post_meta( $order->get_id(), '_created_group_id', true );
		if ( ! $created_group_id ) {
			return;
		}

		$group = masteriyo_get_group( $created_group_id );
		if ( ! $group ) {
			return;
		}

		$groups_url = masteriyo_get_page_permalink( 'account' ) . '#/groups';

		// Different messages based on order/group status
		if ( OrderStatus::COMPLETED === $order->get_status() && PostStatus::PUBLISH === $group->get_status() ) {
			$status_text = __( 'Active', 'learning-management-system' );
		} else {
			$status_text = __( 'Pending (awaiting payment confirmation)', 'learning-management-system' );
		}
		?>
		<h3 style="margin-top: 20px; margin-bottom: 10px;"><?php esc_html_e( 'Group Details', 'learning-management-system' ); ?></h3>

		<ul class="masteriyo-order-overview masteriyo-group-details">
			<li class="masteriyo-order-overview__group-name">
				<?php esc_html_e( 'Group name:', 'learning-management-system' ); ?>
				<strong><?php echo esc_html( $group->get_title() ); ?></strong>
			</li>

			<li class="masteriyo-order-overview__group-status">
				<?php esc_html_e( 'Group status:', 'learning-management-system' ); ?>
				<strong><?php echo esc_html( $status_text ); ?></strong>
			</li>

			<li class="masteriyo-order-overview__group-link">
				<?php esc_html_e( 'Manage group:', 'learning-management-system' ); ?>
				<strong><a href="<?php echo esc_url( $groups_url ); ?>"><?php esc_html_e( 'View in account', 'learning-management-system' ); ?></a></strong>
			</li>
		</ul>
		<?php
	}

	/**
		 * Add group information to invoice data.
		 *
		 * @since 1.20.0
		 *
		 * @param array $data Invoice data.
		 * @param \Masteriyo\Models\Order\Order $order Order object.
		 *
		 * @return array Modified invoice data with group information.
		 */
	public function add_group_info_to_invoice_data( $data, $order ) {
		if ( ! $order || ! $order instanceof \Masteriyo\Models\Order\Order ) {
			return $data;
		}

		// Check if this order has a created group
		$created_group_id = get_post_meta( $order->get_id(), '_created_group_id', true );
		if ( ! $created_group_id ) {
			return $data;
		}

		$group = masteriyo_get_group( $created_group_id );
		if ( ! $group ) {
			return $data;
		}

		// Add group information to invoice data
		$data['group_info'] = array(
			'id'           => $group->get_id(),
			'name'         => $group->get_title(),
			'status'       => $group->get_status(),
			'member_count' => count( $group->get_emails() ),
		);

		// Add group purchase indicator to course data
		if ( isset( $data['course_data'] ) && is_array( $data['course_data'] ) ) {
			foreach ( $data['course_data'] as $key => $course ) {
				$data['course_data'][ $key ]['is_group_purchase'] = true;
			}
		}

		return $data;
	}

	/**
	 * Display group information in invoice.
	 *
	 * @since 1.20.0
	 *
	 * @param array $invoice_data Invoice data array.
	 */
	public function display_group_info_in_invoice( $invoice_data ) {
		if ( ! isset( $invoice_data['group_info'] ) || empty( $invoice_data['group_info'] ) ) {
			return;
		}

		masteriyo_get_template(
			'group-courses/order/invoice-group-info.php',
			array(
				'invoice_data' => $invoice_data,
			)
		);
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
			'group-courses/group-buy-btn.php'              => 'group-buy-btn.php',
			'group-courses/emails/group-joining.php'       => 'emails/group-joining.php',
			'group-courses/emails/group-course-enroll.php' => 'emails/group-course-enroll.php',
			'group-courses/emails/group-published.php'     => 'emails/group-published.php',
			'group-courses/order/invoice-group-info.php'   => 'order/invoice-group-info.php',
		);

		if ( isset( $template_map[ $template_name ] ) ) {
			$new_template = trailingslashit( Constants::get( 'MASTERIYO_GROUP_COURSES_TEMPLATES' ) ) . $template_map[ $template_name ];

			return file_exists( $new_template ) ? $new_template : $template;
		}

		return $template;
	}

	/**
		 * Create group automatically when order is created.
		 *
		 * @since 1.20.0
		 *
		 * @param \Masteriyo\Models\Order\Order $order Order object.
		 */
	public function create_group_on_order_creation( $order ) {
		if ( ! $order ) {
			return;
		}

		// Check if this order needs group creation
		$create_group = $order->get_meta( '_create_group_after_completion', true );
		if ( 'yes' !== $create_group ) {
			return;
		}

		$course_id = $order->get_meta( '_group_course_id', true );
		if ( ! $course_id ) {
			return;
		}

		$course = masteriyo_get_course( $course_id );
		if ( ! $course ) {
			return;
		}

		$user = masteriyo_get_user( $order->get_customer_id() );
		if ( ! $user ) {
			return;
		}

		// Create the group with status based on order status
		$group = masteriyo_create_group_object();
		// Set temporary title for creation
		/* translators: % 1$s: Course name */
		$group->set_title( sprintf( __( '%s - Group', 'learning-management-system' ), $course->get_name() ) );
		/* translators: % 1$s: Course name, % 2$d: Order ID */
		$group->set_description( sprintf( __( 'Group created for course: %1$s (Order #%2$d)', 'learning-management-system' ), $course->get_name(), $order->get_id() ) );
		$group->set_author_id( $user->get_id() );

		// Set group status based on order status
		$group_status = ( OrderStatus::COMPLETED === $order->get_status() ) ? PostStatus::PUBLISH : PostStatus::DRAFT;
		$group->set_status( $group_status );

		$group->set_emails( array( $user->get_email() ) );

		$group_repository = masteriyo_create_group_store();
		$group_repository->create( $group );

		// Update title with the actual group ID after creation
		if ( $group->get_id() ) {
			// Re-read the group to ensure proper change tracking
			$group = masteriyo_get_group( $group->get_id() );
			if ( $group ) {
				/* translators: % 1$s: Course name, % 2$d: Group ID */
				$group->set_title( sprintf( __( '%1$s - Group #%2$d', 'learning-management-system' ), $course->get_name(), $group->get_id() ) );
				$group_repository->update( $group );
			}

			$course_data = array(
				'course_id'       => $course->get_id(),
				'order_id'        => $order->get_id(),
				'enrolled_status' => ( OrderStatus::COMPLETED === $order->get_status() ) ? UserCourseStatus::ACTIVE : UserCourseStatus::INACTIVE,
			);
			update_post_meta( $group->get_id(), 'masteriyo_course_data', array( $course_data ) );

			// Store group ID in order meta
			$order->update_meta_data( '_created_group_id', $group->get_id() );
			$order->save_meta_data();

			// Remove the creation flag
			$order->delete_meta_data( '_create_group_after_completion' );
			$order->save_meta_data();
		}
	}

	/**
		 * Update group status when order status changes.
		 *
		 * @since 1.20.0
		 *
		 * @param int $order_id Order ID.
		 * @param string $old_status Old order status.
		 * @param string $new_status New order status.
		 */
	public function update_group_status_on_order_change( $order_id, $old_status, $new_status ) {
		$order = masteriyo_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		// Check if this order has a created group
		$group_id = get_post_meta( $order_id, '_created_group_id', true );
		if ( ! $group_id ) {
			return;
		}

		$group = masteriyo_get_group( $group_id );
		if ( ! $group ) {
			return;
		}

		// Update group status based on order status
		if ( OrderStatus::COMPLETED === $new_status && PostStatus::PUBLISH !== $group->get_status() ) {
			$group->set_status( PostStatus::PUBLISH );
			$group_repository = masteriyo_create_group_store();
			$group_repository->update( $group );
		} elseif ( OrderStatus::COMPLETED !== $new_status && PostStatus::DRAFT !== $group->get_status() ) {
			// Set to draft for any non-completed status (on-hold, pending, processing, cancelled, failed, refunded, trash)
			$group->set_status( PostStatus::DRAFT );
			$group_repository = masteriyo_create_group_store();
			$group_repository->update( $group );
		}
	}

	/**
	 * Set group to draft when order is trashed.
	 *
	 * @since 1.20.0
	 *
	 * @param int $order_id Order ID.
	 * @param \Masteriyo\Models\Order\Order $order Order object.
	 */
	public function set_group_to_draft_on_order_trash( $order_id, $order ) {
		if ( ! $order ) {
			return;
		}

		$group_id = get_post_meta( $order_id, '_created_group_id', true );
		if ( ! $group_id ) {
			return;
		}

		$group = masteriyo_get_group( $group_id );
		if ( ! $group || PostStatus::DRAFT === $group->get_status() ) {
			return;
		}

		$group->set_status( PostStatus::DRAFT );
		$group_repository = masteriyo_create_group_store();
		$group_repository->update( $group );
	}

	/**
	 * Set group to draft when order is deleted.
	 *
	 * @since 1.20.0
	 *
	 * @param int $order_id Order ID.
	 * @param \Masteriyo\Models\Order\Order $order Order object.
	 */
	public function set_group_to_draft_on_order_delete( $order_id, $order ) {
		if ( ! $order ) {
			return;
		}

		$group_id = get_post_meta( $order_id, '_created_group_id', true );
		if ( ! $group_id ) {
			return;
		}

		$group = masteriyo_get_group( $group_id );
		if ( ! $group || PostStatus::DRAFT === $group->get_status() ) {
			return;
		}

		$group->set_status( PostStatus::DRAFT );
		$group_repository = masteriyo_create_group_store();
		$group_repository->update( $group );
	}

	/**
	 * Restore group status when order is restored from trash.
	 *
	 * @since 1.20.0
	 *
	 * @param int $order_id Order ID.
	 * @param \Masteriyo\Models\Order\Order $order Order object.
	 */
	public function restore_group_status_on_order_restore( $order_id, $order ) {
		if ( ! $order ) {
			return;
		}

		$group_id = get_post_meta( $order_id, '_created_group_id', true );
		if ( ! $group_id ) {
			return;
		}

		$group = masteriyo_get_group( $group_id );
		if ( ! $group ) {
			return;
		}

		// Set group status based on restored order status
		if ( OrderStatus::COMPLETED === $order->get_status() && PostStatus::PUBLISH !== $group->get_status() ) {
			$group->set_status( PostStatus::PUBLISH );
			$group_repository = masteriyo_create_group_store();
			$group_repository->update( $group );
		} elseif ( OrderStatus::COMPLETED !== $order->get_status() && PostStatus::DRAFT !== $group->get_status() ) {
			$group->set_status( PostStatus::DRAFT );
			$group_repository = masteriyo_create_group_store();
			$group_repository->update( $group );
		}
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
			// Default to true if not set
			$enabled_meta = get_post_meta( $course->get_id(), '_group_courses_enabled', true );
			// Consider enabled unless explicitly set to 'no'
			$enabled = ( 'no' !== $enabled_meta );

			$data['group_courses'] = array(
				'enabled'        => $enabled,
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

		if ( isset( $request['group_courses']['enabled'] ) ) {
			$enabled = $request['group_courses']['enabled'] ? 'yes' : 'no';
			update_post_meta( $id, '_group_courses_enabled', $enabled );
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
							'enabled'        => array(
								'description' => __( 'Enable selling to groups.', 'learning-management-system' ),
								'type'        => 'boolean',
								'default'     => true,
								'context'     => array( 'view', 'edit' ),
							),
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
				'menu_title' => '↳ ' . __( 'Groups', 'learning-management-system' ),
				'position'   => 26,
				'hide'       => true,

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

		$status = OrderStatus::COMPLETED === $order_status ? UserCourseStatus::ACTIVE : UserCourseStatus::INACTIVE;

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
				 * @param string  $status    The enrollment status of the user.
				 */
				do_action( 'masteriyo_group_enrollment_course_user_added', $user->ID, $user, $group_id, $course_id, $status );
			}
		}
	}

	/**
	 * Enrolls group members in courses associated with the group.
	 *
	 * @since 1.20.0
	 *
	 * @param int $group_id The group ID.
	 */
	private function enroll_group_members_in_courses( $group_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'masteriyo_user_items';

		// Get course data associated with this group
		$course_data = get_post_meta( $group_id, 'masteriyo_course_data', true );
		if ( empty( $course_data ) || ! is_array( $course_data ) ) {
			return;
		}

		// Get group members
		$members = masteriyo_get_members_emails_from_group( $group_id );
		if ( empty( $members ) ) {
			return;
		}

		// Get group object to check status
		$group = masteriyo_get_group( $group_id );
		if ( ! $group ) {
			return;
		}

		foreach ( $course_data as $data ) {
			if ( ! isset( $data['course_id'] ) ) {
				continue;
			}

			$course_id = absint( $data['course_id'] );
			$order_id  = isset( $data['order_id'] ) ? absint( $data['order_id'] ) : 0;

			// Check if order exists and is completed
			$order             = $order_id ? masteriyo_get_order( $order_id ) : null;
			$enrollment_status = ( $order && OrderStatus::COMPLETED === $order->get_status() && PostStatus::PUBLISH === $group->get_status() ) ? UserCourseStatus::ACTIVE : UserCourseStatus::INACTIVE;

			foreach ( $members as $member_email ) {
				$user = get_user_by( 'email', $member_email );
				if ( ! $user || masteriyo_is_user_already_enrolled( $user->ID, $course_id ) ) {
					continue;
				}

				$user_items_data = array(
					'user_id'    => $user->ID,
					'item_id'    => $course_id,
					'item_type'  => 'user_course',
					'status'     => $enrollment_status,
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
					 * @param string  $status    The enrollment status of the user.
					 */
					do_action( 'masteriyo_group_enrollment_course_user_added', $user->ID, $user, $group_id, $course_id, $enrollment_status );
				}
			}
		}
	}

	/**
		 * Determines whether the currently logged-in user owns a group for the specified course.
		 *
		 * Performs a database lookup to check if the user has any published group
		 * post (`mto-group` post type) associated with the given course ID.
		 *

		 * @since x.x.x

		 * @since 1.20.0

		 *
		 * @param int $course_id Course ID to check against.
		 *
		 * @return bool True if the user owns a group for the given course, false otherwise.
		 */
	private function user_has_a_group_for_this_course( $course_id ) {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		global $wpdb;
		$user_id = get_current_user_id();

		$query = $wpdb->prepare(
			"SELECT COUNT(DISTINCT g.ID)
			FROM {$wpdb->posts} g
			INNER JOIN {$wpdb->postmeta} gm ON g.ID = gm.post_id AND gm.meta_key = 'masteriyo_course_data'
			WHERE g.post_type = 'mto-group'
			AND g.post_author = %d
			AND g.post_status = 'publish'
			AND gm.meta_value LIKE %s",
			$user_id,
			'%:"course_id";i:' . intval( $course_id ) . ';s:%'
		);

		$count = $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return $count > 0;
	}
}
