<?php
/**
 * Migration Tool service provider.
 *
 * @since 1.8.0
 */

namespace Masteriyo\Addons\MigrationTool\Providers;

defined( 'ABSPATH' ) || exit;

use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Masteriyo\Addons\MigrationTool\Controllers\LMSMigrationController;
use Masteriyo\Addons\MigrationTool\Controllers\MigrationNoticeController;
use Masteriyo\Addons\MigrationTool\Helper;
use Masteriyo\Addons\MigrationTool\Jobs\MigrationProcessJob;
use Masteriyo\Addons\MigrationTool\MigrationSession;
use Masteriyo\Addons\MigrationTool\MigrationToolAddon;
use Masteriyo\Addons\MigrationTool\MigratorRegistry;

/**
 * Migration Tool service provider.
 *
 * @since 1.8.0
 */
class MigrationToolServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface {

	/**
	 * Check if the service provider provides a specific service.
	 *
	 * @since 2.1.0
	 *
	 * @param string $id Service identifier.
	 * @return bool
	 */
	public function provides( string $id ): bool {
		return in_array(
			$id,
			array(
				'migration-tool.registry',
				'migration-tool.rest',
				'migration-tool.notice.rest',
				'addons.migration-tool',
				'migration.process_job',
				'migration.session',
			),
			true
		);
	}

	/**
	 * Registers services and dependencies for the Migration Tool.
	 *
	 * @since 1.8.0
	 */
	public function register(): void {
		$this->getContainer()->addShared(
			'migration-tool.registry',
			function () {
				$registry = new MigratorRegistry();

				/*
				 * One inventory, shared with detection. Listing the built-in
				 * migrators here as well would let the two drift, and a migrator
				 * that can migrate but is never detected auto-activates nothing.
				 */
				foreach ( Helper::source_migrators() as $migrator_class ) {
					$registry->register( new $migrator_class() );
				}

				/**
				 * Filter to register additional LMS migrators.
				 *
				 * Adding a new LMS requires only a new class implementing MigratorInterface
				 * and one line here (or hooked into this filter from an addon).
				 *
				 * @param MigratorRegistry $registry
				 */
				return apply_filters( 'masteriyo_migration_tool_register', $registry );
			}
		);

		$this->getContainer()->add( 'migration-tool.rest', LMSMigrationController::class )
			->addArgument( 'permission' )
			->addArgument( 'migration-tool.registry' );

		$this->getContainer()->add( 'migration-tool.notice.rest', MigrationNoticeController::class );

		$this->getContainer()->addShared( 'addons.migration-tool', MigrationToolAddon::class );

		$this->getContainer()->addShared( 'migration.process_job', MigrationProcessJob::class );
		$this->getContainer()->addShared( 'migration.session', MigrationSession::class );
	}

	/**
	 * Boot the service provider.
	 *
	 * Called during Container::addServiceProvider() — before masteriyo() is available.
	 * We instantiate MigrationProcessJob directly so its add_action() call lands
	 * immediately; handle() itself resolves the container lazily at runtime.
	 */
	public function boot(): void {
		( new MigrationProcessJob() )->register();

		add_filter(
			'action_scheduler_queue_runner_time_limit',
			static function ( int $time_limit ): int {
				return max( $time_limit, 60 );
			}
		);
	}
}
