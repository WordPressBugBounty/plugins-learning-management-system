<?php
/**
 * Deactivation feedback service provider.
 *
 * @package Masteriyo\Providers
 */

namespace Masteriyo\Providers;

defined( 'ABSPATH' ) || exit;

use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

/**
 * Service provider for job-related services.
 *
 * @since 1.6.0
 */
class DeactivationFeedbackServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface {
	/**
	 * The provided array is a way to let the container
	 * know that a service is provided by this service
	 * provider. Every service that is registered via
	 * this service provider must have an alias added
	 * to this array or it will be ignored
	 *
	 * @since 1.6.0
	 *
	 * @var array
	 */
	protected $provides = array();

	/**
	 * This is where the magic happens, within the method you can
	 * access the container and register or retrieve anything
	 * that you need to, but remember, every alias registered
	 * within this method must be declared in the `$provides` array.
	 *
	 * @since 1.6.0
	 */
	public function register() {
		// Register any services or dependencies here.
	}

	/**
	 * Bootstraps the application by scheduling a recurring action and registering the job.
	 *
	 * This method is called after all service providers are registered.
	 *
	 * @since 1.6.0
	 */
	public function boot() {
		add_action( 'admin_footer', array( $this, 'feedback_html' ) );
	}

	/**
	 * Deactivation Feedback HTML.
	 *
	 * @since 1.6.0
	 *
	 * @return void
	 */
	public function feedback_html() {
		if ( ! $this->is_plugins_screen() ) {
			return;
		}

		$deactivate_reasons = array(
			'feature_unavailable'     => array(
				/* translators: %s: link to feature requests page */
				'title'             => sprintf( esc_html__( 'Couldn\'t find the feature I needed. Request it %s', 'learning-management-system' ), '<a href="' . esc_url_raw( 'https://masteriyo.feedbear.com/boards/feature-requests' ) . '" target="_blank">' . esc_html__( 'here', 'learning-management-system' ) . '</a>' ),
				'is_input'          => true,
				'input_placeholder' => '',
			),
			'complex_to_use'          => array(
				/* translators: %1$s: link to documentation page %2$s: link to support team page */
				'title'             => sprintf( esc_html__( 'The plugin is too complex. See the %1$s or contact our %2$s', 'learning-management-system' ), '<a href="' . esc_url_raw( 'https://docs.masteriyo.com/getting-started' ) . '" target="_blank">' . esc_html__( 'documentation', 'learning-management-system' ) . '</a>', '<a href="' . esc_url_raw( 'https://masteriyo.com/support/' ) . '" target="_blank">' . esc_html__( 'support team', 'learning-management-system' ) . '</a>' ),
				'is_input'          => true,
				'input_placeholder' => esc_html__( 'Please provide more details if possible', 'learning-management-system' ),
			),
			'found_a_better_plugin'   => array(
				'title'             => esc_html__( 'Found a better alternative', 'learning-management-system' ),
				'input_placeholder' => esc_html__( 'Please mention the alternative if possible', 'learning-management-system' ),
				'is_input'          => true,
			),
			'temporary_deactivation'  => array(
				'title'             => esc_html__( 'Temporarily deactivating', 'learning-management-system' ),
				'is_input'          => true,
				'input_placeholder' => '',
			),
			'no_longer_needed'        => array(
				'title'             => esc_html__( 'No longer need the plugin', 'learning-management-system' ),
				'is_input'          => true,
				'input_placeholder' => '',
			),
			'found_bug_in_the_plugin' => array(
				'title'             => esc_html__( 'Found a bug?', 'learning-management-system' ),
				'input_placeholder' => esc_html__( 'Please describe the issue', 'learning-management-system' ),
				'is_input'          => true,
			),
			'have_masteriyo_pro'      => array(
				'title'             => esc_html__( 'I have Masteriyo Pro', 'learning-management-system' ),
				'is_input'          => true,
				'input_placeholder' => '',
			),
			'other_reasons'           => array(
				'title'             => esc_html__( 'Other reason', 'learning-management-system' ),
				'is_input'          => true,
				'input_placeholder' => esc_html__( 'Please specify the reason', 'learning-management-system' ),
			),
		);

		masteriyo_get_template( 'deactivation/deactivation-feedback.php', array( 'deactivate_reasons' => $deactivate_reasons ) );
	}

	/**
	 * Check if the current screen is the plugins screen and returns a boolean.
	 *
	 * @since 1.6.0
	 *
	 * @return boolean
	 */
	private function is_plugins_screen() {
		if ( ! is_callable( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();

		return $screen && in_array( $screen->id, array( 'plugins', 'plugins-network' ), true );
	}
}
