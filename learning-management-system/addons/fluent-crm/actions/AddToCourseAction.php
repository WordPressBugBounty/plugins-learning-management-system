<?php
/**
* Fluent CRM Integration add to course action.
*
* @since 1.14.0
* @package Masteriyo\Addons\FluentCRM
*/
// phpcs:ignoreFile
namespace Masteriyo\Addons\FluentCRM\actions;

use Masteriyo\Addons\FluentCRM\Helper;
use FluentCrm\App\Services\Funnel\BaseAction;
use FluentCrm\App\Services\Funnel\FunnelHelper;
use FluentCrm\Framework\Support\Arr;
use Masteriyo\Enums\UserCourseStatus;

class AddToCourseAction extends BaseAction {

	/**
	 * AddToCourseAction constructor.
	 *
	 * @since 1.14.0
	 */
	public function __construct() {
		$this->actionName = 'masteriyo_lms_add_to_course';
		$this->priority   = 20;
		parent::__construct();
	}

	/**
	 * Get the block settings.
	 *
	 * @since 1.14.0
	 *
	 * @return array
	 */
	public function getBlock() {
		return array(
			'category'    => __( 'Masteriyo LMS', 'learning-management-system' ),
			'title'       => __( 'Enroll To Course', 'learning-management-system' ),
			'description' => __( 'Enroll the contact to a specific LMS Course', 'learning-management-system' ),
			'icon'        => 'dashicons dashicons-welcome-learn-more',
			'settings'    => array(
				'course_id'          => '',
				'skip_for_public'    => 'no',
				'send_welcome_email' => 'yes',
			),
		);
	}

	/**
	 * Get the block fields.
	 *
	 * @since 1.14.0
	 *
	 * @return array
	 */
	public function getBlockFields() {
		return array(
			'title'     => __( 'Enroll To a Course', 'learning-management-system' ),
			'sub_title' => __( 'Enroll the contact to a specific LMS Course', 'learning-management-system' ),
			'fields'    => array(
				'course_id'          => array(
					'type'        => 'select',
					'option_key'  => 'product_selector_masteriyo_lms',
					'is_multiple' => false,
					'clearable'   => true,
					'label'       => __( 'Select Course to Enroll', 'learning-management-system' ),
					'placeholder' => __( 'Select Course', 'learning-management-system' ),
					'options'     => Helper::get_courses(),
				),
				'skip_for_public'    => array(
					'type'        => 'yes_no_check',
					'check_label' => __( 'Do not enroll the course if contact is not an existing WordPress User', 'learning-management-system' ),
				),
				'send_welcome_email' => array(
					'type'        => 'yes_no_check',
					'check_label' => __( 'Send default WordPress Welcome Email for new WordPress users', 'learning-management-system' ),
					'dependency'  => array(
						'depends_on' => 'skip_for_public',
						'operator'   => '=',
						'value'      => 'no',
					),
				),
				'html'               => array(
					'type'       => 'html',
					'info'       => __( 'WordPress user will be created if no user found with the contact\'s email address', 'learning-management-system' ),
					'dependency' => array(
						'depends_on' => 'skip_for_public',
						'operator'   => '=',
						'value'      => 'no',
					),
				),
			),
		);
	}

	/**
	 * Handle the action.
	 *
	 * @since 1.14.0
	 *
	 * @param mixed $subscriber
	 * @param mixed $sequence
	 * @param mixed $funnelSubscriberId
	 * @param mixed $funnelMetric
	 *
	 * @return bool
	 */
	public function handle( $subscriber, $sequence, $funnelSubscriberId, $funnelMetric ) {
		$settings = $sequence->settings;
		$userId   = $subscriber->getWpUserId();

		if ( ! $userId && $settings['skip_for_public'] == 'yes' ) {
			$funnelMetric->notes  = __( 'Funnel Skipped because user could not be found', 'learning-management-system' );
			$funnelMetric->status = 'skipped';
			$funnelMetric->save();
			FunnelHelper::changeFunnelSubSequenceStatus( $funnelSubscriberId, $sequence->id, 'skipped' );

			return false;
		}

		$courseId = Arr::get( $settings, 'course_id' );

		if ( ! $courseId ) {
			$funnelMetric->notes  = __( 'Funnel Skipped because no course found', 'learning-management-system' );
			$funnelMetric->status = 'skipped';
			$funnelMetric->save();
			FunnelHelper::changeFunnelSubSequenceStatus( $funnelSubscriberId, $sequence->id, 'skipped' );

			return false;
		}

		if ( ! $userId ) {
			// If no user found then let's create a user
			$welcomeEmail = Arr::get( $settings, 'send_welcome_email' ) == 'yes';
			$userId       = FunnelHelper::createWpUserFromSubscriber( $subscriber, $welcomeEmail );

			if ( is_wp_error( $userId ) ) {
				$funnelMetric->notes  = $userId->get_error_message();
				$funnelMetric->status = 'skipped';
				$funnelMetric->save();
				FunnelHelper::changeFunnelSubSequenceStatus( $funnelSubscriberId, $sequence->id, 'skipped' );

				return false;
			}
		}

		$user_course  = masteriyo( 'user-course' );

		$user_course->set_course_id( $courseId );
		$user_course->set_user_id( $userId );
		$user_course->set_status( UserCourseStatus::ACTIVE );
		$user_course->set_date_start( current_time( 'mysql', true ) );

		$result = $user_course->save();

		if ( ! $result ) {
			$funnelMetric->notes  = __( 'User could not be enrolled to the selected course. Maybe course is already enrolled or Academy failed to enroll the course', 'learning-management-system' );
			$funnelMetric->status = 'failed';
			$funnelMetric->save();
			FunnelHelper::changeFunnelSubSequenceStatus( $funnelSubscriberId, $sequence->id, 'skipped' );

			return false;
		}

		return true;
	}

}
