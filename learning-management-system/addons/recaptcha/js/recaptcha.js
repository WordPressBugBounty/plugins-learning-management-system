// Google reCAPTCHA addon.
(function ($) {
	// How this code snippet works:
	// This logic overwrites the default behavior of `grecaptcha.ready()` to
	// ensure that it can be safely called at any time. When `grecaptcha.ready()`
	// is called before reCAPTCHA is loaded, the callback function that is passed
	// by `grecaptcha.ready()` is enqueued for execution after reCAPTCHA is
	// loaded.
	if (typeof grecaptcha === 'undefined') {
		grecaptcha = {};
	}

	grecaptcha.ready = function (cb) {
		if ('object' === typeof grecaptcha) {
			// window.__grecaptcha_cfg is a global variable that stores reCAPTCHA's
			// configuration. By default, any functions listed in its 'fns' property
			// are automatically executed when reCAPTCHA loads.
			const c = '___grecaptcha_cfg';
			window[c] = window[c] || {};
			(window[c]['fns'] = window[c]['fns'] || []).push(cb);
		} else {
			cb();
		}
	};

	// v2 widget id, kept so the widget can be reset after a failed submission.
	let widgetId = null;

	// Fetch a fresh single-use v3 token and (re)write it to the hidden field.
	function refreshV3Token() {
		grecaptcha
			.execute(_MASTERIYO_RECAPTCHA_.siteKey, { action: 'submit' })
			.then(function (token) {
				const $input = $('input[name="g-recaptcha-response"]');

				if ($input.length) {
					$input.val(token);
				} else {
					$('#masteriyo-recaptcha').after(
						'<input type="hidden" name="g-recaptcha-response" value="' +
							token +
							'">'
					);
				}
			});
	}

	// Usage
	grecaptcha.ready(function () {
		if ('v3' === _MASTERIYO_RECAPTCHA_.version) {
			refreshV3Token();
		} else {
			widgetId = grecaptcha.render('masteriyo-recaptcha', {
				sitekey: _MASTERIYO_RECAPTCHA_.siteKey,
				theme: _MASTERIYO_RECAPTCHA_.theme,
				size: _MASTERIYO_RECAPTCHA_.size,
			});
		}
	});

	// Discard the spent single-use token on failure so a retry isn't rejected.
	$(document.body).on('masteriyo_recaptcha_refresh', function () {
		if ('v3' === _MASTERIYO_RECAPTCHA_.version) {
			refreshV3Token();
		} else if (null !== widgetId && 'function' === typeof grecaptcha.reset) {
			grecaptcha.reset(widgetId);
		}
	});
})(jQuery);
