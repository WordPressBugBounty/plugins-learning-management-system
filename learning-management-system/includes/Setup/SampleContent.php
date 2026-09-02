<?php
/**
 * Sample content seeding.
 *
 * @package Masteriyo\Setup
 */

namespace Masteriyo\Setup;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Constants;
use Masteriyo\Enums\PostStatus;
use Masteriyo\Importer\CourseImporter;
use Masteriyo\Jobs\SampleContentSeedJob;
use Masteriyo\PostType\PostType;

/**
 * Class SampleContent
 *
 * Seeds a single draft sample course on a fresh install, even when the user
 * never opens the setup wizard, and tags everything it creates so it can be
 * identified and never created twice.
 *
 * @package Masteriyo\Setup
 */
class SampleContent {

	/**
	 * Option that records the seeding state.
	 *
	 * @var string
	 */
	const OPTION = 'masteriyo_sample_content';

	/**
	 * Option flag set on first install, consumed by the async seed job.
	 *
	 * @var string
	 */
	const PENDING_FLAG = 'masteriyo_sample_content_pending';

	/**
	 * Option counting failed install-time seed attempts.
	 *
	 * @var string
	 */
	const RETRIES = 'masteriyo_sample_content_retries';

	/**
	 * Option used as a cross-process import lock.
	 *
	 * @var string
	 */
	const LOCK = 'masteriyo_sample_content_lock';

	/**
	 * Give up re-queueing the install-time seed after this many failed runs.
	 *
	 * @var int
	 */
	const MAX_RETRIES = 3;

	/**
	 * Bundled sample courses: slug => file basename under sample-data/.
	 *
	 * @var array
	 */
	const COURSES = array(
		'product-tour'   => 'starter-course-1.json',
		'subject-course' => 'starter-course-2.json',
		'cohort-course'  => 'starter-course-3.json',
	);

	/**
	 * Whether an import started by this class is currently running.
	 *
	 * @var bool
	 */
	private static $importing = false;

	/**
	 * Course post IDs collected while an import started by this class runs.
	 *
	 * @var int[]
	 */
	private static $course_ids = array();

	/**
	 * Token proving this process still owns the import lock it took.
	 *
	 * @var string
	 */
	private static $lock_token = '';

