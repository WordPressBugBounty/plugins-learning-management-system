<?php
/**
 * Setting Repository
 */

namespace Masteriyo\Repository;

use Masteriyo\Constants;
use Masteriyo\Database\Model;
use Masteriyo\Models\Setting;

class SettingRepository extends AbstractRepository implements RepositoryInterface {
	/**
	 * Create a setting in the database.
	 *
	 * @since 1.0.0
	 *
	 * @param \Masteriyo\Models\Setting $setting Setting object.
	 */
	public function create( Model &$setting ) {
		$posted_setting = $setting->get_data();
		$setting_in_db  = get_option( 'masteriyo_settings', array() );

		$posted_setting = $this->clean_setting( $posted_setting, true );
		$setting_in_db  = $this->clean_setting( $setting_in_db );

		// if courses permalink / slugs changed then update masteriyo_flush_rewrite_rules.
		$should_update_permalink = false;
		foreach ( $posted_setting['advance']['permalinks'] as $permalink => $value ) {
			if ( ! isset( $setting_in_db['advance']['permalinks'][ $permalink ] ) ) {
				$should_update_permalink = true;
				break;
			}

			if ( $value !== $setting_in_db['advance']['permalinks'][ $permalink ] ) {
				$should_update_permalink = true;
				break;
			}
		}

		if ( $should_update_permalink ) {
			update_option( 'masteriyo_flush_rewrite_rules', 'yes' );
		}

		$setting->reset();

		$default_settings = $setting->get_data();

		$setting_in_db = array_replace_recursive( $default_settings, $setting_in_db, $posted_setting );

		$setting->set_data( $setting_in_db );

		update_option( 'masteriyo_settings', $setting->get_data() );

		/**
		 * Fires after creating a setting.
		 *
		 * @since 1.0.0
		 *
		 * @param \Masteriyo\Models\Setting $object The setting object.
		 */
		do_action( 'masteriyo_new_setting', $setting );
	}

	/**
	 * Read a setting.
	 *
	 * @since 1.0.0
	 *
	 * @param \Masteriyo\Models\Setting $setting Course object.
	 * @param mixed $default Default value.
	 *
	 * @throws Exception If invalid setting.
	 */
	public function read( Model &$setting, $default = null ) {
		$setting_in_db = get_option( 'masteriyo_settings', array() );
		$setting_in_db = masteriyo_parse_args( $setting_in_db, $setting->get_data() );
		$setting_in_db = $this->clean_setting( $setting_in_db );

		$setting->set_data( $setting_in_db );

		$this->process_setting( $setting );

		$setting->set_object_read( true );

		/**
		 * Fires after reading setting from database.
		 *
		 * @since 1.0.0
		 *
		 * @param integer $id ID.
		 * @param \Masteriyo\Models\Setting $object The setting object.
		 */
		do_action( 'masteriyo_setting_read', $setting->get_id(), $setting );
	}

	/**
	 * Update a setting in the database.
	 *
	 * @since 1.0.0
	 *
	 * @param Model $setting Setting object.
	 *
	 * @return void
	 */
	public function update( Model &$setting ) {
		return new \WP_Error(
			'invalid-method',
			// translators: %s: Class method name.
			sprintf( __( "Method '%s' not implemented.", 'learning-management-system' ), __METHOD__ ),
			array( 'status' => 405 )
		);
	}

	/**
	 * Delete a setting from the database.
	 *
	 * @since 1.0.0
	 *
	 * @param \Masteriyo\Models\Setting $setting Setting object.
	 * @param array $args   Array of args to pass.alert-danger
	 */
	public function delete( Model &$setting, $args = array() ) {
		$setting_data = $setting->get_data();
		update_option( 'masteriyo_settings', $setting_data );

		/**
		 * Fires after resetting setting from database.
		 *
		 * @since 1.0.0
		 *
		 * @param \Masteriyo\Models\Setting $object The setting object.
		 */
		do_action( 'masteriyo_reset_setting', $setting );
	}

	/**
	 * Process setting.
	 *
	 * @since 1.0.0
	 *
	 * @param  \Masteriyo\Models\Setting Setting object.
	 * @return void
	 */
	protected function process_setting( &$setting ) {
		if ( Constants::get( 'MASTERIYO_TEMPLATE_DEBUG_MODE' ) ) {
			$setting->set( 'advance.debug.template_debug', Constants::get( 'MASTERIYO_TEMPLATE_DEBUG_MODE' ) );
		}

		if ( Constants::get( 'MASTERIYO_DEBUG' ) ) {
			$setting->set( 'advance.debug.debug', Constants::get( 'MASTERIYO_DEBUG' ) );
		}
	}

	/**
	 * Clean setting and store only which are in the $data of the Setting model.
	 *
	 * @param array $setting Setting array.
	 * @param bool  $sanitize Whether to sanitize the settings. Added since 1.12.2
	 *
	 * @since 1.4.2
	 *
	 * @param array $setting Setting array.
	 */
	protected function clean_setting( $setting, $sanitize = false ) {

		if ( empty( $setting ) ) {
			return array();
		}

		$setting_dot_arr = masteriyo_array_dot( $setting );

		// Default $data array.
		$setting_object          = masteriyo( 'setting' );
		$default_setting_dot_arr = masteriyo_array_dot( $setting_object->get_data() );

		$filtered_settings = array_intersect_key( $setting_dot_arr, $default_setting_dot_arr );

		if ( $sanitize ) {
			foreach ( $filtered_settings as $key => $value ) {
					$filtered_settings[ $key ] = $setting_object->sanitize( $key, $value );
			}
		}

		return masteriyo_array_undot( $filtered_settings );
	}
}
