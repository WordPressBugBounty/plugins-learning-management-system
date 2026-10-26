<?php
/**
 * User UserCSVImporter class.
 *
 * This class extends the CSV Importer class to provide functionality for
 * exporting user data to a CSV file.
 *
 * @since 1.6.13
 *
 * @package Masteriyo\Importer
 */

namespace Masteriyo\Importer;

use Masteriyo\Roles;

defined( 'ABSPATH' ) || exit;


class UserCSVImporter {

	/**
	 * The file path of the CSV file to be imported.
	 *
	 * @since 1.6.13
	 *
	 * @var string
	 */
	protected $file_path;

	/**
	 * Lines the parser could not read, keyed by line number, so the import can report them.
	 *
	 * @var array<int, string>
	 */
	protected $invalid_lines = array();

	/**
	 * Constructor.
	 *
	 * @since 1.6.13
	 *
	 * @param string $file_path The file path of the CSV file to be imported.
	 */
	public function __construct( string $file_path ) {
		$this->file_path = $file_path;
	}

	/**
	 * Import the CSV data, reporting every row that was not imported.
	 *
	 * @since 1.6.13
	 *
	 * @return \WP_Error|\WP_REST_Response
	 */
	public function import() {
		if ( ! file_exists( $this->file_path ) || ! is_readable( $this->file_path ) ) {
			return new \WP_Error( 'invalid_file', 'Invalid or unreadable CSV file.' );
		}

		$rows = $this->parse_csv_file();

		if ( is_wp_error( $rows ) ) {
			return $rows;
		}

		if ( empty( $rows ) && empty( $this->invalid_lines ) ) {
			return new \WP_Error( 'invalid_csv_format', 'Invalid CSV format. Unable to process data.' );
		}

		$imported = 0;
		$skipped  = array();

		foreach ( $this->invalid_lines as $line => $message ) {
			$skipped[] = array(
				'row'      => $line,
				'username' => '',
				'email'    => '',
				'code'     => 'invalid_row',
				'message'  => $message,
			);
		}

		foreach ( $rows as $line => $row ) {
			try {
				$result = $this->create_user_from_csv_row( $row );
			} catch ( \Exception $e ) {
				$result = new \WP_Error( 'user_import_failed', $e->getMessage() );
			}

			if ( is_wp_error( $result ) ) {
				$skipped[] = array(
					'row'      => $line,
					'username' => (string) ( $row['username'] ?? '' ),
					'email'    => (string) ( $row['email'] ?? '' ),
					'code'     => $result->get_error_code(),
					'message'  => $result->get_error_message(),
				);
				continue;
			}

			++$imported;
		}

		usort(
			$skipped,
			static function ( array $a, array $b ): int {
				return $a['row'] <=> $b['row'];
			}
		);

		return new \WP_REST_Response(
			array(
				'status'   => $skipped ? 'partial' : 'completed',
				'imported' => $imported,
				'skipped'  => $skipped,
				'message'  => self::summary( $imported, $skipped ),
			)
		);
	}

	/**
	 * One line naming each skipped row, so the admin knows who to add by hand.
	 *
	 * @param int                                                                          $imported Users created.
	 * @param list<array{row: int, username: string, email: string, code: string, message: string}> $skipped  Rows not imported.
	 *
	 * @return string
	 */
	private static function summary( int $imported, array $skipped ): string {
		if ( ! $skipped ) {
			return __( 'Import successful.', 'learning-management-system' );
		}

		$rows = array_map(
			static function ( array $row ): string {
				/* translators: 1: CSV row number, 2: username or email, 3: reason the row was skipped */
				return sprintf( __( 'row %1$d %2$s (%3$s)', 'learning-management-system' ), $row['row'], $row['username'] ? $row['username'] : $row['email'], $row['message'] );
			},
			$skipped
		);

		return sprintf(
			/* translators: 1: "N users imported", 2: "N rows skipped", 3: the skipped rows */
			__( '%1$s, %2$s: %3$s', 'learning-management-system' ),
			/* translators: %d: number of users imported */
			sprintf( _n( '%d user imported', '%d users imported', $imported, 'learning-management-system' ), $imported ),
			/* translators: %d: number of rows skipped */
			sprintf( _n( '%d row skipped', '%d rows skipped', count( $skipped ), 'learning-management-system' ), count( $skipped ) ),
			implode( '; ', $rows )
		);
	}

