<?php

/**
 * Masteriyo Fluent CRM Integration setup.
 *
 * @package Masteriyo\Addons\FluentCRM
 *
 * @since 1.14.0
 */

namespace Masteriyo\Addons\FluentCRM;

use Masteriyo\Addons\FluentCRM\actions\AddToCourseAction;
use Masteriyo\Addons\FluentCRM\actions\RemoveFromCourseAction;
use Masteriyo\Addons\FluentCRM\triggers\CourseCompletedTrigger;
use Masteriyo\Addons\FluentCRM\triggers\CourseEnrollTrigger;
use Masteriyo\Addons\FluentCRM\triggers\PaidCourseEnrollTrigger;

defined( 'ABSPATH' ) || exit;

/**
 * Main Masteriyo Fluent CRM Integration class.
 *
 * @class Masteriyo\Addons\FluentCRM
 *
 * @since 1.14.0
 */

class FluentCrmAddon {


	/**
	 * The single instance of the class.
	 *
	 * @since 1.14.0
	 *
	 * @var \Masteriyo\Addons\FluentCRM\FluentCrmAddon|null
	 */
	protected static $instance = null;

	/**
	 * Constructor.
	 *
	 * @since 1.14.0
	 */
	protected function __construct() {}

	/**
	 * Get class instance.
	 *
	 * @since 1.14.0
	 *
	 * @return \Masteriyo\Addons\FluentCRM\FluentCrmAddon Instance.
	 */
	final public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Prevent cloning.
	 *
	 * @since 1.14.0
	 */
	public function __clone() {}

	/**
	 * Prevent unserializing.
	 *
	 * @since 1.14.0
	 */
	public function __wakeup() {}

	/**
	 * Initialize module.
	 *
	 * @since 1.14.0
	 */
	public function init() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.14.0
	 */
	public function init_hooks() {
		add_action(
			'fluentcrm_loaded',
			function () {
				// registering triggers
				new CourseEnrollTrigger();
				new CourseCompletedTrigger();
				new PaidCourseEnrollTrigger();

				// registering actions
				new AddToCourseAction();
				new RemoveFromCourseAction();

				// automation conditions
				( new AutomationConditions() )->init();

					// smart codes for email | shortcodes
				( new MasteriyoShortCodes() )->init();
			}
		);

		add_filter(
			'fluent_crm/funnel_icons',
			function ( $icons ) {
				$icons['masteriyolms'] = 'dashicons dashicons-welcome-learn-more';

				return $icons;
			}
		);
	}
}
