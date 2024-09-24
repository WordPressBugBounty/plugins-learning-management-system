<?php
/**
 * Masteriyo\Jobs\SendAddonsTrackingInfoJob file.
 *
 * @package Masteriyo\Jobs
 */

namespace Masteriyo\Jobs;

use Masteriyo\Tracking\SendAddonsTrackingInfo;

/**
 * @since 1.13.0
 */
class SendAddonsTrackingInfoJob {

	/**
	 * Name of the job.
	 *
	 * @since 1.13.0
	 */
	const NAME = 'masteriyo/job/send_addons_tracking_info';

	/**
	 * Registers the job to run when the cron is triggered.
	 *
	 * Adds the action hook to run the job when the cron is triggered.
	 *
	 * @since 1.13.0
	 */
	public function register() {
		add_action( self::NAME, array( $this, 'process' ) );
	}

	/**
	 * Start process.
	 *
	 * @since 1.13.0
	 */
	public function process() {
		$addons_tracking_info = new SendAddonsTrackingInfo();

		$addons_tracking_info->call_api();
	}
}
