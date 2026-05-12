<?php
/**
 * Update Quiz Settings ability.
 *
 * @package Masteriyo\Abilities\Domains\Setting
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Setting;

defined( 'ABSPATH' ) || exit;

/**
 * Ability: write Masteriyo quiz settings (display, passing grade, attempts, etc.).
 *
 * @since x.x.x
 */
class UpdateQuizSettingsAbility extends AbstractScopedSettingsAbility {

	/** {@inheritdoc} */
	protected function settings_section(): string {
		return 'quiz';
	}

	/** {@inheritdoc} */
	public function get_name(): string {
		return 'masteriyo/settings-update-quiz';
	}

	/** {@inheritdoc} */
	public function get_label(): string {
		return __( 'Update Quiz Settings', 'learning-management-system' );
	}

	/** {@inheritdoc} */
	public function get_description(): string {
		return __( 'Write Masteriyo quiz settings (questions per page, passing grade, attempt limits, review visibility). Accepts a partial quiz settings object; only provided keys are updated.', 'learning-management-system' );
	}
}
