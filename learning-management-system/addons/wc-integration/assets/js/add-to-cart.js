/**
 * WooCommerce add to cart functionality for courses archive/single course page.
 *
 * @since 1.11.3 [Free]
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
		 * @since 1.11.3 [Free]
		 */
		init: function () {
			this.bindUIActions();
		},

		/**
		 * Returns the AJAX URL for making requests.
		 *
		 * @since 1.11.3 [Free]
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
		 * @since 1.11.3 [Free]
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
		 * @since 1.11.3 [Free]
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
		 * @since 1.11.3 [Free]
		 *
		 * @param {Event} e - The click event object.
		 * @returns {void}
		 */
		addToCart: function (e) {
			e.preventDefault();

			// Resolve the anchor from the event target rather than using it directly: the
			// button can contain child elements (the lock icon rendered by enroll-button.php
			// for password-protected and cohort-locked courses), and a click landing on one
			// of those makes e.target the <svg>/<path>, which has no href.
			var $button = $(e.target).closest('.masteriyo-enroll-btn');
			var href = $button.attr('href');

			if (!href) {
				return;
			}

			var url = new URL(href, window.location.href);
			var productID = url.searchParams.get('add-to-cart');

			if (!productID) {
				window.location.href = href;
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
					$button.text(masteriyoData.addToCartText);
					// Redirect to login when guest checkout is disabled (401).
					if (
						jqXHR.status === 401 &&
						jqXHR.responseJSON &&
						jqXHR.responseJSON.data &&
						jqXHR.responseJSON.data.redirect
					) {
						window.location.href = jqXHR.responseJSON.data.redirect;
						return;
					}
					console.warn(
						'Masteriyo add-to-cart error',
						jqXHR.status,
						jqXHR.responseJSON,
					);
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
