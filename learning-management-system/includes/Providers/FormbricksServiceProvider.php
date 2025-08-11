<?php
/**
 * Formbricks class service provider.
 *
 * @since 1.20.0
 * @package Masteriyo\Providers
 */

namespace Masteriyo\Providers;

defined( 'ABSPATH' ) || exit;

use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Masteriyo\Constants;

/**
 * Registers and initializes formbricks types and categories for Masteriyo LMS.
 *
 * @since 1.20.0
 */
class FormbricksServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface {

	/**
	 * Environment ID for Formbricks.
	 */
	const ENVIRONMENT_ID = 'cmcn73ighcqgkwk017mrq94f8';

	/**
	 * Services provided by this service provider.
	 *
	 * @since 1.20.0
	 * @var array
	 */
	protected $provides = array();

	/**
	 * Register services in the container.
	 *
	 * @since 1.20.0
	 * @return void
	 */
	public function register() {
		// No container services to register for now.
	}

	/**
	 * Boot the formbricks service provider.
	 * Registers block types, categories, and editor assets.
	 *
	 * @since 1.20.0
	 * @return void
	 */
	public function boot() {
		add_action( 'admin_enqueue_scripts', array( $this, 'declare_internal_pages' ) );
		add_filter( 'themeisle-sdk/survey/' . MASTERIYO_SLUG, array( $this, 'configure_formbricks' ), 10, 2 );
	}

	/**
	 * Declares internal pages for the plugin by triggering the 'themeisle_internal_page' action.
	 *
	 * This method fires the 'themeisle_internal_page' action hook with the plugin's slug and
	 * the top-level page identifier. It is used to register or declare internal admin pages
	 * for the plugin within the WordPress admin dashboard.
	 *@since 1.20.0
	 * @return void
	 */
	public function declare_internal_pages() {
		if ( masteriyo_is_admin_page() ) {
			do_action( 'themeisle_internal_page', MASTERIYO_SLUG, 'toplevel_page_masteriyo' );
		}
	}

	/**
	 * Configures Formbricks survey data based on the provided page slug.
	 *
	 * @param array  $data      Existing data to be configured.
	 * @param string $page_slug The slug of the current page.
	 *@since 1.20.0
	 * @return array Modified data with Formbricks survey information if applicable.
	 */
	public function configure_formbricks( $data, $page_slug ) {

		if ( empty( $page_slug ) ) {
			return $data;
		}
		$survey_data = array(
			'environmentId' => self::ENVIRONMENT_ID,
			'attributes'    => array(
				'free_version'        => MASTERIYO_VERSION,
				'install_days_number' => $this->get_install_days(),
				'is_premium'          => $this->is_premium(),
			),
		);
		return $survey_data;
	}

	/**
	 * Calculates the number of days since the plugin was installed.
	 *
	 * Retrieves the installation date from the 'masteriyo_install_date' option.
	 * If the value is not numeric, it attempts to convert it to a timestamp.
	 * Returns the number of full days elapsed since installation.
	 *
	 *  @since 1.20.0
	 * @return int Number of days since the plugin was installed.
	 */
	private function get_install_days() {
		$install_time = get_option( 'masteriyo_install_date', time() );
		if ( ! is_numeric( $install_time ) ) {
			$install_time = strtotime( $install_time );
		}
		$current_time       = time();
		$days_since_install = floor( ( $current_time - $install_time ) / DAY_IN_SECONDS );
		return $days_since_install;
	}

	/**
	 * Checks if the premium version of the Learning Management System plugin is active.
	 *
	 * This method determines whether the 'learning-management-system-pro/lms.php' plugin
	 * is currently active. Returns true if the premium plugin is active, otherwise false.
	 *
	 * @since 1.20.0
	 * @return bool True if the premium plugin is active, false otherwise.
	 */
	private function is_premium() {
		if ( is_plugin_active( 'learning-management-system-pro/lms.php' ) ) {
			return true;
		} else {
			return false;
		}
	}
}
