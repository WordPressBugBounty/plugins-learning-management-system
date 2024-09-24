<?php
/**
 * The MaxMind database service.
 *
 * @since 1.11.0
 *
 * @package Masteriyo\Addons\MultipleCurrency\MaxMind
 */

namespace Masteriyo\Addons\MultipleCurrency\MaxMind;

use Masteriyo\Addons\MultipleCurrency\Models\Setting;
use MaxMind\Db\Reader;

defined( 'ABSPATH' ) || exit;

/**
 * DatabaseService class.
 *
 * @since 1.11.0
 */
class DatabaseService {

	/**
	 * The name of the MaxMind database to utilize.
	 *
	 * @since 1.11.0
	 */
	const DATABASE = 'GeoLite2-Country';

	/**
	 * The extension for the MaxMind database.
	 *
	 * @since 1.11.0
	 */
	const DATABASE_EXTENSION = '.mmdb';

	/**
	 * Fetches the path that the database should be stored.
	 *
	 * @since 1.11.0
	 *
	 * @return string The local database path.
	 */
	public function get_database_path() {
		$uploads_dir = wp_upload_dir();

		$database_path = trailingslashit( $uploads_dir['basedir'] ) . 'masteriyo/';

		if ( ! empty( $this->get_database_prefix() ) ) {
			$database_path .= $this->get_database_prefix() . '-';
		}
		$database_path .= self::DATABASE . self::DATABASE_EXTENSION;

		/**
		 * Filter the geolocation database storage path.
		 *
		 * @since 1.11.0
	 *
		 * @param string $database_path The path to the database.
		 */
		return apply_filters( 'masteriyo_maxmind_geolocation_database_path', $database_path );
	}

	/**
	 * Fetches the prefix for the MaxMind database file.
	 *
	 * @since 1.11.0
	 *
	 * @return string
	 */
	private function get_database_prefix() {
		$prefix = 'masteriyo';

		return $prefix;
	}

	/**
	 * Fetches the database from the MaxMind service.
	 *
	 * @since 1.11.0
	 *
	 * @param string $license_key The license key to be used when downloading the database.
	 *
	 * @return string|\WP_Error The path to the database file or an error if invalid.
	 */
	public function download_database( $license_key ) {
		global $wp_filesystem;

		$download_uri = add_query_arg(
			array(
				'edition_id'  => self::DATABASE,
				'license_key' => $license_key,
				'suffix'      => 'tar.gz',
			),
			'https://download.maxmind.com/app/geoip_download'
		);

		require_once ABSPATH . 'wp-admin/includes/file.php';

		WP_Filesystem();

		if ( ! $wp_filesystem ) {
			return new \WP_Error( 'filesystem_error', __( 'Failed to initialize WP Filesystem.', 'learning-management-system' ) );
		}

		$tmp_archive_path = download_url( esc_url_raw( $download_uri ) );

		if ( is_wp_error( $tmp_archive_path ) ) {
			$error_data = $tmp_archive_path->get_error_data();
			if ( isset( $error_data['code'] ) ) {
				switch ( $error_data['code'] ) {
					case 401:
						return new \WP_Error(
							'masteriyo_maxmind_geolocation_database_license_key',
							__( 'The MaxMind license key is invalid. If you have recently created this key, you may need to wait for it to become active.', 'learning-management-system' )
						);
				}
			}

			return new \WP_Error( 'masteriyo_maxmind_geolocation_database_download', __( 'Failed to download the MaxMind database.', 'learning-management-system' ) );
		}

		try {
			$file = new \PharData( $tmp_archive_path );

			$tmp_database_path = trailingslashit( dirname( $tmp_archive_path ) ) . trailingslashit( $file->current()->getFilename() ) . self::DATABASE . self::DATABASE_EXTENSION;

			$file->extractTo(
				dirname( $tmp_archive_path ),
				trailingslashit( $file->current()->getFilename() ) . self::DATABASE . self::DATABASE_EXTENSION,
				true
			);
		} catch ( \Exception $exception ) {
			return new \WP_Error( 'masteriyo_maxmind_geolocation_database_archive', $exception->getMessage() );
		} finally {
			$wp_filesystem->delete( $tmp_archive_path );
		}

		return $tmp_database_path;
	}

	/**
	 * Fetches the ISO country code associated with an IP address.
	 *
	 * @since 1.11.0
	 *
	 * @param string $ip_address The IP address to find the country code for.
	 * @return string The country code for the IP address, or empty if not found.
	 */
	public function get_iso_country_code_for_ip( $ip_address ) {
		$country_code = '';

		if ( ! Setting::get( 'maxmind.enabled' ) ) {
			masteriyo_get_logger()->notice( __( 'MaxMind Integration has\'n enabled!', 'learning-management-system' ), array( 'source' => 'maxmind-geolocation' ) );
			return $country_code;
		}

		if ( ! class_exists( 'MaxMind\Db\Reader' ) ) {
			masteriyo_get_logger()->warning( __( 'Missing MaxMind Reader library!', 'learning-management-system' ), array( 'source' => 'maxmind-geolocation' ) );
			return $country_code;
		}

		$database_path = $this->get_database_path();
		if ( ! file_exists( $database_path ) ) {
			return $country_code;
		}

		try {
			$reader = new Reader( $database_path );
			$data   = $reader->get( $ip_address );

			if ( isset( $data['country']['iso_code'] ) ) {
				$country_code = $data['country']['iso_code'];
			}

			$reader->close();
		} catch ( \Exception $e ) {
			masteriyo_get_logger()->error( $e->getMessage(), array( 'source' => 'maxmind-geolocation' ) );
		}

		return $country_code;
	}
}
