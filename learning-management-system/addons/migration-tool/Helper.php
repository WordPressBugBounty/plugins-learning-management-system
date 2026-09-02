<?php
/**
 * Migration tool helper functions.
 *
 * @since 1.16.0 [Free]
 * @package Masteriyo\Addons\MigrationTool
 */

namespace Masteriyo\Addons\MigrationTool;

defined( 'ABSPATH' ) || exit;


use Masteriyo\Addons\MigrationTool\Migrators\LearnDashMigrator;
use Masteriyo\Addons\MigrationTool\Migrators\LearnPressMigrator;
use Masteriyo\Addons\MigrationTool\Migrators\LifterLMSMigrator;
use Masteriyo\Addons\MigrationTool\Migrators\MasterStudyMigrator;
use Masteriyo\Addons\MigrationTool\Migrators\TutorLMSMigrator;
use Masteriyo\AddonsFramework\Addons;
use Masteriyo\Enums\CourseProgressStatus;
use Masteriyo\Enums\PostStatus;
use Masteriyo\PostType\PostType;
use Masteriyo\Roles;

class Helper {

	/**
	 * Option holding the migrator slugs this site has already auto-activated for.
	 *
	 * Never cleared: it records what we did, not what is currently true.
	 */
	const AUTO_ACTIVATED_OPTION = 'masteriyo_migration_tool_auto_activated';

	/**
	 * Option holding the migrator slugs the notice announces.
	 *
	 * A subset of AUTO_ACTIVATED_OPTION: only the platforms whose detection
	 * actually turned the addon on. Finding a platform while the addon is already
	 * active is still recorded above, so it cannot switch the addon back on later,
	 * but claiming we enabled something we did not would be a lie.
	 */
	const ANNOUNCED_OPTION = 'masteriyo_migration_tool_announced';

	/**
	 * User meta set when a user dismisses the auto-activation notice.
	 */
	const NOTICE_DISMISSED_META = 'masteriyo_dismissed_migration_notice';

	/**
	 * Admin path the notice's call to action points at.
	 *
	 * The `?migration` search is read by the Tools screen, which opens the
	 * Migration tab rather than its default one.
	 */
	const NOTICE_CTA_PATH = 'admin.php?page=masteriyo#/tools?migration';

	/**
	 * Updates the user role based on the given user ID and desired role.
	 * If the given role is not already assigned to the user, it will be added.
	 * If the user does not have any of the valid roles (admin, manager, instructor, student), they will be assigned the student role.
	 *
	 * @since 1.16.0 [Free]
	 *
	 * @param int $user_id User ID.
	 * @param string $role Desired role.
	 */
	public static function update_user_role( $user_id, $role = Roles::STUDENT ) {
		$user = new \WP_User( $user_id );

		if ( ! $user || ! isset( $user->ID ) || ! $user->roles ) {
			return;
		}

		$valid_roles = array( Roles::ADMIN, Roles::MANAGER, Roles::INSTRUCTOR, Roles::STUDENT );

		if ( ! empty( $role ) && ! in_array( $role, (array) $user->roles, true ) ) {
			$user->add_role( $role );
		}

		if ( empty( array_intersect( $valid_roles, (array) $user->roles ) ) ) {
			$user->set_role( Roles::STUDENT );
		}
	}

	/**
	 * Determine the video source for a given URL.
	 *
	 * @since 1.16.0 [Free]
	 *
	 * @param string $url URL of video.
	 *
	 * @return array Array with the video source (embed, youtube, vimeo, external) and the URL.
	 */
	public static function determine_video_source_from_url( $url ) {
		$pattern = '/<iframe[^>]*>.*?<\/iframe>/i';

		preg_match_all( $pattern, $url, $match );

		if ( $match[0] ) {
			return array( 'embed-video', $url );
		} elseif (
			strpos( $url, 'youtube.com' ) !== false ||
			strpos( $url, 'youtu.be' ) !== false
		) {
			return array( 'youtube', $url );
		} elseif (
			strpos( $url, 'vimeo.com' ) !== false ||
			strpos( $url, 'player.vimeo.com' ) !== false
		) {
			return array( 'vimeo', $url );
		} else {
			return array( 'external', $url );
		}
	}

