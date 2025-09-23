/* global _MASTERIYO_STRIPE_ */

jQuery(function ($) {
	'use strict';

	let stripe = null;

	try {
		const stripeConfig = {
			locale: _MASTERIYO_STRIPE_.locale || 'auto',
		};

		if ('accountId' in _MASTERIYO_STRIPE_ && _MASTERIYO_STRIPE_.accountId) {
			stripeConfig.stripeAccount = _MASTERIYO_STRIPE_.accountId;
		}

		stripe = Stripe(_MASTERIYO_STRIPE_.publishableKey, stripeConfig);
	} catch (error) {
		console.error('Stripe initialization failed:', error);
		return;
	}

	/**
	 * Return WordPress spinner.
	 *
	 * @since 2.0.0
	 *
	 * @returns string
	 */
	function getSpinner() {
		return '<span class="spinner" style="visibility:visible"></span>';
	}

	/**
	 * Get block loading configuration.
	 *
	 * @since 2.0.0
	 *
	 * @returns
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

	var elements;

	/**
	 * Object to handle Stripe elements payment form.
	 */
	var stripeForm = {
		$form: $('form.masteriyo-checkout'),

		/**
		 * Get Masteriyo AJAX endpoint URL.
		 *
		 * @since 2.0.0
		 *
		 *
		 * @return {String}
		 */
		getAjaxURL: function () {
			return _MASTERIYO_STRIPE_.ajaxURL;
		},

		/**
		 * Attach unload events on submit.
		 *
		 * @since 2.0.0
		 */
		attachUnloadEventsOnSubmit: function () {
			$(window).on('beforeunload', this.handleUnloadEvent);
		},

		/**
		 * Detach unload events on submit.
		 *
		 * @since 2.0.0
		 */
		detachUnloadEventsOnSubmit: function () {
			$(window).off('beforeunload', this.handleUnloadEvent);
		},

		/**
		 * Unmounts all Stripe elements when the checkout page is being updated.
		 *
		 * @since 2.0.0
		 */
		unmountElements: function () {},

		/**
		 * Mounts all elements to their DOM nodes on initial loads and updates.
		 *
		 * @since 2.0.0
		 */
		mountElements: function () {},

		/**
		 * Scroll to notices.
		 *
		 * @since 2.0.0
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
		 * Display error message.
		 *
		 * @since 2.0.0
		 */
		submitError: function (errorMessage) {
			$(
				'.masteriyo-NoticeGroup-checkout, .masteriyo-error, .masteriyo-message',
			).remove();

			stripeForm.$form.prepend(
				'<div class="masteriyo-NoticeGroup masteriyo-NoticeGroup-checkout">' +
					errorMessage +
					'</div>',
			); // eslint-disable-line max-len

			stripeForm.$form
				.find('.input-text, select, input:checkbox')
				.trigger('validate')
				.trigger('blur');

			stripeForm.scrollToNotices();

			$(document.body).trigger('checkout_error', [errorMessage]);
		},

		/**
		 * Remove payment intent ID.
		 *
		 * @since 2.0.0
		 */
		removePaymentIntentFromCheckoutForm: function () {
			$('#masteriyo-stripe-payment-intent-id').remove();
		},

		/**
		 * Fetch payment intent.
		 *
		 * @since 2.0.0
		 */
		fetchPaymentIntent: function () {
			$.ajax({
				url: this.getAjaxURL(),
				method: 'POST',
				data: { action: 'masteriyo_stripe_payment_intent' },
				beforeSend: function (response, textStatus, jqXHR) {
					$('#masteriyo-stripe-method').block(getBlockLoadingConfiguration());
				},
				success: function (response, textStatus, jqXHR) {
					stripeForm.createPaymentElement(response.data.clientSecret);
				},
				error: function (jqXHR, textStatus, errorThrown) {
					stripeForm.removePaymentIntentFromCheckoutForm();
					$('#masteriyo-stripe-method').unblock();
				},
				complete: function (jqXHR, textStatus) {
					$('#masteriyo-stripe-method').unblock();
				},
			});
		},

		/**
		 * Crate payment element.
		 *
		 * @since 2.0.0
		 *
		 * @param {string} clientSecret Client secret from payment intent.
		 */
		createPaymentElement: function (clientSecret) {
			elements = stripe.elements({ clientSecret });
			var paymentElement = elements.create('payment', {
				wallets: { applePay: 'never', googlePay: 'never' },
			});
			paymentElement.mount('#masteriyo-stripe-payment-element');

			paymentElement.on('change', function (event) {
				if (event.value && event.value.type) {
					var $paymentMethodInput = stripeForm.$form.find(
						'input[name="stripe_payment_method"]',
					);

					if ($paymentMethodInput.length === 0) {
						var content = $('<input>').attr({
							type: 'hidden',
							name: 'stripe_payment_method',
							value: event.value.type,
						});

						stripeForm.$form.append(content);
					} else {
						$paymentMethodInput.val(event.value.type);
					}
				}
			});
		},

		/**
		 * Initialize event handlers and UI state.
		 *
		 * @since 2.0.0
		 */
		init: function () {
			// checkout page
			if (!stripeForm.$form.length) {
				stripeForm.$form = $('form.masteriyo-checkout');
			}

			stripeForm.$form.on('checkout_place_order_success', this.confirmPayment);

			stripeForm.$form.on('change', this.reset);

			$(document)
				.on('stripeError', this.onError)
				.on('checkout_error', this.reset);

			this.fetchPaymentIntent();
		},

		/**
		 * Confirm payment.
		 *
		 * @since 2.0.0
		 *
		 * @returns
		 */
		confirmPayment: function (event, response) {
			// Bail early if the stripe is not checked.
			if (!stripeForm.isStripeChosen()) {
				return;
			}

			stripeForm.attachUnloadEventsOnSubmit();
			stripeForm.$form.block(getBlockLoadingConfiguration());

			var user = stripeForm.getUserDetails();

			stripe
				.confirmPayment({
					elements,
					confirmParams: {
						return_url: response.redirect,
						payment_method_data: {
							billing_details: user,
						},
					},
				})
				.then(function (response) {
					stripeForm.attachUnloadEventsOnSubmit();
					stripeForm.$form.unblock();

					if (response.error) {
						stripeForm.submitError(response.error.message);
					}
				});

			return false;
		},

		/**
		 * Check to see if Stripe in general is being used for checkout.
		 *
		 * @since 2.0.0
		 *
		 * @return {boolean}
		 */
		isStripeChosen: function () {
			return 0 !== $('#payment-method-stripe:checked').length;
		},

		/**
		 * Returns the selected payment method HTML element.
		 *
		 * @since 2.0.0
		 *
		 * @return {HTMLElement}
		 */
		getSelectedPaymentElement: function () {
			return $('.payment_methods input[name="payment_method"]:checked');
		},

		/**
		 * Retrieves "user" data from either the billing fields in a form or preset settings.
		 *
		 * @since 2.0.0
		 *
		 * @return {Object}
		 */
		getUserDetails: function () {
			var first_name = $('#billing-first-name').length
					? $('#billing-first-name').val()
					: _MASTERIYO_STRIPE_.billingFirstName,
				last_name = $('#billing-last-name').length
					? $('#billing-last-name').val()
					: _MASTERIYO_STRIPE_.billingLastName,
				user = { name: '', address: {}, email: '', phone: '' };

			user.name = first_name;

			if (first_name && last_name) {
				user.name = first_name + ' ' + last_name;
			} else {
				user.name = $('#stripe-payment-data').data('full-name');
			}

			user.email = $('#billing-email').val();
			user.phone = $('#billing-phone').val();

			/* Stripe does not like empty string values so
			 * we need to remove the parameter if we're not
			 * passing any value.
			 */
			if (typeof user.phone === 'undefined' || 0 >= user.phone.length) {
				delete user.phone;
			}

			if (typeof user.email === 'undefined' || 0 >= user.email.length) {
				if ($('#stripe-payment-data').data('email').length) {
					user.email = $('#stripe-payment-data').data('email');
				} else {
					delete user.email;
				}
			}

			if (typeof user.name === 'undefined' || 0 >= user.name.length) {
				delete user.name;
			}

			var line1 =
					$('#billing_address_1').val() || _MASTERIYO_STRIPE_.billingAddress1,
				line2 =
					$('#address-line-two').val() || _MASTERIYO_STRIPE_.billingAddress2,
				state = $('#billing-state').val() || _MASTERIYO_STRIPE_.billingState,
				city = $('#billing-town-city').val() || _MASTERIYO_STRIPE_.billingCity,
				postal_code =
					$('#billing-zip-code').val() || _MASTERIYO_STRIPE_.billingPostcode,
				country =
					$('#billing-county').val() || _MASTERIYO_STRIPE_.billingCountry;

			if (line1) user.address.line1 = line1;
			if (line2) user.address.line2 = line2;
			if (state) user.address.state = state;
			if (city) user.address.city = city;
			if (postal_code) user.address.postal_code = postal_code;
			if (country) user.address.country = country;

			return user;
		},
	};

	stripeForm.init();
});
