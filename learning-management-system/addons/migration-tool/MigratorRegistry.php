<?php
/**
 * Migrator registry.
 *
 * @package Masteriyo\Addons\MigrationTool
 */

namespace Masteriyo\Addons\MigrationTool;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Addons\MigrationTool\Contracts\MigratorInterface;

/**
 * Class MigratorRegistry.
 *
 * Holds all registered LMS migrators. The controller resolves the requested
 * migrator by slug and delegates — with no knowledge of any specific LMS.
 *
 * Adding a new LMS requires only:
 *   1. A new class in Migrators/ implementing MigratorInterface.
 *   2. One `register()` call, typically via the `masteriyo_migration_tool_register` filter.
 */
class MigratorRegistry {

	/**
	 * @var MigratorInterface[]
	 */
	private $migrators = array();

	/**
	 * Registers a migrator instance.
	 *
	 * @param MigratorInterface $migrator
	 * @return static Fluent — supports chaining in filter callbacks.
	 */
	public function register( MigratorInterface $migrator ): self {
		$this->migrators[ $migrator->get_slug() ] = $migrator;
		return $this;
	}

	/**
	 * Returns the migrator for the given slug, or null if not registered.
	 *
	 * @param string $slug
	 * @return MigratorInterface|null
	 */
	public function get( string $slug ): ?MigratorInterface {
		return $this->migrators[ $slug ] ?? null;
	}

	/**
	 * Returns all registered migrators keyed by slug.
	 *
	 * @return MigratorInterface[]
	 */
	public function all(): array {
		return $this->migrators;
	}

	/**
	 * Returns true if a migrator is registered for the given slug.
	 *
	 * @param string $slug
	 * @return bool
	 */
	public function has( string $slug ): bool {
		return isset( $this->migrators[ $slug ] );
	}
}
