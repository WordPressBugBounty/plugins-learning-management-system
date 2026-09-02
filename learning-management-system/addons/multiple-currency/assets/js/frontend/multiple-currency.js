/**
 * Handles the client-side functionality for switching currency during checkout.
 *
 * This script provides functionality to switch currency during the checkout process
 * using Ajax requests. It initializes event handlers for currency selection and
 * processes the Ajax request to update the checkout summary accordingly.
 *
 * @since 2.11.0
 */

/* eslint-disable */
/* global _MASTERIYO_CHECKOUT_ */

jQuery(function ($) {
	// Bail if the global checkout parameters doesn't exits.
	if (typeof _MASTERIYO_CHECKOUT_ === 'undefined') {
		return false;
	}

	/**
	 * Provides methods to handle currency switching during checkout.
	 *
	 * @since 2.11.0
	 */
	var checkoutForm = {
		$form: $('form.masteriyo-checkout'),

		/**
		 * Initializes the checkout form and event handlers.
		 *
		 * @since 2.11.0
		 */
		init: function () {
			// Switch currency.
			this.$form.on(
				'change',
				'.masteriyo-checkout-summary-switch-currency-select',
				this.switchCurrencyHandler,
			);
		},

		/**
		 * Retrieves the Ajax URL for currency switching.
		 *
		 * @since 2.11.0
		 *
		 * @returns {string} The Ajax URL for currency switching.
		 */
		getAjaxURL: function () {
			return _MASTERIYO_CHECKOUT_.ajaxURL;
		},

		/**
		 * Removes error notices from the checkout form.
		 *
		 * @since 2.11.0
		 */
		removeErrorNotices: function () {
			$(
				'.masteriyo-NoticeGroup-checkout, .masteriyo-error, .masteriyo-message',
			).remove();
		},

		/**
		 * Displays an error message on the checkout form.
		 *
		 * @since 2.11.0
		 *
		 * @param {string} errorMessage - The error message to display.
		 */
		showError: function (errorMessage) {
			checkoutForm.$form.prepend(
				'<div class="masteriyo-NoticeGroup masteriyo-NoticeGroup-checkout" role="alert">' +
					errorMessage +
					'</div>',
			); // eslint-disable-line max-len

			checkoutForm.$form
				.find('.input-text, select, input:checkbox')
				.trigger('validate')
				.trigger('blur');

			checkoutForm.scrollToNotices();

			$(document.body).trigger('checkout_error', [errorMessage]);
		},

		/**
		 * Scrolls to error notices on the checkout form.
		 *
		 * @since 2.11.0
		 */
		scrollToNotices: function () {
			var scrollElement = $(
				'.masteriyo-NoticeGroup-updateOrderReview, .masteriyo-NoticeGroup-checkout',
			);

			if (!scrollElement.length) {
				scrollElement = $('form.masteriyo-checkout');
			}

			if (scrollElement.length) {
				$('html, body').animate(
					{
						scrollTop: scrollElement.offset().top - 100,
					},
					1000,
				);
			}
		},

		/**
		 * Handles the currency switch action.
		 *
		 * @since 2.11.0
		 *
		 * @param {object} event - The event object.
		 */
		switchCurrencyHandler: function (event) {
			var currency = $('.masteriyo-checkout-summary-switch-currency-select')
				.val()
				.trim();

			if (!currency || checkoutForm.$form.is('.processing')) {
				return;
			}

			$.ajax({
				type: 'POST',
				url: checkoutForm.getAjaxURL(),
				dataType: 'json',
				data: {
					action: 'masteriyo_switch_currency',
					_wpnonce: $('[name="masteriyo-switch-currency-nonce"]').val(),
					currency: currency,
				},
				beforeSend: function (jqXHR) {
					checkoutForm.removeErrorNotices();
					checkoutForm.$form.block(getBlockLoadingConfiguration());
				},
				success: function (response, textStatus, jqXHR) {
					if (response.success) {
						$.each(response.data.fragments, function (key, value) {
							if (
								!checkoutForm.fragments ||
								checkoutForm.fragments[key] !== value
							) {
								$(key).replaceWith(value);
							}
						});
						checkoutForm.fragments = response.data.fragments;

						/**
						 * Triggers a custom event when the currency switch is successfully completed.
						 *
						 * This event notifies other parts of the application (such as payment gateways)
						 * that the checkout currency has changed, allowing them to update their state
						 * accordingly. The event includes the new currency code and the full Ajax
						 * response data for additional context.
						 *
						 * @since 2.18.1
						 * @event masteriyo_currency_switched
						 *
						 * @param {string} event - The custom event name 'masteriyo_currency_switched'.
						 * @param {Object} data - Event data object.
						 * @param {string} data.currency - The new currency code selected by the user.
						 * @param {Object} data.response - The complete Ajax response from the currency switch request.
						 * @fires masteriyo_currency_switched
						 */
						$(document.body).trigger('masteriyo_currency_switched', {
							currency: currency,
							response: response,
						});
					} else {
						checkoutForm.showError(
							'<div class="masteriyo-error">' +
								response.data.message +
								'</div>',
						);
					}
				},
				error: function (jqXHR, textStatus, errorThrown) {
					try {
						var error = jqXHR.responseJSON;
						checkoutForm.showError(
							'<div class="masteriyo-error">' + error.data.messages + '</div>',
						);
					} catch (error) {
						// The response was not the shape the branch above reads, so
						// that branch threw before it displayed anything. Falling
						// through to the console would leave the learner staring at
						// a checkout whose currency did not change and no reason
						// why. `i18n_checkout_error` is already localized on the
						// same global this script reads its Ajax URL from.
						checkoutForm.showError(
							'<div class="masteriyo-error">' +
								_MASTERIYO_CHECKOUT_.i18n_checkout_error +
								'</div>',
						);
					}
				},
				complete: function (jqXHR, textStatus) {
					checkoutForm.$form.unblock();
				},
			});
		},
	};

	// Initialize checkout form.
	checkoutForm.init();

	/**
	 * Retrieves the spinner element for blocking UI during Ajax requests.
	 *
	 * @since 2.11.0
	 *
	 * @returns {string} The spinner HTML.
	 */
	function getSpinner() {
		return '<span class="spinner" style="visibility:visible"></span>';
	}

	/**
	 * Retrieves the block loading configuration for blocking UI during Ajax requests.
	 *
	 * @since 2.11.0
	 *
	 * @returns {object} The block loading configuration.
	 */
	function getBlockLoadingConfiguration() {
		return {
			message: getSpinner(),
			css: {
				border: '',
				width: '0%',
			},
			overlayCSS: {
				background: '#fff',
				opacity: 0.6,
			},
		};
	}
});
