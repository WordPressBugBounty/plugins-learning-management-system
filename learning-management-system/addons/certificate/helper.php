<?php
/**
 * Certificate utility functions.
 *
 * @since 1.13.0
 */

use Masteriyo\Addons\Certificate\Models\Certificate;
use Masteriyo\Addons\Certificate\PDF\BlockBuilders\Fallback;
use Masteriyo\Roles;

if ( ! function_exists( 'masteriyo_get_certificate' ) ) {
	/**
	 * Get certificate.
	 *
	 * @since 1.13.0
	 *
	 * @param int|\Masteriyo\Addons\Certificate\Models\Certificate|\WP_Post $certificate Certificate id or Certificate Model or Post.
	 *
	 * @return \Masteriyo\Addons\Certificate\Models\Certificate|null
	 */
	function masteriyo_get_certificate( $certificate ) {
		$certificate_obj   = masteriyo( 'certificate' );
		$certificate_store = masteriyo( 'certificate.store' );

		if ( is_a( $certificate, Certificate::class ) ) {
			$id = $certificate->get_id();
		} elseif ( is_a( $certificate, \WP_Post::class ) ) {
			$id = $certificate->ID;
		} else {
			$id = $certificate;
		}

		try {
			$id = absint( $id );
			$certificate_obj->set_id( $id );
			$certificate_store->read( $certificate_obj );
		} catch ( \Exception $e ) {
			$certificate_obj = null;
		}

		return apply_filters( 'masteriyo_get_certificate', $certificate_obj, $certificate );
	}
}

if ( ! function_exists( 'masteriyo_create_certificate_object' ) ) {
	/**
	 * Create instance of certificate model.
	 *
	 * @since 1.13.0
	 *
	 * @return \Masteriyo\Addons\Certificate\Models\Certificate
	 */
	function masteriyo_create_certificate_object() {
		return masteriyo( 'certificate' );
	}
}

if ( ! function_exists( 'masteriyo_get_course_certificate_id' ) ) {
	/**
	 * Get certificate ID of a course.
	 *
	 * @since 1.13.0
	 *
	 * @param integer $course_id
	 *
	 * @return integer
	 */
	function masteriyo_get_course_certificate_id( $course_id ) {
		return absint( get_post_meta( $course_id, '_certificate_id', true ) );
	}
}

if ( ! function_exists( 'masteriyo_get_blank_certificate_template' ) ) {
	/**
	 * Get blank certificate template content.
	 *
	 * @since 1.13.0
	 *
	 * @return string
	 */
	function masteriyo_get_blank_certificate_template() {
		ob_start();
		include MASTERIYO_CERTIFICATE_TEMPLATES . '/blank-template.php';
		return ob_get_clean();
	}
}

/**
 * Masteriyo process content for import.
 *
 * Downloads images locally and replaces remote image url to local.
 *
 * @param string $content
 * @return string
 */
if ( ! function_exists( 'masteriyo_process_content_for_import' ) ) {
	function masteriyo_process_content_for_import( $content = '' ) {
		preg_match_all( '#\bhttps?://[^,\s()<>]+(?:\([\w\d]+\)|([^,[:punct:]\s]|/))#', $content, $match );

		$urls = array_unique( $match[0] );

		if ( empty( $urls ) ) {
			return $content;
		}

		$map_urls   = array();
		$image_urls = array();

		foreach ( $urls as $url ) {
			if ( masteriyo_is_image_url( $url ) ) {
				$image_urls[] = $url;
			}
		}

		if ( ! empty( $image_urls ) ) {
			foreach ( $image_urls as $image_url ) {
				$downloaded_image       = masteriyo_upload_certificate_image( $image_url );
				$map_urls[ $image_url ] = $downloaded_image['url'];
			}
		}

		foreach ( $map_urls as $old_url => $new_url ) {
			$content = str_replace( $old_url, $new_url, $content );
			$old_url = str_replace( '/', '/\\', $old_url );
			$new_url = str_replace( '/', '/\\', $new_url );
			$content = str_replace( $old_url, $new_url, $content );
		}

		return $content;
	}
}

