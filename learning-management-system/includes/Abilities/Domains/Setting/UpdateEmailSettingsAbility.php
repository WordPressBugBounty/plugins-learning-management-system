<?php
/**
 * Update Email Settings ability.
 *
 * @package Masteriyo\Abilities\Domains\Setting
 * @since   x.x.x
 */

namespace Masteriyo\Abilities\Domains\Setting;

defined( 'ABSPATH' ) || exit;

/**
 * Ability: write Masteriyo email notification settings.
 *
 * Requires manage_options in addition to manage_masteriyo_settings because
 * email credentials and SMTP config are site-critical and admin-only.
 * Not exposed via MCP by default — opt-in via masteriyo_ability_mcp_public filter.
 *
 * @since x.x.x
 */
class UpdateEmailSettingsAbility extends AbstractScopedSettingsAbility {

	/** {@inheritdoc} */
	protected function settings_section(): string {
		return 'emails';
	}

	/**
	 * {@inheritdoc}
	 * Email settings require manage_options on top of the controller's own check.
	 *
	 * @param mixed $input Ability input (array or null).
	 */
	public function check_permission( $input = null ): bool {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}
		return parent::check_permission( $input );
	}

	/**
	 * {@inheritdoc}
	 * Not publicly exposed via MCP by default — site admin must opt-in.
	 */
	public function is_mcp_public(): bool {
		return false;
	}

	/** {@inheritdoc} */
	public function get_name(): string {
		return 'masteriyo/settings-update-emails';
	}

	/** {@inheritdoc} */
	public function get_label(): string {
		return __( 'Update Email Settings', 'learning-management-system' );
	}

	/** {@inheritdoc} */
	public function get_description(): string {
		return __( 'Write Masteriyo email notification settings. Requires administrator privileges. Accepts a partial emails settings object; only provided keys are updated.', 'learning-management-system' );
	}
}
