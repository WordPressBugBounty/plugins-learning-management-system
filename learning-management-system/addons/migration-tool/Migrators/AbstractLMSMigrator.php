<?php
/**
 * Abstract LMS migrator.
 *
 * @since x.x.x
 * @package Masteriyo\Addons\MigrationTool\Migrators
 */

namespace Masteriyo\Addons\MigrationTool\Migrators;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Addons\MigrationTool\Contracts\MigratorInterface;

/**
 * Class AbstractLMSMigrator.
 *
 * Base class for all LMS migrators. Concrete migrators implement get_lms_class()
 * and the three identity methods; all delegation logic lives here.
 *
 * @since x.x.x
 */
abstract class AbstractLMSMigrator implements MigratorInterface {

	/**
	 * Return the fully-qualified class name of the LMS static helper.
	 *
	 * @since x.x.x
	 * @return class-string
	 */
	abstract protected static function get_lms_class(): string;

	/**
	 * Whether the given step's source data may be migrated.
	 *
	 * Default: every step is available. Migrators with steps backed by a separate
	 * source Pro plugin override this to gate those steps on the Pro plugin being active.
	 *
	 * @since x.x.x
	 * @param string $step Step name.
	 * @return bool
	 */
	public function is_step_available( string $step ): bool {
		return true;
	}

	/**
	 * Whether a source plugin is currently active.
	 *
	 * Loads wp-admin/includes/plugin.php on demand so the check is reliable inside
	 * REST requests and Action Scheduler jobs where it may not yet be loaded.
	 *
	 * @since x.x.x
	 * @param string $plugin_file Plugin basename, e.g. 'tutor-pro/tutor-pro.php'.
	 * @return bool
	 */
	protected function is_source_plugin_active( string $plugin_file ): bool {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		return is_plugin_active( $plugin_file );
	}

	/**
	 * Count total source items for a step. Fast COUNT query — no records loaded.
	 *
	 * Returns 0 for steps whose source Pro plugin is inactive so the pipeline skips
	 * them cleanly instead of attempting (and silently failing) to migrate orphaned data.
	 *
	 * @since x.x.x
	 * @param string $step Step name.
	 * @return int
	 */
	public function count_source_items( string $step ): int {
		if ( ! $this->is_step_available( $step ) ) {
			return 0;
		}
		return ( static::get_lms_class() )::count_source_items( $step );
	}

	/**
	 * Return paginated source IDs. Must use LIMIT/OFFSET — never load all at once.
	 *
	 * @since x.x.x
	 * @param string $step    Step name.
	 * @param int    $limit   Batch size.
	 * @param int    $cursor  Last processed ID (0 = first batch).
	 * @param int[]  $exclude IDs to exclude (already-failed items for self-cleaning steps).
	 * @return int[]
	 */
	public function get_source_ids( string $step, int $limit, int $cursor, array $exclude = array() ): array {
		if ( ! $this->is_step_available( $step ) ) {
			return array();
		}
		return ( static::get_lms_class() )::get_source_ids( $step, $limit, $cursor, $exclude );
	}

	/**
	 * Ensure an addon's service provider is registered in the container for this process.
	 *
	 * Addons are only loaded when Pro is active and the addon is enabled. During migration
	 * the addon may be inactive, so this registers its DI bindings on-demand without
	 * permanently activating it or writing to the database.
	 *
	 * Safe to call repeatedly — no-op if the service is already registered.
	 *
	 * @since x.x.x
	 * @param string $service_key    Container key (e.g. 'wishlist-item').
	 * @param string $provider_class Fully-qualified service provider class name.
	 * @throws \Exception If the provider class file is not installed.
	 */
	protected static function ensure_service_provider( string $service_key, string $provider_class ): void {
		global $masteriyo;
		if ( $masteriyo->has( $service_key ) ) {
			return;
		}
		if ( ! class_exists( $provider_class ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new \Exception( sprintf( 'Addon class %s not found — install the required addon to migrate this data.', $provider_class ) );
		}
		$masteriyo->addServiceProvider( new $provider_class() );
	}

	/**
	 * Migrate exactly one item. Called inside START TRANSACTION / COMMIT.
	 *
	 * @since x.x.x
	 * @param string $step    Step name.
	 * @param int    $item_id Source item ID.
	 * @throws \Exception Triggers ROLLBACK; item added to failed list.
	 */
	public function migrate_item( string $step, int $item_id ): void {
		( static::get_lms_class() )::migrate_item( $step, $item_id );
	}

	/**
	 * Called once after a step fully completes.
	 *
	 * @since x.x.x
	 * @param string $step Step name.
	 */
	public function finalize_step( string $step ): void {
		( static::get_lms_class() )::finalize_step( $step );
	}

	/**
	 * Return Masteriyo addon slugs to activate when this step has data to migrate.
	 * Override in concrete migrators for LMS plugins that have addon-specific steps.
	 *
	 * @since x.x.x
	 * @param string $step Step name.
	 * @return string[]
	 */
	public function get_addons_to_activate( string $step ): array {
		return array();
	}
}