if ( ! function_exists( 'masteriyo_upload_certificate_image' ) ) {
	/**
	 * Upload image and create an attachment.
	 * If an image with the given URL has already been uploaded, the uploaded image will be returned.
	 *
	 * @since 1.13.0
	 *
	 * @param string $url
	 *
	 * @return null|array
	 */
	function masteriyo_upload_certificate_image( $url ) {
		if ( empty( $url ) ) {
			return null;
		}

		$uploaded_image = masteriyo_get_uploaded_certificate_image( $url );

		if ( $uploaded_image ) {
			return $uploaded_image;
		}

		$response     = wp_safe_remote_get(
			$url,
			array(
				'timeout'   => '60',
				'sslverify' => false,
			)
		);
		$file_content = wp_remote_retrieve_body( $response );

		if ( empty( $file_content ) || 200 !== $response['response']['code'] ) {
			return null;
		}

		$filename = basename( $url );
		$upload   = wp_upload_bits( $filename, null, $file_content );
		$post     = array(
			'post_title' => $filename,
			'guid'       => $upload['url'],
		);
		$info     = wp_check_filetype( $upload['file'] );

		if ( $info ) {
			$post['post_mime_type'] = $info['type'];
		} else {
			return null;
		}

		$post_id = wp_insert_attachment( $post, $upload['file'] );

		require_once ABSPATH . 'wp-admin/includes/image.php';

		wp_update_attachment_metadata(
			$post_id,
			wp_generate_attachment_metadata( $post_id, $upload['file'] )
		);
		update_post_meta( $post_id, '_masteriyo_image_url_hash', sha1( $url ) );

		return array(
			'id'  => $post_id,
			'url' => $upload['url'],
		);
	}
}

if ( ! function_exists( 'masteriyo_get_uploaded_certificate_image' ) ) {
	/**
	 * Check if an image with the given URL have been uploaded. If found, the uploaded image will be returned.
	 *
	 * @since 1.13.0
	 *
	 * @param string $url
	 *
	 * @return null|array
	 */
	function masteriyo_get_uploaded_certificate_image( $url ) {
		global $wpdb;

		if ( empty( $url ) ) {
			return null;
		}

		$image_id = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT `post_id` FROM `' . $wpdb->postmeta . '`
				WHERE `meta_key` = \'_masteriyo_image_url_hash\'
				AND `meta_value` = %s;',
				sha1( $url )
			)
		);

		if ( $image_id ) {
			return array(
				'id'  => $image_id,
				'url' => wp_get_attachment_url( $image_id ),
			);
		}
		return null;
	}
}

if ( ! function_exists( 'masteriyo_is_image_url' ) ) {
	/**
	 * Is image url.
	 *
	 * @since 1.13.0
	 *
	 * @param string $url
	 *
	 * @return void
	 */
	function masteriyo_is_image_url( $url = '' ) {
		return preg_match( '/^((https?:\/\/)|(www\.))([a-z\d-].?)+(:\d+)?\/[\w\-]+\.(jpg|png|gif|jpeg)\/?$/i', $url );
	}
}

if ( ! function_exists( 'masteriyo_generate_certificate_download_url' ) ) {
	/**
	 * Generate certificate download url.
	 *
	 * @since 1.13.0
	 *
	 * @param int|\Masteriyo\Models\Course|\WP_Post $course_id
	 *
	 * @return string
	 */
	function masteriyo_generate_certificate_download_url( $course_id ) {
		$course = masteriyo_get_course( $course_id );

		if ( is_null( $course ) ) {
			return '';
		}

		$url = add_query_arg(
			array(
				'masteriyo_download_certificate' => true,
				'course_id'                      => $course->get_id(),
				'nonce'                          => wp_create_nonce( 'masteriyo_download_certificate' ),
			),
			home_url( '/' )
		);

		/**
		 * Filters certificate download URL.
		 *
		 * @since 1.13.0
		 *
		 * @param string $url The download URL.
		 * @param \Masteriyo\Models\Course $course Course object.
		 */
		return apply_filters( 'masteriyo_certificate_download_url', $url, $course );
	}
}

