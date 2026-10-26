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
	 * Option holding a fingerprint of the migration files last found fully applied.
	 */
	const CHECKED_OPTION = 'masteriyo_migrations_checked';

	/**
	 * Seconds an explicit rollback or reset waits for a boot-time migrate() to finish.
	 */
	const LOCK_TIMEOUT = 10;

	/**
	 * Whether setup() has confirmed the history table during this request.
	 *
	 * @var bool
	 */
	protected $table_ready = false;

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

		// The table check waits until a method is about to use the table (#1111).
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
		if ( $this->table_ready ) {
			return false;
		}

		$this->table_ready = true;
		$table             = $this->get_table_name();

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
	 * @param string|null $migration  Single migration class name to limit the run to.
	 * @param array|null  $migrations Already discovered migrations (file => name), to avoid a second scan.
	 * @return array
	 */
	protected function get_migrations_to_run( $migration = null, $migrations = null ) {
		$table          = $this->get_table_name();
		$ran_migrations = $this->connection->get_col( "SELECT name FROM $table" );

		if ( null === $migrations ) {
			return $this->get_migrations( $ran_migrations, $migration );
		}

		return array_diff( $migrations, $ran_migrations );
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
		$ran_migrations = array();
		$table          = $this->get_table_name();
		$discovered     = $this->get_migrations( array(), $migration );
		$fingerprint    = md5( implode( "\n", $discovered ) );

		// The same files were found fully applied before, so a plain boot costs no query (#1111).
		if ( null === $migration && get_option( self::CHECKED_OPTION ) === $fingerprint ) {
			return $ran_migrations;
		}

		/*
		 * Everything past here reads the history and may record the fingerprint, so it
		 * all runs under the lock. A rollback deleting its rows would otherwise be seen
		 * half done and remembered as "nothing pending", which skips the rolled-back
		 * migration on every later boot.
		 */
		if ( ! $this->acquire_lock() ) {
			return $ran_migrations;
		}

		$this->setup();

		$migrations = $this->get_migrations_to_run( $migration, $discovered );

		if ( empty( $migrations ) ) {
			$this->remember_checked( $fingerprint, $migration );
			$this->release_lock();

			return $ran_migrations;
		}

		$current_batch = $this->get_current_batch();
		$completed     = true;

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
				$completed = false;
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

		if ( $completed ) {
			$this->remember_checked( $fingerprint, $migration );
		}

		$this->release_lock();

		return $ran_migrations;
	}

	/**
	 * Name of the cross-request migration lock.
	 *
	 * Prefixed per site, because server-level locks are shared across a multisite network.
	 *
	 * @return string
	 */
	protected function get_lock_name() {
		global $wpdb;

		return 'masteriyo_migrate_' . $wpdb->prefix;
	}

	/**
	 * Take the migration lock.
	 *
	 * A long resumable migration otherwise runs concurrently in every simultaneous
	 * request, each redoing the same table work. The lock is auto-released on
	 * disconnect, so a fatal mid-run can never wedge migrations.
	 *
	 * @param int $timeout Seconds to wait for the lock. Boot-time runs do not wait.
	 *
	 * @return boolean False only when another process holds it.
	 */
	protected function acquire_lock( $timeout = 0 ) {
		global $wpdb;

		if ( $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK( %s, %d )', $this->get_lock_name(), $timeout ) ) ) {
			return true;
		}

		/*
		 * '0' means another request holds the lock and is doing the work — yield.
		 * NULL with a DB error means this host cannot GET_LOCK at all (pooler,
		 * restricted user); yielding there would silently disable every present
		 * and future migration, so run unlocked instead — the pre-lock behavior.
		 */
		if ( empty( $wpdb->last_error ) ) {
			return false;
		}

		if ( function_exists( 'masteriyo_get_logger' ) ) {
			masteriyo_get_logger()->warning(
				'GET_LOCK unavailable; running migrations unlocked. DB error: ' . $wpdb->last_error,
				array( 'source' => 'migrator' )
			);
		}

		return true;
	}

	/**
	 * Record that an explicit command did nothing because migrations were running.
	 *
	 * Its return value is indistinguishable from "there was nothing to do", so the
	 * reason has to be written down somewhere.
	 *
	 * @param string $command The command that gave up.
	 */
	protected function warn_lock_blocked( $command ) {
		if ( function_exists( 'masteriyo_get_logger' ) ) {
			masteriyo_get_logger()->warning(
				sprintf( 'Migration %s skipped: another process held the migration lock.', $command ),
				array( 'source' => 'migrator' )
			);
		}
	}

	/**
	 * Release the migration lock.
	 */
	protected function release_lock() {
		global $wpdb;

		$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK( %s )', $this->get_lock_name() ) );
	}

	/**
	 * Record that every discovered migration has run, unless the run was narrowed to one name.
	 *
	 * @param string      $fingerprint Fingerprint of the discovered migration names.
	 * @param string|null $migration   The single migration the run was limited to, if any.
	 */
	protected function remember_checked( $fingerprint, $migration ) {
		if ( null === $migration ) {
			update_option( self::CHECKED_OPTION, $fingerprint );
		}
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

		// An explicit rollback waits for a boot-time migrate() rather than interleaving with it.
		if ( ! $this->acquire_lock( self::LOCK_TIMEOUT ) ) {
			$this->warn_lock_blocked( 'rollback' );

			return $ran_migrations;
		}

		$this->setup();

		$table         = $this->get_table_name();
		$current_batch = $this->get_current_batch();

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

		// Last, so a migrate() that ran during the rollback cannot leave its fingerprint behind.
		delete_option( self::CHECKED_OPTION );
		$this->release_lock();

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

		if ( ! $this->acquire_lock( self::LOCK_TIMEOUT ) ) {
			$this->warn_lock_blocked( 'reset' );

			return $ran_migrations;
		}

		$this->setup();

		$migrations = $this->get_migrations();
		$table      = $this->get_table_name();

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

		delete_option( self::CHECKED_OPTION );
		$this->release_lock();

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
		// Migration files declare a global class, so a plain match is the answer (#1387).
		if ( class_exists( $class_name, false ) && is_subclass_of( $class_name, Migration::class ) ) {
			return $class_name;
		}

		// A namespaced migration: match on its short name, never on a name that merely ends alike.
		$suffix = '\\' . $class_name;

		foreach ( get_declared_classes() as $class ) {
			if ( substr( $class, - strlen( $suffix ) ) === $suffix && is_subclass_of( $class, Migration::class ) ) {
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
