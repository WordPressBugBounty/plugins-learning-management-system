<?php

/**
 * Masteriyo Fluent CRM Integration setup.
 *
 * @package Masteriyo\Addons\FluentCRM
 *
 * @since 1.14.0
 */
//phpcs:ignoreFile
namespace Masteriyo\Addons\FluentCRM;

defined( 'ABSPATH' ) || exit;


use Masteriyo\Addons\FluentCRM\Helper;

class AutomationConditions {

	/**
	 * Constructor.
	 *
	 * @since 1.14.0
	 */
	public function init() {
		add_filter( 'fluentcrm_automation_condition_groups', array( $this, 'add_automation_conditions' ), 10, 1 );
		add_filter(
			'fluentcrm_automation_conditions_assess_masteriyolms',
			array( $this, 'assess_automation_conditions' ),
			10,
			3
		);
		add_filter( 'fluentcrm_ajax_options_product_selector_masteriyolms', array( $this, 'get_courses' ), 10, 1 );
	}

	/**
	 * Add automation conditions.
	 *
	 * @param array $groups The groups.
	 *
	 * @return array
	 */
	public function add_automation_conditions( $groups ) {
		$groups['masteriyolms'] = array(
			'label'    => __( 'Masteriyo LMS', 'learning-management-system' ),
			'value'    => 'masteriyolms',
			'children' => array(
				array(
					'value'             => 'is_in_course',
					'label'             => __( 'Course Enrollment', 'learning-management-system' ),
					'type'              => 'selections',
					'component'         => 'product_selector',
					'is_multiple'       => true,
					'is_singular_value' => true,
				),
				array(
					'value'             => 'is_course_completed',
					'label'             => __( 'Course Completed', 'learning-management-system' ),
					'type'              => 'selections',
					'component'         => 'product_selector',
					'is_multiple'       => true,
					'is_singular_value' => true,
				),
			),
		);
		return $groups;
	}

	/**
	 * Assess automation conditions.
	 *
	 * @param bool   $result     The result.
	 * @param array  $conditions The conditions.
	 * @param object $subscriber The subscriber.
	 *
	 * @return bool
	 */
	public function assess_automation_conditions( $result, $conditions, $subscriber ) {
		foreach ( $conditions as $condition ) {
			$operator = $condition['operator'];
			$courses  = $condition['data_value'];
			$datKey   = $condition['data_key'];

			if ( 'is_in_course' === $datKey ) {
				$isInCourse = Helper::is_in_courses( $courses, $subscriber );
				if ( ( 'in' === $operator && ! $isInCourse ) || ( $isInCourse && 'not_in' === $operator ) ) {
					return false;
				}
			} elseif ( 'is_course_completed' === $datKey ) {
				$isComplete = Helper::is_courses_completed( $courses, $subscriber );
				if ( ( 'in' === $operator && ! $isComplete ) || ( $isComplete && 'not_in' === $operator ) ) {
					return false;
				}
			}
		}

		return $result;
	}

	/**
	 * Get the courses.
	 *
	 * @param array $memberships The memberships.
	 *
	 * @return array
	 */
	public function get_courses( $memberships ){
		return Helper::get_courses();
	}
}