if ( ! function_exists( 'masteriyo_is_certificate_enabled_for_course' ) ) {
	/**
	 * Check if certificate is enabled for a course.
	 *
	 * @since 1.13.0
	 *
	 * @param integer $$course_id
	 *
	 * @return boolean
	 */
	function masteriyo_is_certificate_enabled_for_course( $course_id ) {
		return masteriyo_string_to_bool( get_post_meta( $course_id, '_certificate_enabled', true ) );
	}
}

if ( ! function_exists( 'masteriyo_get_certificate_templates' ) ) {
	/**
	 * Get certificate templates.
	 *
	 * @since 1.13.0
	 *
	 * @return array|\WP_Error
	 */
	function masteriyo_get_certificate_templates() {
		$samples_json_url = 'https://d1sb0nhp4t2db4.cloudfront.net/resources/masteriyo/certificate/certificates.json';
		$samples          = get_transient( 'masteriyo_pro_certificate_samples' );

		if ( ! is_array( $samples ) ) {
			$response = wp_remote_get( $samples_json_url, array( 'timeout' => 10 ) );

			// Bail early if there is error in response.
			if ( is_wp_error( $response ) ) {
				return $response;
			}

			// Bail early if the response code is not 200.
			if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
				return new \WP_Error(
					'masteriyo_rest_certificate_invalid_http_status_code',
					__( 'Something went wrong.', 'learning-management-system' ),
					array( 'status' => wp_remote_retrieve_response_code( $response ) )
				);
			}

			$samples = json_decode( wp_remote_retrieve_body( $response ), true );

			// Bail early if the json is invalid.
			if ( null === $samples ) {
				return new \WP_Error(
					'masteriyo_rest_certificate_invalid_json',
					__( 'Unable to decode samples JSON.', 'learning-management-system' ),
					array( 'status' => 400 )
				);
			}

			if ( is_array( $samples ) ) {
				$samples = array( reset( $samples ) );
			}

			set_transient( 'masteriyo_pro_certificate_samples', $samples, DAY_IN_SECONDS );
		}

		return apply_filters( 'masteriyo_get_certificate_templates', $samples, $samples_json_url );
	}
}

if ( ! function_exists( 'masteriyo_process_certificate_template_smart_tags' ) ) {
	/**
	 * Process smart tags in certificate template html.
	 *
	 * @since 1.13.0
	 *
	 * @param string $template
	 * @param int|\Masteriyo\Models\Course|\WP_Post|null $course_id
	 * @param int|\Masteriyo\Models\User|\WP_User|null $student_id
	 * @param boolean $is_preview
	 *
	 * @return string
	 */
	function masteriyo_process_certificate_template_smart_tags( $template, $course_id = null, $student_id = null, $is_preview = false ) {
		$smart_tags = array();

		/**
		 * Filters smart tags for the certificate template.
		 *
		 * @since 1.13.0
		 *
		 * @param array $smart_tags
		 * @param string $template
		 * @param int|\Masteriyo\Models\Course|\WP_Post $course_id
		 * @param int|\Masteriyo\Models\User|\WP_User $student_id
		 * @param boolean $is_preview
		 */
		$smart_tags = apply_filters( 'masteriyo_certificate_template_smart_tags', $smart_tags, $template, $course_id, $student_id, $is_preview );

		foreach ( $smart_tags as $tag => $value ) {
			$template = str_replace( '{{' . $tag . '}}', $value, $template );
		}

		/**
		 * Filters certificate template after processing smart tags.
		 *
		 * @since 1.13.0
		 *
		 * @param string $template
		 * @param int|\Masteriyo\Models\Course|\WP_Post|null $course_id
		 * @param int|\Masteriyo\Models\User|\WP_User|null $student_id
		 * @param array $smart_tags
		 */
		return apply_filters( 'masteriyo_process_certificate_template_smart_tags', $template, $course_id, $student_id, $smart_tags );
	}
}


