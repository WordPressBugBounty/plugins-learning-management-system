<?php
/**
 * Update Quiz Settings ability.
 *
 * @package Masteriyo\Abilities\Domains\Setting
 */

namespace Masteriyo\Abilities\Domains\Setting;

defined( 'ABSPATH' ) || exit;

/**
 * Ability: write Masteriyo quiz settings (display, passing grade, attempts, etc.).
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
		return sprintf(
			/* translators: %s: the product's name */
			__( 'Write %s quiz settings (questions per page, passing grade, attempt limits, review visibility). Accepts a partial quiz settings object; only provided keys are updated.', 'learning-management-system' ),
			masteriyo_get_plugin_name()
		);
	}
}