	/**
	 * Inserts a new post with specified parameters.
	 *
	 * This function creates a new post using the WordPress function `wp_insert_post`.
	 * It sets various properties of the post such as title, content, author, type,
	 * menu order, and parent post based on the provided arguments.
	 *
	 * @since 1.8.0
	 *
	 * @param string $post_title    Title of the post.
	 * @param string $post_content  Content of the post.
	 * @param int    $author_id     ID of the author creating the post.
	 * @param string $post_type     Type of the post. Default is PostType::SECTION.
	 * @param int    $menu_order    Order of the post in the menu. Default is 0.
	 * @param int|string $post_parent  Parent post ID. Default is an empty string.
	 * @return int|WP_Error         The post ID on success, WP_Error on failure.
	 */
	public static function insert_post( $post_title, $post_content, $author_id, $post_type = PostType::SECTION, $menu_order = 0, $post_parent = '' ) {
		$post_arg = array(
			'post_type'    => $post_type,
			'post_title'   => $post_title,
			'post_content' => $post_content,
			'post_status'  => PostStatus::PUBLISH,
			'post_author'  => $author_id,
			'post_parent'  => $post_parent,
			'menu_order'   => $menu_order,
		);
		return wp_insert_post( $post_arg );
	}

	/**
	 * Updates an existing post with specified parameters.
	 *
	 * This function updates a post identified by $post_id using the WordPress function `wp_update_post`.
	 * It allows updating the post type, menu order, and parent post. If the update fails, it returns false.
	 *
	 * @since 1.8.0
	 *
	 * @param int    $post_id       ID of the post to update.
	 * @param string $post_type     New type of the post. Default is 'topics'.
	 * @param int    $menu_order    New order of the post in the menu. Default is 0.
	 * @param int|string $post_parent  New parent post ID. Default is an empty string.
	 * @return int|false            The updated post ID on success, or false on failure.
	 */
	public static function update_post( $post_id, $post_type = PostType::SECTION, $menu_order = 0, $post_parent = '' ) {
		$post_arg = array(
			'ID'          => $post_id,
			'post_type'   => $post_type,
			'post_parent' => $post_parent,
			'menu_order'  => $menu_order,
		);
		$post_id  = wp_update_post( $post_arg );

		if ( is_wp_error( $post_id ) ) {
			return false;
		}

		return $post_id;
	}

