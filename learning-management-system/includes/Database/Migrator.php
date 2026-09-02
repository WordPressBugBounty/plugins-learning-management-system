<?php
/**
 * Class to run the migration.
 *
 * @since 1.3.4
 */

namespace Masteriyo\Database;

defined( 'ABSPATH' ) || exit;


class Migrator {
	/**
	 * The name of the database connection to use.
	 *
	 * @since 1.3.4
	 *
	 * @var wpdb
	 */
	protected $connection;

	/**
	 * Migration table name.
	 *
	 * @since 1.3.4
	 *
	 * @var string
	 */
	protected $table_name = 'masteriyo_migrations';

	/**
	 * Constructor.
	 *
	 * @since 1.3.4
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Initialize.
	 *
	 * @since 1.3.4
	 */
	public function init() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		global $wpdb;

		$this->connection = $wpdb;

		$this->setup();
	}

	/**
	 * Get the connection.
	 *
	 * @since 1.3.4
	 *
	 * @return wpdb
	 */
	public function get_connection() {
		return $this->connection;
	}

	/**
	 * Get table name.
	 *
	 * @since 1.3.4
	 *
	 * @return string
	 */
	public function get_table_name() {
		return $this->connection->prefix . $this->table_name;
	}

	/**
	 * Get database charset collate.
	 *
	 * @since 1.3.4
	 *
	 * @return string
	 */
	public function get_charset_collate() {
		return $this->connection->has_cap( 'collation' ) ? $this->connection->get_charset_collate() : '';
	}

	/**
	 * Set up the table needed for storing the migrations.
	 *
	 * @since 1.3.4
	 *
	 * @return bool
	 */
	public function setup() {
		$table = $this->get_table_name();

		$search_table_in_db = $this->connection->get_var(
			$this->connection->prepare(
				'SHOW TABLES LIKE "%s"',
				$table
			)
		);

		if ( $search_table_in_db === $table ) {
			return false;
		}

		$collation = $this->get_charset_collate();

		// Create migrations table
		$sql = "CREATE TABLE {$table} (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			name VARCHAR(255) NOT NULL,
			batch BIGINT(20) UNSIGNED NOT NUll,
			ran_at DATETIME DEFAULT '0000-00-00 00:00:00' NOT NULL,
			PRIMARY KEY  (id)
		) {$collation};";

		dbDelta( $sql );

		return true;
	}

	/**
	 * Get all the migration files
	 *
	 * @since 1.3.4
	 *
	 * @param array       $exclude   Filenames without extension to exclude
	 * @param string|null $migration Single migration class name to only perform the migration for.
	 *
	 * @return array
	 */
	protected function get_migrations( $exclude = array(), $migration = null ) {
		/**
		 * Filters the list of migration paths.
		 *
		 * @since 1.3.4
		 *
		 * @param string[] $paths Migration paths.
		 */
		$paths = apply_filters(
			'masteriyo_migrations_paths',
			array(
				$this->get_plugin_base_path( '/database/migrations' ),
			)
		);

		// Get the migrations from the paths.
		$migrations = array_reduce(
			$paths,
			function( $result, $path ) {
				$path_migrations = glob( trailingslashit( $path ) . '*.php' );
				return array_merge( $result, $path_migrations );
			},
			array()
		);

		// Filter the migrations.
		$migrations = array_filter(
			array_map(
				function( $filename ) use ( $exclude, $migration ) {
					$name = basename( $filename, '.php' );

					// Exclude the migration if it is in the exclude array.
					if ( in_array( $name, $exclude, true ) ) {
						return false;
					}

					if ( $migration && $this->get_class_name( $name ) !== $migration ) {
						return false;
					}

					return $filename;
				},
				$migrations
			)
		);

		// Convert the migrations to dictionary.
		$migrations = array_reduce(
			$migrations,
			function( $result, $filename ) {
				$result[ $filename ] = basename( $filename, '.php' );
				return $result;
			},
			array()
		);

		return $migrations;
	}

	/**
	 * Get all the migrations to be run
	 *
	 * @since 1.3.4
	 *
	 * @param string|null $migration
	 * @return array
	 */
	protected function get_migrations_to_run( $migration = null ) {
		$table          = $this->get_table_name();
		$ran_migrations = $this->connection->get_col( "SELECT name FROM $table" );

		$migrations = $this->get_migrations( $ran_migrations, $migration );

		return $migrations;
	}

	/**
	 * Get next migration batch.
	 *
	 * @since 1.3.4
	 *
	 * @return int
	 */
	protected function get_current_batch() {
		$table = $this->get_table_name();
		$batch = intval( $this->connection->get_var( "SELECT MAX(batch) FROM $table" ) );
		$batch = max( 0, $batch );

		return $batch;
	}

	/**
	 * Migrate the migrations.
	 *
	 * @since 1.3.4
	 *
	 * @param string|null $migration Migration to run.
	 *
	 * @return string[]
	 */
	public function migrate( $migration = null ) {
		global $wpdb;

		$ran_migrations = array();
		$table          = $this->get_table_name();
		$migrations     = $this->get_migrations_to_run( $migration );

		if ( empty( $migrations ) ) {
			return $ran_migrations;
		}

		// Non-blocking cross-request lock: a long resumable migration otherwise runs
		// concurrently in every simultaneous request, each redoing the same table work.
		// Auto-released on disconnect, so a fatal mid-run can never wedge migrations.
		// Prefixed per site — server-level locks are shared across a multisite network.
		$lock_name = 'masteriyo_migrate_' . $wpdb->prefix;

		if ( ! $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK( %s, 0 )', $lock_name ) ) ) {
			/*
			 * '0' means another request holds the lock and is doing the work — yield.
			 * NULL with a DB error means this host cannot GET_LOCK at all (pooler,
			 * restricted user); yielding there would silently disable every present
			 * and future migration, so run unlocked instead — the pre-lock behavior.
			 */
			if ( empty( $wpdb->last_error ) ) {
				return $ran_migrations;
			}

			if ( function_exists( 'masteriyo_get_logger' ) ) {
				masteriyo_get_logger()->warning(
					'GET_LOCK unavailable; running migrations unlocked. DB error: ' . $wpdb->last_error,
					array( 'source' => 'migrator' )
				);
			}
		}

		$current_batch = $this->get_current_batch();

		foreach ( $migrations as $file => $name ) {
			/*
			 * A falsy but non-false return (void/null) still counts as ran — an explicit
			 * `false` from up() means "not finished, retry next request", and later
			 * migrations may depend on this one's schema, so the queue stops here rather
			 * than run them out of order. `false` must therefore only ever mean forward
			 * progress is still possible; a migration that discovers it can NEVER finish
			 * has to park itself (record its own incomplete state and return null) so it
			 * stops blocking the queue — a runner-side retry cap cannot tell a big table
			 * legitimately yielding from a permanently failing one.
			 */
			if ( false === $this->run_migration( $file, $name ) ) {
				break;
			}

			$ran_migrations[] = $name;

			$this->connection->insert(
				$table,
				array(
					'name'   => $name,
					'batch'  => $current_batch + 1,
					'ran_at' => gmdate( 'Y-m-d H:i:s' ),
				)
			);
		}

		$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK( %s )', $lock_name ) );

		return $ran_migrations;
	}

	/**
	 * Migration to rollback to by step.
	 *
	 * @since 1.3.4
	 *
	 * @param integer $step Step to rollback.
	 * @param string|null $migration Migration to run.
	 *
	 * @return string[]
	 */
	public function rollback( $step = 1, $migration = null ) {
		$ran_migrations = array();
		$table          = $this->get_table_name();
		$current_batch  = $this->get_current_batch();

		$non_batch_migrations = $this->connection->get_col(
			$this->connection->prepare(
				"SELECT name FROM $table WHERE batch <= %d",
				$current_batch - absint( $step )
			)
		);

		$migrations = $this->get_migrations( $non_batch_migrations, $migration, true );

		foreach ( $migrations as $file => $name ) {
			if ( $this->run_migration( $file, $name, true ) ) {
				$ran_migrations[] = $name;
			}

			$this->connection->delete(
				$table,
				array(
					'name' => $name,
				),
				array(
					'%s',
				)
			);
		}

		return $ran_migrations;
	}

	/**
	 * Reset the migrations.
	 *
	 * @since 1.3.4
	 *
	 * @return string[]
	 */
	public function reset() {
		$ran_migrations = array();
		$migrations     = $this->get_migrations();
		$table          = $this->get_table_name();

		foreach ( $migrations as $file => $name ) {
			if ( $this->run_migration( $file, $name, true ) ) {
				$ran_migrations[] = $name;
			}

			$this->connection->delete(
				$table,
				array(
					'name' => $name,
				),
				array(
					'%s',
				)
			);
		}

		return $ran_migrations;
	}

	/**
	 * Run individual migration.
	 *
	 * @since 1.3.4
	 *
	 * @param string $file File path.
	 * @param string $name Migration name to run.
	 * @param boolean $rollback Whether to rollback or not.
	 *
	 * @return boolean|string False if not run, or up()/down() explicitly returned false to
	 *                        signal unfinished work; migration name's file path if it ran to
	 *                        completion.
	 */
	protected function run_migration( $file, $name, $rollback = false ) {
		require_once $file;

		$class_name    = masteriyo_snake_to_pascal( $this->get_class_name( $name ) );
		$fq_class_name = $this->get_class_with_namespace( $class_name );

		if ( false === $fq_class_name ) {
			return false;
		}

		$class     = $fq_class_name;
		$migration = new $class();
		$method    = $rollback ? 'down' : 'up';

		if ( ! method_exists( $migration, $method ) ) {
			return false;
		}

		$result = $migration->{$method}();

		return false === $result ? false : $file;
	}

	/**
	 * Get class with namespace.
	 *
	 * @since 1.3.4
	 *
	 * @param string $class_name
	 * @return string|boolean
	 */
	protected function get_class_with_namespace( $class_name ) {
		$all_classes = get_declared_classes();

		foreach ( $all_classes as $class ) {
			if ( substr( $class, - strlen( $class_name ) ) === $class_name ) {
				return $class;
			}
		}

		return false;
	}

	/**
	 * Get the class name in camel case.
	 *
	 * @since 1.3.4
	 *
	 * @param string $class Class class
	 * @return void
	 */
	protected function get_class_name( $class ) {
		return substr( $class, 18 );
	}

	/**
	 * Get the plugin base path.
	 *
	 * @since 1.3.4
	 *
	 * @param string $path Path to append.
	 * @return string
	 */
	protected function get_plugin_base_path( $path ) {
		$base_path = __FILE__;

		while ( basename( $base_path ) !== 'includes' ) {
			$base_path = dirname( $base_path );
		}

		return dirname( $base_path ) . $path;
	}
}
