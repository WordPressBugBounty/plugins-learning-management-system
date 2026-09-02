<?php
/**
 * Email class.
 *
 * @package Masteriyo\ABstracts
 *
 * @since 1.0.0
 */

namespace Masteriyo\Abstracts;

use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

defined( 'ABSPATH' ) || exit;

/**
 * Email Class.
 *
 * @since 1.0.0
 *
 * @package Masteriyo\Emails
 */
abstract class Email {

	/**
	 * Email method ID.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $id;

	/**
	 * Email HTML template.
	 *
	 * @since 1.5.35
	 *
	 * @var string
	 */
	protected $html_template;

	/**
	 * Recipient.
	 *
	 * @since 1.0.0
	 *
	 * @var string[]
	 */
	private $recipients = array();

	/**
	 * Template data.
	 *
	 * @since 1.5.35
	 *
	 * @var array
	 */
	protected $data = array();

	/**
	 * Get email additional content.
	 *
	 * @since 1.0.0
	 * @since 1.5.35 Updated as abstract function.
	 *
	 * @return string
	 */
	abstract public function get_additional_content();

	/**
	 * Get email subject.
	 *
	 * @since 1.0.0
	 * @since 1.5.35 Updated as abstract function.
	 *
	 * @return string
	 */
	abstract public function get_subject();

	/**
	 * Check if this email is enabled.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	abstract public function is_enabled();

	/**
	 * Constructor
	 *
	 * @since 1.5.35
	 */
	public function __construct() {
		$this->data = $this->get_default_data();
	}

	/**
	 * Format email string. Like processing placeholders.
	 *
	 * @since 1.0.0
	 *
	 * @param string $string Text to format.
	 *
	 * @return string
	 */
	public function format_string( $string ) {
		$string  = is_string( $string ) ? $string : '';
		$find    = array_keys( $this->get_placeholders() );
		$replace = array_values( $this->get_placeholders() );

		/**
		 * Filters formatted string in email.
		 *
		 * @since 1.0.0
		 *
		 * @param string $str Formatted string.
		 * @param Masteriyo\Emails\Email $email Email class object.
		 */
		return apply_filters( 'masteriyo_email_format_string', str_replace( $find, $replace, $string ), $this );
	}

	/**
	 * Set the locale to the site locale to make sure emails are in the site language.
	 *
	 * @since 1.0.0
	 */
	public function setup_locale() {
		/**
		 * Filters boolean: True if locale should be setup for email.
		 *
		 * @since 1.0.0
		 *
		 * @param boolean $bool True if locale should be setup for email.
		 */
		if ( apply_filters( 'masteriyo_email_setup_locale', true ) ) {
			masteriyo_switch_to_site_locale();
		}
	}

	/**
	 * Restore the locale to the default locale. Use after finished with setup_locale.
	 *
	 * @since 1.0.0
	 */
	public function restore_locale() {
		/**
		 * Filters boolean: True if the setup locale should be restored for email.
		 *
		 * @since 1.0.0
		 *
		 * @param boolean $bool True if the setup locale should be restored for email.
		 */
		if ( apply_filters( 'masteriyo_email_restore_locale', true ) ) {
			masteriyo_restore_locale();
		}
	}

