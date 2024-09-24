/**
 * Implements functionality for checking surecart courses prices within the Masteriyo platform.
 *
 * @since 1.12.0
 *
 * @param {Object} $ - The jQuery object.
 * @param {Object} masteriyoData - Global data object containing API endpoints and nonces.
 */
(function ($, masteriyoData) {
	'use strict';

	var masteriyoSurecartPrices = {
		/**
		 * Initialize surecart courses functionality.
		 *
		 * @since 1.12.0
		 */
		init: function () {
			this.bindUIActions();
		},

		/**
		 * Bind event listeners to UI elements.
		 *
		 * @since 1.12.0
		 */
		bindUIActions: function () {
			$('.masteriyo-surecart-course-btn').on(
				'click',
				this.checkSurecartCourseActivity.bind(this),
			);
			$('.masteriyo-surecart-course__exit-popup').on(
				'click',
				this.closeSurecartCoursesModal.bind(this),
			);
		},

		/**
		 * Check for user activity before proceeding to the surecart courses enrollment modal.
		 *
		 * @since 1.12.0
		 *
		 * @param {Event} e - The event object.
		 */
		checkSurecartCourseActivity: function (e) {
			e.preventDefault();
			var course_activity = $(e.target)
				.data('course-activity')
				.replace(/^['"]|['"]$/g, '');
			if (course_activity === 'started') {
				var courseUrl = $(e.target).attr('href');
				window.open(courseUrl, '_blank');
			} else {
				this.checkSurecartCoursePrices(e);
			}
		},

		/**
		 * Show the surecart courses enrollment modal.
		 *
		 * @since 1.12.0
		 *
		 * @param {Event} e - The event object.
		 */
		checkSurecartCoursePrices: function (e) {
			e.preventDefault();
			var count_prices = $(e.target)
				.data('prices-count')
				.replace(/^['"]|['"]$/g, '');

			if (count_prices === 'single') {
				var product_id = $(e.target)
					.data('product-id')
					.replace(/^['"]|['"]$/g, '');
				var base_url = masteriyoData.add_to_cart_url;
				var new_url = base_url.replace(
					'price_id%5D',
					'price_id%5D=' + encodeURIComponent(product_id),
				);
				window.location.href = new_url;
			} else {
				this.openSurecartCoursesModal(e);
			}
		},

		/**
		 * Show the surecart courses enrollment modal.
		 *
		 * @since 1.12.0
		 *
		 * @param {Event} e - The event object.
		 */
		openSurecartCoursesModal: function (e) {
			e.preventDefault();
			$('#masteriyoSurecartModal').removeClass('masteriyo-hidden');
			this.fetchPrices(e);
		},

		/**
		 * Fetch prices from the server.
		 *
		 * @since 1.12.0
		 */
		fetchPrices: function (e) {
			var $loadingText = $(
				'#masteriyoSurecartModal .masteriyo-surecart-course__lists--loading-text',
			);
			// get target course id from html data attribute
			var courseID = $(e.target).data('course-id')
				? $(e.target).data('course-id')
				: 0;

			var $lists = $(
				'#masteriyoSurecartModal .masteriyo-surecart-course__lists',
			);
			var $list = $(
				'#masteriyoSurecartModal .masteriyo-surecart-course__lists--list',
			);
			var $title = $(
				'#masteriyoSurecartModal .masteriyo-surecart-course__title',
			);
			var $emptyMessage = $(
				'#masteriyoSurecartModal .masteriyo-surecart-course__empty-state',
			);

			var originalTitle = $title.text();
			var fetching_text = masteriyoData.fetching_text;
			$title.text(fetching_text);

			$lists.hide();
			$emptyMessage.hide();

			$.ajax({
				url: masteriyoData.restUrl,
				type: 'GET',
				dataType: 'json',
				data: { course_id: courseID },
				success: function (response) {
					masteriyoSurecartPrices.renderPricesList(response || []);
				},
				error: function () {
					$emptyMessage.show();
					$title.text(originalTitle);
				},
			});
		},

		/**
		 * Render the list of prices in the modal.
		 *
		 * @since 1.12.0
		 *
		 * @param {Array} prices - The groups to render.
		 */
		renderPricesList: function (prices) {
			var $lists = $(
				'#masteriyoSurecartModal .masteriyo-surecart-course__lists',
			);
			var $list = $(
				'#masteriyoSurecartModal .masteriyo-surecart-course__lists--list',
			);
			var $title = $(
				'#masteriyoSurecartModal .masteriyo-surecart-course__title',
			);
			var $emptyMessage = $(
				'#masteriyoSurecartModal .masteriyo-surecart-course__empty-state',
			);

			$list.html('');
			$title.text(prices[0].course_name);

			if (prices.length === 0) {
				$emptyMessage.show();
				$lists.hide();
				return;
			}
			$emptyMessage.hide();
			$lists.show();
			$title.show();

			prices.forEach(function (price) {
				var base_url = masteriyoData.add_to_cart_url;
				var new_url = base_url.replace(
					'price_id%5D',
					'price_id%5D=' + encodeURIComponent(price.id),
				);
				var $add_to_cart_text = masteriyoData.add_to_cart_text;
				var $item = $(`
									<li class="masteriyo-surecart-course__lists--list-item" style="list-style-type: none; margin-bottom: 10px;">
											<input type="button" style="width: 100%;" class="masteriyo-surecart-course__lists--list" name="surecart_prices" value="${$add_to_cart_text} ${
												price.actual_amount
											} ${price.name ? '(' + price.name + ')' : ''}"
											id="masteriyo-surecart-course-option-${
												price.id
											}" target="_blank" onclick="window.location.href='${new_url}'">
									</li>
							`);
				$list.append($item);
			});
		},

		/**
		 * Hide the surecart courses enrollment modal.
		 *
		 * @since 1.12.0
		 */
		closeSurecartCoursesModal: function () {
			$('#masteriyoSurecartModal').addClass('masteriyo-hidden');
		},
	};

	masteriyoSurecartPrices.init();
})(jQuery, window.MASTERIYO_SURECART_COURSES_DATA);
