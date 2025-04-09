<?php
/**
* Fluent CRM Integration remove from course action.
*
* @since 1.14.0
* @package Masteriyo\Addons\FluentCRM
*/
// phpcs:ignoreFile
namespace Masteriyo\Addons\FluentCRM\actions;

defined( 'ABSPATH' ) || exit;


use Masteriyo\Addons\FluentCRM\Helper;
use FluentCrm\App\Services\Funnel\BaseAction;
use FluentCrm\App\Services\Funnel\FunnelHelper;
use FluentCrm\Framework\Support\Arr;

class RemoveFromCourseAction extends BaseAction {

	/**
	 * RemoveFromCourseAction constructor.
	 *
	 * @since 1.14.0
	 */
	public function __construct() {
		$this->actionName = 'masteriyo_lms_remove_from_course';
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
		return [
			'category'    => __( 'Masteriyo LMS', 'learning-management-system' ),
			'title'       => __( 'Remove From a Course', 'learning-management-system' ),
			'description' => __( 'Remove the contact from a specific LMS Course', 'learning-management-system' ),
			'icon'        => 'dashicons dashicons-welcome-learn-more',
			'settings'    => [
				'course_id' => ''
			]
		];
	}

	/**
	 * Get the block fields.
	 *
	 * @since 1.14.0
	 *
	 * @return array
	 */
	public function getBlockFields() {
		return [
			'title'     => __( 'Remove From a Course', 'learning-management-system' ),
			'sub_title' => __( 'Remove the contact from a specific LMS Course', 'learning-management-system' ),
			'fields'    => [
				'course_id' => [
					'type'        => 'select',
					'option_key'  => 'product_selector_academy_lms',
					'options'     => Helper::get_courses(),
					'is_multiple' => false,
					'clearable'   => true,
					'label'       => __( 'Select a course that you want to remove from', 'learning-management-system' ),
					'placeholder' => __( 'Select Course', 'learning-management-system' )
				]
			]
		];
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
	public function handle( $subscriber, $sequence, $funnelSubscriberId, $funnelMetric ): bool {
		$settings = $sequence->settings;
		$userId   = $subscriber->getWpUserId();

		$courseId = Arr::get( $settings, 'course_id' );

		if ( ! $userId ) {
			$funnelMetric->notes = __( 'Funnel Skipped because user could not be found', 'learning-management-system' );
			$funnelMetric->save();
			FunnelHelper::changeFunnelSubSequenceStatus( $funnelSubscriberId, $sequence->id, 'skipped' );

			return false;
		}

        $result = Helper::masteriyo_remove_user_from_course( $userId, $courseId );

		if ( ! $result ) {
			$funnelMetric->notes = __( 'User is not enrolled in the course', 'learning-management-system' );
			$funnelMetric->save();
			FunnelHelper::changeFunnelSubSequenceStatus( $funnelSubscriberId, $sequence->id, 'skipped' );
		}
		return true;
	}
}
