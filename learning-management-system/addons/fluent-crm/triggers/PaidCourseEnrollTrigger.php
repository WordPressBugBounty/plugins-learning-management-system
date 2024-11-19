<?php

/**
* Fluent CRM Integration Curse enroll trigger.
*
* @since 1.14.0
* @package Masteriyo\Addons\FluentCRM
*/
// phpcs:ignoreFile
namespace Masteriyo\Addons\FluentCRM\triggers;

use Masteriyo\Addons\FluentCRM\Helper;
use FluentCrm\App\Services\Funnel\BaseTrigger;
use FluentCrm\App\Services\Funnel\FunnelHelper;
use FluentCrm\App\Services\Funnel\FunnelProcessor;
use FluentCrm\Framework\Support\Arr;
use Masteriyo\Query\UserCourseQuery;

defined( 'ABSPATH' ) || exit;

class PaidCourseEnrollTrigger extends BaseTrigger {

	/**
	 * CourseEnrollTrigger constructor.
	 *
	 * @since 1.14.0
	 */
	public function __construct() {
		$this->triggerName  = 'masteriyo_order_status_completed';
		$this->priority     = 10;
		$this->actionArgNum = 2;
		parent::__construct();
	}

	/**
	 * Get the trigger.
	 *
	 * @since 1.14.0
	 *
	 * @return array
	 */
	public function getTrigger() {
		return array(
			'category'    => __( 'Masteriyo LMS', 'learning-management-system' ),
			'label'       => __( 'Paid Course Started', 'learning-management-system' ),
			'icon'        => 'dashicons dashicons-welcome-learn-more',
			'description' => __( 'This funnel runs when a student starts a new paid course', 'learning-management-system' ),
		);
	}

	/**
	 * Get the default settings.
	 *
	 * @since 1.14.0
	 *
	 * @return array
	 */
	public function getFunnelSettingsDefaults() {
		return array(
			'subscription_status' => 'subscribed',
		);
	}

	/**
	 * Get the settings fields.
	 *
	 * @since 1.14.0
	 *
	 * @param array $funnel The funnel.
	 *
	 * @return array
	 */
	public function getSettingsFields( $funnel ) {
		return array(
			'title'     => __( 'Starting a paid course in Masteriyo LMS', 'learning-management-system' ),
			'sub_title' => __( 'This Funnel will start when a student starts a new paid course', 'learning-management-system' ),
			'fields'    => array(
				'subscription_status'      => array(
					'type'        => 'option_selectors',
					'option_key'  => 'editable_statuses',
					'is_multiple' => false,
					'label'       => __( 'Subscription Status', 'learning-management-system' ),
					'placeholder' => __( 'Select Status', 'learning-management-system' ),
				),
				'subscription_status_info' => array(
					'type'       => 'html',
					'info'       => '<b>' . __( 'An Automated double-option email will be sent for new subscribers', 'learning-management-system' ) . '</b>',
					'dependency' => array(
						'depends_on' => 'subscription_status',
						'operator'   => '=',
						'value'      => 'pending',
					),
				),
			),
		);
	}

	/**
	 * Get the default condition settings.
	 *
	 * @since 1.14.0
	 *
	 * @param array $funnel The funnel.
	 *
	 * @return array
	 */
	public function getFunnelConditionDefaults( $funnel ) {
		return array(
			'update_type'  => 'update', // skip_all_actions, skip_update_if_exist
			'course_ids'   => array(),
			'run_multiple' => 'no',
		);
	}