	/**
	 * Determine whether sample content should be seeded.
	 *
	 * True only when the site was never seeded before and has no course the user
	 * would call content. Trashed and abandoned auto-drafts do not count: summing
	 * every status let one trashed test course disable seeding for good.
	 *
	 * @return bool
	 */
	public static function should_seed() {
		if ( false !== get_option( self::OPTION ) ) {
			return false;
		}

		$counts  = (array) wp_count_posts( PostType::COURSE );
		$ignored = array( PostStatus::TRASH, PostStatus::AUTO_DRAFT, PostStatus::INHERIT );

		foreach ( $counts as $status => $count ) {
			if ( ! in_array( $status, $ignored, true ) && $count > 0 ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Import one or more bundled sample courses.
	 *
	 * Slugs already listed in the option are skipped. Each file is imported in
	 * its own try/catch so one bad file never aborts the rest, and the option
	 * is saved after every successful file so a partial failure resumes without
	 * duplicating what already imported.
	 *
	 * @param array  $slugs  Course slugs to import.
	 * @param string $status Post status to assign to imported courses.
	 * @return bool|null True if anything imported, false if nothing did, null when
	 *                   the run was deferred to a queued job instead.
	 */
	public static function import( array $slugs, $status = 'draft' ) {
		if ( ! self::acquire_lock() ) {
			// Another import is running right now (typically the install-time job
			// racing a wizard finish). Queue the batch instead of dropping it — the
			// per-slug bookkeeping makes any overlap a no-op — and pass it as
			// non-unique, because hook-level uniqueness would refuse it while the
			// lock holder's own job is still marked running.
			self::enqueue( $slugs, false, $status );

			// null, not false: nothing failed, it is simply queued. Callers that report
			// to a person need to tell those two apart.
			return null;
		}

		$option     = get_option( self::OPTION, array() );
		$slugs_done = ( ! empty( $option['slugs'] ) && is_array( $option['slugs'] ) ) ? $option['slugs'] : array();
		$course_ids = ( ! empty( $option['course_ids'] ) && is_array( $option['course_ids'] ) ) ? $option['course_ids'] : array();

		// Action Scheduler runs with no authenticated user, and CourseImporter stamps
		// get_current_user_id() as post_author — user 0 makes the samples authorless
		// and invisible to author-scoped queries. Borrow the oldest administrator.
		$original_user = get_current_user_id();

		if ( 0 === $original_user ) {
			$admins = get_users(
				array(
					'role'    => 'administrator',
					'fields'  => 'ID',
					'number'  => 1,
					'orderby' => 'ID',
					'order'   => 'ASC',
				)
			);

			if ( ! empty( $admins ) ) {
				wp_set_current_user( (int) $admins[0] );
			}
		}

		self::$importing  = true;
		self::$course_ids = $course_ids;

		try {
			foreach ( $slugs as $slug ) {
				if ( in_array( $slug, $slugs_done, true ) ) {
					continue;
				}

				if ( ! isset( self::COURSES[ $slug ] ) ) {
					masteriyo_get_logger()->error(
						sprintf( 'Sample content slug "%s" is not registered.', $slug ),
						array( 'source' => 'sample-content' )
					);
					continue;
				}

				$file = Constants::get( 'MASTERIYO_PLUGIN_DIR' ) . '/sample-data/' . self::COURSES[ $slug ];

				if ( ! file_exists( $file ) ) {
					masteriyo_get_logger()->error(
						sprintf( 'Sample content file "%s" does not exist.', $file ),
						array( 'source' => 'sample-content' )
					);
					continue;
				}

				try {
					$courses_before = count( self::$course_ids );

					$importer = new CourseImporter( $status );
					$importer->import( $file, 'sample-content', false );

					// CourseImporter logs-and-continues on wp_insert_post() failures, so
					// returning proves nothing. Each bundled file carries exactly one
					// course; unless tag_imported() saw it arrive, the slug is not done —
					// marking it anyway would suppress every retry and permanently record
					// a sample as seeded that no one can find.
					if ( count( self::$course_ids ) !== $courses_before + 1 ) {
						masteriyo_get_logger()->error(
							sprintf( 'Sample content "%s" did not produce its course; leaving it retryable.', $slug ),
							array( 'source' => 'sample-content' )
						);
						continue;
					}

					$slugs_done[] = $slug;
					$course_ids   = self::$course_ids;

					self::save_option( $slugs_done, $course_ids );
				} catch ( \Throwable $e ) {
					masteriyo_get_logger()->error( $e->getMessage(), array( 'source' => 'sample-content' ) );
				}
			}
		} finally {
			// A fatal anywhere above must not leave $importing true: the next import in
			// this process would be a user's own, and tag_imported() would mark it as
			// sample content.
			self::$importing  = false;
			self::$course_ids = array();

			if ( get_current_user_id() !== $original_user ) {
				wp_set_current_user( $original_user );
			}

			self::release_lock();
		}

		// Nothing to persist here: the per-slug save inside the loop already recorded
		// every success while the lock was held. Writing again after releasing it is
		// how a slow run overwrites a newer one's record.
		return ! empty( $slugs_done );
	}

	/**
	 * Tag posts created by a sample-content import.
	 *
	 * Hooked to masteriyo_after_import. Writes the sample-content marker on
	 * every new post and collects the imported course IDs, but only while an
	 * import started by this class is in progress, so a user's own course
	 * import is never tagged.
	 *
	 * @param mixed $items   Imported items.
	 * @param array $history Import history (old post ID => new post ID).
	 * @return void
	 */
	public static function tag_imported( $items, $history ) {
		if ( ! self::$importing ) {
			return;
		}

		$posts = ( ! empty( $history['posts'] ) && is_array( $history['posts'] ) ) ? $history['posts'] : array();

		foreach ( $posts as $new_post_id ) {
			add_post_meta( $new_post_id, '_masteriyo_is_sample_content', 1, true );

			if ( PostType::COURSE === get_post_type( $new_post_id ) ) {
				self::$course_ids[] = $new_post_id;
			}
		}

		self::$course_ids = array_values( array_unique( self::$course_ids ) );
	}

	/**
	 * Register the hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'masteriyo_after_import', array( __CLASS__, 'tag_imported' ), 10, 2 );
		add_action( 'action_scheduler_init', array( __CLASS__, 'maybe_enqueue_pending' ) );
	}

	/**
	 * Enqueue the pending seed job, if any.
	 *
	 * Action Scheduler only bootstraps its store on init priority 1, and
	 * Install::install() runs on init priority 0, so the actual enqueue is
	 * deferred to the action_scheduler_init hook instead of the install call.
	 *
	 * @return void
	 */
	public static function maybe_enqueue_pending() {
		if ( ! get_option( self::PENDING_FLAG ) ) {
			return;
		}

		if ( ! self::should_seed() ) {
			delete_option( self::PENDING_FLAG );
			return;
		}

		if ( ! self::enqueue( array( 'product-tour' ) ) ) {
			// Keep the flag so the next request tries again. Clearing it before the job
			// has actually been accepted would strand the site with no sample content
			// and nothing but a log line to say why.
			return;
		}

		delete_option( self::PENDING_FLAG );
	}

	/**
	 * Re-flag the install-time seed after a run that imported nothing.
	 *
	 * Called by the seed job. Bounded: a site where the import fails every time
	 * (corrupt bundle, exhausted disk) stops being retried after MAX_RETRIES rather
	 * than churning on every request forever.
	 *
	 * @return void
	 */
	public static function retry_pending_seed() {
		// Not should_seed(): the wizard-extras job may have succeeded first, and its
		// option write must not block the product tour's retry. What matters here is
		// that the tour itself is still missing and no real course exists yet.
		$option = get_option( self::OPTION, array() );
		$done   = ( ! empty( $option['slugs'] ) && is_array( $option['slugs'] ) ) ? $option['slugs'] : array();

		if ( in_array( 'product-tour', $done, true ) ) {
			return;
		}

		$courses = get_posts(
			array(
				'post_type'   => PostType::COURSE,
				'post_status' => 'any',
				'numberposts' => 1,
				'fields'      => 'ids',
				'meta_query'  => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					array(
						'key'     => '_masteriyo_is_sample_content',
						'compare' => 'NOT EXISTS',
					),
				),
			)
		);

		if ( ! empty( $courses ) ) {
			return;
		}

		$retries = (int) get_option( self::RETRIES, 0 ) + 1;
		update_option( self::RETRIES, $retries, false );

		if ( $retries > self::MAX_RETRIES ) {
			masteriyo_get_logger()->error(
				sprintf( 'Sample content seeding failed %d times; giving up.', $retries ),
				array( 'source' => 'sample-content' )
			);
			return;
		}

		update_option( self::PENDING_FLAG, 1, false );
	}

	/**
	 * Enqueue a seed job for these slugs unless the same set is already queued.
	 *
	 * Action Scheduler's $unique flag is hook-scoped, not args-scoped: while any
	 * batch is pending or running, a unique enqueue of a *different* batch is
	 * refused too. That is fine for its remaining callers — the install-time seed
	 * always queues the same one-slug batch, and the lock-contention fallback in
	 * import() passes $unique = false — but it means this method must never be
	 * the only path a batch takes on a user-facing flow. The wizard imports
	 * synchronously for that reason.
	 *
	 * @param array  $slugs  Course slugs to seed.
	 * @param bool   $unique Refuse to queue while any batch is pending or running.
	 * @param string $status Post status the deferred import assigns. Dropping it
	 *                       here is how a user's "Publish" silently became drafts.
	 * @return bool Whether a job for these slugs is now queued.
	 */
	public static function enqueue( array $slugs, $unique = true, $status = 'draft' ) {
		$args = array( array_values( $slugs ), $status );

		if ( false !== as_next_scheduled_action( SampleContentSeedJob::NAME, $args, SampleContentSeedJob::GROUP_NAME ) ) {
			return true;
		}

		return 0 !== as_enqueue_async_action( SampleContentSeedJob::NAME, $args, SampleContentSeedJob::GROUP_NAME, $unique );
	}

	/**
	 * Take the cross-process import lock.
	 *
	 * add_option() is the atomic primitive: it refuses to write when the row
	 * exists. A lock older than five minutes is treated as the debris of a
	 * fatalled import and stolen.
	 *
	 * @return bool Whether this process now holds the lock.
	 */
	private static function acquire_lock() {
		$token = wp_generate_uuid4();

		if ( add_option( self::LOCK, self::lock_value( $token ), '', 'no' ) ) {
			self::$lock_token = $token;
			return true;
		}

		$held = get_option( self::LOCK );

		// A row without a readable timestamp predates the token and counts as stale,
		// so an upgrade landing on old debris cannot wedge the seeder for good.
		$since = ( is_array( $held ) && isset( $held['time'] ) ) ? (int) $held['time'] : 0;

		if ( time() - $since <= 5 * MINUTE_IN_SECONDS ) {
			return false;
		}

		// Steal by removing the row and re-adding it, never by overwriting: delete_option()
		// reports whether this process did the deleting and add_option() refuses an existing
		// row, so two processes meeting the same debris cannot both come away holding it.
		if ( delete_option( self::LOCK ) && add_option( self::LOCK, self::lock_value( $token ), '', 'no' ) ) {
			self::$lock_token = $token;
			return true;
		}

		return false;
	}

	/**
	 * Build the stored lock value.
	 *
	 * @param string $token Token identifying the holder.
	 * @return array
	 */
	private static function lock_value( $token ) {
		return array(
			'token' => $token,
			'time'  => time(),
		);
	}

	/**
	 * Release the import lock, but only if this process still owns it.
	 *
	 * @return void
	 */
	private static function release_lock() {
		$held = get_option( self::LOCK );

		// A run that overran the steal threshold no longer owns the row. Deleting it
		// would free a lock the thief is still importing under, letting a third process
		// in alongside it and seed the same slug twice.
		if ( is_array( $held ) && isset( $held['token'] ) && $held['token'] === self::$lock_token ) {
			delete_option( self::LOCK );
		}

		self::$lock_token = '';
	}

	/**
	 * Forget that these slugs were imported, so they can be imported again.
	 *
	 * import() skips recorded slugs, so a deleted course could never come back.
	 *
	 * @param array $slugs Course slugs to drop from the record.
	 * @return void
	 */
	public static function forget( array $slugs ) {
		$option = get_option( self::OPTION );

		if ( ! is_array( $option ) ) {
			return;
		}

		$done = ( ! empty( $option['slugs'] ) && is_array( $option['slugs'] ) ) ? $option['slugs'] : array();
		$kept = array_values( array_diff( $done, $slugs ) );

		if ( $kept === $done ) {
			return;
		}

		$course_ids = array();

		foreach ( ( ! empty( $option['course_ids'] ) && is_array( $option['course_ids'] ) ) ? $option['course_ids'] : array() as $course_id ) {
			if ( PostType::COURSE === get_post_type( absint( $course_id ) ) ) {
				$course_ids[] = absint( $course_id );
			}
		}

		// Not save_option(): that merges against what is stored, which would put the
		// slug straight back and leave the re-import with nothing to do.
		update_option(
			self::OPTION,
			array(
				'seeded_at'  => time(),
				'slugs'      => $kept,
				'course_ids' => $course_ids,
				'removed'    => false,
			),
			false
		);
	}

	/**
	 * Persist the current seeding state to the option.
	 *
	 * @param array $slugs_done Slugs imported so far.
	 * @param array $course_ids Course post IDs imported so far.
	 * @return void
	 */
	private static function save_option( array $slugs_done, array $course_ids ) {
		// Merged against what is stored, never a snapshot taken before the loop: a
		// second run that stole a stale lock would otherwise erase the slug this one
		// just recorded, and the course would be imported a second time.
		$stored = get_option( self::OPTION );
		$stored = is_array( $stored ) ? $stored : array();

		$slugs_done = array_values( array_unique( array_merge( self::stored_list( $stored, 'slugs' ), $slugs_done ) ) );
		$course_ids = array_values( array_unique( array_merge( self::stored_list( $stored, 'course_ids' ), $course_ids ) ) );

		update_option(
			self::OPTION,
			array(
				'seeded_at'  => time(),
				'slugs'      => $slugs_done,
				'course_ids' => $course_ids,
				'removed'    => false,
			),
			false
		);
	}

	/**
	 * One list out of the stored option.
	 *
	 * @param array  $stored Stored option.
	 * @param string $key    List key.
	 * @return array
	 */
	private static function stored_list( array $stored, $key ) {
		return ( ! empty( $stored[ $key ] ) && is_array( $stored[ $key ] ) ) ? $stored[ $key ] : array();
	}
}
