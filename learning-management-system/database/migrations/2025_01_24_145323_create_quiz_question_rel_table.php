<?php

defined( 'ABSPATH' ) || exit;

/**
 * Migration class template used by the wp cli to create migration classes.
 *
 * @since 1.17.0
 */

use Masteriyo\Database\Migration;

/**
 * Quiz-Question Relationship Table.
 *
 * @since 1.17.0
 */
class CreateQuizQuestionRelTable extends Migration {
	/**
	 * Run the migration.
	 *
	 * @since 1.17.0
	 */
	public function up() {
		$sql = "CREATE TABLE {$this->prefix}masteriyo_quiz_question_rel (
			id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			quiz_id BIGINT UNSIGNED NOT NULL,
			question_id BIGINT UNSIGNED NOT NULL,
			menu_order INT NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
			UNIQUE KEY quiz_question_unique (quiz_id, question_id),
			INDEX quiz_index (quiz_id),
			INDEX question_index (question_id)
	) $this->charset_collate;";

		dbDelta( $sql );
	}

	/**
	 * Reverse the migrations.
	 *
	 * @since 1.17.0
	 */
	public function down() {
		$this->connection->query( "DROP TABLE IF EXISTS {$this->prefix}masteriyo_quiz_question_rel;" );
	}
}