	/**
	 * Get the condition fields.
	 *
	 * @since 1.14.0
	 *
	 * @param array $funnel The funnel.
	 *
	 * @return array
	 */
	public function getConditionFields( $funnel ) {
		return array(
			'update_type'  => array(
				'type'    => 'radio',
				'label'   => __( 'If Contact Already Exist?', 'learning-management-system' ),
				'help'    => __( 'Please specify what will happen if the subscriber already exist in the database', 'learning-management-system' ),
				'options' => FunnelHelper::getUpdateOptions(),
			),
			'course_ids'   => array(
				'type'        => 'multi-select',
				'label'       => __( 'Target Courses', 'learning-management-system' ),
				'help'        => __( 'Select for which Courses this automation will run', 'learning-management-system' ),
				'options'     => Helper::get_courses( null, '', 'paid' ),
				'inline_help' => __( 'Keep it blank to run to any Course Enrollment', 'learning-management-system' ),
			),
			'run_multiple' => array(
				'type'        => 'yes_no_check',
				'label'       => '',
				'check_label' => __( 'Restart the Automation Multiple times for a contact for this event. (Only enable if you want to restart automation for the same contact)', 'learning-management-system' ),
				'inline_help' => __( 'If you enable, then it will restart the automation for a contact if the contact already in the automation. Otherwise, It will just skip if already exist', 'learning-management-system' ),
			),
		);
	}

	/**
	 * Handle the trigger.
	 *
	 * @since 1.14.0
	 *
	 * @param mixed $funnel The funnel.
	 * @param mixed $originalArgs The original arguments.
	 *
	 * @return void
	 */
	public function handle( $funnel, $originalArgs ) {

		// $courseId = $originalArgs->get_course_id();

		$user_course = $originalArgs[1];
		$userId = $user_course->get_customer_id();

		$course_items = $user_course->get_items( 'course' );
		$data = array();

		foreach ( $course_items as $course_item ) {
			$data[] = array(
				'course_id' => $course_item->get_course_id(),
			);
		}

		foreach ( $data as $data_entry ) {
			$query = new UserCourseQuery(
				array(
					'course_id' => $data_entry['course_id'],
					'user_id'   => $user_course->get_customer_id(),
				)
			);

			$user_courses = $query->get_user_courses();
		}

		$course = current( $user_courses );
		$course = $course->get_course();
		$courseId = $course->get_id();

		// $userId   = $originalArgs[2];

		$subscriberData = FunnelHelper::prepareUserData( $userId );

		$subscriberData['source'] = __( 'Masteriyo LMS', 'learning-management-system' );

		if ( empty( $subscriberData['email'] ) ) {
			return;
		}

		$willProcess = $this->isProcessable( $funnel, $courseId, $subscriberData );

		$willProcess = apply_filters( 'fluentcrm_funnel_will_process_' . $this->triggerName, $willProcess, $funnel, $subscriberData, $originalArgs );
		if ( ! $willProcess ) {
			return;
		}

		$subscriberData = wp_parse_args( $subscriberData, $funnel->settings );

		$subscriberData['status'] = $subscriberData['subscription_status'];
		unset( $subscriberData['subscription_status'] );

		( new FunnelProcessor() )->startFunnelSequence(
			$funnel,
			$subscriberData,
			array(
				'source_trigger_name' => $this->triggerName,
				'source_ref_id'       => $courseId,
			)
		);
	}

	/**
	 * Check if the trigger is processable.
	 *
	 * @since 1.14.0
	 *
	 * @param mixed $funnel The funnel.
	 * @param mixed $courseId The course ID.
	 * @param mixed $subscriberData The subscriber data.
	 *
	 * @return bool
	 */
	private function isProcessable( $funnel, $courseId, $subscriberData ) {
		$conditions = $funnel->conditions;
		// check update_type
		$updateType = Arr::get( $conditions, 'update_type' );

		$subscriber = FunnelHelper::getSubscriber( $subscriberData['email'] );
		if ( $subscriber && $updateType == 'skip_all_if_exist' ) {
			return false;
		}

		// check the products ids
		if ( $conditions['course_ids'] ) {
			if ( ! in_array( $courseId, $conditions['course_ids'] ) ) {
				return false;
			}
		}

		// check run_only_one
		if ( $subscriber && FunnelHelper::ifAlreadyInFunnel( $funnel->id, $subscriber->id ) ) {
			$multipleRun = Arr::get( $conditions, 'run_multiple' ) == 'yes';
			if ( $multipleRun ) {
				FunnelHelper::removeSubscribersFromFunnel( $funnel->id, array( $subscriber->id ) );
			} else {
				return false;
			}
		}

		return true;
	}
}
