/* eslint-disable */
/* global _MASTERIYO_CHECKOUT_ */

jQuery(function ($) {
	// Bail if the global checkout parameters doesn't exits.
	if (typeof _MASTERIYO_CHECKOUT_ === 'undefined') {
		return false;
	}

	/**
	 * Return WordPress spinner.
	 *
	 * @returns string
	 */
	function getSpinner() {
		return '<span class="spinner" style="visibility:visible"></span>';
	}

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

	/**
	 * A localized string, or a usable fallback if this build predates it.
	 *
	 * @param {string} key
	 * @param {string} fallback
	 * @returns string
	 */
	function getLabel(key, fallback) {
		var labels = _MASTERIYO_CHECKOUT_.labels || {};

		return labels[key] || fallback;
	}

	/**
	 * A permissive email shape — deliberately weaker than the server's `is_email()`.
	 *
	 * Every address `is_email()` accepts matches this: it accepts only `local@domain`
	 * with exactly one `@`, no whitespace in either half, and a domain carrying at
	 * least one dot between non-empty labels — which is precisely what this asks for
	 * and nothing else. Anything this pattern lets through is still the server's to
	 * judge, so the client can only ever be the looser of the two.
	 */
	var EMAIL_SHAPE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

	var checkoutForm = {
		$form: $('form.masteriyo-checkout'),
		previousState: '',

		/**
		 * Whether a submit has been attempted yet.
		 *
		 * Nothing is validated before the first one: a buyer who has not finished
		 * filling the form in has not made a mistake yet, and telling them otherwise
		 * while they type is how a checkout starts nagging.
		 */
		validationAttempted: false,

		/**
		 * Whether we have started navigating away, and so must leave the button busy.
		 */
		redirecting: false,

		/**
		 * Return ajax URL.
		 *
		 * @returns string
		 */
		getAjaxURL: function () {
			return _MASTERIYO_CHECKOUT_.ajaxURL;
		},

		/**
		 * Return checkout URL.
		 *
		 * @returns string
		 */
		getCheckoutURL: function () {
			return _MASTERIYO_CHECKOUT_.checkoutURL;
		},

		/**
		 * Initialize.
		 */
		init: function () {
			$(document.body).on('update_checkout', this.updateCheckout);
			$(document.body).on('init_checkout', this.initCheckout);
			$(document.body).on(
				'masteriyo_payment_section_updated',
				this.onPaymentSectionUpdated,
			);

			// Payment methods
			this.$form.on(
				'click',
				'input[name="payment_method"]',
				this.paymentMethodSelected,
			);

			// Prevent HTML5 validation which can conflict.
			this.$form.attr('novalidate', 'novalidate');

			// Form submission
			this.$form.on('submit', this.submit);

			this.initPaymentMethods();
			this.initSummaryToggle();
			this.initFieldValidation();

			// Update on page load
			if (true === _MASTERIYO_CHECKOUT_.is_checkout) {
				$(document.body).trigger('initCheckout');
			}

			$('#masteriyo-checkout-attachment-upload').on('change', function (event) {
				var file = event.target.files[0];

				if (file) {
					$('#masteriyo-checkout-attachment-upload-file-name').text(file.name);
					$('#masteriyo-checkout-attachment-uploaded-file-info')
						.css({
							display: 'flex',
							gap: '10px',
							'align-items': 'center',
						})
						.show();

					$('#masteriyo-checkout-attachment-upload').hide();
				}
			});

			$('#masteriyo-checkout-attachment-uploaded-delete-file').on(
				'click',
				function () {
					$('#masteriyo-checkout-attachment-upload').val('').show();
					$('#masteriyo-checkout-attachment-upload-file-name').text('');
					$('#masteriyo-checkout-attachment-uploaded-file-info').hide();
				},
			);
		},
		initPaymentMethods: function () {
			var $payment_methods = this.$form.find('input[name="payment_method"]');

			// A lone gateway's radio is hidden by the stylesheet, not from here —
			// `.masteriyo-payment-methods--single`, which the template sets because
			// it is the side that knows how many gateways there are.

			// If there was a previously selected method, check that one.
			if (checkoutForm.selectedPaymentMethod) {
				$('#' + checkoutForm.selectedPaymentMethod).prop('checked', true);
			}

			// If there are none selected, select the first.
			if (0 === $payment_methods.filter(':checked').length) {
				$payment_methods.eq(0).prop('checked', true);
			}

			// Get name of new selected method.
			var checked_payment_method = $payment_methods
				.filter(':checked')
				.eq(0)
				.prop('id');

			if ($payment_methods.length > 1) {
				// Hide open descriptions.
				$('div.payment-box:not(".' + checked_payment_method + '")')
					.filter(':visible')
					.slideUp(0);
			}

			// Trigger click event for selected method
			$payment_methods.filter(':checked').eq(0).trigger('click');
		},

		/**
		 * The order summary's disclosure bar.
		 *
		 * `aria-expanded` is the whole state: the assistive-technology semantics and
		 * the hook the stylesheet hides the panel with are one attribute, so there is
		 * no second copy to fall out of sync, and a native <button> gives Enter and
		 * Space for free.
		 *
		 * The bar and the panel wrapper both sit outside
		 * `.masteriyo-checkout-summary-your-order`, the node the checkout fragments
		 * replace on every tax recalculation, coupon change and currency switch — so
		 * a refresh can neither collapse an open drawer nor leave a second bar behind
		 * it. The handler is delegated from the form for the same reason: it binds
		 * once, whatever gets replaced underneath it.
		 *
		 * Deliberately not slideToggle. jQuery's animations finish by writing an
		 * inline `display`, which outranks the container query that shows the summary
		 * again as a column once the checkout is wide enough for one — a buyer who
		 * collapsed the bar on a phone and rotated it would find the summary gone.
		 */
		initSummaryToggle: function () {
			this.$form.on(
				'click',
				'.masteriyo-checkout-summary-toggle',
				function (event) {
					event.preventDefault();

					var $toggle = $(this);

					$toggle.attr(
						'aria-expanded',
						'true' === $toggle.attr('aria-expanded') ? 'false' : 'true',
					);
				},
			);
		},

		/**
		 * Re-check a field once its buyer has moved on — but only after a submit
		 * has already failed.
		 *
		 * Delegated from the form on `focusout` rather than `blur`, for two reasons:
		 * `focusout` bubbles, and `submitError()` fires a bare `blur` at every control
		 * on the form when the *server* rejects an order, which must not be read as
		 * twelve buyers leaving twelve fields.
		 */
		initFieldValidation: function () {
			this.$form.on('focusout change', '[required]', function () {
				if (!checkoutForm.validationAttempted) {
					return;
				}

				checkoutForm.validateField($(this));
			});
		},

		/**
		 * The controls this script is allowed to have an opinion about.
		 *
		 * Requiredness is read off the rendered markup, which the field definitions
		 * put there — so this list is the server's list, not a second copy of it. A
		 * field the settings or the selected country hide is not on the form as far
		 * as the buyer is concerned, and `:visible` is how that is asked.
		 *
		 * @returns jQuery
		 */
		getValidatableFields: function () {
			return this.$form.find('[required]').filter(':visible');
		},

		/**
		 * The name to call a field by in a message.
		 *
		 * @param {jQuery} $field
		 * @returns string
		 */
		getFieldLabel: function ($field) {
			var id = $field.attr('id');
			var $label = id
				? checkoutForm.$form.find('label[for="' + id + '"]').first()
				: $();

			if (!$label.length) {
				return '';
			}

			// The required marker is part of the label element and not part of the
			// field's name.
			var $text = $label.clone();

			$text.find('span').remove();

			return $.trim($text.text().replace(/\s+/g, ' '));
		},

		/**
		 * What is wrong with a field, if anything.
		 *
		 * Exactly two checks, both of which the server also makes: a required field
		 * must not be empty, and an email address must look like one. Anything else —
		 * postcode formats, phone formats, whether a state belongs to a country — is
		 * the server's alone, so an unusual but valid entry is never blocked here.
		 *
		 * @param {jQuery} $field
		 * @returns string The message, or an empty string when the field is fine.
		 */
		getFieldError: function ($field) {
			var field = $field.get(0);

			if (!field) {
				return '';
			}

			var isCheckable = 'checkbox' === field.type || 'radio' === field.type;
			var isEmpty = isCheckable
				? !$field.is(':checked')
				: '' === $.trim($field.val() || '');

			if (isEmpty) {
				var label = checkoutForm.getFieldLabel($field);

				if (!label) {
					return getLabel('required_field_generic', 'This field is required.');
				}

				return getLabel('required_field', '%s is a required field.').replace(
					'%s',
					label,
				);
			}

			if ('billing_email' === field.name || 'email' === field.type) {
				if (!EMAIL_SHAPE.test($.trim($field.val()))) {
					return getLabel(
						'invalid_email',
						'Please enter a valid email address.',
					);
				}
			}

			return '';
		},

		/**
		 * Check one field and show or clear its message.
		 *
		 * @param {jQuery} $field
		 * @returns boolean Whether the field passed.
		 */
		validateField: function ($field) {
			// A field the form is not showing is a field the buyer cannot fix.
			if (!$field.is(':visible')) {
				checkoutForm.clearFieldError($field);
				return true;
			}

			var message = checkoutForm.getFieldError($field);

			if (message) {
				checkoutForm.showFieldError($field, message);
				return false;
			}

			checkoutForm.clearFieldError($field);

			return true;
		},

		/**
		 * Check every validatable field.
		 *
		 * @returns jQuery The fields that failed, in document order.
		 */
		validateFields: function () {
			var invalid = [];

			checkoutForm.getValidatableFields().each(function () {
				if (!checkoutForm.validateField($(this))) {
					invalid.push(this);
				}
			});

			return $(invalid);
		},

		/**
		 * Put a message under a field and point the field at it.
		 *
		 * @param {jQuery} $field
		 * @param {string} message
		 */
		showFieldError: function ($field, message) {
			var id = ($field.attr('id') || $field.attr('name') || 'field') + '-error';
			var $message = $('#' + id);

			if (!$message.length) {
				$message = $('<div>', {
					id: id,
					class: 'masteriyo-checkout-field-error',
				});

				var $wrapper = $field.closest('div');

				if ($wrapper.length) {
					$wrapper.append($message);
				} else {
					$field.after($message);
				}
			}

			$message.text(message);

			$field
				.addClass('masteriyo-input--error')
				.attr('aria-invalid', 'true')
				.attr('aria-describedby', id);
		},

		/**
		 * Take a message back off a field.
		 *
		 * @param {jQuery} $field
		 */
		clearFieldError: function ($field) {
			var id = ($field.attr('id') || $field.attr('name') || 'field') + '-error';

			$('#' + id).remove();

			$field
				.removeClass('masteriyo-input--error')
				.removeAttr('aria-invalid')
				.removeAttr('aria-describedby');
		},

		/**
		 * Send the buyer to the first thing they have to fix.
		 *
		 * @param {jQuery} $invalid
		 */
		focusFirstInvalidField: function ($invalid) {
			var field = $invalid.get(0);

			if (!field) {
				return;
			}

			if (field.scrollIntoView) {
				field.scrollIntoView({ block: 'center', behavior: 'smooth' });
			}

			field.focus();
		},

		/**
		 * Put the purchase button into its submitting state.
		 *
		 * The button is the progress surface: it is where the buyer's attention
		 * already is, and disabling it is what makes a second submit impossible
		 * rather than merely discouraged. The total beside the label is left alone —
		 * it is a checkout fragment, and what is being paid does not change because
		 * paying it has started.
		 */
		setOrderButtonProcessing: function () {
			var $button = $('#masteriyo-place-order');

			if (!$button.length) {
				return;
			}

			var $action = $button.find('.masteriyo-place-order-action');

			// Only a button carrying the action element can have its label swapped
			// without taking the total element with it; a stale template override
			// simply gets the disabled state.
			if ($action.length) {
				checkoutForm.orderButtonLabel = $action.text();
				$action.text(getLabel('processing', 'Processing…'));
			}

			$button
				.prop('disabled', true)
				.attr('aria-busy', 'true')
				.addClass('masteriyo-checkout--btn--processing');
		},

		/**
		 * Give the purchase button back.
		 */
		restoreOrderButton: function () {
			var $button = $('#masteriyo-place-order');

			if (!$button.length) {
				return;
			}

			var $action = $button.find('.masteriyo-place-order-action');
			var label = checkoutForm.orderButtonLabel;

			if ($action.length && 'undefined' !== typeof label) {
				$action.text(label);
			}

			$button
				.prop('disabled', false)
				.removeAttr('aria-busy')
				.removeClass('masteriyo-checkout--btn--processing');
		},

		/**
		 * Show/hide payment section and re-initialize payment methods after cart total changes.
		 *
		 * @param {Event} event
		 * @param {boolean} needsPayment Whether the cart requires payment.
		 */
		onPaymentSectionUpdated: function (event, needsPayment) {
			if (needsPayment === false) {
				$('#masteriyo-payments').slideUp(230);
			} else if ($('#masteriyo-payments').length) {
				$('#masteriyo-payments').slideDown(230);
				checkoutForm.initPaymentMethods();
			} else {
				// Payment section was never rendered (e.g. page loaded with 100%
				// coupon already applied). Reload so PHP renders the full payment UI.
				window.location.reload();
			}
		},

		/**
		 * Attach unload events on submit.
		 */
		attachUnloadEventsOnSubmit: function () {
			$(window).on('beforeunload', this.handleUnloadEvent);
		},

		/**
		 * Return payment method title.
		 *
		 * @return Payment method title.
		 */
		getPaymentMethod: function () {
			return this.$form.find('input[name="payment_method"]:checked').val();
		},

		paymentMethodSelected: function (e) {
			e.stopPropagation();

			if ($('.payment-methods input.input-radio').length > 1) {
				var target_payment_box = $('div.payment-box.' + $(this).attr('ID')),
					is_checked = $(this).is(':checked');

				if (is_checked && !target_payment_box.is(':visible')) {
					$('div.payment-box').filter(':visible').slideUp(230);

					if (is_checked) {
						target_payment_box.slideDown(230);
					}
				}
			} else {
				$('div.payment-box').show();
			}

			// The card that is chosen says so, so that a gateway with nothing in its
			// body still reads as selected.
			$('.masteriyo-payment-method').removeClass(
				'masteriyo-payment-method--selected',
			);
			$(this)
				.closest('.masteriyo-payment-method')
				.addClass('masteriyo-payment-method--selected');

			// Only the action half of the button label changes with the gateway; the
			// total beside it is the checkout fragments' to own. A template override
			// still carrying the old single-label button has no action element, and
			// falls back to the button itself.
			var $orderButton = $('#masteriyo-place-order');
			var $orderAction = $orderButton.find('.masteriyo-place-order-action');
			var $orderLabel = $orderAction.length ? $orderAction : $orderButton;

			if ($(this).data('order_button_text')) {
				$orderLabel.text($(this).data('order_button_text'));
			} else {
				$orderLabel.text($orderButton.data('value'));
			}

			var selectedPaymentMethod = $(
				'.masteriyo-checkout input[name="payment_method"]:checked',
			).attr('id');

			if (selectedPaymentMethod !== this.selectedPaymentMethod) {
				$(document.body).trigger('paymentMethodSelected');
			}

			this.selectedPaymentMethod = selectedPaymentMethod;
		},

		/**
		 * Initialize checkout.
		 */
		initCheckout: function () {
			$(document.body).trigger('update_checkout');
		},

		/**
		 * Reset update checkout timer.
		 */
		resetUpdateCheckoutTimer: function () {
			clearTimeout(checkoutForm.updateTimer);
		},

		/**
		 * Return true if the json is valid.
		 *
		 * @param {string} raw_json
		 * @returns
		 */
		isValidJson: function (raw_json) {
			try {
				var json = JSON.parse(raw_json);

				return json && 'object' === typeof json;
			} catch (e) {
				return false;
			}
		},

		/**
		 * Update checkout.
		 *
		 * @param {} event
		 * @param {*} args
		 */
		updateCheckout: function (event, args) {
			// Small timeout to prevent multiple requests when several fields update at the same time
			checkoutForm.resetUpdateCheckoutTimer();
			checkoutForm.updateTimer = setTimeout(
				checkoutForm.updateCheckoutAction,
				'5',
				args,
			);
		},

		/**
		 * Modern browsers have their own standard generic messages that they will display.
		 * Confirm, alert, prompt or custom message are not allowed during the unload event
		 * Browsers will display their own standard messages
		 *
		 * @param {*} event
		 * @returns
		 */
		handleUnloadEvent: function (event) {
			// Check if the browser is Internet Explorer
			if (
				navigator.userAgent.indexOf('MSIE') !== -1 ||
				!!document.documentMode
			) {
				// IE handles unload events differently than modern browsers
				event.preventDefault();
				return undefined;
			}

			return true;
		},

		/**
		 * Detach unload events on submit.
		 */
		detachUnloadEventsOnSubmit: function () {
			$(window).off('beforeunload', this.handleUnloadEvent);
		},

		/**
		 * Display error message.
		 *
		 * `role="alert"` is what turns the banner from something that merely appears
		 * into something that is announced: it is prepended after the page has
		 * settled, so nothing else would tell a screen reader the order was refused.
		 */
		submitError: function (errorMessage) {
			$(
				'.masteriyo-NoticeGroup-checkout, .masteriyo-error, .masteriyo-message',
			).remove();

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
		 * Scroll to notices.
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
		 * Handle fail checkout form submission.
		 *
		 * @param {*} jqXHR
		 * @param {*} textStatus
		 * @param {*} errorThrown
		 */
		handleFormSubmissionFailure: function (jqXHR, textStatus, errorThrown) {
			// Detach the unload handler that prevents a reload / redirect.
			checkoutForm.detachUnloadEventsOnSubmit();

			try {
				error = jqXHR.responseJSON;
				checkoutForm.submitError(
					'<div class="masteriyo-error">' + error.data.messages + '</div>',
				);
			} catch (error) {
				console.log(error);
			}
		},

		/**
		 * Handle successful checkout form submission.
		 *
		 * @param {*} response
		 * @param {*} textStatus
		 * @param {*} jqXHR
		 */
		handleFormSubmissionSuccess: function (response, textStatus, jqXHR) {
			// Detach the unload handler that prevents a reload / redirect
			checkoutForm.detachUnloadEventsOnSubmit();

			try {
				if (
					'success' === response.result &&
					checkoutForm.$form.triggerHandler(
						'checkout_place_order_success',
						response,
					) !== false
				) {
					if (
						response.payment_method &&
						'razorpay' === response.payment_method
					) {
						this.initializeRazorpayPayment(
							response.data,
							response.order_id,
							response.redirect,
							response.nonce,
						);
					} else if (
						response.redirect &&
						(-1 === response.redirect.indexOf('https://') ||
							-1 === response.redirect.indexOf('http://'))
					) {
						checkoutForm.redirecting = true;
						window.location = response.redirect;
					} else {
						checkoutForm.redirecting = true;
						window.location = decodeURI(response.redirect);
					}
				} else if ('failure' === response.result) {
					throw 'Result failure';
				} else {
					throw 'Invalid response';
				}
			} catch (err) {
				console.log(err);

				// Reload page
				if (true === response.reload) {
					checkoutForm.redirecting = true;
					window.location.reload();
					return;
				}

				// Trigger update in case we need a fresh nonce
				if (true === response.refresh) {
					$(document.body).trigger('update_checkout');
				}

				// Add new errors
				if (response.messages) {
					checkoutForm.submitError(response.messages);
				}
			}
		},
		/**
		 * Calculate taxes based on country and state.
		 */
		calculateTaxes: function () {
			var country = $('#billing-country').val();
			var state = $('#billing-state').val();
			var $stateWrapper = $('.masteriyo-checkout----state');

			if (!country) {
				return;
			}

			if ($stateWrapper.is(':visible') && !state && !this.previousState) {
				return;
			} else {
				this.previousState = state;
			}

			// Block the form during AJAX request
			this.$form.block(getBlockLoadingConfiguration());

			$.ajax({
				type: 'POST',
				url: this.getAjaxURL(),
				dataType: 'json',
				data: {
					action: 'masteriyo_calculate_taxes',
					country: country,
					state: state,
					_wpnonce: _MASTERIYO_CHECKOUT_.nonce,
				},
				success: function (response) {
					if (response.success) {
						var needsPayment = response.data.fragments.needs_payment;
						var summaryKey = '.masteriyo-checkout-summary-your-order';

						// Detect whether the payment requirement or cart total
						// actually changed before we overwrite the stored fragments.
						// Undefined fragments means this is the initial load, where
						// the payment method JS already mounts on its own.
						var paymentRelevantChanged =
							checkoutForm.fragments &&
							(checkoutForm.fragments.needs_payment !== needsPayment ||
								checkoutForm.fragments[summaryKey] !==
									response.data.fragments[summaryKey]);

						$.each(response.data.fragments, function (key, value) {
							if (key === 'needs_payment') {
								return;
							}
							if (
								!checkoutForm.fragments ||
								checkoutForm.fragments[key] !== value
							) {
								$(key).replaceWith(value);
							}
						});
						checkoutForm.fragments = response.data.fragments;

						// Only refresh the payment section when the amount or payment
						// requirement actually changed — not on initial load or on
						// billing edits that don't affect the total.
						if (paymentRelevantChanged) {
							$(document.body).trigger('masteriyo_payment_section_updated', [
								needsPayment,
							]);
						}
					} else {
						checkoutForm.submitError(
							'<div class="masteriyo-error">' +
								response.data.message +
								'</div>',
						);
					}
				},
				error: function (jqXHR, textStatus, errorThrown) {
					try {
						var error = jqXHR.responseJSON;
						checkoutForm.submitError(
							'<div class="masteriyo-error">' + error.data.messages + '</div>',
						);
					} catch (error) {
						console.log(error);
					}
				},
				complete: function () {
					checkoutForm.$form.unblock();
				},
			});
		},

		/**
		 * Handle checkout form submission.
		 */
		submit: function (event) {
			if (checkoutForm.$form.is('.processing')) {
				return false;
			}

			// From here on the buyer has asked to pay, so they have earned an answer
			// about the fields they left behind — and every later blur re-checks the
			// field it left.
			checkoutForm.validationAttempted = true;

			var $invalid = checkoutForm.validateFields();

			if ($invalid.length) {
				checkoutForm.focusFirstInvalidField($invalid);

				return false;
			}

			if (
				false !== checkoutForm.$form.triggerHandler('checkout_place_order') &&
				false !==
					checkoutForm.$form.triggerHandler(
						'checkout_place_order_' + checkoutForm.getPaymentMethod(),
					)
			) {
				// Attach event to block reloading the page when the form has been submitted
				checkoutForm.attachUnloadEventsOnSubmit();

				var formData = new FormData(checkoutForm.$form[0]);

				// Perform checkout operation.
				$.ajax({
					type: 'POST',
					url: checkoutForm.getCheckoutURL(),
					dataType: 'json',
					data: formData,
					processData: false,
					contentType: false,
					beforeSend: function (jqXHR) {
						checkoutForm.redirecting = false;
						checkoutForm.setOrderButtonProcessing();
						checkoutForm.$form
							.addClass('processing')
							.block(getBlockLoadingConfiguration());
					},
					success: function (response, textStatus, jqXHR) {
						checkoutForm.handleFormSubmissionSuccess(
							response,
							textStatus,
							jqXHR,
						);
					},
					error: function (jqXHR, textStatus, errorThrown) {
						checkoutForm.handleFormSubmissionFailure(
							jqXHR,
							textStatus,
							errorThrown,
						);
					},
					complete: function (jqXHR, textStatus) {
						// Detach the unload handler that prevents a reload / redirect
						checkoutForm.detachUnloadEventsOnSubmit();
						checkoutForm.$form.removeClass('processing').unblock();

						// Every path that is not a navigation gives the button back —
						// a rejected order, a gateway error, a dropped connection, and
						// the gateways that answer in a modal of their own rather than
						// by leaving the page. A buyer left looking at a permanently
						// spinning button has no way to try again.
						if (!checkoutForm.redirecting) {
							checkoutForm.restoreOrderButton();
						}
					},
				});
			} else {
				// A gateway cancelled the submission before it left the page. No
				// request was made, so there is nothing to wait for.
				checkoutForm.restoreOrderButton();
			}

			return false;
		},

		/**
		 * Initialize Razorpay payment process.
		 *
		 * @since 2.7.1
		 *
		 * @param {*} options - The options for Razorpay.
		 * @param {*} orderId - The order ID.
		 * @param {*} redirectUrl - The URL to redirect upon successful payment verification.
		 * @param {string} [nonce] - Fresh checkout nonce, valid for the user as of order creation.
		 */
		initializeRazorpayPayment: function (
			options,
			orderId,
			redirectUrl,
			nonce,
		) {
			options.handler = function (response) {
				checkoutForm.paymentSignatureVerification(
					response.razorpay_payment_id,
					response.razorpay_order_id,
					response.razorpay_signature,
					orderId,
					redirectUrl,
					nonce,
				);
			};

			var rzp = new Razorpay(options);
			rzp.on('payment.failed', function (response) {
				checkoutForm.submitError(
					'Payment failed: ' + response.error.description,
				);
			});

			rzp.open();
		},

		/**
		 * Verifies the Razorpay payment signature.
		 *
		 * @since 2.7.1
		 *
		 * @async
		 * @param {string} razorpay_payment_id - The unique payment identifier from Razorpay.
		 * @param {string} razorpay_order_id - The unique order identifier from Razorpay related to the payment.
		 * @param {string} razorpay_signature - The signature to be verified.
		 * @param {string} order_id - The internal identifier of the order.
		 * @param {string} redirect_url - The URL to redirect the user after verification.
		 * @param {string} [nonce] - Fresh checkout nonce issued alongside the Razorpay order; falls back to the page-load nonce when absent.
		 */
		paymentSignatureVerification: async function (
			razorpay_payment_id,
			razorpay_order_id,
			razorpay_signature,
			order_id,
			redirect_url,
			nonce,
		) {
			try {
				const result = await $.ajax({
					url: checkoutForm.getAjaxURL(),
					data: {
						action: 'masteriyo_razorpay_payment_signature_verification',
						nonce: nonce || _MASTERIYO_CHECKOUT_.nonce,
						payment_id: razorpay_payment_id,
						order_id: razorpay_order_id,
						signature: razorpay_signature,
						masteriyo_order_id: order_id,
					},
					type: 'POST',
				});

				if (result && result.success) {
					window.location = redirect_url;
				} else {
					checkoutForm.submitError(
						result.messages || 'Payment verification failed.',
					);
				}
			} catch (error) {
				checkoutForm.submitError(
					'An error occurred while verifying the payment.',
				);
			}
		},
	};

	checkoutForm.init();

	// Loop through the options and append them to the countries field
	$.each(_MASTERIYO_CHECKOUT_.countries, function (code, name) {
		$('#billing-country').append(
			$('<option>', {
				value: code,
				text: name,
			}),
		);
	});

	// Set initial country value if available
	if (_MASTERIYO_CHECKOUT_.billing_country) {
		$('#billing-country').val(_MASTERIYO_CHECKOUT_.billing_country);

		// Get references to state elements
		var $billingState = $('#billing-state');
		var $billingStateWrapper = $billingState.parent(
			'.masteriyo-checkout----state',
		);
		var states = _MASTERIYO_CHECKOUT_.states;

		if (states && states[_MASTERIYO_CHECKOUT_.billing_country]) {
			// Clear existing options
			$billingState.empty();
			$billingState.append(
				$('<option>', {
					value: '',
					text: 'Select state',
				}),
			);

			// Populate states for the selected country
			$.each(
				states[_MASTERIYO_CHECKOUT_.billing_country],
				function (code, name) {
					$billingState.append(
						$('<option>', {
							value: code,
							text: name,
						}),
					);
				},
			);

			$billingStateWrapper.show();

			// Set initial state value if available
			if (_MASTERIYO_CHECKOUT_.billing_state) {
				$billingState.val(_MASTERIYO_CHECKOUT_.billing_state);
			}
		} else {
			$billingStateWrapper.hide();
		}
	}

	// Initialize country and state fields with change handlers.
	$('#billing-country').on('change', function () {
		var $billingState = $('#billing-state');
		var $billingStateWrapper = $billingState.parent(
			'.masteriyo-checkout----state',
		);

		// Get the selected country value
		var selectedCountry = $(this).val();
		var countries = _MASTERIYO_CHECKOUT_.countries;
		var states = _MASTERIYO_CHECKOUT_.states;
		if (!countries || !states) {
			return;
		}

		if (!countries[selectedCountry]) {
			$billingState.html('<option value="">Select state</option>');
			return;
		}

		var selectedCountryStates = states[selectedCountry];

		if (!selectedCountryStates) {
			$billingStateWrapper.hide();
		} else {
			$billingState.empty();
			$billingState.append(
				$('<option>', {
					value: '',
					text: 'Select state',
				}),
			);

			// Loop through the options and append them to the countries field
			$.each(selectedCountryStates, function (code, name) {
				$billingState.append(
					$('<option>', {
						value: code,
						text: name,
					}),
				);
			});

			$billingStateWrapper.show();
		}

		checkoutForm.calculateTaxes();
	});

	// Calculate taxes when state changes.
	$('#billing-state').on('change', function () {
		checkoutForm.calculateTaxes();
	});

	// Initial tax calculation
	checkoutForm.calculateTaxes();
});