	/**
	 * Migrates course categories from LearnPress to Masteriyo.
	 *
	 * This function retrieves the course categories associated with a given course from LearnPress
	 * and assigns them to the same course in Masteriyo.
	 *
	 * @since 1.8.0
	 *
	 * @param int $course_id The ID of the course for which categories are to be migrated.
	 *                      This should be the Masteriyo course ID which corresponds to the LearnPress course.
	 *
	 * @return void This function does not return anything. It operates by side effect, updating the course taxonomy.
	 */
	public static function migrate_course_categories_from_to_masteriyo( $course_id, $taxonomy = 'course_category', $target_taxonomy = 'course_cat' ) {
		// Static cache: "{$target_taxonomy}:{$name}" → Masteriyo term_id.
		// Persists for the lifetime of the AS job, eliminating repeated term_exists() DB queries.
		static $masteriyo_cat_cache = array();

		$categories = wp_get_post_terms( $course_id, $taxonomy, array( 'fields' => 'ids' ) );

		if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
			$masteriyo_categories = array();

			foreach ( $categories as $cat_id ) {
				$cat = get_term( $cat_id, $taxonomy );

				if ( ! is_wp_error( $cat ) ) {
					$cache_key = $target_taxonomy . ':' . $cat->name;

					if ( isset( $masteriyo_cat_cache[ $cache_key ] ) ) {
						$masteriyo_categories[] = $masteriyo_cat_cache[ $cache_key ];
						continue;
					}

					$masteriyo_cat_id = term_exists( $cat->name, $target_taxonomy );

					if ( 0 === $masteriyo_cat_id || null === $masteriyo_cat_id ) {
						$masteriyo_cat = wp_insert_term( $cat->name, $target_taxonomy );

						if ( ! is_wp_error( $masteriyo_cat ) ) {
							$masteriyo_cat_id = $masteriyo_cat['term_id'];
						}
					} else {
						$masteriyo_cat_id = $masteriyo_cat_id['term_id'];
					}

					if ( $masteriyo_cat_id ) {
						$masteriyo_cat_cache[ $cache_key ] = (int) $masteriyo_cat_id;
						$masteriyo_categories[]            = (int) $masteriyo_cat_id;
					}
				}
			}

			if ( ! empty( $masteriyo_categories ) ) {
				wp_set_object_terms( $course_id, $masteriyo_categories, $target_taxonomy, false );
			}
		}
	}

	/**
	 * Migrate course author from LifterLMS.
	 *
	 * @since 1.16.0 [Free]
	 *
	 * @param int $course_id LifterLMS course ID.
	 */
	public static function migrate_course_author( $course_id ) {
		$post_author = get_post_field( 'post_author', $course_id );

		if ( ! $post_author ) {
			return;
		}

		Helper::update_user_role( $post_author, Roles::INSTRUCTOR );
	}

	/**
	 * Insert a user course enrollment into masteriyo_user_items and optionally write order meta.
	 *
	 * Shared by all migrators. Inserts the enrollment row, assigns the student role,
	 * and writes _order_id / _price to masteriyo_user_itemmeta when provided.
	 * Returns the new user_item_id on success, null on failure (caller decides how to handle).
	 *
	 * @param int         $user_id       WordPress user ID.
	 * @param int         $course_id     Masteriyo course post ID.
	 * @param string      $date_start    Enrollment start date (MySQL datetime, UTC).
	 * @param string|null $date_end      Completion/expiry date, or null if not yet complete.
	 * @param string      $status        UserCourseStatus value (default 'active').
	 * @param int|null    $order_id      Masteriyo order ID to store in itemmeta, or null.
	 * @param float|null  $price         Order price to store in itemmeta, or null.
	 * @param string|null $date_modified Last-modified date override (MySQL datetime, UTC); defaults to $date_start.
	 * @return int|null New user_item_id, or null if the insert failed.
	 */
	public static function enroll_user(
		int $user_id,
		int $course_id,
		string $date_start,
		?string $date_end = null,
		string $status = 'active',
		?int $order_id = null,
		?float $price = null,
		?string $date_modified = null
	): ?int {
		global $wpdb;

		$data    = array(
			'item_id'       => $course_id,
			'user_id'       => $user_id,
			'item_type'     => 'user_course',
			'date_start'    => $date_start,
			'date_modified' => $date_modified ?? $date_start,
			'date_end'      => $date_end,
			'parent_id'     => 0,
			'status'        => $status,
		);
		$formats = array( '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s' );

		$inserted = $wpdb->insert( $wpdb->prefix . 'masteriyo_user_items', $data, $formats );

		if ( false === $inserted ) {
			return null;
		}

		$user_item_id = (int) $wpdb->insert_id;

		static::update_user_role( $user_id, Roles::STUDENT );

		if ( $order_id ) {
			$wpdb->insert(
				$wpdb->prefix . 'masteriyo_user_itemmeta',
				array(
					'user_item_id' => $user_item_id,
					'meta_key'     => '_order_id',
					'meta_value'   => $order_id,
				)
			);
		}

		if ( null !== $price ) {
			$wpdb->insert(
				$wpdb->prefix . 'masteriyo_user_itemmeta',
				array(
					'user_item_id' => $user_item_id,
					'meta_key'     => '_price',
					'meta_value'   => $price,
				)
			);
		}

		return $user_item_id;
	}

	/**
	 * Find or create the course_progress parent activity row in masteriyo_user_activities.
	 *
	 * Shared by all migrators. Returns the existing row ID if found; otherwise inserts
	 * a new 'started' course_progress row and returns its ID.
	 * Fixes TutorLMS bug: uses CourseProgressStatus::STARTED constant and null for
	 * completed_at (correct for an in-progress course) instead of a zero-date string.
	 *
	 * @param int    $user_id    WordPress user ID.
	 * @param int    $course_id  Masteriyo course post ID.
	 * @param string $created_at Row creation timestamp (MySQL datetime, UTC).
	 * @return int Progress activity ID, or 0 if the insert failed.
	 */
	public static function get_or_create_course_progress( int $user_id, int $course_id, string $created_at ): int {
		global $wpdb;

		$table = $wpdb->prefix . 'masteriyo_user_activities';

		$progress_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE user_id = %d AND item_id = %d AND activity_type = 'course_progress' LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$user_id,
				$course_id
			)
		);

		if ( ! $progress_id ) {
			$wpdb->insert(
				$table,
				array(
					'user_id'         => $user_id,
					'item_id'         => $course_id,
					'activity_type'   => 'course_progress',
					'activity_status' => CourseProgressStatus::STARTED,
					'parent_id'       => 0,
					'created_at'      => $created_at,
					'modified_at'     => $created_at,
					'completed_at'    => null,
				),
				array( '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s' )
			);
			$progress_id = (int) $wpdb->insert_id;
		}

		return $progress_id;
	}

	/**
	 * Resolve (or create) a course_difficulty term and set it on the course.
	 *
	 * Shared by all migrators. Looks up the term by slug; inserts it if missing.
	 * Writes _difficulty_id post meta and sets the taxonomy term on the course.
	 *
	 * @param int    $course_id Masteriyo course post ID.
	 * @param string $slug      Difficulty slug (e.g. 'beginner'). Empty string is a no-op.
	 * @return void
	 */
	public static function set_course_difficulty( int $course_id, string $slug ): void {
		if ( ! $slug ) {
			update_post_meta( $course_id, '_difficulty_id', 0 );
			return;
		}

		$term = get_term_by( 'slug', $slug, 'course_difficulty' );

		if ( ! $term || is_wp_error( $term ) ) {
			$inserted = wp_insert_term( ucfirst( $slug ), 'course_difficulty', array( 'slug' => $slug ) );

			if ( is_wp_error( $inserted ) ) {
				update_post_meta( $course_id, '_difficulty_id', 0 );
				return;
			}

			$term_id = $inserted['term_id'];
		} else {
			$term_id = $term->term_id;
		}

		update_post_meta( $course_id, '_difficulty_id', $term_id );
		wp_set_object_terms( $course_id, $term_id, 'course_difficulty', false );
	}

	/**
	 * Create and assign a Masteriyo certificate template to a course.
	 *
	 * Called during the courses migration step for any source LMS that stored a certificate
	 * reference on the course. The source LMS certificate design cannot be converted, so
	 * Certificate Sample 1 is auto-assigned (falls back to the blank template if CDN is
	 * unreachable). Writes `_certificate_id` and `_certificate_enabled = yes` on the course.
	 *
	 * @param int $course_id  Masteriyo mto-course post ID.
	 * @param int $author_id  Post author user ID (used for the certificate post).
	 * @return void
	 */
	public static function assign_certificate_template( int $course_id, int $author_id ): void {
		// Idempotency: skip if this course already has a valid certificate assigned.
		$existing_cert_id = (int) get_post_meta( $course_id, '_certificate_id', true );
		if ( $existing_cert_id && 'mto-certificate' === get_post_type( $existing_cert_id ) ) {
			return;
		}

		$shared_cert_id = static::get_or_create_migration_certificate( $author_id );

		if ( $shared_cert_id ) {
			update_post_meta( $course_id, '_certificate_id', $shared_cert_id );
			update_post_meta( $course_id, '_certificate_enabled', 'yes' );
		} else {
			// Fallback: at minimum mark enabled so the course isn't left broken.
			update_post_meta( $course_id, '_certificate_enabled', 'yes' );
		}
	}

	/**
	 * Return the ID of the shared migration certificate post, creating it on first call.
	 *
	 * One `mto-certificate` post is created for the entire migration run and flagged with
	 * `_masteriyo_migration_certificate = 1`. All migrated courses point to this single
	 * post via `_certificate_id`, avoiding one certificate post per course. The ID is
	 * cached in a static variable so only one DB lookup occurs per request.
	 *
	 * @param int $author_id Post author user ID (used only on first creation).
	 * @return int Certificate post ID, or 0 on failure.
	 */
	public static function get_or_create_migration_certificate( int $author_id ): int {
		static $cert_id = null;

		if ( null !== $cert_id ) {
			return $cert_id;
		}

		// Check if a shared migration certificate was already created in a previous run.
		$existing = get_posts(
			array(
				'post_type'      => 'mto-certificate',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'meta_key'       => '_masteriyo_migration_certificate', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
				'meta_value'     => '1',                                // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'fields'         => 'ids',
			)
		);

		if ( ! empty( $existing ) ) {
			$cert_id = (int) reset( $existing );
			return $cert_id;
		}

		// Build certificate HTML — try CDN Sample 1 first, fall back to blank template.
		$cert_html = '';

		if ( function_exists( 'masteriyo_get_certificate_templates' ) ) {
			$templates = masteriyo_get_certificate_templates();
			if ( is_array( $templates ) && ! empty( $templates ) ) {
				$first_template = reset( $templates );
				if ( ! empty( $first_template['content'] ) ) {
					$cert_html = function_exists( 'masteriyo_process_content_for_import' )
						? masteriyo_process_content_for_import( $first_template['content'] )
						: $first_template['content'];
				}
			}
		}

		if ( '' === $cert_html && function_exists( 'masteriyo_get_blank_certificate_template' ) ) {
			$cert_html = masteriyo_get_blank_certificate_template();
		}

		if ( '' === $cert_html ) {
			$cert_id = 0;
			return $cert_id;
		}

		$new_id = wp_insert_post(
			array(
				'post_type'    => 'mto-certificate',
				'post_status'  => 'publish',
				'post_title'   => __( 'Migration Certificate', 'learning-management-system' ),
				'post_content' => $cert_html,
				'post_author'  => $author_id,
			)
		);

		if ( ! $new_id || is_wp_error( $new_id ) ) {
			masteriyo_get_logger()->warning(
				'Could not create shared migration certificate post.',
				array( 'source' => 'migration-tool' )
			);
			$cert_id = 0;
			return $cert_id;
		}

		// Flag so it can be found and reused across migration runs.
		update_post_meta( $new_id, '_masteriyo_migration_certificate', '1' );

		masteriyo_get_logger()->info(
			sprintf(
				'Shared migration certificate created (ID %d). Original LMS certificate designs cannot be converted — customize this template at Masteriyo > Certificates.',
				$new_id
			),
			array( 'source' => 'migration-tool' )
		);

		$cert_id = $new_id;
		return $cert_id;
	}

	/**
	 * Preserve a TutorLMS field that has no direct Masteriyo equivalent.
	 *
	 * Stored under _migrated_{key} so Pro add-ons or cleanup routines can find it.
	 *
	 * @param int    $post_id Target Masteriyo post ID.
	 * @param string $key     Original field name (leading underscores are stripped).
	 * @param mixed  $value   Value to store. Empty values are not written.
	 * @return void
	 */
	public static function store_unmigrated_meta( int $post_id, string $key, $value ): void {
		if ( '' === $value || null === $value || array() === $value ) {
			return;
		}
		update_post_meta( $post_id, '_migrated_' . ltrim( $key, '_' ), $value );
	}

	/**
	 * Whether the Migration Tool should be auto-activated for a source LMS.
	 *
	 * Acts at most once per source LMS. An admin who deactivates the addon
	 * afterwards is obeyed, because the slug stays in the already-acted list.
	 *
	 * @param string   $slug          Migrator slug, e.g. 'tutor'.
	 * @param string[] $already_acted Migrator slugs already auto-activated on this site.
	 * @return bool
	 */
	public static function should_auto_activate( string $slug, array $already_acted ): bool {
		return ! in_array( $slug, $already_acted, true );
	}

	/**
	 * Migrators whose source plugin this addon watches for.
	 *
	 * Detection order, so a site running two platforms always reports them the
	 * same way rather than in plugin activation order.
	 *
	 * @return string[] Migrator class names.
	 */
	public static function source_migrators(): array {
		return array(
			TutorLMSMigrator::class,
			LearnDashMigrator::class,
			LearnPressMigrator::class,
			LifterLMSMigrator::class,
			MasterStudyMigrator::class,
		);
	}

	/**
	 * Every competing LMS currently active on this site.
	 *
	 * A site can be leaving more than one platform at a time, so this is a list
	 * rather than a first match.
	 *
	 * @return \Masteriyo\Addons\MigrationTool\Contracts\MigratorInterface[]
	 */
	public static function detect_source_lms(): array {
		$detected = array();

		foreach ( self::source_migrators() as $class ) {
			$migrator = new $class();

			if ( $migrator->is_source_plugin_active( $migrator->get_plugin_file() ) ) {
				$detected[] = $migrator;
			}
		}

		return $detected;
	}

	/**
	 * Whether the auto-activation notice should be rendered for this request.
	 *
	 * Shared by the WordPress notice shown outside Masteriyo and the React one
	 * shown inside it, so the two can never disagree about when to appear.
	 *
	 * @return bool
	 */
	public static function should_display_activation_notice(): bool {
		/*
		 * The administrator check is not redundant next to the capability. The
		 * Migration tab in Tools renders behind `isCurrentUserAdmin`, which is the
		 * administrator role, while `manage_masteriyo_settings` also belongs to the
		 * manager role. Without this a manager is invited to a tab that does not
		 * exist for them.
		 */
		if ( ! current_user_can( 'manage_masteriyo_settings' ) || ! masteriyo_is_current_user_admin() ) {
			return false;
		}

		return ! empty( self::notice_source_lms() );
	}

	/**
	 * The source LMS platforms the notice announces.
	 *
	 * Only those this addon switched itself on for. A platform installed after an
	 * admin enabled the addon by hand is present on the site but was never
	 * announced by us, so it is left out.
	 *
	 * @return \Masteriyo\Addons\MigrationTool\Contracts\MigratorInterface[]
	 */
	public static function notice_source_lms(): array {
		$dismissed = (bool) get_user_meta( get_current_user_id(), self::NOTICE_DISMISSED_META, true );

		if ( $dismissed ) {
			return array();
		}

		$announced = (array) get_option( self::ANNOUNCED_OPTION, array() );

		return array_values(
			array_filter(
				self::detect_source_lms(),
				function( $migrator ) use ( $announced ) {
					return in_array( $migrator->get_slug(), $announced, true );
				}
			)
		);
	}

	/**
	 * Names of the announced source LMS platforms, for the notice text.
	 *
	 * @param \Masteriyo\Addons\MigrationTool\Contracts\MigratorInterface[]|null $migrators Defaults to the announced platforms.
	 * @return string Empty when there is nothing to announce.
	 */
	public static function notice_source_labels( ?array $migrators = null ): string {
		return implode(
			', ',
			array_map(
				function( $migrator ) {
					return $migrator->get_label();
				},
				null === $migrators ? self::notice_source_lms() : $migrators
			)
		);
	}

	/**
	 * The notice text.
	 *
	 * More than one platform can be announced at once, so the verb has to agree
	 * with how many were found.
	 *
	 * @return string
	 */
	public static function notice_message(): string {
		$migrators = self::notice_source_lms();

		return sprintf(
			/* translators: %1$s: Names of the detected LMS plugins, e.g. Tutor LMS, LifterLMS. %2$s: the product's name */
			_n(
				'%1$s was detected on this site, so the Migration Tool has been enabled. You can now move your courses, students and orders over to %2$s.',
				'%1$s were detected on this site, so the Migration Tool has been enabled. You can now move your courses, students and orders over to %2$s.',
				count( $migrators ),
				'learning-management-system'
			),
			self::notice_source_labels( $migrators ),
			masteriyo_get_plugin_name()
		);
	}

	/**
	 * Auto-activate the Migration Tool when a source LMS is on the site.
	 *
	 * Hooked on `admin_init` from main.php. See
	 * docs/adr/0001-auto-activate-migration-tool.md.
	 */
	public static function maybe_auto_activate(): void {
		/*
		 * Changing addon state needs a real administrator, which is why this runs
		 * on `admin_init` rather than at plugin load: `current_user_can()` reaches
		 * `wp_get_current_user()`, and pluggable functions do not exist yet while
		 * plugins load. `is_admin()` is no gate on its own — it is true for an
		 * unauthenticated request to /wp-admin/, which WordPress serves through
		 * plugin load before auth_redirect() sends it to the login screen.
		 */
		if ( ! current_user_can( 'manage_masteriyo_settings' ) ) {
			return;
		}

		$already_acted = (array) get_option( self::AUTO_ACTIVATED_OPTION, array() );
		$new_slugs     = array();

		foreach ( self::detect_source_lms() as $migrator ) {
			if ( self::should_auto_activate( $migrator->get_slug(), $already_acted ) ) {
				$new_slugs[] = $migrator->get_slug();
			}
		}

		if ( empty( $new_slugs ) ) {
			return;
		}

		$addons = new Addons();

		// Read before the write below, because that is what decides whether this
		// detection is the reason the addon is on.
		$was_already_active = $addons->is_active( MASTERIYO_MIGRATION_TOOL_SLUG );

		/*
		 * Every platform found is recorded, not only the one that triggered this,
		 * so a second platform that was already on the site cannot switch the addon
		 * back on later against an admin who turned it off. A platform installed
		 * after this point is new, and is meant to trigger again.
		 *
		 * Recorded before activating, so a failure part-way through cannot make this
		 * run again on every admin page load.
		 */
		update_option( self::AUTO_ACTIVATED_OPTION, array_merge( $already_acted, $new_slugs ) );

		if ( $was_already_active ) {
			return;
		}

		update_option(
			self::ANNOUNCED_OPTION,
			array_merge( (array) get_option( self::ANNOUNCED_OPTION, array() ), $new_slugs )
		);

		$addons->set_active( MASTERIYO_MIGRATION_TOOL_SLUG );
	}
}