if ( ! function_exists( 'masteriyo_make_block_builder_instance' ) ) {
	/**
	 * Creates an instance of block builder class.
	 *
	 * @param array $block_data Block data.
	 * @param \Masteriyo\Addons\Certificate\PDF\CertificatePDF $pdf
	 *
	 * @return \Masteriyo\Addons\Certificate\PDF\BlockBuilders\Block
	 */
	function masteriyo_make_block_builder_instance( $block_data, $pdf ) {
		$class_name    = masteriyo_find_block_builder_class( $block_data['blockName'] );
		$block_builder = new $class_name( $block_data, $pdf );

		/**
		 * Filters block builder instance.
		 *
		 * @since 1.13.0
		 *
		 * @param \Masteriyo\Addons\Certificate\PDF\BlockBuilders\Block $block_builder
		 * @param array $block_data Block data.
		 * @param \Masteriyo\Addons\Certificate\PDF\CertificatePDF $pdf
		 */
		return apply_filters( 'masteriyo_block_builder_instance', $block_builder, $block_data, $pdf );
	}
}

if ( ! function_exists( 'masteriyo_find_block_builder_class' ) ) {
	/**
	 * Find HTML builder class for a block.
	 *
	 * @since 1.13.0
	 *
	 * @param string $block_name
	 *
	 * @return string
	 */
	function masteriyo_find_block_builder_class( $block_name ) {
		$class_name = str_replace( '/', '', ucwords( $block_name, '/' ) );
		$class_name = str_replace( '-', '', ucwords( $class_name, '-' ) );
		$namespace  = 'Masteriyo\\Addons\\Certificate\\PDF\\BlockBuilders\\';
		$class_name = $namespace . $class_name;

		if ( ! class_exists( $class_name ) ) {
			$class_name = Fallback::class;
		}

		/**
		 * Filters HTML builder class for a block.
		 *
		 * @since 1.13.0
		 *
		 * @param string $class_name
		 * @param string $block_name
		 */
		return apply_filters( 'masteriyo_block_builder_class', $class_name, $block_name );
	}
}

if ( ! function_exists( 'masteriyo_sanitize_pdf_page_size' ) ) {
	/**
	 * Validate PDF page size.
	 *
	 * @since 1.13.0
	 *
	 * @param string $page_size
	 * @param string $default
	 *
	 * @return string 'Letter' or 'A4'
	 */
	function masteriyo_sanitize_pdf_page_size( $page_size, $default = 'Letter' ) {
		$page_size = ucwords( $page_size );

		if ( in_array( $page_size, array( 'Letter', 'A4' ), true ) ) {
			return $page_size;
		}
		return $default;
	}
}

if ( ! function_exists( 'masteriyo_sanitize_pdf_page_orientation' ) ) {
	/**
	 * Validate PDF page size.
	 *
	 * @since 1.13.0
	 *
	 * @param string $orientation
	 * @param string $default
	 *
	 * @return string 'P' for portrait and 'L' for landscape.
	 */
	function masteriyo_sanitize_pdf_page_orientation( $orientation, $default = 'L' ) {
		if ( 'portrait' === $orientation ) {
			return 'P';
		}
		if ( 'landscape' === $orientation ) {
			return 'L';
		}

		$orientation = strtoupper( $orientation );

		if ( in_array( $orientation, array( 'L', 'P' ), true ) ) {
			return $orientation;
		}
		return $default;
	}
}