	/**
	 * Parse the CSV file into rows keyed by their line number in the file.
	 *
	 * @since 1.6.13
	 *
	 * @return array<int, array<string, string>>
	 */
	protected function parse_csv_file() {
		$header   = null;
		$csv_data = array();

		$wp_filesystem = masteriyo_get_filesystem();

		if ( ! $wp_filesystem || ! $wp_filesystem->exists( $this->file_path ) ) {
			return array(); // Return an empty array if the file doesn't exist.
		}

		$file_content = $wp_filesystem->get_contents( $this->file_path );
		$file_content = preg_replace( '/^\xEF\xBB\xBF/', '', (string) $file_content );
		$lines        = explode( "\n", $file_content );

		if ( $lines ) {
			// The exporter writes with no escape character; PHP 8.4 deprecates leaving it implicit.
			$header = str_getcsv( array_shift( $lines ), ',', '"', '' );
			$header = array_map(
				function ( $item ) {
					return strtolower( str_replace( ' ', '_', $item ) );
				},
				$header
			);

			foreach ( $lines as $index => $line ) {
				if ( empty( trim( $line ) ) ) {
					continue; // Skip empty lines.
				}
				$data = array_map( 'masteriyo_unescape_csv_formula', str_getcsv( $line, ',', '"', '' ) );
				if ( count( $header ) === count( $data ) ) {
					$csv_data[ $index + 2 ] = array_combine( $header, $data );
				} else {
					/* translators: 1: number of columns in the header, 2: number of columns in the row */
					$this->invalid_lines[ $index + 2 ] = sprintf( __( 'Expected %1$d columns, found %2$d.', 'learning-management-system' ), count( $header ), count( $data ) );
				}
			}
		}

		return $csv_data;
	}

	/**
	 * Create a user from the CSV row data.
	 *
	 * @since 1.6.13
	 *
	 * @param array $row The CSV row data.
	 */
	protected function create_user_from_csv_row( $row ) {
		add_filter( 'masteriyo_registration_is_generate_password', '__return_true' );

		if ( empty( $row['email'] ) || empty( $row['username'] ) || empty( $row['roles'] ) ) {
			return new \WP_Error( 'missing_required_fields', 'Email, username or roles are missing.' );
		}

		if ( ! is_email( $row['email'] ) ) {
			return new \WP_Error( 'invalid_email', 'Invalid email provided.' );
		}

		// Check if the username already exists.
		if ( username_exists( $row['username'] ) ) {
			return new \WP_Error( 'username_exists', 'The username already exists.' );
		}

		// Check if the email already exists.
		if ( email_exists( $row['email'] ) ) {
				return new \WP_Error( 'email_exists', 'The email address already exists.' );
		}

		// Validate allowed fields.
		$allowed_fields = array(
			'username',
			'nicename',
			'email',
			'url',
			'date_created',
			'status',
			'display_name',
			'nickname',
			'first_name',
			'last_name',
			'password',
			'description',
			'locale',
			'roles',
			'profile_image_id',
			'billing_first_name',
			'billing_last_name',
			'billing_company_name',
			'billing_company_id',
			'billing_address_1',
			'billing_address_2',
			'billing_city',
			'billing_postcode',
			'billing_country',
			'billing_state',
			'billing_email',
			'billing_phone',
		);

		// Sanitize data.
		$row['email']    = sanitize_email( $row['email'] );
		$row['username'] = sanitize_user( $row['username'] );
		$row['roles']    = sanitize_text_field( $row['roles'] );

		// Import may assign only Masteriyo-managed roles, and the Manager role
		// only for a full administrator, so create_users alone cannot escalate.
		$allowed_roles = array_keys( Roles::get_all() );
		if ( ! current_user_can( 'manage_options' ) ) {
			$allowed_roles = array_diff( $allowed_roles, array( Roles::MANAGER ) );
		}

		// The export joins a user's roles with commas; the other roles are dropped, not refused.
		$roles = array_values( array_intersect( array_map( 'trim', explode( ',', $row['roles'] ) ), $allowed_roles ) );
		if ( ! $roles ) {
			return new \WP_Error( 'invalid_role', 'The specified role is not assignable via import.' );
		}

		// Create the user.
		$user = masteriyo_create_new_user(
			$row['email'],
			$row['username'],
			'',
			$roles,
			array(
				'first_name' => sanitize_text_field( $row['first_name'] ),
				'last_name'  => sanitize_text_field( $row['last_name'] ),
			)
		);

		if ( is_wp_error( $user ) ) {
			return $user;
		}

		// Set additional user data for valid keys only.
		foreach ( $row as $key => $value ) {
			if ( in_array( $key, array( 'id', 'email', 'username', 'role', 'roles', 'first_name', 'last_name' ), true ) ) {
				continue;
			}

			if ( ! in_array( $key, $allowed_fields, true ) ) {
				continue; // Skip processing if the key is not allowed.
			}

			$method_name = 'set_' . $key;
			if ( is_callable( array( $user, $method_name ) ) ) {
				// Sanitize and set the value.
				$sanitized_value = $this->sanitize_user_data( $key, $value );
				$user->$method_name( $sanitized_value );
			}
		}

		$user->save();

		return $user;
	}


	/**
	 * Sanitize user data based on the field key.
	 *
	 * @since 1.6.13
	 *
	 * @param string $key   The field key.
	 * @param mixed  $value The value to be sanitized.
	 *
	 * @return mixed Sanitized value.
	 */
	protected function sanitize_user_data( $key, $value ) {
		if ( 'date_created' === $key && ! ( $value instanceof \DateTime ) ) {
			try {
				$value = new \DateTime( $value );
				return $value->format( 'Y-m-d H:i:s' );
			} catch ( \Exception $e ) {
				return '';
			}
		}

		return sanitize_text_field( $value );
	}
}
