<?php
/**
 * Delete Section ability.
 *
 * @package Masteriyo\Abilities\Domains\Section
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Section;

defined( 'ABSPATH' ) || exit;

use Masteriyo\Abilities\Support\RestProxyAbility;

/**
 * Ability: move a section to trash or permanently delete it.
 *
 * Without `force=true` the section is trashed and can be restored.
 * With `force=true` the section and all its child lessons and quizzes are
 * permanently deleted.
 *
 * @since x.x.x
 */
class DeleteSectionAbility extends RestProxyAbility {

	/**
	 * {@inheritdoc}
	 */
	protected function controller_service(): string {
		return 'section.rest';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function verb(): string {
		return 'delete';
	}

	/**
	 * {@inheritdoc}
	 */
	protected function rest_base(): string {
		return 'sections';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_name(): string {
		return 'masteriyo/section-delete';
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return __( 'Delete Section', 'learning-management-system' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_description(): string {
		return __( 'Move a section to trash (use force=true to permanently delete).', 'learning-management-system' );
	}
}
