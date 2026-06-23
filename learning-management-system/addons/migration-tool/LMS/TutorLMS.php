<?php
/**
 * Migration Tool helper class.
 *
 * @since 1.13.0
 */
namespace Masteriyo\Addons\MigrationTool\LMS;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Addons\MigrationTool\Helper;
use Masteriyo\Enums\CommentStatus;
use Masteriyo\Enums\CommentType;
use Masteriyo\Enums\CourseAccessMode;
use Masteriyo\Enums\CoursePriceType;
use Masteriyo\Enums\OrderStatus;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Enums\UserCourseStatus;
use Masteriyo\PostType\PostType;
use Masteriyo\Roles;
use WP_Query;

class TutorLMS {

	/**
	 * Links a WooCommerce order to Masteriyo by setting _masteriyo_course_id on
	 * the matching order item. No mto-order post is created — WC orders are the
	 * source of truth for the WC integration, exactly as in the live WC addon.
	 *
	 * @since x.x.x
	 *
	 * @param int $order_id WC order ID.
	 * @param int $course_id Masteriyo course post ID.
	 */
	public static function sync_wc_order_with_masteriyo( $order_id, $course_id ) {
		if ( ! function_exists( 'wc_get_order' ) ) {
			return;
		}

		$wc_order = \wc_get_order( $order_id );
		if ( ! $wc_order ) {
			return;
		}

		$product_id = (int) get_post_meta( $course_id, '_wc_product_id', true );

		foreach ( $wc_order->get_items() as $order_item ) {
			if ( ! is_a( $order_item, 'WC_Order_Item_Product' ) ) {
				continue;
			}
			// Tag the order item if it matches this course's WC product,
			// or if _masteriyo_course_id is not yet set on it.
			if ( $product_id && (int) $order_item->get_product_id() === $product_id ) {
				$order_item->update_meta_data( '_masteriyo_course_id', $course_id );
				$order_item->save_meta_data();
			}
		}

		// Link the WC order ID to the enrollment using the same meta key the WC addon reads.
		$buyer_user_id = (int) $wc_order->get_customer_id();
		if ( $buyer_user_id ) {
			$user_course = masteriyo_get_user_course_by_user_and_course( $buyer_user_id, $course_id );
			if ( $user_course ) {
				$user_course->update_meta_data( '_wc_order_id', $order_id );
				$user_course->save_meta_data();
			}
		}
	}

	/**
	 * Creates a Masteriyo native order (mto-order) from an EDD payment record.
	 * EDD has no built-in Masteriyo integration, so orders are fully migrated.
	 *
	 * @since x.x.x
	 *
	 * @param int $payment_id EDD payment post ID.
	 * @param int $course_id  Masteriyo course post ID.
	 */
	public static function insert_edd_order( $payment_id, $course_id ) {
		if ( ! function_exists( 'edd_get_payment' ) ) {
			return;
		}

		$payment = edd_get_payment( $payment_id );
		if ( ! $payment || ! $payment->ID ) {
			masteriyo_get_logger()->warning(
				sprintf( 'Order %d skipped: EDD payment record not found.', $payment_id ),
				array( 'source' => 'migration-tool' )
			);
			return;
		}

		$status_map = array(
			'complete'    => OrderStatus::COMPLETED,
			'publish'     => OrderStatus::COMPLETED,
			'pending'     => OrderStatus::PENDING,
			'processing'  => OrderStatus::PENDING,
			'refunded'    => OrderStatus::REFUNDED,
			'failed'      => OrderStatus::FAILED,
			'cancelled'   => OrderStatus::CANCELLED,
			'revoked'     => OrderStatus::CANCELLED,
			'preapproval' => OrderStatus::PENDING,
			'abandoned'   => OrderStatus::CANCELLED,
		);

		$masteriyo_status = $status_map[ $payment->status ] ?? OrderStatus::PENDING;
		$payment_date     = $payment->date ? $payment->date : current_time( 'mysql', true );
		$user_info        = is_array( $payment->user_info ) ? $payment->user_info : array();

		$order = masteriyo( 'order' );
		$order->set_status( $masteriyo_status );
		$order->set_customer_id( absint( $payment->user_id ) );
		$order->set_total( (float) $payment->total );
		$edd_currency = function_exists( 'edd_get_currency' ) ? edd_get_currency() : get_option( 'woocommerce_currency', 'USD' );
		$order->set_currency( isset( $payment->currency ) ? $payment->currency : $edd_currency );
		$order->set_transaction_id( $payment->transaction_id ?? '' );
		$order->set_payment_method( $payment->gateway ?? '' );
		$order->set_payment_method_title( $payment->gateway ?? '' );
		$order->set_billing_email( $payment->email ?? '' );
		$order->set_billing_first_name( $user_info['first_name'] ?? '' );
		$order->set_billing_last_name( $user_info['last_name'] ?? '' );
		$order->set_date_created( $payment_date );
		$order->set_created_via( 'migration' );

		if ( in_array( $payment->status, array( 'complete', 'publish' ), true ) ) {
			// EDD $payment->date is the order creation date; completed_date is when payment actually succeeded.
			$paid_date = ! empty( $payment->completed_date ) ? $payment->completed_date : $payment_date;
			$order->set_date_paid( $paid_date );
		}

		masteriyo( 'order.store' )->create( $order );

		if ( ! $order->get_id() ) {
			masteriyo_get_logger()->error(
				sprintf( 'Could not create order for EDD payment %d.', $payment_id ),
				array( 'source' => 'migration-tool' )
			);
			return;
		}

		$order_item = masteriyo( 'order-item.course' );
		$order_item->set_order_id( $order->get_id() );
		$order_item->set_course_id( $course_id );
		$order_item->set_name( get_the_title( $course_id ) );
		$order_item->set_quantity( 1 );
		// Use pre-discount subtotal; $payment->total is the post-discount amount.
		$order_item->set_subtotal( isset( $payment->subtotal ) && $payment->subtotal > 0 ? (float) $payment->subtotal : (float) $payment->total );
		$order_item->set_total( (float) $payment->total );

		masteriyo( 'order-item.course.store' )->create( $order_item );

		// Link the order ID to the enrollment so payment history appears on the student account page.
		$user_course = masteriyo_get_user_course_by_user_and_course( absint( $payment->user_id ), $course_id );
		if ( $user_course ) {
			$user_course->set_order_id( $order->get_id() );
			$user_course->save();
		}
	}

