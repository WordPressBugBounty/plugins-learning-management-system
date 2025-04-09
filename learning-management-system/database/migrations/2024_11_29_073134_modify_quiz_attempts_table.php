<?php

defined( 'ABSPATH' ) || exit;

/**
 * Modify quiz attempts table.
 *
 * Modified data type for answers column.
 *
 * @since 1.14.2
 */

use Masteriyo\Database\Migration;

/**
 * Modify quiz attempts table.
 */
class ModifyQuizAttemptsTable extends Migration {
	/**
	 * Run the migration.
	 *
	 * @since 1.14.2
	 */
	public function up() {
		$sql = "ALTER TABLE {$this->prefix}masteriyo_quiz_attempts
		MODIFY COLUMN answers LONGTEXT;";

		$this->connection->query( $sql );
	}

	/**
	 * Reverse the migrations.
	 *
	 * @since 1.14.2
	 */
	public function down() {
		$sql = "ALTER TABLE {$this->prefix}masteriyo_quiz_attempts
		MODIFY COLUMN answers TEXT;";

		$this->connection->query( $sql );
	}
}
