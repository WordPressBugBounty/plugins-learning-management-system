/**
 * Initializes a Swiper carousel.
 *
 * @since 1.13.0
 *
 * @param {jQuery} $ - The jQuery library.
 * @returns {void}
 */

'use strict';

(function ($) {
	var defaultData = {
		columns: 3,
		space_between: 0,
		reverse_direction: false,
		delay: 2500,
		infinite_loop: false,
		autoplay: true,
		speed: 600,
		navigation: true,
		pagination: false,
		centeredSlides: false,
		pauseOnHover: true,
		scrollbar: false,
		breakpoints: {
			320: {
				slidesPerView: 1,
				spaceBetween: 0,
			},
			768: {
				slidesPerView: 2,
				spaceBetween: 0,
			},
			1024: {
				slidesPerView: 3,
				spaceBetween: 0,
			},
		},
		rewind: true,
	};
	/**
	 * Initializes a Swiper carousel for course listings on the frontend.
	 *
	 * This function takes a jQuery element representing the course carousel and initializes a Swiper slider with the provided configuration options.
	 *
	 * @since 1.13.0
	 *
	 * @param {jQuery} element - The jQuery element representing the course carousel.
	 * @param {string} swiperClass - The custom class name for the Swiper container.
	 * @returns {void}
	 */
	function initializeSwiper(element, swiperClass) {
		var sliderData = $(element).data('settings');

		if (typeof sliderData === 'string') {
			sliderData = JSON.parse(sliderData);
		}

		var data = $.extend(true, {}, defaultData, sliderData);

		var swiperConfig = {
			direction: 'horizontal',
			slidesPerView: parseInt(data.columns, 10),
			spaceBetween: parseInt(data.space_between, 10),
			centeredSlides: !!data.centeredSlides,
			breakpoints: data.breakpoints,
			autoplay: !!data.autoplay
				? {
						delay: parseInt(data.delay, 10),
						reverseDirection: !!data.reverse_direction,
						pauseOnMouseEnter: !!data.pauseOnHover,
						disableOnInteraction: false,
					}
				: false,
			loop: !!data.infinite_loop,
			speed: parseInt(data.speed, 10),
			navigation: !!data.navigation
				? {
						nextEl: element.find('.swiper-button-next')[0],
						prevEl: element.find('.swiper-button-prev')[0],
					}
				: false,
			pagination: !!data.pagination
				? {
						el: element.find('.swiper-pagination')[0],
						clickable: true,
					}
				: false,
			scrollbar: !!data.scrollbar
				? {
						el: element.find('.swiper-scrollbar')[0],
						hide: false,
					}
				: false,
			rewind: !!data.rewind,
		};

		new Swiper(`.${swiperClass}.swiper`, swiperConfig);
	}

	/**
	 * Initializes all Swiper carousels on the page if the current page is not an Elementor page.
	 *
	 * @since 1.13.0
	 *
	 * @returns {void}
	 */
	function initializeSwiperForNonElementorPage() {
		$('.masteriyo-course-carousel').each(function () {
			initializeSwiper($(this), 'masteriyo-courses-wrapper');
		});
		$('.masteriyo-category-carousel').each(function () {
			initializeSwiper($(this), 'masteriyo-course-categories');
		});
	}

	/**
	 * Initializes all Swiper carousels on the page if the current page is an Elementor page.
	 *
	 * @since 1.13.0
	 *
	 * @returns {void}
	 */
	$(window).on('elementor/frontend/init', function () {
		/**
		 * Initializes the Masteriyo Course Carousel Elementor widget.
		 *
		 * This function is called when the 'masteriyo-course-carousel.default' Elementor widget is ready on the frontend.
		 * It finds the course carousel element on the page and initializes the Swiper slider.
		 *
		 * @since 1.13.0
		 *
		 *
		 * @param {jQuery} $scope - The jQuery object of the Elementor widget.
		 * @param {jQuery} $ - The jQuery instance.
		 */
		elementorFrontend.hooks.addAction(
			'frontend/element_ready/masteriyo-course-carousel.default',
			function ($scope, $) {
				var $element = $scope.find('.masteriyo-course-carousel');
				if ($element.length) {
					initializeSwiper($element, 'masteriyo-courses-wrapper');
				}
			},
		);

		/**
		 * Initializes the Masteriyo Category Carousel Elementor widget.
		 *
		 * This function is called when the 'masteriyo-category-carousel.default' Elementor widget is ready on the frontend.
		 * It finds the category carousel element on the page and initializes the Swiper slider.
		 *
		 * @since 1.13.0
		 *
		 * @param {jQuery} $scope - The jQuery object of the Elementor widget.
		 * @param {jQuery} $ - The jQuery instance.
		 */
		elementorFrontend.hooks.addAction(
			'frontend/element_ready/masteriyo-category-carousel.default',
			function ($scope, $) {
				var $element = $scope.find('.masteriyo-category-carousel');
				if ($element.length) {
					initializeSwiper($element, 'masteriyo-course-categories');
				}
			},
		);
	});

	/**
	 * Initializes all Swiper carousels on the page if the current page is not an Elementor page.
	 *
	 * @since 1.13.0 [Free]
	 */
	$(document).ready(function () {
		if (typeof elementorFrontend === undefined) {
			initializeSwiperForNonElementorPage();
		}
	});
})(jQuery);
