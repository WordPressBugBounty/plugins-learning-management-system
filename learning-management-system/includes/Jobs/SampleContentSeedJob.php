<?php

namespace Masteriyo\Jobs;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Setup\SampleContent;

/**
 * Class SampleContentSeedJob
 *
 * Handles the asynchronous seeding of sample content.
 *
 * @package Masteriyo\Jobs
 */
class SampleContentSeedJob {
	/**
	 * Name of the action.
	 */
	const NAME = 'masteriyo/job/seed_sample_content';

	/**
	 * Group name of the action.
	 */
	const GROUP_NAME = 'masteriyo-sample-content';

	/**
	 * Register the action hook handler.
	 */
	public function register() {
		add_action( self::NAME, array( $this, 'handle' ), 10, 2 );
	}

	/**
	 * Handle the seed action.
	 *
	 * @param mixed  $slugs  Course slugs to import.
	 * @param string $status Post status for the imported courses. Jobs queued
	 *                       before this argument existed carry one arg and land
	 *                       on the default.
	 */
	public function handle( $slugs, $status = 'draft' ) {
		$slugs    = (array) $slugs;
		$imported = false;

		try {
			$imported = SampleContent::import( $slugs, $status );
		} catch ( \Exception $e ) {
			masteriyo_get_logger()->error( $e->getMessage(), array( 'source' => 'sample-content' ) );
		}

		// The install-time seed consumed its pending flag when this job was queued;
		// if the run genuinely imported nothing, re-flag it (bounded) so the site is
		// not left permanently without sample content. null is not a failure — the
		// batch lost the lock and import() already re-queued it, so counting it here
		// would burn the bounded retry budget on benign contention.
		if ( false === $imported && in_array( 'product-tour', $slugs, true ) ) {
			SampleContent::retry_pending_seed();
		}
	}
}
