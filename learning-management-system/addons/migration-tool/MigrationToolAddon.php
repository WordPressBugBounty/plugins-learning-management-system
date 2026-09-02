<?php
/**
 * Migration Tool Addon for Masteriyo.
 *
 * @since 1.8.0
 */

namespace Masteriyo\Addons\MigrationTool;

/**
 * Migration Tool Addon main class for Masteriyo.
 *
 * @since 1.8.0
 */
class MigrationToolAddon {

	/**
	 * Initialize.
	 *
	 * @since 1.8.0
	 */
	public function init() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 *
	 * @since 1.8.0
	 */
	public function init_hooks() {
		add_filter( 'masteriyo_rest_api_get_rest_namespaces', array( $this, 'register_rest_namespaces' ) );

		/**
		 * Fires once the migration tool addon has initialised.
		 *
		 * The migration steps whose destination is a pro addon — assignment,
		 * course-bundle, zoom and gradebook — ship only with pro and register here,
		 * rather than being named from this file. Reaching this point already means
		 * the addon is active, because `main.php` returns before it otherwise.
		 *
		 * @param \Masteriyo\Addons\MigrationTool\MigrationToolAddon $addon The addon instance.
		 */
		do_action( 'masteriyo_migration_tool_addon_initialized', $this );
	}

	/**
	 * Register REST API namespaces for the migration tool.
	 *
	 * @since 1.8.0
	 *
	 * @param array $namespaces Rest namespaces.
	 *
	 * @return array Modified REST namespaces including migration tool endpoints.
	 */
	public function register_rest_namespaces( $namespaces ) {
		$namespaces['masteriyo/v1']['migration-tool']        = 'migration-tool.rest';
		$namespaces['masteriyo/v1']['migration-tool-notice'] = 'migration-tool.notice.rest';
		return $namespaces;
	}
}