	/**
	 * Creates a Masteriyo native order (mto-order) from a Tutor native checkout record.
	 *
	 * Reads from {prefix}tutor_orders and {prefix}tutor_customers, maps statuses, and
	 * inserts a mto-order post with order items and billing meta so the order is fully
	 * owned by Masteriyo after migration.
	 *
	 * @since x.x.x
	 *
	 * @param int $order_id  Tutor order ID (tutor_orders.id).
	 * @param int $course_id Masteriyo course post ID.
	 */
	public static function insert_tutor_native_order( $order_id, $course_id ) {
		global $wpdb;

		$customers_table = $wpdb->prefix . 'tutor_customers';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$customers_table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $customers_table ) ) === $customers_table;

		if ( $customers_table_exists ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$tutor_order = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT o.*, c.billing_first_name, c.billing_last_name, c.billing_email,
					        c.billing_phone, c.billing_address, c.billing_city, c.billing_state,
					        c.billing_country, c.billing_zip_code
					 FROM {$wpdb->prefix}tutor_orders o
					 LEFT JOIN {$customers_table} c ON c.user_id = o.user_id
					 WHERE o.id = %d",
					$order_id
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$tutor_order = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT o.* FROM {$wpdb->prefix}tutor_orders o WHERE o.id = %d",
					$order_id
				)
			);
		}

		if ( ! $tutor_order ) {
			masteriyo_get_logger()->warning(
				sprintf( 'Order %d skipped: Tutor order record not found.', $order_id ),
				array( 'source' => 'migration-tool' )
			);
			return;
		}

		$masteriyo_status = OrderStatus::PENDING;
		if ( 'paid' === $tutor_order->payment_status || 'completed' === $tutor_order->order_status ) {
			$masteriyo_status = OrderStatus::COMPLETED;
		} elseif ( in_array( $tutor_order->payment_status, array( 'refunded', 'partially-refunded' ), true ) ) {
			$masteriyo_status = OrderStatus::REFUNDED;
		} elseif ( 'failed' === $tutor_order->payment_status ) {
			$masteriyo_status = OrderStatus::FAILED;
		} elseif ( in_array( $tutor_order->order_status, array( 'cancelled', 'trash' ), true ) ) {
			$masteriyo_status = OrderStatus::CANCELLED;
		}

		$order_date   = $tutor_order->created_at_gmt ? $tutor_order->created_at_gmt : current_time( 'mysql', true );
		$total        = isset( $tutor_order->total_price ) ? (float) $tutor_order->total_price : 0.0;
		$tutor_option = get_option( 'tutor_option' );
		// Prefer the per-order currency column; fall back to the site-wide setting.
		$currency = ! empty( $tutor_order->currency )
			? $tutor_order->currency
			: ( ( is_array( $tutor_option ) && ! empty( $tutor_option['tutor_currency'] ) )
				? $tutor_option['tutor_currency']
				: get_option( 'woocommerce_currency', 'USD' ) );

		$order = masteriyo( 'order' );
		$order->set_status( $masteriyo_status );
		$order->set_customer_id( absint( $tutor_order->user_id ) );
		$order->set_total( $total );
		$order->set_currency( $currency );
		$order->set_transaction_id( $tutor_order->transaction_id ?? '' );
		$order->set_payment_method( $tutor_order->payment_method ?? '' );
		$order->set_payment_method_title( $tutor_order->payment_method ?? '' );
		$order->set_billing_email( $tutor_order->billing_email ?? '' );
		$order->set_billing_first_name( $tutor_order->billing_first_name ?? '' );
		$order->set_billing_last_name( $tutor_order->billing_last_name ?? '' );
		$order->set_billing_phone( $tutor_order->billing_phone ?? '' );
		$order->set_billing_address_1( $tutor_order->billing_address ?? '' );
		$order->set_billing_city( $tutor_order->billing_city ?? '' );
		$order->set_billing_state( $tutor_order->billing_state ?? '' );
		$order->set_billing_country( $tutor_order->billing_country ?? '' );
		$order->set_billing_postcode( $tutor_order->billing_zip_code ?? '' );
		$order->set_date_created( $order_date );
		$order->set_created_via( 'migration' );
		$order->set_customer_note( sanitize_textarea_field( $tutor_order->note ?? '' ) );

		if ( 'paid' === $tutor_order->payment_status ) {
			$order->set_date_paid( $order_date );
		}

		masteriyo( 'order.store' )->create( $order );

		if ( ! $order->get_id() ) {
			masteriyo_get_logger()->error(
				sprintf( 'Could not create order for Tutor order %d.', $order_id ),
				array( 'source' => 'migration-tool' )
			);
			return;
		}

		if ( ! empty( $tutor_order->tax_type ) ) {
			update_post_meta( $order->get_id(), '_tax_type', sanitize_text_field( $tutor_order->tax_type ) );
		}

		$order_item = masteriyo( 'order-item.course' );
		$order_item->set_order_id( $order->get_id() );
		$order_item->set_course_id( $course_id );
		$order_item->set_name( get_the_title( $course_id ) );
		$order_item->set_quantity( 1 );
		$order_item->set_subtotal( isset( $tutor_order->sub_total ) ? (float) $tutor_order->sub_total : $total );
		$order_item->set_total( $total );

		masteriyo( 'order-item.course.store' )->create( $order_item );

		// Link the order ID to the enrollment so payment history appears on the student account page.
		$user_course = masteriyo_get_user_course_by_user_and_course( absint( $tutor_order->user_id ), $course_id );
		if ( $user_course ) {
			$user_course->set_order_id( $order->get_id() );
			$user_course->save();
		}
	}

	/**
	 * Register the WishList service provider in the DI container on-demand.
	 *
	 * Cannot use AbstractLMSMigrator::ensure_service_provider() here because TutorLMS
	 * is a static helper class, not a subclass of AbstractLMSMigrator. Inlines the same
	 * logic so the container key is available before migrate_single_wishlist_user() is called.
	 *
	 * @since x.x.x
	 * @throws \Exception If the WishList addon is not installed.
	 */
	private static function register_wishlist_service_provider(): void {
		global $masteriyo;

		$service_key    = 'wishlist-item';
		$provider_class = \Masteriyo\Addons\WishList\Providers\WishListServiceProvider::class;

		if ( $masteriyo->has( $service_key ) ) {
			return;
		}

		if ( ! class_exists( $provider_class ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \Exception( 'Addon class WishListServiceProvider not found — install the WishList addon to migrate wishlist data.' );
		}

		$masteriyo->addServiceProvider( new $provider_class() );
	}

	/**
	 * Migrate all TutorLMS wishlist entries for a single user to Masteriyo wishlist items.
	 *
	 * Reads _tutor_course_wishlist usermeta for one user (a serialized array of course IDs)
	 * and creates an mto-wishlist-item post for each wishlisted course that hasn't been
	 * migrated yet. O(1) per user vs the old O(courses × users) approach.
	 *
	 * @since x.x.x
	 *
	 * @param int $user_id WordPress user ID.
	 * @return void
	 */
	private static function migrate_single_wishlist_user( int $user_id ): void {
		global $wpdb;

		$wishlist = get_user_meta( $user_id, '_tutor_course_wishlist', true );
		if ( empty( $wishlist ) || ! is_array( $wishlist ) ) {
			delete_user_meta( $user_id, '_tutor_course_wishlist' );
			return;
		}

		$already_migrated = array_map(
			'intval',
			$wpdb->get_col(
				$wpdb->prepare(
					"SELECT post_parent FROM {$wpdb->posts}
					 WHERE post_type = 'mto-wishlist-item' AND post_author = %d",
					$user_id
				)
			)
		);

		foreach ( array_map( 'intval', $wishlist ) as $course_id ) {
			if ( in_array( $course_id, $already_migrated, true ) ) {
				continue;
			}

			$item = masteriyo( 'wishlist-item' );
			$item->set_course_id( $course_id );
			$item->set_author_id( $user_id );
			$item->save();

			if ( ! $item->get_id() ) {
				masteriyo_get_logger()->warning(
					sprintf( 'Could not save wishlist item for user %d, course %d.', $user_id, $course_id ),
					array( 'source' => 'migration-tool' )
				);
			}
		}

		// Delete source meta so this user no longer appears in get_source_ids — makes
		// the step self-cleaning (LIMIT-only pagination, no OFFSET needed).
		delete_user_meta( $user_id, '_tutor_course_wishlist' );
	}

	/**
	 * Migrate a Tutor LMS quiz post to a Masteriyo quiz (mto-quiz).
	 *
	 * Renames the CPT in-place to preserve the post ID, then loads the post through
	 * the Masteriyo Quiz model to write all meta via the model layer so that model
	 * hooks fire and the object cache is invalidated correctly.
	 *
	 * @since x.x.x
	 *
	 * @param \WP_Post $item       Tutor LMS quiz post object.
	 * @param int      $section_id New Masteriyo section post ID (parent).
	 * @param int      $course_id  Masteriyo course post ID.
	 * @return void
	 */
	public static function update_tutor_course_quiz_to_masteriyo( $item, $section_id, $course_id ) {
		global $wpdb;

		if ( 'tutor_quiz' !== $item->post_type ) {
			return;
		}

		$quiz_id = $item->ID;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$wpdb->posts,
			array(
				'post_type'   => 'mto-quiz',
				'post_parent' => $section_id,
			),
			array( 'ID' => $quiz_id ),
			array( '%s', '%d' ),
			array( '%d' )
		);
		clean_post_cache( $quiz_id );

		// Migrate questions before loading the quiz model — process_question_migration_from_tutor
		// uses direct meta writes and must run before we call save() so full_mark is accurate.
		$questions   = ( $quiz_id > 0 ) ? \tutor_utils()->get_questions_by_quiz( $quiz_id ) : array();
		$total_marks = 0;
		foreach ( $questions as $question ) {
			static::process_question_migration_from_tutor( $question, $quiz_id, $course_id, $total_marks );
			$total_marks += (int) $question->question_mark;
		}

		$mto_quiz = masteriyo_get_quiz( $quiz_id );
		if ( ! $mto_quiz ) {
			masteriyo_get_logger()->error(
				sprintf( 'Could not load quiz %d after migration — it may not have been saved correctly.', $quiz_id ),
				array( 'source' => 'migration-tool' )
			);
			return;
		}

		$quiz_options = get_post_meta( $quiz_id, 'tutor_quiz_option', true );
		$quiz_options = is_array( $quiz_options ) ? $quiz_options : array();

		$pass_pct  = (float) ( $quiz_options['passing_grade'] ?? 0 );
		$pass_mark = $pass_pct > 0 ? round( ( $pass_pct / 100 ) * $total_marks, 2 ) : 0.0;

		$duration_secs = static::quiz_duration_to_seconds( $quiz_options['time_limit'] ?? array() );

		$mto_quiz->set_course_id( $course_id );
		$mto_quiz->set_parent_id( $section_id );
		$mto_quiz->set_full_mark( (float) $total_marks );
		$mto_quiz->set_pass_mark( $pass_mark );
		$mto_quiz->set_duration( $duration_secs );
		$mto_quiz->set_questions_display_per_page( (int) ( $quiz_options['max_questions_for_answer'] ?? 0 ) );
		$mto_quiz->set_attempts_allowed( (int) ( $quiz_options['attempts_allowed'] ?? 0 ) );

		if ( method_exists( $mto_quiz, 'set_pass_is_required' ) ) {
			$mto_quiz->set_pass_is_required( ! empty( $quiz_options['pass_is_required'] ) );
		}

		$mto_quiz->save();

		// Store quiz options and pass mark type — no model setters for these fields.
		$pass_mark_type = $quiz_options['passing_grade_by'] ?? 'percentage';
		update_post_meta( $quiz_id, '_mto_quiz_options', $quiz_options );
		update_post_meta( $quiz_id, '_pass_mark_type', $pass_mark_type );
		delete_post_meta( $quiz_id, 'tutor_quiz_option' );

		// questions_order rand and feedback_mode have no free Masteriyo Quiz model equivalent; skipped.
	}

	/**
	 * Migrate a Tutor LMS lesson post to a Masteriyo lesson (mto-lesson).
	 *
	 * Renames the CPT in-place to preserve the post ID, then loads the post through
	 * the Masteriyo Lesson model to write all meta via the model layer.
	 *
	 * @since x.x.x
	 *
	 * @param \WP_Post $item       Tutor LMS lesson post object.
	 * @param int      $section_id New Masteriyo section post ID (parent).
	 * @param int      $course_id  Masteriyo course post ID.
	 * @return void
	 */
	public static function update_tutor_lesson_to_masteriyo( $item, $section_id, $course_id ) {
		global $wpdb;

		if ( 'lesson' !== $item->post_type ) {
			return;
		}

		$lesson_id = $item->ID;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$wpdb->posts,
			array(
				'post_type'   => 'mto-lesson',
				'post_parent' => $section_id,
			),
			array( 'ID' => $lesson_id ),
			array( '%s', '%d' ),
			array( '%d' )
		);
		clean_post_cache( $lesson_id );

		$mto_lesson = masteriyo_get_lesson( $lesson_id );
		if ( ! $mto_lesson ) {
			masteriyo_get_logger()->error(
				sprintf( 'Could not load lesson %d after migration — it may not have been saved correctly.', $lesson_id ),
				array( 'source' => 'migration-tool' )
			);
			return;
		}

		$mto_lesson->set_course_id( $course_id );
		$mto_lesson->set_parent_id( $section_id );

		// Featured image.
		$thumb_id = (int) get_post_thumbnail_id( $lesson_id );
		if ( $thumb_id ) {
			$mto_lesson->set_featured_image( $thumb_id );
		}

		$video = static::map_video_meta( $lesson_id );
		if ( ! empty( $video ) ) {
			$mto_lesson->set_video_source( $video['source'] );
			// Lesson::get_video_source_id() reads video_source_url as absint(), so the attachment ID must be stored as the URL string for self-hosted videos.
			if ( 'self-hosted' === $video['source'] && ! empty( $video['id'] ) ) {
				$mto_lesson->set_video_source_url( (string) $video['id'] );
			} else {
				$mto_lesson->set_video_source_url( $video['url'] );
			}
		}

		$attachments = \tutor_utils()->get_attachments( $lesson_id );
		if ( ! empty( $attachments ) ) {
			$mto_lesson->set_download_materials( wp_list_pluck( $attachments, 'post_id' ) );
		}

		$mto_lesson->save();

		// Preview flag has no free Masteriyo Lesson model field; skipped.
	}

	/**
	 * update tutor enrolled user to masteriyo enrolled user.
	 *
	 * @since 1.13.0
	 */
	public static function update_tutor_enrolled_user_to_masteriyo_enrolled_user( $course_id, $email, $enrolled_user ) {
		global $wpdb;

		$user_row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->users} WHERE user_email = %s",
				$email
			)
		);

		if ( ! $user_row || empty( $user_row->ID ) ) {
			masteriyo_get_logger()->warning(
				sprintf( 'Enrollment skipped: user with email "%s" not found.', $email ),
				array( 'source' => 'migration-tool' )
			);
			return false;
		}

		$wp_user = new \WP_User( $user_row->ID );

		if ( $wp_user->ID ) {
			$masteriyo_roles = array( Roles::ADMIN, Roles::MANAGER, Roles::INSTRUCTOR, Roles::STUDENT );
			if ( empty( array_intersect( $masteriyo_roles, (array) $wp_user->roles ) ) ) {
				$wp_user->add_role( Roles::STUDENT );
			}
			delete_user_meta( $wp_user->ID, '_is_tutor_student' );
			// Remove TutorLMS roles so Tutor permissions don't remain active after migration.
			$wp_user->remove_role( 'tutor_student' );
			$wp_user->remove_role( 'tutor_instructor' );
		}

		$status_map = array(
			'completed' => UserCourseStatus::ACTIVE,
			'cancelled' => UserCourseStatus::INACTIVE,
		);
		$mto_status = $status_map[ $enrolled_user->post_status ] ?? UserCourseStatus::INACTIVE;

		$user_course = masteriyo( 'user-course' );
		$user_course->set_course_id( $course_id );
		$user_course->set_user_id( $wp_user->ID );
		$user_course->set_status( $mto_status );
		$user_course->set_date_start( gmdate( 'Y-m-d H:i:s', strtotime( $enrolled_user->post_date ) ) );
		$user_course->save();

		$inserted_id = $user_course->get_id();

		// UserCourseRepository::create() silently skips duplicates and leaves get_id() = 0.
		// Retrieve the existing enrollment ID in that case so order linkage still works.
		if ( ! $inserted_id ) {
			$existing    = masteriyo_get_user_course_by_user_and_course( $wp_user->ID, $course_id );
			$inserted_id = $existing ? $existing->get_id() : 0;
		}

		if ( ! $inserted_id ) {
			masteriyo_get_logger()->error( 'Could not save enrollment record — it may already exist or the user was not found.', array( 'source' => 'migration-tool' ) );
			return false;
		}

		return $inserted_id;
	}



	/**
	 * Write all Masteriyo course meta from TutorLMS post meta via the Course model layer.
	 *
	 * The CPT has already been renamed to mto-course before this method is called.
	 * Loading through masteriyo_get_course() and calling save() ensures model hooks
	 * fire and the object cache is invalidated correctly.
	 *
	 * @since x.x.x
	 *
	 * @param int $course_id Course post ID (already renamed to mto-course).
	 * @return void
	 */
	public static function update_masteriyo_course_from_tutor( $course_id ) {
		$mto_course = masteriyo_get_course( $course_id );
		if ( ! $mto_course ) {
			masteriyo_get_logger()->error(
				sprintf( 'Could not load course %d after migration — it may not have been saved correctly.', $course_id ),
				array( 'source' => 'migration-tool' )
			);
			return;
		}

		if ( ! function_exists( 'tutor_utils' ) ) {
			return; // TutorLMS inactive — course renamed but pricing/settings skipped.
		}

		// --- Pricing ---
		$regular_price = '';
		$sale_price    = '';
		$product_id    = \tutor_utils()->get_course_product_id( $course_id );
		$monetize_by   = \tutor_utils()->get_option( 'monetize_by' );

		if ( 'wc' === $monetize_by && function_exists( 'wc_get_product' ) ) {
			$wc_product = \wc_get_product( $product_id );
			if ( $wc_product ) {
				$regular_price = \wc_get_price_to_display( $wc_product, array( 'price' => $wc_product->get_regular_price() ) );
				$sale_price    = \wc_get_price_to_display( $wc_product, array( 'price' => $wc_product->get_sale_price() ) );

				$new_type = $wc_product->is_type( 'subscription' ) || $wc_product->is_type( 'variable-subscription' )
					? 'mto_course_recurring'
					: 'mto_course';

				wp_set_object_terms( $product_id, $new_type, 'product_type' );
				update_post_meta( $course_id, '_wc_product_id', $product_id );
				update_post_meta( $product_id, '_masteriyo_course_id', $course_id );
			}
		} elseif ( 'edd' === $monetize_by && \tutor_utils()->has_edd() ) {
			if ( function_exists( 'edd_has_variable_prices' ) && \edd_has_variable_prices( $product_id ) ) {
				$prices        = \edd_get_variable_prices( $product_id );
				$amounts       = wp_list_pluck( $prices, 'amount' );
				$regular_price = (string) max( $amounts );
				$sale_price    = (string) min( $amounts );
			} else {
				$regular_price = (string) \edd_get_download_price( $product_id );
			}
		} else {
			// Tutor native checkout — prices stored without underscore prefix.
			$regular_price = (string) get_post_meta( $course_id, 'tutor_course_price', true );
			$sale_price    = (string) get_post_meta( $course_id, 'tutor_course_sale_price', true );
		}

		$mto_course->set_regular_price( '' !== $regular_price ? $regular_price : '0' );
		$mto_course->set_sale_price( '' !== $sale_price ? $sale_price : '0' );

		// --- Access mode & price type ---
		$public_course = get_post_meta( $course_id, '_tutor_is_public_course', true ) === 'yes';
		$purchasable   = \tutor_utils()->is_course_purchasable( $course_id );

		if ( $public_course ) {
			$mto_course->set_price_type( CoursePriceType::FREE );
			$mto_course->set_access_mode( CourseAccessMode::OPEN );
		} elseif ( $purchasable ) {
			$mto_course->set_price_type( CoursePriceType::PAID );
			$mto_course->set_access_mode( CourseAccessMode::ONE_TIME );
		} else {
			$mto_course->set_price_type( CoursePriceType::FREE );
			$mto_course->set_access_mode( CourseAccessMode::NEED_REGISTRATION );
		}

		// --- Course settings ---
		$course_maximum_student = \tutor_utils()->get_course_settings( $course_id, 'maximum_students', 0 );
		$mto_course->set_enrollment_limit( (int) $course_maximum_student );

		// TutorLMS allows HTML in the course benefits field; sanitize before writing.
		$highlights = wp_kses_post( (string) get_post_meta( $course_id, '_tutor_course_benefits', true ) );
		$mto_course->set_highlights( $highlights );

		// Tutor stores duration as array( 'hours' => N, 'minutes' => M, 'seconds' => S ).
		// Masteriyo Course._duration is in minutes.
		$duration_raw = maybe_unserialize( get_post_meta( $course_id, '_course_duration', true ) );
		if ( is_array( $duration_raw ) && isset( $duration_raw['hours'] ) ) {
			$duration = ( (int) ( $duration_raw['hours'] ?? 0 ) * 60 ) + (int) ( $duration_raw['minutes'] ?? 0 );
		} else {
			$duration = static::convert_time_limit_to_minutes( $duration_raw );
		}
		$mto_course->set_duration( $duration );

		// Per-course retake setting (stored inside _tutor_course_settings array).
		$course_settings = maybe_unserialize( get_post_meta( $course_id, '_tutor_course_settings', true ) );
		$retake_enabled  = ! empty( $course_settings['enable_course_retake'] );
		$mto_course->set_enable_course_retake( $retake_enabled );

		// Featured image.
		$thumb_id = (int) get_post_thumbnail_id( $course_id );
		if ( $thumb_id ) {
			$mto_course->set_featured_image( $thumb_id );
		}

		$mto_course->set_show_curriculum( true );
		$mto_course->set_reviews_allowed( true );
		$mto_course->set_review_after_course_completion( true );

		// --- Difficulty ---
		$course_level  = (string) get_post_meta( $course_id, '_tutor_course_level', true );
		$difficulty_id = static::resolve_course_difficulty_term( $course_level );
		$mto_course->set_difficulty_id( $difficulty_id );

		// --- Taxonomy: categories and tags ---
		$category_ids = static::migrate_course_categories_from_tutor_to_masteriyo( $course_id );
		$mto_course->set_category_ids( $category_ids );

		$tag_ids = static::migrate_course_tags_from_tutor_to_masteriyo( $course_id );
		$mto_course->set_tag_ids( $tag_ids );

		$mto_course->save();

		// --- Fields with no model setter — write directly after save() ---

		// Q&A: _tutor_enable_qa ('yes'/'no') → _enable_course_qa on the mto-course post.
		$enable_qa = get_post_meta( $course_id, '_tutor_enable_qa', true );
		if ( '' !== $enable_qa ) {
			update_post_meta( $course_id, '_enable_course_qa', 'yes' === $enable_qa ? 'yes' : 'no' );
		}

		// Certificate: create and assign a Masteriyo certificate template for every course that had
		// a TutorLMS certificate assigned. Tutor's design cannot be converted — Sample 1 is used.
		$tutor_cert_template = get_post_meta( $course_id, 'tutor_course_certificate_template', true );
		if ( $tutor_cert_template && 'none' !== $tutor_cert_template ) {
			Helper::assign_certificate_template( $course_id, (int) get_post_field( 'post_author', $course_id ) );
		}

		// Video intro on the course level (Course model has no video setters — write raw meta).
		// For self-hosted, _video_source_url must store the attachment ID as a numeric string
		// (mirrors the Lesson model pattern: is_numeric() check + absint() for resolution).
		$course_video = static::map_video_meta( $course_id );
		if ( ! empty( $course_video ) ) {
			update_post_meta( $course_id, '_video_source', $course_video['source'] );
			if ( 'self-hosted' === $course_video['source'] ) {
				if ( ! empty( $course_video['id'] ) ) {
					update_post_meta( $course_id, '_video_source_url', (string) $course_video['id'] );
					update_post_meta( $course_id, '_video_source_id', $course_video['id'] );
				}
			} else {
				update_post_meta( $course_id, '_video_source_url', $course_video['url'] );
				update_post_meta( $course_id, '_video_source_id', $course_video['id'] );
			}
		}

		// Course FAQ, attachments, and prerequisites are Pro-only features — migrated by the Pro plugin directly.

		// No direct Masteriyo equivalent — stored under _migrated_* for post-migration reference.
		$requirements = get_post_meta( $course_id, '_tutor_course_requirements', true );
		if ( ! empty( $requirements ) ) {
			Helper::store_unmigrated_meta( $course_id, 'tutor_course_requirements', $requirements );
		}
		$target_audience = get_post_meta( $course_id, '_tutor_course_target_audience', true );
		if ( ! empty( $target_audience ) ) {
			Helper::store_unmigrated_meta( $course_id, 'tutor_course_target_audience', $target_audience );
		}

		update_post_meta( $course_id, '_was_tutor_course', true );
	}

	/**
	 * Resolve (or create) the Masteriyo course_difficulty term for a TutorLMS level slug.
	 *
	 * Returns the term ID so the caller can pass it to the Course model via
	 * set_difficulty_id() — taxonomy assignment is handled by the repository on save().
	 *
	 * @since x.x.x
	 *
	 * @param string $tutor_level TutorLMS level slug (e.g. 'beginner', 'intermediate', 'advanced').
	 * @return int Masteriyo course_difficulty term ID, or 0 if no level provided.
	 */
	public static function resolve_course_difficulty_term( string $tutor_level ): int {
		// 'all_level' means no specific difficulty in TutorLMS — skip to avoid creating a spurious taxonomy term.
		if ( '' === $tutor_level || 'all_level' === $tutor_level ) {
			return 0;
		}

		$term = get_term_by( 'slug', $tutor_level, 'course_difficulty' );
		if ( $term && ! is_wp_error( $term ) ) {
			return (int) $term->term_id;
		}

		$inserted = wp_insert_term( ucfirst( $tutor_level ), 'course_difficulty', array( 'slug' => $tutor_level ) );
		if ( is_wp_error( $inserted ) ) {
			masteriyo_get_logger()->warning(
				sprintf( 'Could not create difficulty level "%s" — %s', $tutor_level, $inserted->get_error_message() ),
				array( 'source' => 'migration-tool' )
			);
			return 0;
		}

		return (int) $inserted['term_id'];
	}

	/**
	 * Resolve (or create) Masteriyo course_cat terms for all TutorLMS course-category terms
	 * attached to the given course, preserving the parent-child hierarchy.
	 *
	 * Returns the array of Masteriyo term IDs so the caller can pass them to the Course
	 * model via set_category_ids() — taxonomy assignment is handled by the repository on save().
	 *
	 * @since x.x.x
	 *
	 * @param int    $course_id Course post ID.
	 * @param string $taxonomy  TutorLMS category taxonomy slug. Default 'course-category'.
	 * @return int[] Masteriyo course_cat term IDs.
	 */
	public static function migrate_course_categories_from_tutor_to_masteriyo( $course_id, $taxonomy = 'course-category' ): array {
		$categories = wp_get_post_terms( $course_id, $taxonomy, array( 'fields' => 'ids' ) );

		if ( empty( $categories ) || is_wp_error( $categories ) ) {
			return array();
		}

		// Build a slug-keyed hierarchy map first.
		$hierarchy = array();
		foreach ( $categories as $cat_id ) {
			$cat = get_term( $cat_id, $taxonomy );
			if ( is_object( $cat ) && ! is_wp_error( $cat ) ) {
				$hierarchy[ $cat->term_id ] = array(
					'name'   => $cat->name,
					'parent' => $cat->parent,
				);
			}
		}

		$masteriyo_id_map = array(); // tutor term_id → masteriyo term_id

		// Top-level first so parent IDs are available when processing children.
		foreach ( array( 0, 1 ) as $pass ) {
			foreach ( $hierarchy as $cat_id => $cat_info ) {
				$is_child = ( 0 !== (int) $cat_info['parent'] );
				if ( ( 0 === $pass && $is_child ) || ( 1 === $pass && ! $is_child ) ) {
					continue;
				}

				$parent_id        = $is_child ? ( $masteriyo_id_map[ $cat_info['parent'] ] ?? 0 ) : 0;
				$existing         = term_exists( $cat_info['name'], 'course_cat', $is_child ? $parent_id : 0 );
				$masteriyo_cat_id = is_array( $existing ) ? (int) $existing['term_id'] : (int) $existing;

				if ( ! $masteriyo_cat_id ) {
					$args    = $is_child ? array( 'parent' => $parent_id ) : array();
					$created = wp_insert_term( $cat_info['name'], 'course_cat', $args );
					if ( is_wp_error( $created ) ) {
						masteriyo_get_logger()->warning(
							sprintf( 'Could not create course category "%s" — %s', $cat_info['name'], $created->get_error_message() ),
							array( 'source' => 'migration-tool' )
						);
						continue;
					}
					$masteriyo_cat_id = (int) $created['term_id'];
					$image_id         = get_term_meta( $cat_id, 'thumbnail_id', true );
					if ( $image_id ) {
						add_term_meta( $masteriyo_cat_id, '_featured_image', $image_id );
					}
				}

				$masteriyo_id_map[ $cat_id ] = $masteriyo_cat_id;
			}
		}

		return array_values( $masteriyo_id_map );
	}

	/**
	 * Resolve (or create) Masteriyo course_tag terms for all TutorLMS course-tag terms
	 * attached to the given course.
	 *
	 * Returns the array of Masteriyo term IDs so the caller can pass them to the Course
	 * model via set_tag_ids() — taxonomy assignment is handled by the repository on save().
	 *
	 * @since x.x.x
	 *
	 * @param int $course_id Course post ID.
	 * @return int[] Masteriyo course_tag term IDs.
	 */
	public static function migrate_course_tags_from_tutor_to_masteriyo( $course_id ): array {
		$tags = wp_get_post_terms( $course_id, 'course-tag', array( 'fields' => 'names' ) );

		if ( empty( $tags ) || is_wp_error( $tags ) ) {
			return array();
		}

		$term_ids = array();
		foreach ( $tags as $tag_name ) {
			$existing = get_term_by( 'name', $tag_name, 'course_tag' );
			if ( $existing && ! is_wp_error( $existing ) ) {
				$term_ids[] = (int) $existing->term_id;
			} else {
				$created = wp_insert_term( $tag_name, 'course_tag' );
				if ( ! is_wp_error( $created ) ) {
					$term_ids[] = (int) $created['term_id'];
				}
			}
		}

		return $term_ids;
	}

	/**
	 * get course id by tutor course id
	 *
	 * @since 1.13.0
	 *
	 * @param number $course_id
	 * @return object|null
	 */
	public static function get_enrolled_users_by_course_id( $course_id ) {
		global $wpdb;

		$posts_table = $wpdb->prefix . 'posts';
		$users_table = $wpdb->prefix . 'users';

		$sql = "SELECT p.ID, p.post_title, p.post_date, p.post_status, u.user_login, u.user_email
            FROM {$posts_table} AS p
            INNER JOIN {$users_table} AS u ON p.post_author = u.ID
            WHERE p.post_parent = %d
              AND p.post_type = 'tutor_enrolled'
              AND p.post_status != 'trash'";

		$prepared_sql = $wpdb->prepare( $sql, $course_id );//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results      = $wpdb->get_results( $prepared_sql );//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return $results;
	}

	/**
	 * get tutor course reviews
	 *
	 * @since 1.13.0
	 *
	 * @return array|object|null
	 */
	public static function get_tutor_course_reviews() {
		global $wpdb;

		$select_columns =
		'_reviews.comment_ID,
		_reviews.comment_post_ID,
		_reviews.comment_author,
		_reviews.comment_author_email,
		_reviews.comment_date,
		_reviews.comment_content,
		_reviews.comment_approved AS comment_status,
		_reviews.user_id,
		_reviews.comment_parent,
		_rev_meta.meta_value AS rating,
		_reviewer.display_name';

		$query = $wpdb->prepare(
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT {$select_columns}
		FROM 	{$wpdb->comments} _reviews
				INNER JOIN {$wpdb->commentmeta} _rev_meta
					ON _reviews.comment_ID = _rev_meta.comment_id
				LEFT JOIN {$wpdb->users} _reviewer
					ON _reviews.user_id = _reviewer.ID
		WHERE  _reviews.comment_type = 'tutor_course_rating'
 				AND _rev_meta.meta_key = 'tutor_rating'
		ORDER BY _reviews.comment_ID"
		);

		return $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Processes migration for a single Tutor quiz question.
	 *
	 * @since 1.13.0
	 *
	 * @param object $question Tutor quiz question data.
	 * @param int $quiz_id Tutor quiz ID.
	 * @param int $course_id Masteriyo course ID.
	 */
	public static function process_question_migration_from_tutor( $question, $quiz_id, $course_id, $total_mark ) {
		global $wpdb;

		$question_type = static::map_question_type( $question->question_type );

		if ( is_null( $question_type ) ) {
			masteriyo_get_logger()->warning(
				sprintf(
					'Question %d skipped: question type "%s" has no Masteriyo equivalent.',
					$question->question_id,
					$question->question_type
				),
				array( 'source' => 'migration-tool' )
			);
			return;
		}

		$answers_tutor        = static::answer_list_by_question( $question->question_id, $question->question_type );
		$question_description = sanitize_text_field( $question->question_description ) ?? '';
		$answers              = array();

		if ( 'image_sequencing' === $question->question_type ) {
			masteriyo_get_logger()->warning(
				sprintf(
					'Question %d (image_sequencing) migrated as sortable — image attachments dropped; only text labels preserved.',
					$question->question_id
				),
				array( 'source' => 'migration-tool' )
			);
		}

		foreach ( $answers_tutor as $answer_tutor ) {
			if ( 'true_false' === $answer_tutor->belongs_question_type ) {
				$answers[] = array(
					'name'    => $answer_tutor->answer_title,
					'correct' => masteriyo_string_to_bool( $answer_tutor->is_correct ),
				);
			}

			if ( 'single_choice' === $answer_tutor->belongs_question_type ) {
				$answers[] = array(
					'name'               => $answer_tutor->answer_title,
					'correct'            => masteriyo_string_to_bool( $answer_tutor->is_correct ),
					'answer_view_format' => $answer_tutor->answer_view_format ?? '',
					'image_id'           => $answer_tutor->image_id ?? '',
				);
			}

			if ( 'multiple_choice' === $answer_tutor->belongs_question_type ) {
				$answers[] = array(
					'name'               => $answer_tutor->answer_title,
					'correct'            => masteriyo_string_to_bool( $answer_tutor->is_correct ),
					'answer_view_format' => $answer_tutor->answer_view_format ?? '',
					'image_id'           => $answer_tutor->image_id ?? '',
				);
			}

			if ( 'fill_in_the_blank' === $answer_tutor->belongs_question_type ) {
				$answers[] = array( 'name' => '{{' . ( $answer_tutor->answer_one_gap_match ?? '' ) . '}}' );
				if ( ! empty( $answer_tutor->answer_two_gap_match ) ) {
					$answers[] = array( 'name' => '{{' . $answer_tutor->answer_two_gap_match . '}}' );
				}
			}

			// gap_match → fill-in-the-blanks: each answer row is one blank.
			if ( 'gap_match' === $answer_tutor->belongs_question_type ) {
				$answers[] = array( 'name' => '{{' . $answer_tutor->answer_title . '}}' );
			}

			// ordering → sortable: rows are already sorted by answer_order ASC.
			if ( 'ordering' === $answer_tutor->belongs_question_type ) {
				$answers[] = array( 'name' => $answer_tutor->answer_title );
			}

			// image_matching → matching: text-based prompt/match pairs.
			if ( 'image_matching' === $answer_tutor->belongs_question_type ) {
				$answers[] = array(
					'prompt' => $answer_tutor->answer_title,
					'match'  => $answer_tutor->answer_two_gap_match ?? '',
				);
			}

			// match_lists → matching: left column = prompt, right column = match value.
			if ( 'match_lists' === $answer_tutor->belongs_question_type ) {
				$answers[] = array(
					'prompt' => $answer_tutor->answer_title,
					'match'  => $answer_tutor->answer_two_gap_match ?? '',
				);
			}

			// image_sequencing → sortable: images become text labels (image IDs dropped — warning logged above).
			if ( 'image_sequencing' === $answer_tutor->belongs_question_type ) {
				$answers[] = array( 'name' => $answer_tutor->answer_title );
			}

			// text-answer, short_answer, open_ended, essay: no stored answers in Masteriyo — $answers stays empty.
		}

		$existing_question_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta}
				 WHERE meta_key = '_tutor_question_id' AND meta_value = %d
				 LIMIT 1",
				$question->question_id
			)
		);

		if ( $existing_question_id ) {
			return;
		}

		// Direct DB insert bypasses the full wp_insert_post() hook chain (~15 actions per question).
		// Questions are internal CPTs with no public URL, taxonomy, or GUID requirement.
		$now = current_time( 'mysql' );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$wpdb->posts,
			array(
				'post_title'            => $question->question_title,
				'post_content'          => wp_json_encode( $answers ),
				'post_excerpt'          => $question_description,
				'post_type'             => PostType::QUESTION,
				'post_status'           => PostStatus::PUBLISH,
				'post_author'           => (int) ( $question->post_author ?? 0 ),
				'post_parent'           => $quiz_id,
				'post_date'             => $now,
				'post_date_gmt'         => get_gmt_from_date( $now ),
				'post_modified'         => $now,
				'post_modified_gmt'     => get_gmt_from_date( $now ),
				'comment_status'        => 'closed',
				'ping_status'           => 'closed',
				'to_ping'               => '',
				'pinged'                => '',
				'post_content_filtered' => '',
			),
			array( '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		$question_id = (int) $wpdb->insert_id;
		if ( ! $question_id ) {
			masteriyo_get_logger()->error( 'Could not save quiz question — direct DB insert failed.', array( 'source' => 'migration-tool' ) );
			return;
		}

		$meta_rows = array(
			array( $question_id, '_course_id', (string) $course_id ),
			array( $question_id, '_type', $question_type ),
			array( $question_id, '_points', (string) $question->question_mark ),
			array( $question_id, '_parent_id', (string) $quiz_id ),
			array( $question_id, '_tutor_question_id', (string) $question->question_id ),
		);

		if ( $question_description ) {
			$meta_rows[] = array( $question_id, '_enable_description', '1' );
		}

		if ( ! empty( $question->answer_explanation ) ) {
			$meta_rows[] = array( $question_id, '_answer_explanation', wp_kses_post( $question->answer_explanation ) );
		}

		$placeholders = implode( ', ', array_fill( 0, count( $meta_rows ), '(%d, %s, %s)' ) );
		$values       = array();
		foreach ( $meta_rows as $row ) {
			$values[] = $row[0];
			$values[] = $row[1];
			$values[] = $row[2];
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( $wpdb->prepare( "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) VALUES {$placeholders}", ...$values ) );
	}

	/**
	 * gets the list of answers by question id.
	 *
	 * @since 1.13.0
	 *
	 * @param object $question_id Tutor quiz question id.
	 * @param int $question_type Tutor question type.
	 */
	public static function answer_list_by_question( int $question_id, string $question_type ): array {
		global $wpdb;
		$answers = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}tutor_quiz_question_answers
			where belongs_question_id = %d
				AND belongs_question_type = %s
			order by answer_order asc ;",
				$question_id,
				$question_type
			)
		);
		return is_array( $answers ) && count( $answers ) ? $answers : array();
	}

	/**
	 * Add the masteriyo_student user role.
	 *
	 * @since 1.13.0
	 *
	 * @param int $user_id User ID.
	 */
	public static function update_user_role_to_masteriyo_student( $user_id ) {
		$user = new \WP_User( $user_id );

		if ( ! $user || ! isset( $user->ID ) || 0 === $user->ID || ! $user->exists() ) {
			return;
		}

		if (
		! in_array( Roles::ADMIN, (array) $user->roles, true ) &&
		! in_array( Roles::MANAGER, (array) $user->roles, true ) &&
		! in_array( Roles::STUDENT, (array) $user->roles, true ) &&
		! in_array( Roles::INSTRUCTOR, (array) $user->roles, true )
		) {
			$user->add_role( Roles::STUDENT );
			delete_user_meta( $user->get_id(), '_is_tutor_student' );
		}
	}

	/**
	 * get tutor announcements data
	 *
	 * @since 1.13.0
	 *
	 * @return array
	 */
	public static function get_tutor_announcements_data() {
		$args = array(
			'post_type'      => 'tutor_announcements',
			'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
			'posts_per_page' => -1,
			'orderby'        => 'ID',
			'order'          => 'DESC',
		);

		$the_query     = new WP_Query( $args );
		$announcements = $the_query->have_posts() ? $the_query->posts : array();
		return $announcements;
	}


	/**
	 * Convert WooCommerce order status to Masteriyo order status.
	 *
	 * @since 1.13.0
	 *
	 * @param string $status The WooCommerce order status.
	 * @return string The corresponding Masteriyo order status.
	 */
	public static function convert_wc_status( $status ) {
		$map = array(
			'processing'    => OrderStatus::PROCESSING,
			'pending'       => OrderStatus::PENDING,
			'cancelled'     => OrderStatus::CANCELLED,
			'on-hold'       => OrderStatus::ON_HOLD,
			'completed'     => OrderStatus::COMPLETED,
			'refunded'      => OrderStatus::REFUNDED,
			'failed'        => OrderStatus::FAILED,
			'wc-processing' => OrderStatus::PROCESSING,
			'wc-pending'    => OrderStatus::PENDING,
			'wc-cancelled'  => OrderStatus::CANCELLED,
			'wc-on-hold'    => OrderStatus::ON_HOLD,
			'wc-completed'  => OrderStatus::COMPLETED,
			'wc-refunded'   => OrderStatus::REFUNDED,
			'wc-failed'     => OrderStatus::FAILED,
		);

		$new_status = isset( $map[ $status ] ) ? $map[ $status ] : 'pending';

		return OrderStatus::PROCESSING === $new_status ? 'pending' : $new_status;
	}

	/**
	 * Get the duration of a Tutor post in minutes.
	 *
	 * Parses the duration meta field of a Tutor post and converts it to minutes.
	 *
	 * @since 1.13.0
	 *
	 * @param int $post_id The ID of the Tutor post.
	 * @return int Returns the duration in minutes. Returns 0 if the duration is not valid or not set.
	 */
	public static function convert_time_limit_to_minutes( $time_limit ) {
		$time_value = isset( $time_limit['time_value'] ) ? (int) $time_limit['time_value'] : 0;
		$time_type  = isset( $time_limit['time_type'] ) ? $time_limit['time_type'] : '';

		switch ( $time_type ) {
			case 'hours':
				return $time_value * 60;

			case 'days':
				return $time_value * 24 * 60;

			case 'time_limit_seconds':
				// Division produces a float in PHP 7; round before casting.
				return (int) round( $time_value / 60 );

			case 'minutes':
			default:
				return $time_value;
		}
	}


	/**
	 * Assign Masteriyo roles to a single TutorLMS user.
	 *
	 * Idempotent: add_role() is safe to call twice for the same role.
	 * Does NOT remove TutorLMS roles — the old roles are cleaned up by
	 * migrate_single_enrollment() per-enrollment when the user is enrolled.
	 *
	 * @since x.x.x
	 * @param int $user_id WP user ID.
	 * @throws \Exception If the WP user record does not exist.
	 */
	public static function migrate_single_user( int $user_id ): void {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			throw new \Exception( sprintf( 'migrate_single_user: WP user %d not found.', $user_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$roles = (array) $user->roles;
		$caps  = is_array( $user->caps ) ? $user->caps : array();

		if ( in_array( 'tutor_student', $roles, true ) || isset( $caps['tutor_student'] ) ) {
			$user->add_role( Roles::STUDENT );
			$user->remove_role( 'tutor_student' );
			$user->remove_cap( 'tutor_student' );
		}

		if ( in_array( 'tutor_instructor', $roles, true ) || isset( $caps['tutor_instructor'] ) ) {
			if ( ! in_array( 'administrator', $roles, true ) && ! isset( $caps['administrator'] ) ) {
				$user->add_role( Roles::INSTRUCTOR );
			}
			$user->remove_role( 'tutor_instructor' );
			$user->remove_cap( 'tutor_instructor' );
		}

		// Migrate profile bio/photo for all users (instructors also handled via migrate_single_course,
		// but the _masteriyo_profile_migrated guard prevents double-processing).
		if ( ! get_user_meta( $user_id, '_masteriyo_profile_migrated', true ) ) {
			static::migrate_instructor_profile( $user_id );
			update_user_meta( $user_id, '_masteriyo_profile_migrated', '1' );
		}
	}

	/**
	 * Migrate a single TutorLMS course CPT to Masteriyo.
	 *
	 * Renames 'courses' → 'mto-course', assigns instructor role, migrates
	 * course meta/sections/lessons/quizzes/wishlist entries.
	 * Does NOT process enrollments — those are owned by the 'enrollments' step.
	 *
	 * @since x.x.x
	 * @param int $course_id TutorLMS course post ID.
	 * @throws \Exception If the post record does not exist.
	 */
	public static function migrate_single_course( int $course_id ): void {
		global $wpdb;

		$course = get_post( $course_id );
		if ( ! $course ) {
			throw new \Exception( sprintf( 'migrate_single_course: post %d not found.', $course_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$new_status = 'pending' === $course->post_status ? 'draft' : $course->post_status;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$wpdb->posts,
			array(
				'post_type'   => 'mto-course',
				'post_status' => $new_status,
			),
			array( 'ID' => $course_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
		clean_post_cache( $course_id );

		if ( 0 !== (int) $course->post_author ) {
			$user_id = (int) $course->post_author;
			$user    = new \WP_User( $user_id );
			if ( $user->exists() ) {
				$user->remove_role( 'tutor_instructor' );
				$user->remove_role( Roles::STUDENT );
				delete_user_meta( $user_id, '_tutor_instructor_status' );
				delete_user_meta( $user_id, '_is_tutor_instructor' );
				if ( ! in_array( 'administrator', (array) $user->roles, true ) ) {
					$user->add_role( Roles::INSTRUCTOR );
				}
				if ( ! get_user_meta( $user_id, '_masteriyo_profile_migrated', true ) ) {
					static::migrate_instructor_profile( $user_id );
					update_user_meta( $user_id, '_masteriyo_profile_migrated', '1' );
				}
			}
		}

		static::update_masteriyo_course_from_tutor( $course_id );

		// For EDD: set product→course meta so migrate_single_order() can resolve course_id.
		if ( function_exists( 'tutor_utils' ) && 'edd' === \tutor_utils()->get_option( 'monetize_by' ) ) {
			$product_id = (int) get_post_meta( $course_id, '_tutor_course_product_id', true );
			if ( $product_id && function_exists( 'edd_get_download' ) && \edd_get_download( $product_id ) ) {
				update_post_meta( $course_id, '_edd_download_id', $product_id );
				update_post_meta( $product_id, '_is_masteriyo_course', 'yes' );
				update_post_meta( $product_id, '_masteriyo_course_id', $course_id );
				delete_post_meta( $course_id, '_tutor_course_product_id' );
			}
		}

		$sections = get_posts(
			array(
				'post_type'      => 'topics',
				'post_parent'    => $course_id,
				'posts_per_page' => -1,
				'orderby'        => 'menu_order',
				'order'          => 'ASC',
				'post_status'    => 'any',
			)
		);

		foreach ( $sections as $section ) {
			$section_id = $section->ID;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				$wpdb->posts,
				array(
					'post_type'   => 'mto-section',
					'post_parent' => $course_id,
				),
				array( 'ID' => $section_id ),
				array( '%s', '%d' ),
				array( '%d' )
			);
			clean_post_cache( $section_id );

			$mto_section = masteriyo_get_section( $section_id );
			if ( $mto_section ) {
				$mto_section->set_course_id( $course_id );
				$mto_section->save();
			}

			$items = get_posts(
				array(
					'post_type'      => array( 'lesson', 'tutor_quiz' ),
					'post_parent'    => $section_id,
					'posts_per_page' => -1,
					'orderby'        => 'menu_order',
					'order'          => 'ASC',
					'post_status'    => 'any',
				)
			);

			foreach ( $items as $item ) {
				static::update_tutor_lesson_to_masteriyo( $item, $section_id, $course_id );
				static::update_tutor_course_quiz_to_masteriyo( $item, $section_id, $course_id );
			}
		}

	}

	/**
	 * Migrate a single TutorLMS enrollment (tutor_enrolled CPT) to a Masteriyo user_course record.
	 *
	 * For WC gateway: also tags the WC order item with _masteriyo_course_id so the WC addon can
	 * read it. EDD and Tutor native orders are handled separately in the 'orders' step.
	 *
	 * @since x.x.x
	 * @param int $enrollment_post_id Post ID of the tutor_enrolled CPT.
	 * @throws \Exception If the enrollment post or its user does not exist.
	 */
	public static function migrate_single_enrollment( int $enrollment_post_id ): void {
		$enrolled_post = get_post( $enrollment_post_id );
		if ( ! $enrolled_post || 'tutor_enrolled' !== $enrolled_post->post_type ) {
			throw new \Exception(
				sprintf( 'migrate_single_enrollment: tutor_enrolled post %d not found.', $enrollment_post_id ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		$course_id = (int) $enrolled_post->post_parent;
		$user_id   = (int) $enrolled_post->post_author;
		$user      = get_userdata( $user_id );

		if ( ! $user ) {
			throw new \Exception(
				sprintf( 'migrate_single_enrollment: user %d not found for enrollment %d.', $user_id, $enrollment_post_id ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		$email  = sanitize_email( $user->user_email );
		$result = static::update_tutor_enrolled_user_to_masteriyo_enrolled_user( $course_id, $email, $enrolled_post );

		if ( false !== $result && function_exists( 'tutor_utils' ) ) {
			$order_id    = get_post_meta( $enrollment_post_id, '_tutor_enrolled_by_order_id', true );
			$monetize_by = \tutor_utils()->get_option( 'monetize_by' );
			if ( 'wc' === $monetize_by && $order_id ) {
				static::sync_wc_order_with_masteriyo( (int) $order_id, $course_id );
			}
		}

		// Masteriyo does not use a CPT for enrollments — data now lives in masteriyo_user_items.
		// Remove the Tutor source post so it does not linger after migration.
		wp_delete_post( $enrollment_post_id, true );
	}

	/**
	 * Migrate a single order for EDD or Tutor native checkout to a Masteriyo mto-order.
	 *
	 * WC orders are handled inside migrate_single_enrollment() — this method is a no-op for WC.
	 * For EDD: $order_id is an EDD payment post ID. For Tutor native: $order_id is tutor_orders.id.
	 *
	 * @since x.x.x
	 * @param int $order_id EDD payment post ID or Tutor native order ID.
	 * @throws \Exception If the order or its course cannot be resolved.
	 */
	public static function migrate_single_order( int $order_id ): void {
		global $wpdb;

		if ( ! function_exists( 'tutor_utils' ) ) {
			return;
		}

		$monetize_by = \tutor_utils()->get_option( 'monetize_by' );

		if ( 'edd' === $monetize_by ) {
			if ( ! function_exists( 'edd_get_payment' ) ) {
				return;
			}
			$payment = \edd_get_payment( $order_id );
			if ( ! $payment || ! $payment->ID ) {
				throw new \Exception( sprintf( 'migrate_single_order: EDD payment %d not found.', $order_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			}

			// Course ID was written to the EDD download product during migrate_single_course().
			$course_id = 0;
			foreach ( (array) $payment->cart_details as $item ) {
				$product_id = (int) ( $item['id'] ?? 0 );
				if ( $product_id ) {
					$course_id = (int) get_post_meta( $product_id, '_masteriyo_course_id', true );
					if ( $course_id ) {
						break;
					}
				}
			}

			if ( ! $course_id ) {
				throw new \Exception(
					sprintf( 'migrate_single_order: cannot resolve course_id for EDD payment %d — run courses step first.', $order_id ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				);
			}

			static::insert_edd_order( $order_id, $course_id );
			wp_delete_post( $order_id, true );
			return;
		}

		if ( 'tutor' === $monetize_by ) {
			// Resolve course_id from tutor_order_items (standard Tutor LMS schema).
			$course_id = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT item_id FROM {$wpdb->prefix}tutor_order_items WHERE order_id = %d LIMIT 1",
					$order_id
				)
			);

			// Fallback: derive from tutor_enrolled postmeta if tutor_order_items is absent.
			if ( ! $course_id ) {
				$course_id = (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT p.post_parent
						 FROM {$wpdb->postmeta} pm
						 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
						 WHERE pm.meta_key = '_tutor_enrolled_by_order_id'
						   AND pm.meta_value = %s
						   AND p.post_type = 'tutor_enrolled'
						 LIMIT 1",
						(string) $order_id
					)
				);
			}

			if ( ! $course_id ) {
				throw new \Exception(
					sprintf( 'migrate_single_order: cannot resolve course_id for Tutor order %d.', $order_id ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
				);
			}

			static::insert_tutor_native_order( $order_id, $course_id );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->delete( $wpdb->prefix . 'tutor_orders', array( 'id' => $order_id ), array( '%d' ) );
			return;
		}
	}

	/**
	 * Migrate a single TutorLMS course rating comment to a Masteriyo course_review.
	 *
	 * @since x.x.x
	 * @param int $comment_id WP comment ID of the tutor_course_rating comment.
	 * @throws \Exception If the comment does not exist.
	 */
	public static function migrate_single_review( int $comment_id ): void {
		$review = get_comment( $comment_id );
		if ( ! $review ) {
			throw new \Exception( sprintf( 'migrate_single_review: comment %d not found.', $comment_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$rating        = get_comment_meta( $comment_id, 'tutor_rating', true ) ?? $review->comment_karma;
		$content       = $review->comment_content ?? '';
		$content_title = wp_trim_words( $content, 15, '...' );

		wp_update_comment(
			array(
				'comment_ID'       => $comment_id,
				'comment_content'  => $content,
				'comment_approved' => in_array( $review->comment_approved ?? '', array( 'approved', '1', 1 ), true ) ? CommentStatus::APPROVE : CommentStatus::HOLD,
				'comment_type'     => CommentType::COURSE_REVIEW,
				'comment_agent'    => 'Masteriyo',
				'comment_karma'    => (int) $rating,
				'comment_parent'   => (int) $review->comment_parent,
			)
		);

		update_comment_meta( $comment_id, '_title', $content_title );
		update_comment_meta( $comment_id, '_rating', (float) $rating );
		delete_comment_meta( $comment_id, 'tutor_rating' );
	}

	/**
	 * Migrate a single tutor-announcements post to a Masteriyo course announcement.
	 *
	 * @since x.x.x
	 * @param int $post_id tutor-announcements post ID.
	 * @throws \Exception If the post does not exist.
	 */
	public static function migrate_single_announcement( int $post_id ): void {
		$announcement = get_post( $post_id );
		if ( ! $announcement ) {
			throw new \Exception( sprintf( 'migrate_single_announcement: post %d not found.', $post_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$course_id = (int) $announcement->post_parent;

		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_type'   => PostType::COURSEANNOUNCEMENT,
				'post_status' => in_array( $announcement->post_status, array( 'publish', 'draft' ), true )
					? $announcement->post_status
					: PostStatus::PUBLISH,
				'post_author' => $announcement->post_author,
				'ping_status' => 'closed',
				'post_parent' => 0,
			)
		);

		update_post_meta( $post_id, '_course_id', $course_id );
	}

	/**
	 * Migrate a single TutorLMS Q&A thread (parent question + all replies) to Masteriyo course_qa.
	 *
	 * $comment_id must be a parent Q&A comment (comment_parent = 0).
	 * get_qa_answer_by_question() returns the parent comment plus all child replies in one call.
	 *
	 * @since x.x.x
	 * @param int $comment_id Parent Q&A comment ID.
	 * @throws \Exception If the parent comment does not exist.
	 */
	public static function migrate_single_qa( int $comment_id ): void {
		$question = get_comment( $comment_id );
		if ( ! $question ) {
			throw new \Exception( sprintf( 'migrate_single_qa: comment %d not found.', $comment_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		$question_data_tutors = \tutor_utils()->get_qa_answer_by_question( $comment_id );

		foreach ( (array) $question_data_tutors as $qa ) {
			$result = wp_update_comment(
				array(
					'comment_ID'       => $qa->comment_ID,
					'comment_type'     => CommentType::COURSE_QA,
					'comment_approved' => in_array( $qa->comment_approved ?? '', array( 'approved', '1', 1 ), true ) ? CommentStatus::APPROVE : CommentStatus::HOLD,
					'comment_agent'    => 'Masteriyo',
				)
			);

			if ( is_wp_error( $result ) ) {
				masteriyo_get_logger()->error(
					sprintf( 'Could not update Q&A comment %d.', $qa->comment_ID ),
					array( 'source' => 'migration-tool' )
				);
			}
		}

		wp_update_comment(
			array(
				'comment_ID'       => $comment_id,
				'comment_type'     => CommentType::COURSE_QA,
				'comment_approved' => in_array( $question->comment_approved ?? '', array( 'approved', '1', 1 ), true ) ? CommentStatus::APPROVE : CommentStatus::HOLD,
				'comment_agent'    => 'Masteriyo',
			)
		);
	}

	/**
	 * Migrate lesson completion progress for a single Masteriyo enrollment.
	 *
	 * $user_course_id is a masteriyo_user_items.id (item_type='user_course'), populated
	 * by the enrollments step. We resolve user and course from the Masteriyo table so
	 * this step is fully independent of Tutor CPT posts, which are deleted during enrollment.
	 *
	 * @since x.x.x
	 * @param int $user_course_id masteriyo_user_items.id for item_type='user_course'.
	 */
	public static function migrate_single_progress( int $user_course_id ): void {
		global $wpdb;
		$now            = gmdate( 'Y-m-d H:i:s' );
		$activities_tbl = $wpdb->prefix . 'masteriyo_user_activities';

		$user_course = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT user_id, item_id FROM {$wpdb->prefix}masteriyo_user_items
				 WHERE id = %d AND item_type = 'user_course'",
				$user_course_id
			)
		);

		if ( ! $user_course ) {
			return;
		}

		$user_id   = (int) $user_course->user_id;
		$course_id = (int) $user_course->item_id;

		$lesson_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT p.ID FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
				 WHERE p.post_type = 'mto-lesson'
				   AND pm.meta_key = '_course_id'
				   AND pm.meta_value = %d",
				$course_id
			)
		);
		$lesson_ids = array_map( 'intval', $lesson_ids );

		if ( empty( $lesson_ids ) ) {
			return;
		}

		$progress_id = Helper::get_or_create_course_progress( $user_id, $course_id, $now );

		if ( ! $progress_id ) {
			masteriyo_get_logger()->warning(
				sprintf( 'Could not create progress record for user %d in course %d.', $user_id, $course_id ),
				array( 'source' => 'migration-tool' )
			);
			return;
		}

		$completions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT CAST(REPLACE(meta_key, '_tutor_completed_lesson_id_', '') AS UNSIGNED) AS lesson_id,
				        meta_value AS completed_ts
				 FROM {$wpdb->usermeta}
				 WHERE user_id = %d
				   AND meta_key LIKE %s
				   AND meta_value != ''",
				$user_id,
				$wpdb->esc_like( '_tutor_completed_lesson_id_' ) . '%'
			)
		);

		$lesson_ids_set    = array_flip( $lesson_ids );
		$valid_completions = array();
		foreach ( $completions as $c ) {
			$lid = (int) $c->lesson_id;
			if ( isset( $lesson_ids_set[ $lid ] ) ) {
				$ts                  = ! empty( $c->completed_ts ) ? (int) strtotime( $c->completed_ts ) : 0;
				$valid_completions[] = array(
					'lesson_id'    => $lid,
					'completed_at' => $ts > 0 ? gmdate( 'Y-m-d H:i:s', $ts ) : $now,
				);
			}
		}

		if ( empty( $valid_completions ) ) {
			return;
		}

		$existing_acts = $wpdb->get_results(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT item_id FROM {$activities_tbl}
				 WHERE user_id = %d AND activity_type = 'lesson' AND parent_id = %d",
				$user_id,
				$progress_id
			)
		);
		$existing_set = array();
		foreach ( $existing_acts as $a ) {
			$existing_set[ (int) $a->item_id ] = true;
		}

		foreach ( $valid_completions as $c ) {
			if ( isset( $existing_set[ $c['lesson_id'] ] ) ) {
				continue;
			}
			$wpdb->insert(
				$activities_tbl,
				array(
					'user_id'         => $user_id,
					'item_id'         => $c['lesson_id'],
					'activity_type'   => 'lesson',
					'activity_status' => 'completed',
					'parent_id'       => $progress_id,
					'created_at'      => $c['completed_at'],
					'modified_at'     => $now,
					'completed_at'    => $c['completed_at'],
				),
				array( '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s' )
			);
		}

		foreach ( $lesson_ids as $lid ) {
			delete_user_meta( $user_id, '_tutor_completed_lesson_id_' . $lid );
		}
	}

	/**
	 * Migrate a single Tutor quiz attempt to masteriyo_quiz_attempts.
	 *
	 * Idempotent via (quiz_id, user_id, attempt_started_at) deduplication guard.
	 * _tutor_question_id post meta must exist on Masteriyo question posts (set in courses step).
	 *
	 * @since x.x.x
	 * @param int $attempt_id tutor_quiz_attempts.attempt_id.
	 * @throws \Exception If the attempt record is missing or the insert fails.
	 */
	public static function migrate_single_quiz_attempt( int $attempt_id ): void {
		global $wpdb;
		$now          = gmdate( 'Y-m-d H:i:s' );
		$attempts_tbl = $wpdb->prefix . 'masteriyo_quiz_attempts';

		$attempt = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}tutor_quiz_attempts WHERE attempt_id = %d",
				$attempt_id
			)
		);

		if ( ! $attempt ) {
			throw new \Exception( sprintf( 'migrate_single_quiz_attempt: attempt %d not found.', $attempt_id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
		}

		if ( empty( $attempt->attempt_started_at ) ) {
			return;
		}

		$course_id      = (int) $attempt->course_id;
		$already_exists = (int) $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT COUNT(*) FROM {$attempts_tbl}
				 WHERE quiz_id = %d AND user_id = %d AND attempt_started_at = %s",
				(int) $attempt->quiz_id,
				$attempt->user_id,
				$attempt->attempt_started_at
			)
		);

		if ( $already_exists ) {
			return;
		}

		$raw_answers = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT aa.question_id, aa.given_answer, aa.achieved_mark, aa.is_correct,
				        q.question_title, q.question_type
				 FROM {$wpdb->prefix}tutor_quiz_attempt_answers aa
				 LEFT JOIN {$wpdb->prefix}tutor_quiz_questions q ON aa.question_id = q.question_id
				 WHERE aa.quiz_attempt_id = %d",
				$attempt_id
			)
		);

		$tutor_question_ids = array_unique( array_map( fn( $a ) => (int) $a->question_id, $raw_answers ) );
		$mto_question_map   = array();

		if ( ! empty( $tutor_question_ids ) ) {
			$q_ids_in  = implode( ',', array_fill( 0, count( $tutor_question_ids ), '%d' ) );
			$q_results = $wpdb->get_results(
				$wpdb->prepare(
					// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT post_id, meta_value FROM {$wpdb->postmeta}
					 WHERE meta_key = '_tutor_question_id' AND meta_value IN ({$q_ids_in})",
					// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$tutor_question_ids
				)
			);
			foreach ( $q_results as $r ) {
				$mto_question_map[ (int) $r->meta_value ] = (int) $r->post_id;
			}
		}

		$total_correct   = 0;
		$total_incorrect = 0;
		$answers_php     = array();

		foreach ( $raw_answers as $raw ) {
			$raw->is_correct ? $total_correct++ : $total_incorrect++;
			$answers_php[] = array(
				'id'            => $mto_question_map[ (int) $raw->question_id ] ?? 0,
				'name'          => $raw->question_title ?? '',
				'type'          => $raw->question_type ?? '',
				'given_answer'  => $raw->given_answer,
				'achieved_mark' => (float) $raw->achieved_mark,
				'is_correct'    => (bool) $raw->is_correct,
			);
		}

		$status_map     = array(
			'attempt_started' => 'attempt_started',
			'attempt_ended'   => 'attempt_ended',
			'review_required' => 'attempt_ended',
			'pass'            => 'pass',
			'fail'            => 'fail',
		);
		$attempt_status = $status_map[ $attempt->attempt_status ] ?? 'attempt_ended';

		$total_attempts_seq = 1 + (int) $wpdb->get_var(
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				"SELECT COUNT(*) FROM {$attempts_tbl} WHERE quiz_id = %d AND user_id = %d",
				(int) $attempt->quiz_id,
				$attempt->user_id
			)
		);

		$inserted = $wpdb->insert(
			$attempts_tbl,
			array(
				'course_id'                => $course_id,
				'quiz_id'                  => (int) $attempt->quiz_id,
				'user_id'                  => (int) $attempt->user_id,
				'total_questions'          => (int) $attempt->total_questions,
				'total_answered_questions' => (int) $attempt->total_answered_questions,
				'total_marks'              => (float) $attempt->total_marks,
				'total_attempts'           => $total_attempts_seq,
				'total_correct_answers'    => $total_correct,
				'total_incorrect_answers'  => $total_incorrect,
				'earned_marks'             => (float) $attempt->earned_marks,
				'answers'                  => maybe_serialize( $answers_php ),
				'attempt_status'           => $attempt_status,
				'attempt_started_at'       => $attempt->attempt_started_at,
				'attempt_ended_at'         => $attempt->attempt_ended_at,
			),
			array( '%d', '%d', '%s', '%d', '%d', '%f', '%d', '%d', '%d', '%f', '%s', '%s', '%s', '%s' )
		);

		if ( ! $inserted ) {
			throw new \Exception(
				sprintf( 'migrate_single_quiz_attempt: failed to insert attempt %d.', $attempt_id ) // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		if ( 'pass' === $attempt_status ) {
			$activities_tbl = $wpdb->prefix . 'masteriyo_user_activities';
			$progress_id    = (int) $wpdb->get_var(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT id FROM {$activities_tbl}
					 WHERE item_id = %d AND user_id = %d AND activity_type = 'course_progress'
					 LIMIT 1",
					$course_id,
					(int) $attempt->user_id
				)
			);

			if ( $progress_id ) {
				$already = $wpdb->get_var(
					$wpdb->prepare(
						// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						"SELECT id FROM {$activities_tbl}
						 WHERE item_id = %d AND user_id = %d AND activity_type = 'quiz' AND parent_id = %d
						 LIMIT 1",
						(int) $attempt->quiz_id,
						(int) $attempt->user_id,
						$progress_id
					)
				);
				if ( ! $already ) {
					$ended = isset( $attempt->attempt_ended_at ) ? $attempt->attempt_ended_at : $now;
					$wpdb->insert(
						$activities_tbl,
						array(
							'user_id'         => (int) $attempt->user_id,
							'item_id'         => (int) $attempt->quiz_id,
							'activity_type'   => 'quiz',
							'activity_status' => 'completed',
							'parent_id'       => $progress_id,
							'created_at'      => $ended,
							'modified_at'     => $ended,
							'completed_at'    => $ended,
						),
						array( '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s' )
					);
				}
			}
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $wpdb->prefix . 'tutor_quiz_attempt_answers', array( 'quiz_attempt_id' => $attempt_id ), array( '%d' ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( $wpdb->prefix . 'tutor_quiz_attempts', array( 'attempt_id' => $attempt_id ), array( '%d' ) );
	}

	/**
	 * Migrate a single tutor-google-meet post to mto-google-meet.
	 *
	 * Renames the CPT in-place (preserves post ID) and maps the 4 meta keys that
	 * differ between TutorLMS and Masteriyo Google Meet schemas.
	 *
	 * @since x.x.x
	 * @param int $post_id tutor-google-meet post ID.
	 */
	public static function migrate_single_google_meet( int $post_id ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update( $wpdb->posts, array( 'post_type' => 'mto-google-meet' ), array( 'ID' => $post_id ) );
		clean_post_cache( $post_id );

		// Read all TutorLMS meta keys.
		$meet_url      = get_post_meta( $post_id, '_tutor_google_meet_meeting_url', true );
		$starts_at     = get_post_meta( $post_id, '_tutor_google_meet_start_datetime', true );
		$ends_at       = get_post_meta( $post_id, '_tutor_google_meet_end_datetime', true );
		$course_id_val = get_post_meta( $post_id, '_tutor_google_meet_course_id', true );
		$author_id_val = get_post_meta( $post_id, '_tutor_google_meet_instructor_id', true );
		$event_details = get_post_meta( $post_id, '_tutor_google_meet_event_details', true );

		// Write Masteriyo meta keys.
		update_post_meta( $post_id, 'meet_url', $meet_url );
		update_post_meta( $post_id, 'starts_at', $starts_at );
		update_post_meta( $post_id, 'ends_at', $ends_at );
		update_post_meta( $post_id, 'course_id', (int) $course_id_val );
		update_post_meta( $post_id, 'author_id', (int) $author_id_val );

		// Parse event_details JSON → individual Masteriyo keys.
		$event = is_string( $event_details ) && '' !== $event_details
			? json_decode( $event_details, true )
			: array();
		$event = is_array( $event ) ? $event : array();

		if ( ! empty( $event['id'] ) ) {
			update_post_meta( $post_id, 'meeting_id', sanitize_text_field( $event['id'] ) );
		}
		if ( ! empty( $event['timezone'] ) ) {
			update_post_meta( $post_id, 'time_zone', sanitize_text_field( $event['timezone'] ) );
		}
		// 'calender_url' is the intentional Masteriyo spelling.
		$calendar_url = $event['hangoutLink'] ?? $event['htmlLink'] ?? '';
		if ( $calendar_url ) {
			update_post_meta( $post_id, 'calender_url', esc_url_raw( $calendar_url ) );
		}

		// Delete all original TutorLMS meta keys so none remain on the migrated post.
		$tutor_keys = array(
			'_tutor_google_meet_meeting_url',
			'_tutor_google_meet_start_datetime',
			'_tutor_google_meet_end_datetime',
			'_tutor_google_meet_course_id',
			'_tutor_google_meet_instructor_id',
			'_tutor_google_meet_event_details',
		);
		foreach ( $tutor_keys as $key ) {
			delete_post_meta( $post_id, $key );
		}
	}

	/**
	 * Dispatch a single-item migration to the appropriate migrate_single_*() method.
	 *
	 * Called by MigrationProcessJob inside a START TRANSACTION / COMMIT wrapper.
	 * Must be idempotent — safe to call twice for the same (step, item_id) pair.
	 *
	 * @since x.x.x
	 * @param string $step    Step name matching a key in get_steps().
	 * @param int    $item_id Source item ID (post ID, comment ID, or table row ID).
	 * @throws \Exception Triggers ROLLBACK in the job engine; item is added to the failed list.
	 */
	public static function migrate_item( string $step, int $item_id ): void {
		// questions_n_answers calls tutor_utils() unconditionally — fail fast with a clear message.
		// courses now guards tutor_utils() inside update_masteriyo_course_from_tutor() so it can
		// still rename the post_type even if TutorLMS is inactive; only pricing/settings are skipped.
		if ( 'questions_n_answers' === $step && ! function_exists( 'tutor_utils' ) ) {
			throw new \Exception(
				'TutorLMS is not loaded — "questions_n_answers" step requires tutor_utils(). Ensure TutorLMS is active.' // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			);
		}

		switch ( $step ) {
			case 'users':
				static::migrate_single_user( $item_id );
				break;
			case 'courses':
				static::migrate_single_course( $item_id );
				break;
			case 'enrollments':
				static::migrate_single_enrollment( $item_id );
				break;
			case 'orders':
				static::migrate_single_order( $item_id );
				break;
			case 'reviews':
				static::migrate_single_review( $item_id );
				break;
			case 'announcement':
				static::migrate_single_announcement( $item_id );
				break;
			case 'questions_n_answers':
				static::migrate_single_qa( $item_id );
				break;
			case 'progress':
				static::migrate_single_progress( $item_id );
				break;
			case 'quiz_attempts':
				static::migrate_single_quiz_attempt( $item_id );
				break;
			case 'google_meet':
				static::migrate_single_google_meet( $item_id );
				break;
			case 'wishlists':
				static::register_wishlist_service_provider();
				static::migrate_single_wishlist_user( $item_id );
				break;
		}
	}

	/**
	 * Count total source items for a given step. Uses fast COUNT queries — no records loaded.
	 *
	 * @since x.x.x
	 * @param string $step Step name.
	 * @return int
	 */
	public static function count_source_items( string $step ): int {
		global $wpdb;

		switch ( $step ) {
			case 'users':
				$capabilities_key = $wpdb->get_blog_prefix() . 'capabilities';
				return (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(DISTINCT u.ID)
						 FROM {$wpdb->users} u
						 INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
						 WHERE um.meta_key = %s
						   AND ( um.meta_value LIKE %s OR um.meta_value LIKE %s )",
						$capabilities_key,
						'%tutor_student%',
						'%tutor_instructor%'
					)
				);

			case 'courses':
				return (int) $wpdb->get_var(
					"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'courses'"
				);

			case 'enrollments':
				return (int) $wpdb->get_var(
					"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'tutor_enrolled'"
				);

			case 'orders':
				if ( ! function_exists( 'tutor_utils' ) ) {
					return 0;
				}
				$monetize_by = \tutor_utils()->get_option( 'monetize_by' );
				if ( 'wc' === $monetize_by ) {
					return 0; // WC orders handled inside enrollments step.
				}
				if ( 'edd' === $monetize_by ) {
					return (int) $wpdb->get_var(
						"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'edd_payment'"
					);
				}
				if ( 'tutor' === $monetize_by ) {
					$orders_tbl = $wpdb->prefix . 'tutor_orders';
					if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $orders_tbl ) ) ) {
						return 0;
					}
					return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$orders_tbl}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				}
				return 0;

			case 'reviews':
				return (int) $wpdb->get_var(
					"SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_type = 'tutor_course_rating'"
				);

			case 'announcement':
				return (int) $wpdb->get_var(
					"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'tutor_announcements'"
				);

			case 'questions_n_answers':
				return (int) $wpdb->get_var(
					"SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_type = 'tutor_q_and_a' AND comment_parent = 0"
				);

			case 'progress':
				// Count tutor_enrolled CPTs where the student has at least one lesson
				// completion in usermeta. Uses TutorLMS source data so this is valid at
				// session start (before the enrollment step deletes the CPTs).
				// tutor_enrolled → masteriyo_user_items is 1:1, so this cardinality
				// matches exactly what get_source_ids returns after enrollment migration,
				// satisfying MigrationProcessJob's while(offset < total) loop.
				return (int) $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(DISTINCT p.ID)
						 FROM {$wpdb->posts} p
						 WHERE p.post_type = 'tutor_enrolled'
						   AND p.post_status != 'trash'
						   AND EXISTS (
						       SELECT 1 FROM {$wpdb->usermeta} um
						       WHERE um.user_id = p.post_author
						         AND um.meta_key LIKE %s
						         AND um.meta_value != ''
						   )",
						$wpdb->esc_like( '_tutor_completed_lesson_id_' ) . '%'
					)
				);

			case 'quiz_attempts':
				return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}tutor_quiz_attempts" );

			case 'google_meet':
				return (int) $wpdb->get_var(
					"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'tutor-google-meet'"
				);

			case 'wishlists':
				return (int) $wpdb->get_var(
					"SELECT COUNT(DISTINCT user_id) FROM {$wpdb->usermeta} WHERE meta_key = '_tutor_course_wishlist'"
				);

		}

		return 0;
	}

	/**
	 * Return one batch of source IDs starting after $cursor.
	 *
	 * @since x.x.x
	 * @param string $step    Step name.
	 * @param int    $limit   Batch size.
	 * @param int    $cursor  Last processed ID (0 = first batch).
	 * @param int[]  $exclude Already-failed IDs to skip (self-cleaning steps only).
	 * @return int[]
	 */
	public static function get_source_ids( string $step, int $limit, int $cursor, array $exclude = array() ): array {
		global $wpdb;

		// Pre-build NOT IN clause used by every self-cleaning case below.
		$not_in      = '';
		$not_in_args = array();
		if ( ! empty( $exclude ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $exclude ), '%d' ) );
			$not_in       = "AND ID NOT IN ({$placeholders})"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$not_in_args  = array_map( 'intval', $exclude );
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		switch ( $step ) {
			case 'users':
				$capabilities_key = $wpdb->get_blog_prefix() . 'capabilities';
				// No OFFSET — migrate_single_user removes tutor roles after each item,
				// so processed users vanish from the result set automatically.
				// Always querying at implicit OFFSET 0 yields the next unprocessed batch.
				$user_not_in      = '';
				$user_not_in_args = array();
				if ( ! empty( $exclude ) ) {
					$placeholders     = implode( ',', array_fill( 0, count( $exclude ), '%d' ) );
					$user_not_in      = "AND u.ID NOT IN ({$placeholders})"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$user_not_in_args = array_map( 'intval', $exclude );
				}
				$ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT DISTINCT u.ID
						 FROM {$wpdb->users} u
						 INNER JOIN {$wpdb->usermeta} um ON u.ID = um.user_id
						 WHERE um.meta_key = %s
						   AND ( um.meta_value LIKE %s OR um.meta_value LIKE %s )
						   {$user_not_in}
						 ORDER BY u.ID ASC
						 LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						array_merge(
							array( $capabilities_key, '%tutor_student%', '%tutor_instructor%' ),
							$user_not_in_args,
							array( $limit )
						)
					)
				);
				return array_map( 'intval', $ids ?? array() );

			case 'courses':
				// No OFFSET — migrate_single_course renames post_type to 'mto-course' in-place,
				// so migrated courses vanish from the WHERE clause automatically.
				$ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT ID FROM {$wpdb->posts}
						 WHERE post_type = 'courses'
						 {$not_in}
						 ORDER BY ID ASC
						 LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						array_merge( $not_in_args, array( $limit ) )
					)
				);
				return array_map( 'intval', $ids ?? array() );

			case 'enrollments':
				// No OFFSET — migrate_single_enrollment deletes the tutor_enrolled CPT after
				// migration, so processed records vanish from the result set automatically.
				$ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT ID FROM {$wpdb->posts}
						 WHERE post_type = 'tutor_enrolled'
						 {$not_in}
						 ORDER BY ID ASC
						 LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						array_merge( $not_in_args, array( $limit ) )
					)
				);
				return array_map( 'intval', $ids ?? array() );

			case 'orders':
				if ( ! function_exists( 'tutor_utils' ) ) {
					return array();
				}
				$monetize_by = \tutor_utils()->get_option( 'monetize_by' );
				if ( 'wc' === $monetize_by ) {
					return array(); // WC orders handled inside enrollments step.
				}
				if ( 'edd' === $monetize_by ) {
					// No OFFSET — migrate_single_order deletes the edd_payment post after
					// migration, so processed records vanish from the result set automatically.
					$ids = $wpdb->get_col(
						$wpdb->prepare(
							"SELECT ID FROM {$wpdb->posts}
							 WHERE post_type = 'edd_payment'
							 {$not_in}
							 ORDER BY ID ASC
							 LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							array_merge( $not_in_args, array( $limit ) )
						)
					);
					return array_map( 'intval', $ids ?? array() );
				}
				if ( 'tutor' === $monetize_by ) {
					$orders_tbl = $wpdb->prefix . 'tutor_orders';
					if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $orders_tbl ) ) ) {
						return array();
					}
					// No OFFSET — migrate_single_order deletes from tutor_orders after
					// migration, so processed records vanish from the result set automatically.
					$order_not_in      = '';
					$order_not_in_args = array();
					if ( ! empty( $exclude ) ) {
						$placeholders      = implode( ',', array_fill( 0, count( $exclude ), '%d' ) );
						$order_not_in      = "WHERE id NOT IN ({$placeholders})"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						$order_not_in_args = array_map( 'intval', $exclude );
					}
					$ids = $wpdb->get_col(
						$wpdb->prepare(
							"SELECT id FROM {$orders_tbl}
							 {$order_not_in}
							 ORDER BY id ASC
							 LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
							array_merge( $order_not_in_args, array( $limit ) )
						)
					);
					return array_map( 'intval', $ids ?? array() );
				}
				return array();

			case 'reviews':
				// No OFFSET — migrate_single_review changes comment_type to 'course_review',
				// so migrated comments vanish from the WHERE clause automatically.
				$review_not_in      = '';
				$review_not_in_args = array();
				if ( ! empty( $exclude ) ) {
					$placeholders       = implode( ',', array_fill( 0, count( $exclude ), '%d' ) );
					$review_not_in      = "AND comment_ID NOT IN ({$placeholders})"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$review_not_in_args = array_map( 'intval', $exclude );
				}
				$ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT comment_ID FROM {$wpdb->comments}
						 WHERE comment_type = 'tutor_course_rating'
						 {$review_not_in}
						 ORDER BY comment_ID ASC
						 LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						array_merge( $review_not_in_args, array( $limit ) )
					)
				);
				return array_map( 'intval', $ids ?? array() );

			case 'announcement':
				// No OFFSET — migrate_single_announcement changes post_type after each item,
				// so migrated announcements vanish from the result set automatically.
				$ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT ID FROM {$wpdb->posts}
						 WHERE post_type = 'tutor_announcements'
						 {$not_in}
						 ORDER BY ID ASC
						 LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						array_merge( $not_in_args, array( $limit ) )
					)
				);
				return array_map( 'intval', $ids ?? array() );

			case 'questions_n_answers':
				// No OFFSET — migrate_single_qa changes comment_type after each item,
				// so migrated Q&A threads vanish from the result set automatically.
				$qa_not_in      = '';
				$qa_not_in_args = array();
				if ( ! empty( $exclude ) ) {
					$placeholders   = implode( ',', array_fill( 0, count( $exclude ), '%d' ) );
					$qa_not_in      = "AND comment_ID NOT IN ({$placeholders})"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$qa_not_in_args = array_map( 'intval', $exclude );
				}
				$ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT comment_ID FROM {$wpdb->comments}
						 WHERE comment_type = 'tutor_q_and_a' AND comment_parent = 0
						 {$qa_not_in}
						 ORDER BY comment_ID ASC
						 LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						array_merge( $qa_not_in_args, array( $limit ) )
					)
				);
				return array_map( 'intval', $ids ?? array() );

			case 'progress':
				// Cursor-based — source row is not deleted, so we advance by mui.id.
				$ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT mui.id
						 FROM {$wpdb->prefix}masteriyo_user_items mui
						 WHERE mui.item_type = 'user_course'
						   AND mui.id > %d
						   AND EXISTS (
						       SELECT 1 FROM {$wpdb->usermeta} um
						       WHERE um.user_id = mui.user_id
						         AND um.meta_key LIKE %s
						         AND um.meta_value != ''
						   )
						 ORDER BY mui.id ASC
						 LIMIT %d",
						$cursor,
						$wpdb->esc_like( '_tutor_completed_lesson_id_' ) . '%',
						$limit
					)
				);
				return array_map( 'intval', $ids ?? array() );

			case 'quiz_attempts':
				// No OFFSET — migrate_single_quiz_attempt deletes from tutor_quiz_attempts after
				// migration, so processed records vanish from the result set automatically.
				$qa_attempt_not_in      = '';
				$qa_attempt_not_in_args = array();
				if ( ! empty( $exclude ) ) {
					$placeholders           = implode( ',', array_fill( 0, count( $exclude ), '%d' ) );
					$qa_attempt_not_in      = "WHERE attempt_id NOT IN ({$placeholders})"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$qa_attempt_not_in_args = array_map( 'intval', $exclude );
				}
				$ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT attempt_id FROM {$wpdb->prefix}tutor_quiz_attempts
						 {$qa_attempt_not_in}
						 ORDER BY attempt_id ASC
						 LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						array_merge( $qa_attempt_not_in_args, array( $limit ) )
					)
				);
				return array_map( 'intval', $ids ?? array() );

			case 'google_meet':
				// No OFFSET — migrate_single_google_meet changes post_type in-place after each item.
				$ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT ID FROM {$wpdb->posts}
						 WHERE post_type = 'tutor-google-meet'
						 {$not_in}
						 ORDER BY ID ASC
						 LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						array_merge( $not_in_args, array( $limit ) )
					)
				);
				return array_map( 'intval', $ids ?? array() );

			case 'wishlists':
				// Cursor-based — source row is not deleted, so we advance by user_id.
				$ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT DISTINCT user_id FROM {$wpdb->usermeta}
						 WHERE meta_key = '_tutor_course_wishlist'
						   AND user_id > %d
						 ORDER BY user_id ASC
						 LIMIT %d",
						$cursor,
						$limit
					)
				);
				return array_map( 'intval', $ids ?? array() );

		}
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return array();
	}

	/**
	 * Bulk-update course_progress status once all progress items are migrated.
	 * Replaces the per-item recount queries — runs once after the step completes.
	 *
	 * @since x.x.x
	 * @param string $step Step name.
	 */
	public static function finalize_step( string $step ): void {
		if ( 'progress' !== $step ) {
			return;
		}

		global $wpdb;
		$tbl = $wpdb->prefix . 'masteriyo_user_activities';

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			"UPDATE {$tbl} AS parent
			 INNER JOIN (
			     SELECT CAST( pm.meta_value AS UNSIGNED ) AS course_id,
			            COUNT( DISTINCT p.ID )            AS curriculum_total
			     FROM   {$wpdb->posts}    AS p
			     INNER  JOIN {$wpdb->postmeta} AS pm
			            ON  p.ID = pm.post_id AND pm.meta_key = '_course_id'
			     WHERE  p.post_type = 'mto-lesson'
			     GROUP  BY pm.meta_value
			 ) AS curriculum ON parent.item_id = curriculum.course_id
			 INNER JOIN (
			     SELECT parent_id,
			            COUNT( DISTINCT item_id ) AS done
			     FROM   {$tbl}
			     WHERE  activity_type = 'lesson' AND activity_status = 'completed'
			     GROUP  BY parent_id
			 ) AS completed ON parent.id = completed.parent_id
			 SET parent.activity_status = CASE
			         WHEN curriculum.curriculum_total > 0 AND completed.done >= curriculum.curriculum_total THEN 'completed'
			         ELSE 'progress'
			     END,
			     parent.completed_at = IF(
			         curriculum.curriculum_total > 0 AND completed.done >= curriculum.curriculum_total,
			         UTC_TIMESTAMP(),
			         '0000-00-00 00:00:00'
			     ),
			     parent.modified_at  = UTC_TIMESTAMP()
			 WHERE parent.activity_type = 'course_progress'
			   AND completed.done       > 0"
		);
		// phpcs:enable
	}

	/**
	 * Extract video metadata from a TutorLMS post's _video meta.
	 *
	 * @since x.x.x
	 *
	 * @param int $post_id TutorLMS post ID (course or lesson).
	 * @return array{source: string, url: string, id: int, runtime: int}
	 */
	public static function map_video_meta( int $post_id ): array {
		$video = get_post_meta( $post_id, '_video', true );
		if ( empty( $video ) || ! is_array( $video ) ) {
			return array();
		}

		$raw_source = $video['source'] ?? '';

		switch ( $raw_source ) {
			case 'source_youtube':
				return array(
					'source'  => 'youtube',
					'url'     => $video['source_youtube'] ?? '',
					'id'      => 0,
					'runtime' => (int) ( $video['runtime'] ?? 0 ),
				);
			case 'source_vimeo':
				return array(
					'source'  => 'vimeo',
					'url'     => $video['source_vimeo'] ?? '',
					'id'      => 0,
					'runtime' => (int) ( $video['runtime'] ?? 0 ),
				);
			case 'source_embedded':
				return array(
					'source'  => 'embed-video',
					'url'     => $video['source_embedded'] ?? '',
					'id'      => 0,
					'runtime' => (int) ( $video['runtime'] ?? 0 ),
				);
			case 'source_external_url':
				return array(
					'source'  => 'external',
					'url'     => $video['source_external_url'] ?? '',
					'id'      => 0,
					'runtime' => (int) ( $video['runtime'] ?? 0 ),
				);
			case 'html5':
				return array(
					'source'  => 'self-hosted',
					'url'     => '',
					'id'      => (int) ( $video['source_video_id'] ?? 0 ),
					'runtime' => (int) ( $video['runtime'] ?? 0 ),
				);
			default:
				return array();
		}
	}

	/**
	 * Map a TutorLMS question type slug to the Masteriyo equivalent.
	 *
	 * Returns null for types that have no Masteriyo equivalent (caller should log and skip).
	 * Pro-only types (sortable, matching) are returned even from free context so data lands in DB
	 * correctly when the site has or later upgrades to Pro.
	 *
	 * @since x.x.x
	 *
	 * @param string $tutor_type TutorLMS question_type value.
	 * @return string|null Masteriyo question type, or null if unsupported.
	 */
	public static function map_question_type( string $tutor_type ): ?string {
		$map = array(
			'true_false'        => 'true-false',
			'single_choice'     => 'single-choice',
			'multiple_choice'   => 'multiple-choice',
			'fill_in_the_blank' => 'fill-in-the-blanks',
			'gap_match'         => 'fill-in-the-blanks',
			'ordering'          => 'sortable',
			'image_matching'    => 'matching',
			'match_lists'       => 'matching',
			'image_sequencing'  => 'sortable',
			'open_ended'        => 'text-answer',
			'text_answer'       => 'text-answer',
			'short_answer'      => 'text-answer',
			'essay'             => 'text-answer',
		);
		return $map[ $tutor_type ] ?? null;
	}

	/**
	 * Map a TutorLMS order status to a Masteriyo OrderStatus value.
	 *
	 * @since x.x.x
	 *
	 * @param string $tutor_status Value from tutor_orders.order_status.
	 * @return string Masteriyo OrderStatus constant value.
	 */
	public static function map_order_status( string $tutor_status ): string {
		$map = array(
			'paid'               => OrderStatus::COMPLETED,
			'completed'          => OrderStatus::COMPLETED,
			'refunded'           => OrderStatus::REFUNDED,
			'partially-refunded' => OrderStatus::REFUNDED,
			'failed'             => OrderStatus::FAILED,
			'cancelled'          => OrderStatus::CANCELLED,
			'trash'              => OrderStatus::CANCELLED,
			'pending'            => OrderStatus::PENDING,
			'processing'         => OrderStatus::PENDING,
		);
		return $map[ $tutor_status ] ?? OrderStatus::PENDING;
	}

	/**
	 * Convert a TutorLMS quiz time_limit option array to seconds.
	 *
	 * @since x.x.x
	 *
	 * @param array $time_limit TutorLMS time_limit option with time_value and time_type keys.
	 * @return int Duration in seconds. Returns 0 if no limit set.
	 */
	public static function quiz_duration_to_seconds( array $time_limit ): int {
		$value    = (int) ( $time_limit['time_value'] ?? 0 );
		$type     = $time_limit['time_type'] ?? 'minutes';
		$unit_map = array(
			'seconds' => 1,
			'minutes' => MINUTE_IN_SECONDS,
			'hours'   => HOUR_IN_SECONDS,
			'days'    => DAY_IN_SECONDS,
			'weeks'   => WEEK_IN_SECONDS,
		);
		return $value * ( $unit_map[ $type ] ?? MINUTE_IN_SECONDS );
	}

	/**
	 * Migrate TutorLMS instructor profile metadata to WordPress/Masteriyo equivalents.
	 *
	 * @since x.x.x
	 *
	 * @param int $user_id WordPress user ID of the instructor.
	 * @return void
	 */
	public static function migrate_instructor_profile( int $user_id ): void {
		$bio      = get_user_meta( $user_id, '_tutor_profile_bio', true );
		$photo_id = (int) get_user_meta( $user_id, '_tutor_profile_photo', true );
		$website  = get_user_meta( $user_id, '_tutor_profile_website', true );

		if ( $bio ) {
			wp_update_user(
				array(
					'ID'          => $user_id,
					'description' => wp_kses_post( $bio ),
				)
			);
		}
		if ( $website ) {
			wp_update_user(
				array(
					'ID'       => $user_id,
					'user_url' => esc_url_raw( $website ),
				)
			);
		}
		if ( $photo_id ) {
			update_user_meta( $user_id, '_profile_image_id', $photo_id );
		}

		$job_title = get_user_meta( $user_id, '_tutor_profile_job_title', true );
		if ( $job_title ) {
			update_user_meta( $user_id, '_migrated_tutor_job_title', sanitize_text_field( $job_title ) );
		}

		$pro_social_map = array(
			'facebook' => '_public_profile_facebook_url',
			'linkedin' => '_public_profile_linkedin_url',
		);

		foreach ( array( 'facebook', 'twitter', 'linkedin', 'github' ) as $network ) {
			$handle = get_user_meta( $user_id, "_tutor_profile_{$network}", true );
			if ( $handle ) {
				$clean = sanitize_text_field( $handle );
				update_user_meta( $user_id, "_migrated_tutor_social_{$network}", $clean );

				if ( isset( $pro_social_map[ $network ] ) ) {
					update_user_meta( $user_id, $pro_social_map[ $network ], $clean );
				}
			}
		}
	}
}
