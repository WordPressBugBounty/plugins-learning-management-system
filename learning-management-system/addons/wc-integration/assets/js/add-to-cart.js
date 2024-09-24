/**
 * WooCommerce add to cart functionality for courses archive/single course page.
 *
 * @since 1.11.3
 *
 * @param {Object} $ - The jQuery object.
 * @param {Object} masteriyoData - Global data object ajaxURL, nonce, WC cart url, and various texts.
 */
(function ($, masteriyoData) {
	'use strict';

	if (typeof masteriyoData === 'undefined') {
		console.error('Masteriyo data is not defined.');
		return;
	}

	var masteriyoWC = {
		/**
		 * Initialize WC add to cart functionality.
		 *
		 * @since 1.11.3
		 */
		init: function () {
			this.bindUIActions();
		},

		/**
		 * Returns the AJAX URL for making requests.
		 *
		 * @since 1.11.3
		 *
		 * @param {Object} data - The global data object ajaxURL, nonce, WC cart url, and various texts.
		 * @returns {string} The AJAX URL.
		 */
		getAjaxURL: function (data) {
			return data.ajaxURL || '';
		},

		/**
		 * Returns the nonce for the "add to cart" action.
		 *
		 * @since 1.11.3
		 *
		 * @param {Object} data - The global data object ajaxURL, nonce, WC cart url, and various texts.
		 * @returns {string} The nonce for the "add to cart" action.
		 */
		getAddToCartNonce: function (data) {
			return data.nonces ? data.nonces.addToCart : '';
		},

		/**
		 * Bind event listeners to UI elements.
		 *
		 * @since 1.11.3
		 */
		bindUIActions: function () {
			$(document).on(
				'click',
				'.masteriyo-enroll-btn.masteriyo-add-to-cart-btn',
				this.addToCart.bind(this),
			);
		},

		/**
		 * Adds a product to the cart using AJAX.
		 *
		 * This function is triggered when the user clicks the "Add to Cart" button for a product.
		 *
		 * @since 1.11.3
		 *
		 * @param {Event} e - The click event object.
		 * @returns {void}
		 */
		addToCart: function (e) {
			e.preventDefault();

			var $button = $(e.target);
			var url = new URL($button.attr('href'));
			var productID = url.searchParams.get('add-to-cart');

			if (!productID) {
				console.warn('Product ID is missing.');
				return;
			}

			$.ajax({
				type: 'POST',
				url: this.getAjaxURL(masteriyoData),
				dataType: 'json',
				data: {
					action: 'masteriyo_wc_integration_add_to_cart',
					_wpnonce: this.getAddToCartNonce(masteriyoData),
					product_id: productID,
				},
				beforeSend: function () {
					$button.text(masteriyoData.addingToCartText).prop('disabled', true);
				},
				success: function (response) {
					if (response.success) {
						$button
							.text(masteriyoData.goToCartText)
							.attr('href', masteriyoData.cartURL)
							.removeClass('masteriyo-add-to-cart-btn');
					} else {
						$button.text(masteriyoData.addToCartText);
						console.error(response.data.message || 'An error occurred.');
					}
				},
				error: function (jqXHR) {
					console.error('AJAX error:', jqXHR);
					$button.text(masteriyoData.addToCartText);
				},
				complete: function () {
					$button.prop('disabled', false);
				},
			});
		},
	};

	$(document).ready(function () {
		masteriyoWC.init();
	});
})(jQuery, window._MASTERIYO_WC_INTEGRATION_ADD_TO_CART_DATA_);
