<?php

/**
 * Integration class for SureCart Plugin.
 *
 * @since 1.12.0
 * @package Masteriyo\Addons\SureCartIntegration
 */

namespace Masteriyo\Addons\SureCartIntegration;

use Masteriyo\Constants;
use Masteriyo\Enums\CourseProgressStatus;
use Masteriyo\Query\CourseProgressQuery;
use SureCart\Integrations\IntegrationService;
use SureCart\Integrations\Contracts\IntegrationInterface;
use SureCart\Integrations\Contracts\PurchaseSyncInterface;
use SureCart\Models\Integration;
use SureCart\Models\Price;
use SureCart\Support\Currency;

defined( 'ABSPATH' ) || exit;

class SureCartService extends IntegrationService implements IntegrationInterface, PurchaseSyncInterface {

	public function bootstrap() {
		parent::bootstrap();

		add_filter( 'masteriyo_course_add_to_cart_url', array( $this, 'change_add_to_cart_url' ), 10, 2 );
		add_filter( 'masteriyo_start_course_url', array( $this, 'change_add_to_cart_url' ), 10, 2 );

		add_filter( 'masteriyo_single_course_add_to_cart_text', array( $this, 'add_to_cart_btn_text' ), 10, 2 );
		add_filter( 'masteriyo_course_add_to_cart_text', array( $this, 'add_to_cart_btn_text' ), 10, 2 );
		add_filter( 'masteriyo_single_course_start_text', array( $this, 'add_to_cart_btn_text' ), 10, 2 );

		add_action( 'masteriyo_single_course_sidebar_content', array( $this, 'render_surecart_sidebar_content' ), 15 );
		add_filter( 'masteriyo_get_template', array( $this, 'change_template_for_surecart_courses' ), 10, 5 );

		add_action( 'masteriyo_layout_1_single_course_aside_items', array( $this, 'render_surecart_sidebar_content_layout_1' ), 15 );

		add_action( 'wp_footer', array( $this, 'add_surecart_courses_popup_modal' ), 15 );

		add_filter( 'masteriyo_enroll_button_class', array( $this, 'enroll_button_class' ), 10, 3 );

		add_filter( 'masteriyo_localized_public_scripts', array( $this, 'localize_surecart_courses_scripts' ) );

		add_action( 'masteriyo_rest_api_register_course_routes', array( $this, 'register_rest_api_course_routes' ), 10, 3 );

		add_filter( 'masteriyo_price', array( $this, 'add_course_price_action' ), 10, 5 );
		add_filter( 'masteriyo_add_to_cart_button_attributes', array( $this, 'add_course_attributes' ), 10, 2 );

		// add_filter( 'masteriyo_rest_response_course_data', array( $this, 'change_course_price' ), 10, 4 ); //Can be used in the future.
	}

	/**
	 *
	 * Add course attributes.
	 *
	 * @since 1.12.0
	 *
	 * @param array $attr
	 * @param Masteriyo\Models\Course $course
	 *
	 * @return array additional attributes
	 */
	public function add_course_attributes( $attr, $course ) {

		if ( is_null( $course ) ) {
			return $attr;
		}

		$activity = masteriyo_check_user_course_activity( $course->get_id() );

		if ( 'active' === $activity ) {
			$attr['data-course-activity'] = 'started';
			return $attr;
		}

		$prices = $this->masteriyo_check_integration_and_price( $course->get_id() );

		if ( $prices === $course->get_id() ) {
			return $attr;
		}

		$count_prices = count( $prices );

		if ( $count_prices > 1 ) {
			$attr['data-prices-count']    = 'multiple';
			$attr['data-course-activity'] = 'not-started';

			return $attr;
		} else {
			$attr['data-prices-count']    = 'single';
			$attr['data-product-id']      = $prices[0]->id;
			$attr['data-course-activity'] = 'not-started';
			return $attr;
		}
	}

