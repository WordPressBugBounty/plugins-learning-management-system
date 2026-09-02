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
				'<div class="masteriyo-NoticeGroup masteriyo-NoticeGroup-checkout" role="alert">' +
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
		 * Whether a fetchPaymentIntent AJAX call is currently in flight.
		 *
		 * @type {boolean}
		 */
		isFetchingPaymentIntent: false,

		/**
		 * Whether a fetch arrived while one was in flight.
		 *
		 * A dropped call is not always a duplicate: a coupon or currency change
		 * refetches for a different amount, and discarding it would mount an
		 * intent priced for the previous cart. The latest ask is queued and
		 * replayed once the in-flight request completes.
		 *
		 * @type {boolean}
		 */
		refetchPaymentIntentQueued: false,

		/**
		 * Hold the payment slot open while there is nothing in it yet.
		 *
		 * A skeleton rather than a loading overlay, because until the element mounts
		 * there is nothing underneath to overlay: the mount container tracks its
		 * content's height, so an empty one is a few pixels tall and the payment
		 * section would grow by the height of a card form under a buyer who is
		 * already reading it. The skeleton gives the slot that height up front and
		 * hands it back to the real element with nothing to move.
		 *
		 * @param {boolean} loading
		 */
		setPaymentSlotLoading: function (loading) {
			$('#masteriyo-stripe-method')
				.toggleClass('masteriyo-stripe-method--loading', !!loading)
				.attr('aria-busy', loading ? 'true' : 'false');
		},

		/**
		 * Fetch payment intent.
		 *
		 * @since 2.0.0
		 */
		fetchPaymentIntent: function () {
			if (stripeForm.isFetchingPaymentIntent) {
				stripeForm.refetchPaymentIntentQueued = true;
				return;
			}
			stripeForm.isFetchingPaymentIntent = true;
			$.ajax({
				url: this.getAjaxURL(),
				method: 'POST',
				data: { action: 'masteriyo_stripe_payment_intent' },
				beforeSend: function (response, textStatus, jqXHR) {
					stripeForm.setPaymentSlotLoading(true);
				},
				success: function (response, textStatus, jqXHR) {
					// A 200 that carries no secret is still nothing to mount, and the
					// slot must not be left holding a place for an element that is
					// never coming.
					if (!response || !response.data || !response.data.clientSecret) {
						stripeForm.setPaymentSlotLoading(false);
						return;
					}

					stripeForm.createPaymentElement(response.data.clientSecret);
				},
				error: function (jqXHR, textStatus, errorThrown) {
					stripeForm.removePaymentIntentFromCheckoutForm();

					// Nothing is coming, so the slot stops pretending something is.
					stripeForm.setPaymentSlotLoading(false);
				},
				complete: function (jqXHR, textStatus) {
					stripeForm.isFetchingPaymentIntent = false;

					if (stripeForm.refetchPaymentIntentQueued) {
						stripeForm.refetchPaymentIntentQueued = false;
						stripeForm.fetchPaymentIntent();
					}
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
			var paymentElement = elements.create('payment');
			paymentElement.mount('#masteriyo-stripe-payment-element');

			// `ready` is the element's own word for "mounted and able to accept
			// input", which is the moment — and the only moment — the slot can be
			// handed over without the buyer seeing an empty box. Registered first,
			// so nothing registered after it can cost the slot its release.
			paymentElement.on('ready', function () {
				stripeForm.setPaymentSlotLoading(false);
			});

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

			// An element that could not render says so in its own words, and it
			// cannot do that from behind a placeholder.
			paymentElement.on('loaderror', function () {
				stripeForm.setPaymentSlotLoading(false);
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
				.on('checkout_error', this.reset)
				/**
				 * Binds a handler for the currency switch event during checkout.
				 *
				 * Listens for the 'masteriyo_currency_switched' event to trigger updates
				 * to the Stripe payment intent and elements when the user changes the
				 * checkout currency.
				 *
				 * @since 2.18.1
				 * @listens masteriyo_currency_switched - Custom event fired after successful currency switch.
				 */
				.on('masteriyo_currency_switched', this.handleCurrencySwitch);

			$(document.body).on(
				'masteriyo_payment_section_updated',
				this.handlePaymentSectionUpdated,
			);

			this.fetchPaymentIntent();
		},

		/**
		 * Handle payment section update (e.g. after coupon applied/removed).
		 *
		 * Refreshes the Stripe payment intent when the cart total changes so the
		 * amount matches, and cleans up elements when payment is not needed.
		 *
		 * @param {Event} event
		 * @param {boolean} needsPayment Whether the cart requires payment.
		 */
		handlePaymentSectionUpdated: function (event, needsPayment) {
			stripeForm.removePaymentIntentFromCheckoutForm();

			if (needsPayment === false) {
				elements = null;
				return;
			}

			// Cart total changed but payment is still needed — refresh the payment
			// intent so the amount matches the new total.
			elements = null;
			stripeForm.fetchPaymentIntent();
		},

		/**
		 * Handle currency switch event and refresh payment intent
		 *
		 * @param {object} event - The event object
		 * @param {object} data - Event data containing currency and response
		 */
		handleCurrencySwitch: function (event, data) {
			// Remove existing payment intent.
			stripeForm.removePaymentIntentFromCheckoutForm();

			// Unmount existing elements if they exist.
			if (elements) {
				stripeForm.unmountElements();
			}

			// Fetch new payment intent for the new currency.
			stripeForm.fetchPaymentIntent();
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

			// Bail if elements are not initialized (e.g. zero-amount order after coupon).
			if (!elements) {
				return;
			}

			stripeForm.attachUnloadEventsOnSubmit();
			stripeForm.$form.block(getBlockLoadingConfiguration());

			var confirmParams = { return_url: response.redirect };

			// The Payment Element collects the billing fields a method requires
			// (Klarna's email, SEPA's account holder). Passing the same field
			// again via payment_method_data makes Stripe reject the confirmation,
			// so only the card flow — where the Element collects none of them —
			// sends the checkout form's values. The hidden input records the
			// method type the element's change handler last saw; card is the
			// element's default selection.
			var chosenMethodType =
				stripeForm.$form.find('input[name="stripe_payment_method"]').val() ||
				'card';

			if ('card' === chosenMethodType) {
				confirmParams.payment_method_data = {
					billing_details: stripeForm.getUserDetails(),
				};
			}

			stripe
				.confirmPayment({ elements, confirmParams })
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
					$('#billing-address-1').val() || _MASTERIYO_STRIPE_.billingAddress1,
				line2 =
					$('#billing-address-2').val() || _MASTERIYO_STRIPE_.billingAddress2,
				state = $('#billing-state').val() || _MASTERIYO_STRIPE_.billingState,
				city = $('#billing-city').val() || _MASTERIYO_STRIPE_.billingCity,
				postal_code =
					$('#billing-postcode').val() || _MASTERIYO_STRIPE_.billingPostcode,
				country =
					$('#billing-country').val() || _MASTERIYO_STRIPE_.billingCountry;

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
