<?php
/**
 * Update General Settings ability.
 *
 * @package Masteriyo\Abilities\Domains\Setting
 */

namespace Masteriyo\Abilities\Domains\Setting;

defined( 'ABSPATH' ) || exit;

/**
 * Ability: write Masteriyo general settings (styling, pages, course listing, etc.).
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
		return sprintf(
			/* translators: %s: the product's name */
			__( 'Write %s general settings (styling, pages, course listing options). Accepts a partial general settings object; only provided keys are updated.', 'learning-management-system' ),
			masteriyo_get_plugin_name()
		);
	}
}