if ( ! function_exists( 'masteriyo_get_certificate_color_presets' ) ) {
	/**
	 * Get color presets for certificate.
	 *
	 * @since 1.13.0
	 *
	 * @return array
	 */
	function masteriyo_get_certificate_color_presets() {
		$color_presets = array(
			'black'                 => array(
				'name'  => __( 'Black', 'learning-management-system' ),
				'slug'  => 'black',
				'color' => '#000000',
			),
			'cyan-bluish-gray'      => array(
				'name'  => __( 'Cyan bluish gray', 'learning-management-system' ),
				'slug'  => 'cyan-bluish-gray',
				'color' => '#abb8c3',
			),
			'white'                 => array(
				'name'  => __( 'White', 'learning-management-system' ),
				'slug'  => 'white',
				'color' => '#ffffff',
			),
			'pale-pink'             => array(
				'name'  => __( 'Pale pink', 'learning-management-system' ),
				'slug'  => 'pale-pink',
				'color' => '#f78da7',
			),
			'vivid-red'             => array(
				'name'  => __( 'Vivid red', 'learning-management-system' ),
				'slug'  => 'vivid-red',
				'color' => '#cf2e2e',
			),
			'luminous-vivid-orange' => array(
				'name'  => __( 'Luminous vivid orange', 'learning-management-system' ),
				'slug'  => 'luminous-vivid-orange',
				'color' => '#ff6900',
			),
			'luminous-vivid-amber'  => array(
				'name'  => __( 'Luminous vivid amber', 'learning-management-system' ),
				'slug'  => 'luminous-vivid-amber',
				'color' => '#fcb900',
			),
			'light-green-cyan'      => array(
				'name'  => __( 'Light green cyan', 'learning-management-system' ),
				'slug'  => 'light-green-cyan',
				'color' => '#7bdcb5',
			),
			'vivid-green-cyan'      => array(
				'name'  => __( 'Vivid green cyan', 'learning-management-system' ),
				'slug'  => 'vivid-green-cyan',
				'color' => '#00d084',
			),
			'pale-cyan-blue'        => array(
				'name'  => __( 'Pale cyan blue', 'learning-management-system' ),
				'slug'  => 'pale-cyan-blue',
				'color' => '#8ed1fc',
			),
			'vivid-green-cyan'      => array(
				'name'  => __( 'Vivid cyan blue', 'learning-management-system' ),
				'slug'  => 'vivid-green-cyan',
				'color' => '#00d084',
			),
			'vivid-purple'          => array(
				'name'  => __( 'Vivid purple', 'learning-management-system' ),
				'slug'  => 'vivid-purple',
				'color' => '#9b51e0',
			),
		);

		/**
		 * Filters color presets for certificate.
		 *
		 * @since 1.13.0
		 *
		 * @param array $color_presets
		 */
		return apply_filters( 'masteriyo_certificate_color_presets', $color_presets );
	}
}

if ( ! function_exists( 'masteriyo_certificate_process_color' ) ) {
	/**
	 * Check if the given color string is a preset color name and return actual color value.
	 *
	 * @since 1.13.0
	 *
	 * @param string $color
	 *
	 * @return string
	 */
	function masteriyo_certificate_process_color( $color ) {
		$presets = masteriyo_get_certificate_color_presets();

		if ( isset( $presets[ $color ] ) ) {
			return $presets[ $color ]['color'];
		}
		return $color;
	}
}

if ( ! function_exists( 'masteriyo_percent_to_amount' ) ) {
	/**
	 * Convert a percentage to an amount.
	 *
	 * @since 1.13.0
	 *
	 * @param float $percentage
	 * @param float $total
	 *
	 * @return float
	 */
	function masteriyo_percent_to_amount( $percentage, $total ) {
		return ( $percentage / 100 ) * $total;
	}
}

if ( ! function_exists( 'masteriyo_is_certificate_html_inspection_mode' ) ) {
	/**
	 * Returns boolean: True if certificate HTML inspection mode is enabled.
	 *
	 * If returned true, the certificate html will be outputted instead of a PDF file.
	 *
	 * @since 1.13.0
	 *
	 * @return boolean
	 */
	function masteriyo_is_certificate_html_inspection_mode() {
		/**
		 * Filters boolean: True if certificate HTML inspection mode is enabled.
		 *
		 * If true, the certificate html will be outputted instead of a PDF file.
		 *
		 * @since 1.13.0
		 *
		 * @param boolean $bool
		 */
		return apply_filters( 'masteriyo_is_certificate_html_inspection_mode', false );
	}
}

if ( ! function_exists( 'masteriyo_get_image_relative_path' ) ) {
	/**
	 * Image url to absolute path.
	 *
	 * @since 1.13.0
	 *
	 * @param string $url Image url.
	 *
	 * @return string
	 */
	function masteriyo_get_image_relative_path( $url ) {
		$relative_path = wp_make_link_relative( $url );

		return ABSPATH . $relative_path;
	}
}

