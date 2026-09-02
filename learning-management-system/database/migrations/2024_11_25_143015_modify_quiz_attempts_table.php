<?php

defined( 'ABSPATH' ) || exit;

/**
 * Modify quiz attempts table.
 *
 * Modified data type for answers column.
 *
 * @since 1.14.2 [Free]
 */

use Masteriyo\Database\Migration;

/**
 * Modify quiz attempts table.
 */
class ModifyQuizAttemptsTable extends Migration {
	/**
	 * Run the migration.
	 *
	 * @since 1.14.2 [Free]
	 */
	public function up() {
		$table = "{$this->prefix}masteriyo_quiz_attempts";

		$check_table_sql = "SHOW TABLES LIKE '{$table}'";
		$result          = $this->connection->query( $check_table_sql );

		if ( $result ) {
			$sql = "ALTER TABLE {$table} MODIFY COLUMN answers LONGTEXT;";
			$this->connection->query( $sql );
		}
	}

	/**
	 * Reverse the migrations.
	 *
	 * @since 1.14.2 [Free]
	 */
	public function down() {
		$table = "{$this->prefix}masteriyo_quiz_attempts";

		$check_table_sql = "SHOW TABLES LIKE '{$table}'";
		$result          = $this->connection->query( $check_table_sql );

		if ( $result ) {
			$sql = "ALTER TABLE {$table} MODIFY COLUMN answers TEXT;";
			$this->connection->query( $sql );
		}
	}
}
