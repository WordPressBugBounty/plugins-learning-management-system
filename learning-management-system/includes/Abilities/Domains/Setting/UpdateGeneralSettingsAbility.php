<?php
/**
 * Update General Settings ability.
 *
 * @package Masteriyo\Abilities\Domains\Setting
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Setting;

defined( 'ABSPATH' ) || exit;

/**
 * Ability: write Masteriyo general settings (styling, pages, course listing, etc.).
 *
 * @since x.x.x
 */
class UpdateGeneralSettingsAbility extends AbstractScopedSettingsAbility {

	/** {@inheritdoc} */
	protected function settings_section(): string {
		return 'general';
	}

	/** {@inheritdoc} */
	public function get_name(): string {
		return 'masteriyo/settings-update-general';
	}

	/** {@inheritdoc} */
	public function get_label(): string {
		return __( 'Update General Settings', 'learning-management-system' );
	}

	/** {@inheritdoc} */
	public function get_description(): string {
		return __( 'Write Masteriyo general settings (styling, pages, course listing options). Accepts a partial general settings object; only provided keys are updated.', 'learning-management-system' );
	}
}