if ( ! function_exists( 'masteriyo_is_certificate_enabled_for_single_course' ) ) {
	/**
	 * Check if certificate is enabled for a single course page.
	 *
	 * @since 1.13.3
	 *
	 * @param integer $$course_id
	 *
	 * @return boolean
	 */
	function masteriyo_is_certificate_enabled_for_single_course( $course_id ) {
		return masteriyo_string_to_bool( get_post_meta( $course_id, '_certificate_single_course_enabled', true ) );
	}
}


if ( ! function_exists( 'masteriyo_get_certificate_addon_view_url' ) ) {
	/**
	 * Get the certificate view url of a user.
	 *
	 * @since 1.13.3
	 *
	 * @param \Masteriyo\Models\Course $course The course object.
	 * @param int|WP_User|Masteriyo\Database\Model $user User ID, WP_User object, or Masteriyo\Database\Model object.
	 *
	 * @return string $view_url The certificate view url.
	 */
	function masteriyo_get_certificate_addon_view_url( $course, $user, $certificate_id ) {

		$certificate = masteriyo_get_certificate( $certificate_id );

		$user = masteriyo_get_user( $user );

		if ( is_null( $certificate ) || is_wp_error( $certificate ) ) {
			return '';
		}

		$view_url = add_query_arg(
			array(
				'course_id'      => $course->get_id(),
				'certificate_id' => $certificate_id,
				'username'       => $user->get_username(),
			),
			// masteriyo_get_certificate_share_url( $user->get_username() ) // This is will be used later
			home_url( '/' )
		);

		return $view_url;
	}
}

// TODO - This function will be used later
// if ( ! function_exists( 'masteriyo_get_certificate_share_url' ) ) {
// 	/**
// 	 * Retrieve the public profile URL for a masteriyo student/instructor.
// 	 *
// 	 * @since 1.13.3
// 	 *
// 	 * @param string $username Username of the user.
// 	 * @return string The URL to the user's public profile.
// 	 */
// 	function masteriyo_get_certificate_share_url( $username ) {


// 		$structure = get_option( 'permalink_structure' );

// 		if ( 'plain' === $structure || '' === $structure ) {
// 			$slug = '?username=' . $username;
// 		} else {
// 			$slug = $username;
// 		}

// 		return home_url( $slug );
// 	}
// }

if ( ! function_exists( 'masteriyo_get_user_by_username_certificate' ) ) {
	/**
	 * Get the masteriyo user from username.
	 *
	 * @since 1.13.3
	 *
	 * @param string $username The username to validate.
	 *
	 * @return WP_User|Masteriyo\Database\Model|bool Returns Masteriyo\Database\Model object if valid and exists, false otherwise.
	 */
	function masteriyo_get_user_by_username_certificate( $username ) {

		$username = masteriyo_validate_username_certificate( $username );

		if ( ! $username ) {
			return false;
		}

		$user = get_user_by( 'login', $username );

		if ( $user ) {

			$user = masteriyo_get_user( $user );

			if ( ! $user || is_wp_error( $user ) ) {
				return false;
			}

			if ( ! ( $user->has_role( Roles::STUDENT ) || $user->has_role( Roles::INSTRUCTOR ) || $user->has_role( Roles::ADMIN ) ) ) {
				return false;
			}

			return $user;
		}

		return false;
	}
}

if ( ! function_exists( 'masteriyo_validate_username_certificate' ) ) {
	/**
	 * Validates a username.
	 *
	 * @since 1.13.3
	 *
	 * @param string $username The username to validate.
	 *
	 * @return string|bool The validated username if valid and exists, false otherwise.
	 */
	function masteriyo_validate_username_certificate( $username ) {

		if ( empty( $username ) ) {
			return false;
		}

		$username = sanitize_text_field( $username );
		$user     = get_user_by( 'login', $username );

		if ( $user ) {
			return $username;
		}

		return false;
	}
}
