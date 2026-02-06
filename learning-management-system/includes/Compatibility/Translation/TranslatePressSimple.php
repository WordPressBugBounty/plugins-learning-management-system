<?php
/**
 * TranslatePress compatibility - SIMPLE VERSION
 *
 * This is a minimal, safe integration that just registers post types and taxonomies.
 * TranslatePress handles everything else automatically.
 *
 * @package Masteriyo\Compatibility\Translation
 * @since 2.1.0
 */

namespace Masteriyo\Compatibility\Translation;

defined( 'ABSPATH' ) || exit;

use Masteriyo\PostType\PostType;
use Masteriyo\Taxonomy\Taxonomy;

/**
 * TranslatePress compatibility class - SIMPLE VERSION
 *
 * @since 2.1.0
 */
class TranslatePressSimple {

	/**
	 * Initialize.
	 *
	 * @since 2.1.0
	 */
	public function init() {
		if ( ! $this->is_plugin_active() ) {
			return;
		}

		// Register post types for translation.
		add_filter( 'trp_register_custom_post_types', array( $this, 'register_post_types' ), 10, 1 );

		// Register taxonomies for translation.
		add_filter( 'trp_register_custom_taxonomies', array( $this, 'register_taxonomies' ), 10, 1 );

		// Add REST API translation support for React pages.
		add_action( 'rest_api_init', array( $this, 'add_rest_api_translation' ) );

		// Add JavaScript localization for React.
		add_action( 'admin_enqueue_scripts', array( $this, 'localize_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'localize_scripts' ) );

		// Enable gettext translation for Masteriyo strings.
		add_filter( 'trp_skip_gettext_processing', array( $this, 'enable_ajax_translation' ), 10, 4 );

		// Force process Masteriyo domain strings.
		add_filter( 'trp_register_gettext_domain', array( $this, 'register_gettext_domain' ) );

		// Register Masteriyo text domain for TranslatePress scanning.
		add_filter( 'trp_translatable_strings', array( $this, 'add_translatable_strings' ), 10, 3 );

		// Enable dynamic string detection for React content.
		add_filter( 'trp_enable_dynamic_translation', '__return_true' );

		// Add string scanning for AJAX content.
		add_action( 'wp_ajax_masteriyo_get_strings', array( $this, 'ajax_get_translatable_strings' ) );
		add_action( 'wp_ajax_nopriv_masteriyo_get_strings', array( $this, 'ajax_get_translatable_strings' ) );

		// Flush rewrite rules when TranslatePress settings change.
		add_action( 'trp_settings_saved', 'flush_rewrite_rules' );

		// Maybe flush rewrite rules on first load.
		add_action( 'init', array( $this, 'maybe_flush_rewrite_rules' ), 999 );
	}

	/**
	 * Check if TranslatePress is active.
	 *
	 * @since 2.1.0
	 * @return bool
	 */
	protected function is_plugin_active() {
		return function_exists( 'is_plugin_active' ) && is_plugin_active( 'translatepress-multilingual/index.php' );
	}

	/**
	 * Register Masteriyo post types with TranslatePress.
	 *
	 * @since 2.1.0
	 * @param array $post_types Existing post types.
	 * @return array
	 */
	public function register_post_types( $post_types ) {
		$masteriyo_post_types = ( new PostType() )->all();

		if ( ! is_array( $post_types ) ) {
			$post_types = array();
		}

		return array_unique( array_merge( $post_types, $masteriyo_post_types ) );
	}

	/**
	 * Register Masteriyo taxonomies with TranslatePress.
	 *
	 * @since 2.1.0
	 * @param array $taxonomies Existing taxonomies.
	 * @return array
	 */
	public function register_taxonomies( $taxonomies ) {
		$masteriyo_taxonomies = ( new Taxonomy() )->all();

		if ( ! is_array( $taxonomies ) ) {
			$taxonomies = array();
		}

		return array_unique( array_merge( $taxonomies, $masteriyo_taxonomies ) );
	}

	/**
	 * Add REST API translation support.
	 *
	 * Ensures REST API requests include language context.
	 *
	 * @since 2.1.0
	 */
	public function add_rest_api_translation() {
		// Allow TranslatePress to translate REST API responses.
		add_filter( 'rest_pre_echo_response', array( $this, 'translate_rest_response' ), 10, 3 );
	}

	/**
	 * Translate REST API response.
	 *
	 * @since 2.1.0
	 * @param mixed            $result Response to send.
	 * @param WP_REST_Server   $server Server instance.
	 * @param WP_REST_Request  $request Request used to generate the response.
	 * @return mixed
	 */
	public function translate_rest_response( $result, $server, $request ) {
		// Get language from request parameter.
		$language = $request->get_param( 'trp-form-language' );

		if ( $language && class_exists( 'TRP_Translate_Press' ) ) {
			// Set the language for this request.
			global $TRP_LANGUAGE;
			$TRP_LANGUAGE = $language;
		}

		return $result;
	}

	/**
	 * Localize TranslatePress data to JavaScript.
	 *
	 * @since 2.1.0
	 */
	public function localize_scripts() {
		if ( ! class_exists( 'TRP_Translate_Press' ) ) {
			return;
		}

		$trp          = \TRP_Translate_Press::get_trp_instance();
		$trp_settings = $trp->get_component( 'settings' );

		if ( ! $trp_settings ) {
			return;
		}

		$settings = $trp_settings->get_settings();

		// Get current language using TranslatePress's URL converter.
		$current_language = $settings['default-language'];
		$url_converter    = $trp->get_component( 'url_converter' );
		if ( $url_converter && method_exists( $url_converter, 'get_lang_from_url_string' ) ) {
			$detected_lang = $url_converter->get_lang_from_url_string();
			if ( $detected_lang ) {
				$current_language = $detected_lang;
			}
		}

		// Prepare data for JavaScript.
		$translatepress_data = array(
			'currentLanguage' => $current_language,
			'defaultLanguage' => $settings['default-language'],
			'languages'       => $settings['publish-languages'],
			'urlStructure'    => $settings['url-structure'] ?? 'subdirectory',
		);

		// Add to Masteriyo global object.
		wp_localize_script( 'masteriyo-admin', 'masteriyo_translatepress', $translatepress_data );
		wp_localize_script( 'masteriyo', 'masteriyo_translatepress', $translatepress_data );
	}

	/**
	 * Enable AJAX translation.
	 *
	 * @since 2.1.0
	 * @param bool   $skip Skip gettext processing.
	 * @param string $translation Translated text.
	 * @param string $text Original text.
	 * @param string $domain Text domain.
	 * @return bool
	 */
	public function enable_ajax_translation( $skip, $translation, $text, $domain ) {
		// Don't skip translation for Masteriyo domain.
		if ( 'learning-management-system' === $domain ) {
			return false;
		}

		return $skip;
	}

	/**
	 * Register Masteriyo gettext domain with TranslatePress.
	 *
	 * @since 2.1.0
	 * @param array $domains Registered domains.
	 * @return array
	 */
	public function register_gettext_domain( $domains ) {
		if ( ! is_array( $domains ) ) {
			$domains = array();
		}

		// Add Masteriyo domain.
		$domains[] = 'learning-management-system';

		return array_unique( $domains );
	}

	/**
	 * Maybe flush rewrite rules on first load.
	 *
	 * @since 2.1.0
	 */
	public function maybe_flush_rewrite_rules() {
		$flushed = get_option( 'masteriyo_translatepress_rewrite_flushed' );

		if ( ! $flushed ) {
			flush_rewrite_rules( false );
			update_option( 'masteriyo_translatepress_rewrite_flushed', true, false );
		}
	}

	/**
	 * Add Masteriyo translatable strings to TranslatePress.
	 *
	 * This ensures JavaScript strings from React are translatable.
	 *
	 * @since 2.1.0
	 * @param array  $strings Translatable strings.
	 * @param string $language Language code.
	 * @param array  $context Translation context.
	 * @return array
	 */
	public function add_translatable_strings( $strings, $language, $context ) {
		// Get all Masteriyo JavaScript translations.
		$jed_data = array();

		// Load translations for the Masteriyo domain.
		if ( function_exists( 'load_script_textdomain' ) ) {
			// Get translation file path.
			$locale    = determine_locale();
			$mo_file   = WP_LANG_DIR . '/plugins/learning-management-system-' . $locale . '.mo';
			$json_file = WP_LANG_DIR . '/plugins/learning-management-system-' . $locale . '-' . md5( 'masteriyo-admin' ) . '.json';

			// Try to load JSON translations.
			if ( file_exists( $json_file ) ) {
				$translations = file_get_contents( $json_file );
				$jed_data     = json_decode( $translations, true );
			}
		}

		// Add strings from Jed data.
		if ( ! empty( $jed_data['locale_data']['messages'] ) ) {
			foreach ( $jed_data['locale_data']['messages'] as $original => $translation ) {
				if ( ! empty( $original ) && is_array( $translation ) ) {
					$strings[] = array(
						'original' => $original,
						'domain'   => 'learning-management-system',
					);
				}
			}
		}

		return $strings;
	}

	/**
	 * AJAX handler to get translatable strings.
	 *
	 * @since 2.1.0
	 */
	public function ajax_get_translatable_strings() {
		// This endpoint helps TranslatePress discover strings from AJAX requests.
		wp_send_json_success(
			array(
				'domain'  => 'learning-management-system',
				'strings' => array(), // TranslatePress will scan the response.
			)
		);
	}
}
