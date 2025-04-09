<?php

/**
 * Masteriyo Fluent CRM Integration setup | shortcodes.
 *
 * @package Masteriyo\Addons\FluentCRM
 *
 * @since 1.14.0
 */
//phpcs:ignoreFile
namespace Masteriyo\Addons\FluentCRM;

defined( 'ABSPATH' ) || exit;


use Masteriyo\Addons\FluentCRM\Helper;
use FluentCrm\App\Models\FunnelSubscriber;

class MasteriyoShortCodes {

	/**
	 * Constructor.
	 *
	 * @since 1.14.0
	 */
  public function init() {
		add_filter( 'fluent_crm/smartcode_group_callback_ml', array( $this, 'parse_masteriyo_codes' ), 10, 4 );
		add_filter( 'fluent_crm/extended_smart_codes', array( $this, 'masteriyo_push_general_codes' ) );
		add_filter( 'fluent_crm_funnel_context_smart_codes', array( $this, 'masteriyo_push_context_codes' ), 14, 2 );
	}

	/**
	 * Parse the Masteriyo LMS shortcodes.
	 *
	 * @since 1.14.0
	 *
	 * @param string $code The shortcode.
	 * @param string $valueKey The value key.
	 * @param string $defaultValue The default value.
	 * @param object $subscriber The subscriber.
	 *
	 * @return string
	 */
	public function parse_masteriyo_codes( $code, $valueKey, $defaultValue, $subscriber ) {
		$userId = $subscriber->getWpUserId();

		if ( ! $userId ) {
			return $defaultValue;
		}

		/*
		 * General Student Items
		 */
		switch ( $valueKey ) {
			case 'courses':
        $courses = masteriyo_get_all_user_course_ids( $userId );
				$coursesNames = array();
				if ( ! empty( $courses ) && is_array( $courses ) ) {
					foreach ( $courses as $course_id ) {
            $course = masteriyo_get_course( $course_id );

            if( $course ) {
              $coursesNames[] = $course->get_title();
            }
					}
				}
				if ( ! $coursesNames ) {
					return $defaultValue;
			}

			$html = '<ul class="masteriyo_courses">';
			foreach ( $coursesNames as $courseName ) {
					$html .= '<li>' . $courseName . '</li>';
			}
			$html .= '</ul>';

			return $html;

			case 'courses_link':
        $courses = masteriyo_get_all_user_course_ids( $userId );
				$coursesNames = array();
				if ( ! empty( $courses ) && is_array( $courses ) ) {
					foreach ( $courses as $course_id ) {
            $course = masteriyo_get_course( $course_id );

            if( $course ) {
              $coursesNames[] = [
                'title' => $course->get_title(),
                'permalink' => $course->get_permalink()
              ];
            }
					}
				}

				if ( ! $coursesNames ) {
					return $defaultValue;
				}

				$html = '<ul class="masteriyo_courses">';
				foreach ( $coursesNames as $coursesName ) {
					$html .= '<li><a href="' . $coursesName['permalink'] . '">' . $coursesName['title'] . '</a>';
				}
				$html .= '</ul>';

				return $html;
		}//end switch

		/*
		 * Contextual Course / Topic Related SmartCodes
		 */
		$triggerSource = false;
		$triggerId = false;

		if ( ! empty( $subscriber->funnel_subscriber_id ) ) {
			$funnelSub = FunnelSubscriber::where( 'id', $subscriber->funnel_subscriber_id )->first();
			if ( $funnelSub ) {
				$triggerSource = Helper::masteriyo_get_trigger_source( $funnelSub->source_trigger_name );
				$triggerId = $funnelSub->source_ref_id;
			}
		}

		$courseItems = [ 'course_name', 'course_href', 'course_name_linked' ];

		if ( 'course' === $triggerSource && ! in_array( $valueKey, $courseItems, true ) ) {
			$triggerId = false;
		}

		if ( ! $triggerId ) {
			return $defaultValue;
		}

		switch ( $valueKey ) {
			case 'course_name':
				return get_the_title( $triggerId );
			case 'course_href':
				return get_the_permalink( $triggerId );
			case 'course_name_linked':
				$title = get_the_title( $triggerId );
				if ( $title ) {
					return '<a href="' . get_the_permalink( $triggerId ) . '">' . $title . '</a>';
				}

				return $defaultValue;
		}
	}

	/**
	 * Push the Masteriyo LMS general codes.
	 *
	 * @since 1.14.0
	 *
	 * @param array $codes The codes.
	 *
	 * @return array
	 */
	public function masteriyo_push_general_codes( $codes ) {
		$codes['masteriyolms'] = [
			'key' => 'masteriyolms',
			'title' => 'MasteriyoLMS',
			'shortcodes' => $this->get_smart_codes()
		];

		return $codes;
	}

	/**
	 * Get the smart codes.
	 *
	 * @since 1.14.0
	 *
	 * @param string $context The context.
	 *
	 * @return array
	 */
	private function get_smart_codes( $context = '' ) {
		$generalCodes = [
			'{{ml.courses}}' => 'User Enrolled Course Names (Comma Separated)',
			'{{ml.courses_link}}' => 'User Enrolled Course with links (list)',
			'{{ml.course_name}}' => 'Current Course Title',
			'{{ml.course_name_linked}}' => 'Current Course Title with Hyperlink',
			'{{ml.course_href}}' => 'HTTP Link of the current course'
		];

		if ( ! $context ) {
			return $generalCodes;
		}

		$courseContext = [
			'{{ml.course_name}}' => 'Current Course Title',
			'{{ml.course_name_linked}}' => 'Current Course Title with Hyperlink',
			'{{ml.course_href}}' => 'HTTP Link of the current course'

		];

		if ( 'all' === $context ) {
			return array_merge( $generalCodes, $courseContext );
		} elseif ( 'course' === $context ) {
			return $courseContext;
		}

		return [];
	}

	/**
	 * Push the context codes.
	 *
	 * @since 1.14.0
	 *
	 * @param array $codes The codes.
	 * @param string $context The context.
	 *
	 * @return array
	 */
	public function masteriyo_push_context_codes( $codes, $context ) {
		$triggerSource = Helper::masteriyo_get_trigger_source( $context );
		if ( ! $triggerSource ) {
			return $codes;
		}

		if ( 'course' === $triggerSource ) {
			$codes[] = [
				'key' => 'ml_course',
				'title' => 'Enrolled Course',
				'shortcodes' => $this->get_smart_codes( $triggerSource )
			];

			return $codes;
		}

		return $codes;
	}
}