	/**
	 * Send an email.
	 *
	 * @since 1.0.0
	 *
	 * @param string $to Email to.
	 * @param string $subject Email subject.
	 * @param string $message Email message.
	 * @param string $headers Email headers.
	 * @param array  $attachments Email attachments.
	 *
	 * @return bool Result
	 */
	public function send( $to, $subject, $message, $headers, $attachments ) {
		$this->setup_locale();

		add_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
		add_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ) );
		add_action( 'wp_mail_failed', array( $this, 'log_mail_failed' ) );

		/**
		 * Filters email content.
		 *
		 * @since 1.0.0
		 *
		 * @param string $content Email content.
		 * @param Masteriyo\Emails\Email $email Email class object.
		 */
		$message = apply_filters( 'masteriyo_mail_content', $this->apply_styles( $message ), $this );

		/**
		 * Filters email sender function.
		 *
		 * @since 1.0.0
		 *
		 * @param \Callable $email_sender Email sender function.
		 * @param Masteriyo\Emails\Email $email Email class object.
		 */
		$mail_callback = apply_filters( 'masteriyo_mail_callback', 'wp_mail', $this );

		/**
		 * Filters email sender function parameters.
		 *
		 * @since 1.0.0
		 *
		 * @param array $params Parameters for the email sender function.
		 * @param Masteriyo\Emails\Email $email Email class object.
		 */
		$mail_callback_params = apply_filters( 'masteriyo_mail_callback_params', array( $to, $subject, $message, $headers, $attachments ), $this );

		// No recipient means nothing to send; calling the mailer anyway fires it with a blank address.
		$return = empty( $mail_callback_params[0] ) ? false : $mail_callback( ...$mail_callback_params );

		remove_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
		remove_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
		remove_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ) );
		remove_action( 'wp_mail_failed', array( $this, 'log_mail_failed' ) );

		$this->restore_locale();

		return $return;
	}

	/**
	 * Log email delivery failure via the wp_mail_failed action.
	 *
	 * @param \WP_Error $wp_error Error object with failure details (to, subject, reason).
	 */
	public function log_mail_failed( $wp_error ) {
		$data    = $wp_error->get_error_data();
		$to      = isset( $data['to'] ) ? implode( ', ', (array) $data['to'] ) : '';
		$subject = isset( $data['subject'] ) ? $data['subject'] : '';

		masteriyo_get_logger()->error(
			sprintf(
				'Email delivery failed. Recipient: %s, Subject: %s, Reason: %s',
				$to,
				$subject,
				$wp_error->get_error_message()
			),
			array( 'source' => 'masteriyo-email' )
		);
	}

	/**
	 * Get email headers.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_headers() {
		$headers = array( 'Content-Type: ' . $this->get_content_type() . "\r\n" );

		if ( $this->get_reply_to_name() && $this->get_reply_to_address() ) {
			$headers[] = 'Reply-to: ' . $this->get_reply_to_name() . ' <' . $this->get_reply_to_address() . ">\r\n";
		}

		/**
		 * Filters email headers.
		 *
		 * @since 1.0.0
		 *
		 * @since 1.5.35 Email headers can be array.
		 *
		 * @param string|string[] $headers Email headers.
		 * @param string $headers Email object id.
		 * @param Masteriyo\Emails\Email $email Email class object.
		 */
		$headers = apply_filters( 'masteriyo_email_headers', $headers, $this->get_id(), $this );
		$headers = join( '', array_unique( $headers ) );

		return $headers;
	}

	/**
	 * Get email attachments.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_attachments() {
		/**
		 * Filters email attachments.
		 *
		 * @since 1.0.0
		 *
		 * @param array $attachments Absolute paths of attachments.
		 * @param string $headers Email object id.
		 * @param \Masteriyo\Emails\Email $email Email class object.
		 */
		return apply_filters( 'masteriyo_email_attachments', array(), $this->get_id(), $this );
	}

	/**
	 * Get email content type.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_content_type() {
		return 'text/html';
	}

	/**
	 * Apply styles to dynamic content.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content Content to apply styles on.
	 *
	 * @return string
	 */
	public function apply_styles( $content ) {
		/**
		 * Filters email styles.
		 *
		 * @since 1.0.0
		 *
		 * @param string $css Email style CSS.
		 * @param Masteriyo\Emails\Email $email Email class object.
		 */
		$css = apply_filters( 'masteriyo_email_styles', masteriyo_get_template_html( 'emails/email-styles.php' ), $this );

		try {
			$css_to_inline_styles = new CssToInlineStyles();

			/**
			 * Fires before applying CSS into an email HTML.
			 *
			 * @since 1.0.0
			 *
			 * @param \TijsVerkoyen\CssToInlineStyles\CssToInlineStyles $css_to_inline_styles Object that provides functions for converting CSS styles into inline style attributes in your HTML code.
			 * @param \Masteriyo\Emails\Email $email Email object.
			 */
			do_action( 'masteriyo_emogrifier', $css_to_inline_styles, $this );

			$content = $css_to_inline_styles->convert(
				$content,
				$css
			);

			// Remove elements with display: none.
			$dom = new \DOMDocument();
			// Suppress warnings for invalid HTML.
			\libxml_use_internal_errors( true );
			// Load HTML with UTF-8 encoding hack.
			$converted = mb_encode_numericentity(
				$content,
				array( 0x80, 0x10FFFF, 0, 0xFFFF ),
				'UTF-8'
			);

			$dom->loadHTML(
				$converted,
				LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
			);
			\libxml_clear_errors();

			$xpath = new \DOMXPath( $dom );
			// Find all elements with style attribute.
			$nodes = $xpath->query( '//*[@style]' );

			foreach ( $nodes as $node ) {
				$style = $node->getAttribute( 'style' );
				if ( preg_match( '/display\s*:\s*none/i', $style ) ) {
					$node->parentNode->removeChild( $node ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				}
			}

			$content = $dom->saveHTML( $dom->documentElement ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			$content = "<!DOCTYPE html>\n" . $content;

		} catch ( \Exception $e ) {
			masteriyo_get_logger()->error(
				$e->getMessage(),
				array( 'source' => 'email-styles' )
			);
			$content = '<style type="text/css">' . $css . '</style>' . $content;
		}

		return $content;
	}

	/**
	 * Get the from_name for outgoing emails.
	 *
	 * @since 1.0.0
	 *
	 * @param string $from_name Default wp_mail() name associated with the "from" email address.
	 *
	 * @return string
	 */
	public function get_from_name() {
		$from_name = masteriyo_get_setting( 'emails.general.from_name' );
		$from_name = is_null( $from_name ) ? '' : $from_name;
		$from_name = empty( trim( $from_name ) ) ? $this->get_site_title() : $from_name;

		/**
		 * Filters "From" value of an email.
		 *
		 * @since 1.0.0
		 *
		 * @param string $from_name From name for an email.
		 * @param Masteriyo\Emails\Email $email Email class object.
		 */
		$from_name = apply_filters( 'masteriyo_email_from_name', $from_name, $this );

		return wp_specialchars_decode( esc_html( $from_name ), ENT_QUOTES );
	}

	/**
	 * Get the from_address for outgoing emails.
	 *
	 * @since 1.0.0p
	 *
	 * @param string $from_email Default wp_mail() email address to send from.
	 *
	 * @return string
	 */
	public function get_from_address( $from_email = '' ) {
		$from_email = masteriyo_get_setting( 'emails.general.from_email' );
		$from_email = is_null( $from_email ) ? '' : $from_email;
		$from_email = empty( trim( $from_email ) ) ? get_bloginfo( 'admin_email' ) : $from_email;

		/**
		 * Filters from address of an email.
		 *
		 * @since 1.0.0
		 *
		 * @param string $from_email From email address.
		 * @param Masteriyo\Emails\Email $email Email class object.
		 */
		$from_email = apply_filters( 'masteriyo_email_from_address', $from_email, $this );

		return sanitize_email( $from_email );
	}

	/**
	 * Get the reply-to name for outgoing emails.
	 *
	 * @since 2.8.0
	 *
	 * @return string
	 */
	public function get_reply_to_name() {
		$reply_to_name = masteriyo_get_setting( 'emails.general.reply_to_name' );
		$reply_to_name = is_null( $reply_to_name ) ? '' : $reply_to_name;
		// Fall back to the "From" name, which is what the Reply-To header carried before this
		// dedicated setting existed. get_from_name() falls back to the site title itself, so an
		// unconfigured site is unchanged while a site that set a "From" name keeps it.
		$reply_to_name = empty( trim( $reply_to_name ) ) ? $this->get_from_name() : $reply_to_name;

		/**
		 * Filters "Reply-To" name value of an email.
		 *
		 * @since 2.8.0
		 *
		 * @param string $reply_to_name Reply-To name for an email.
		 * @param Masteriyo\Emails\Email $email Email class object.
		 */
		$reply_to_name = apply_filters( 'masteriyo_email_reply_to_name', $reply_to_name, $this );

		return wp_specialchars_decode( esc_html( $reply_to_name ), ENT_QUOTES );
	}

	/**
	 * Get the reply-to address for outgoing emails.
	 *
	 * @since 2.8.0
	 *
	 * @return string
	 */
	public function get_reply_to_address() {
		// `reply_to_address` is the spelling every other consumer of this setting uses --
		// SettingsController::prepare_email_data() and all 100 per-email declarations in
		// includes/Helper/email.php. Reading `reply_to_email` here meant this method could
		// never see a value the settings layer wrote.
		$reply_to_email = masteriyo_get_setting( 'emails.general.reply_to_address' );
		$reply_to_email = is_null( $reply_to_email ) ? '' : $reply_to_email;
		// Fall back to the "From" address, as the Reply-To header did before this dedicated
		// setting existed. get_from_address() falls back to the admin email itself.
		$reply_to_email = empty( trim( $reply_to_email ) ) ? $this->get_from_address() : $reply_to_email;

		/**
		 * Filters "Reply-To" email address value of an email.
		 *
		 * @since 2.8.0
		 *
		 * @param string $reply_to_email Reply-To email address.
		 * @param Masteriyo\Emails\Email $email Email class object.
		 */
		$reply_to_email = apply_filters( 'masteriyo_email_reply_to_email', $reply_to_email, $this );

		return sanitize_email( $reply_to_email );
	}

	/**
	 * Get email identifier.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get email full ID.
	 *
	 * @since 1.5.35
	 *
	 * @return string
	 */
	public function get_full_id() {
		return 'masteriyo/email/' . $this->get_id();
	}

	/**
	 * Get email identifier for Action Scheduler.
	 *
	 * @since 1.5.35
	 *
	 * @return string
	 */
	public function get_schedule_handle() {
		return 'masteriyo/schedule/email/' . $this->get_id();
	}

	/**
	 * Get email HTML template.
	 *
	 * @since 1.5.35
	 *
	 * @return string
	 */
	public function get_html_template() {
		return $this->html_template;
	}

	/**
	 * Get valid recipients.
	 *
	 * @since 1.0.0
	 *
	 * @return string|string[]
	 */
	public function get_recipients() {
		return array_filter( array_map( 'sanitize_email', $this->recipients ), 'is_email' );
	}

	/**
	 * Set recipients.
	 *
	 * @since 1.0.0
	 *
	 * @param string|string[] $recipients Email recipients.
	 */
	public function set_recipients( $recipients ) {
		$recipients = is_array( $recipients ) ? $recipients : (array) $recipients;

		$this->recipients = array_filter( array_map( 'sanitize_email', $recipients ), 'is_email' );
	}

	/**
	 * Get placeholders.
	 *
	 * @since 1.0.0
	 *
	 * @since 2.6.9 Account url was added.
	 *
	 * @return array
	 */
	public function get_placeholders() {
		return array(
			'{site_title}'   => $this->get_site_title(),
			'{site_address}' => $this->get_site_url(),
			'{site_url}'     => $this->get_site_url(),
			'{admin_email}'  => $this->get_site_admin_email(),
			'{account_url}'  => make_clickable( $this->get_account_url() ),
		);
	}

	/**
	 * Return all template data.
	 *
	 * @since 1.5.35
	 * @return array
	 */
	public function all() {
		return $this->data;
	}

	/**
	 * Return template data.
	 *
	 * @since 1.5.35
	 *
	 * @return array
	 */
	public function get( $key ) {
		return isset( $this->data[ $key ] ) ? $this->data[ $key ] : null;
	}

	/**
	 * Add data.
	 *
	 * @since 1.5.35
	 *
	 * @param string $key
	 * @param mixed $value
	 */
	public function set( $key, $value ) {
		$this->data[ $key ] = $value;
	}

	/**
	 * Delete data.
	 *
	 * @since 1.5.35
	 *
	 * @param string $key
	 */
	public function delete( $key ) {
		unset( $this->data[ $key ] );
	}

	/**
	 * Clear all the data.
	 *
	 * @since 1.5.35
	 */
	public function clear() {
		$this->data = array();
	}

	/**
	 * Get email content.
	 *
	 * @since 1.5.35
	 *
	 * @return string
	 */
	public function get_content() {
		return masteriyo_get_template_html( $this->get_html_template(), $this->all() );
	}

	/**
	 * Return site title.
	 *
	 * @since 1.5.35
	 *
	 * @return string
	 */
	public function get_site_title() {
		return get_bloginfo( 'name' );
	}

	/**
	 * Return site admin email.
	 *
	 * @since 1.5.35
	 *
	 * @return string
	 */
	public function get_site_admin_email() {
		return get_bloginfo( 'admin_email' );
	}

	/**
	 * Return site url.
	 *
	 * @since 1.5.35
	 *
	 * @return string
	 */
	public function get_site_url() {
		return wp_parse_url( home_url(), PHP_URL_HOST );
	}

	/**
	 * Get account url.
	 *
	 * @since 2.6.9
	 *
	 * @return string
	 */
	public function get_account_url() {
		return masteriyo_get_account_url();
	}

	/**
	 * Return template data.
	 *
	 * @since 1.5.35
	 *
	 * @return array
	 */
	public function get_default_data() {
		return array(
			'site_title'         => $this->get_site_title(),
			'site_address'       => $this->get_site_url(),
			'site_url'           => $this->get_site_url(),
			'admin_email'        => $this->get_site_admin_email(),
			'additional_content' => $this->get_additional_content(),
			'email'              => $this,
		);
	}
}