	/**
	 * Registers the REST API routes for the Surecart Integration addon.
	 *
	 * @since 1.12.0
	 *
	 * @param string $namespace The API namespace.
	 * @param string $rest_base The REST base.
	 * @param \Masteriyo\RestApi\Controllers\Version1\CoursesController $controller The Courses controller instance.
	 */
	public function register_rest_api_course_routes( $namespace, $rest_base, $controller ) {

		register_rest_route(
			$namespace,
			'/' . $rest_base . '/surecart-prices',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'masteriyo_route_check_integration_and_price' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'course_id' => array(
							'default'     => 0,
							'description' => __( 'Course ID.', 'learning-management-system' ),
							'required'    => true,
							'type'        => 'integer',
						),
					),
				),
			)
		);
	}

	/**
	 * Localize surecart course page scripts.
	 *
	 * @since 1.12.0
	 *
	 * @param array $scripts
	 *
	 * @return array
	 */
	public function localize_surecart_courses_scripts( $scripts ) {
		$url = add_query_arg(
			array(
				'line_items' => array(
					array(
						'price_id' => '',
						'quantity' => 1,
					),
				),
			),
			\SureCart::pages()->url( 'checkout' )
		);

		$add_to_cart_text = __( 'Add to Cart', 'learning-management-system' );
		$fetching_text    = __( 'Fetching Prices...', 'learning-management-system' );

		$surecart_courses_scripts = array(
			'masteriyo-surecart-courses-course-archive' => array(
				'name' => 'MASTERIYO_SURECART_COURSES_DATA',
				'data' => array(
					'add_to_cart_url'  => $url,
					'restUrl'          => rest_url( 'masteriyo/v1/courses/surecart-prices' ),
					'add_to_cart_text' => esc_html( $add_to_cart_text ),
					'fetching_text'    => esc_html( $fetching_text ),
				),
			),
		);

		$scripts = masteriyo_parse_args(
			$scripts,
			$surecart_courses_scripts
		);

		return $scripts;
	}

	/**
	 * Renders a popup modal for surecart courses on courses pages.
	 *
	 * @since 1.12.0
	 *
	 * @return void
	 */
	public function add_surecart_courses_popup_modal() {
		if ( masteriyo_is_courses_page() ) {
			masteriyo_get_template(
				'sure-cart-integration/add-to-cart-modal.php',
				array(
					'prices' => '',
					'course' => '',
				)
			);
		}
	}

	/**
	 * Append required class for enroll button.
	 *
	 * @since 1.12.0
	 *
	 * @param string[] $class An array of class names.
	 * @param \Masteriyo\Models\Course $course Course object.
	 * @param \Masteriyo\Models\CourseProgress $progress Course progress object.
	 *
	 * @return string[]
	 */
	public function enroll_button_class( $class, $course, $progress ) {

		if ( is_null( $course ) ) {
			return $class;
		}

		$prices = $this->masteriyo_check_integration_and_price( $course->get_id() );

		if ( $prices === $course->get_id() ) {
			return $class;
		}

		if ( masteriyo_is_single_course_page() ) {

			$activity = masteriyo_check_user_course_activity( $course->get_id() );

			if ( 'active' === $activity ) {
				return $class;
			}

			foreach ( $prices as $price ) {
				$class   = array_filter(
					$class,
					function ( $c ) {
						return 'masteriyo-btn-primary' !== $c && 'masteriyo-btn' !== $c;
					}
				);
				$class[] = 'masteriyo-hidden';
			}
			return $class;
		}

		foreach ( $prices as $price ) {
			$class[] = 'masteriyo-surecart-course-btn';
			return $class;
		}

		return $class;
	}

	/**
	 * Change to SureCart Add to Cart URL.
	 *
	 * @since 1.12.0
	 *
	 * @param string $url
	 * @param Masteriyo\Models\Course $course
	 *
	 * @return string
	 */
	public function change_add_to_cart_url( $url, $course ) {

		if ( is_null( $course ) ) {
			return $url;
		}

		$activity = masteriyo_check_user_course_activity( $course->get_id() );

		if ( 'active' === $activity ) {
			return $url;
		}

		$prices = $this->masteriyo_check_integration_and_price( $course->get_id() );

		if ( $prices === $course->get_id() ) {
			return $url;
		}

		$count_prices = count( $prices );

		if ( 1 === $count_prices ) {
			foreach ( $prices as $price ) {
				$url = add_query_arg(
					array(
						'line_items' => array(
							array(
								'price_id' => $price->id,
								'quantity' => 1,
							),
						),
					),
					\SureCart::pages()->url( 'checkout' )
				);
			}
			return $url;
		} else {
			$url = '#';
			return $url;
		}
	}

	/**
	 * Render surecart sidebar content for default layout.
	 *
	 * @since 1.12.0
	 *
	 * @param \Masteriyo\Models\Course $course Course object.
	 * @return void
	 */
	public function render_surecart_sidebar_content( $course ) {

		if ( is_null( $course ) ) {
			return;
		}

		$prices = $this->masteriyo_check_integration_and_price( $course->get_id() );

		if ( $prices === $course->get_id() ) {
			return;
		}

		masteriyo_get_template(
			'sure-cart-integration/add-to-cart-btn.php',
			array(
				'layout' => 'layout',
				'prices' => $prices,
			)
		);
	}

	/**
	 * Render surecart sidebar content for layout 1.
	 *
	 * @since 1.12.0
	 *
	 * @param \Masteriyo\Models\Course $course Course object.
	 * @return void
	 */
	public function render_surecart_sidebar_content_layout_1( $course ) {

		if ( is_null( $course ) ) {
			return;
		}

		$prices = $this->masteriyo_check_integration_and_price( $course->get_id() );

		if ( $prices === $course->get_id() ) {
			return;
		}

		masteriyo_get_template(
			'sure-cart-integration/add-to-cart-btn.php',
			array(
				'layout' => 'layout-1',
				'prices' => $prices,
			)
		);
	}

	/**
	 * Change template for courses template in single course page.
	 *
	 * @since 1.12.0
	 *
	 * @param string $template Template path.
	 * @param string $template_name Template name.
	 * @param array $args Template arguments.
	 * @param string $template_path Template path from function parameter.
	 * @param string $default_path Default templates directory path.
	 *
	 * @return string
	 */
	public function change_template_for_surecart_courses( $template, $template_name, $args, $template_path, $default_path ) {

		$template_map = array(
			'sure-cart-integration/add-to-cart-btn.php'   => 'add-to-cart-btn.php',
			'sure-cart-integration/add-to-cart-modal.php' => 'add-to-cart-modal.php',
		);

		if ( isset( $template_map[ $template_name ] ) ) {
			$new_template = trailingslashit( Constants::get( 'MASTERIYO_SURECART_INTEGRATION_TEMPLATES' ) ) . $template_map[ $template_name ];

			return file_exists( $new_template ) ? $new_template : $template;
		}

		return $template;
	}

	/**
	 * Change to SureCart Add to Cart text.
	 *
	 * @since 1.12.0
	 *
	 * @param string $text
	 * @param Masteriyo\Models\Course $course
	 *
	 * @return string
	 */
	public function add_to_cart_btn_text( $text, $course ) {

		if ( is_null( $course ) ) {
			return $text;
		}

		$activity = masteriyo_check_user_course_activity( $course->get_id() );

		if ( 'active' === $activity ) {
			return $text;
		}

		$prices = $this->masteriyo_check_integration_and_price( $course->get_id() );

		if ( $prices === $course->get_id() ) {
			return $text;
		}

		foreach ( $prices as $price ) {
			$text = __( 'Add to Cart', 'learning-management-system' );
			return $text;
		}

		return $text;
	}

	/**
	 * Update the price for integrated course.
	 *
	 * @since 1.12.0
	 *
	 * @param int $id
	 * @param Masteriyo\Models\Course $course
	 *
	 * @return void
	 */
	public function add_course_price_action( $html, $price, $args, $unformatted_price, $course ) {

		if ( masteriyo_is_single_course_page() || masteriyo_is_courses_page() ) {

			$prices = $this->masteriyo_check_integration_and_price( $course->get_id() );

			$id = $course->get_id();

			if ( $prices === $course->get_id() ) {
				return $html;
			}

			$count_prices = count( $prices );

			if ( $count_prices > 2 ) {
				$paid_text = __( 'Paid', 'learning-management-system' );
				$html      = '<span class="masteriyo-price-amount amount"><bdi> ' . $paid_text . '  </bdi></span>';
				return $html;
			} else {
				foreach ( $prices as $price ) {
					$now[] = Currency::format( $price->amount, $price->currency );
				}
				$now  = implode( ',</br>', $now );
				$html = '<span class="masteriyo-price-amount amount"><bdi> ' . $now . '  </bdi></span>';
				return $html;
			}
		}
		return $html;
	}

	/**
	 * Get the cached products prices.
	 *
	 * @since 1.12.0
	 *
	 * @param array $args The product args.
	 *
	 * @return array $prices.
	 */
	public function masteriyo_check_integration_and_price( $course_id ) {

		if ( is_object( $course_id ) ) {
			$course_id = $course_id['course_id'];
		}

		$integrations = Integration::where( 'integration_id', $course_id )->andWhere( 'model_name', 'product' )->get();

		if ( empty( $integrations ) ) {
			return $course_id;
		}
		$product_ids = array_column( $integrations, 'model_id' );

		if ( empty( $product_ids ) ) {
			return $course_id;
		}
		$prices = $this->getCachedProductsPrices( $product_ids );
		if ( empty( $prices ) ) {
			return $course_id;
		}

		$prices = (array) $prices;
		return $prices;
	}

	/**
	 * Get the cached products prices.
	 *
	 * @since 1.12.0
	 *
	 * @param array $args The product args.
	 *
	 * @return array $prices.
	 */
	public function masteriyo_route_check_integration_and_price( $course_id ) {

		if ( is_object( $course_id ) ) {
			$course_id = $course_id['course_id'];
		}

		$course = masteriyo_get_course( $course_id );

		$integrations = Integration::where( 'integration_id', $course_id )->andWhere( 'model_name', 'product' )->get();

		if ( empty( $integrations ) ) {
			return $course_id;
		}
		$product_ids = array_column( $integrations, 'model_id' );

		if ( empty( $product_ids ) ) {
			return $course_id;
		}
		$prices = $this->getCachedProductsPrices( $product_ids );
		if ( empty( $prices ) ) {
			return $course_id;
		}

		$prices = (array) $prices;

		foreach ( $prices as $price ) {
			$price['actual_amount'] = Currency::format( $price->amount, $price->currency );
			$price['course_name']   = $course->get_name();
			$price['course_id']     = $course->get_id();
		}

		return $prices;
	}

	/**
	 * Get cached products prices function
	 *
	 * @since 1.12.0
	 *
	 * @param array $product_ids
	 * @return void
	 */
	public function getCachedProductsPrices( $product_ids = array() ) {
		$prices = array();
		foreach ( $product_ids as $product_id ) {
			$prices = array_merge( $prices, $this->getCachedProductPrices( $product_id ) );
		}
		return $prices;
	}

	/**
	 * Get cached product prices function
	 *
	 * @since 1.12.0
	 *
	 * @param [type] $product_id
	 * @return void
	 */
	public function getCachedProductPrices( $product_id ) {
		$prices = Price::where(
			array(
				'product_ids' => array( $product_id ),
				'archived'    => false,
			)
		)->get();
		return $prices;
	}

	/**
	 * Get the slug for the integration.
	 *
	 * @since 1.12.0
	 *
	 * @return string
	 */
	public function getName() {
		return 'masteriyo/masteriyolms-course';
	}

	/**
	 * Get the model for the integration.
	 *
	 * @since 1.12.0
	 *
	 * @return string
	 */
	public function getModel() {
		return 'product';
	}

	/**
	 * Get the slug for the integration.
	 *
	 * @since 1.12.0
	 *
	 * @return string
	 */
	public function getLogo() {
		 return esc_url_raw( trailingslashit( plugin_dir_url( MASTERIYO_SURECART_INTEGRATION_ADDON_FILE ) ) . 'masteriyolms.svg' );
	}

	/**
	 * Get the slug for the integration.
	 *
	 * @since 1.12.0
	 *
	 * @return string
	 */
	public function getLabel() {
		return __( 'MasteriyoLMS Course', 'learning-management-system' );
	}

	/**
	 * Get the slug for the integration.
	 *
	 * @since 1.12.0
	 *
	 * @return string
	 */
	public function getItemLabel() {
		return __( 'Course Access', 'learning-management-system' );
	}

	/**
	 * Get the slug for the integration.
	 *
	 * @since 1.12.0
	 *
	 * @return string
	 */
	public function getItemHelp() {
		return __( 'Enable access to a MasteriyoLMS course.', 'learning-management-system' );
	}

	/**
	 * Is this enabled?
	 *
	 * @since 1.12.0
	 *
	 * @return boolean
	 */
	public function enabled() {
		return defined( 'MASTERIYO_VERSION' );
	}

	/**
	 * Get item listing for the integration.
	 *
	 * @since 1.12.0
	 *
	 * @param array  $items The integration items.
	 * @param string $search The search term.
	 *
	 * @return array The items for the integration.
	 */
	public function getItems( $items = array(), $search = '' ) {
		if ( ! defined( 'MASTERIYO_VERSION' ) ) {
			return;
		}

		$course_query = new \WP_Query(
			array(
				'post_type' => 'mto-course',
				's'         => $search,
				'per_page'  => -1,
			)
		);

		if ( ( isset( $course_query->posts ) ) && ( ! empty( $course_query->posts ) ) ) {
			$items = array_map(
				function ( $post ) {
					return (object) array(
						'id'    => $post->ID,
						'label' => $post->post_title,
					);
				},
				$course_query->posts
			);
		}

		return $items;
	}

	/**
	 * Get the individual item.
	 *
	 * @since 1.12.0
	 *
	 * @param string $id Id for the record.
	 *
	 * @return object The item for the integration.
	 */
	public function getItem( $id ) {

		if ( ! defined( 'MASTERIYO_VERSION' ) ) {
			return;
		}

		$course = get_post( $id );
		if ( ! $course ) {
			return (object) array();
		}
		return (object) array(
			'id'             => $id,
			'provider_label' => __( 'MasteriyoLMS Course', 'learning-management-system' ),
			'label'          => $course->post_title,
		);
	}

	/**
	 * Enable Access to the course.
	 *
	 * @since 1.12.0
	 *
	 * @param \SureCart\Models\Integration $integration The integrations.
	 * @param \WP_User                     $wp_user The user.
	 *
	 * @return boolean|void Returns true if the user course access updation was successful otherwise false.
	 */
	public function onPurchaseCreated( $integration, $wp_user ) {
		$this->updateAccess( $integration->integration_id, $wp_user, true );
	}

	/**
	 * Enable access when purchase is invoked
	 *
	 * @since 1.12.0
	 *
	 * @param \SureCart\Models\Integration $integration The integrations.
	 * @param \WP_User                     $wp_user The user.
	 *
	 * @return boolean|void Returns true if the user course access updation was successful otherwise false.
	 */
	public function onPurchaseInvoked( $integration, $wp_user ) {
		$this->onPurchaseCreated( $integration, $wp_user );
	}

	/**
	 * Remove a user role.
	 *
	 * @since 1.12.0
	 *
	 * @param \SureCart\Models\Integration $integration The integrations.
	 * @param \WP_User                     $wp_user The user.
	 *
	 * @return boolean|void Returns true if the user course access updation was successful otherwise false.
	 */
	public function onPurchaseRevoked( $integration, $wp_user ) {
		$this->updateAccess( $integration->integration_id, $wp_user, false );
	}

	/**
	 * Update access to a course.
	 *
	 * @since 1.12.0
	 *
	 * @param integer  $course_id The course id.
	 * @param \WP_User $wp_user The user.
	 * @param boolean  $add True to add the user to the course, false to remove.
	 *
	 * @return boolean|void Returns true if the user course access update was successful otherwise false.
	 */
	public function updateAccess( $course_id, $wp_user, $add = true ) {
		if ( ! defined( 'MASTERIYO_VERSION' ) ) {
			return;
		}

		// update course access.
		if ( $add ) {
			return masteriyo_enroll_surecart_user( $wp_user->ID, $course_id );
		} else {
			return masteriyo_unenroll_surecart_user( $wp_user->ID, $course_id );
		}
	}
}
