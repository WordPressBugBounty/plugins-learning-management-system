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

	// Usage
	grecaptcha.ready(function () {
		if ('v3' === _MASTERIYO_RECAPTCHA_.version) {
			grecaptcha
				.execute(_MASTERIYO_RECAPTCHA_.siteKey, { action: 'submit' })
				.then(function (token) {
					// Add your logic to submit to your backend server here.
					console.log(token);
					$('#masteriyo-recaptcha').after(
						'<input type="hidden" name="g-recaptcha-response" value="' +
							token +
							'">'
					);
				});
		} else {
			grecaptcha.render('masteriyo-recaptcha', {
				sitekey: _MASTERIYO_RECAPTCHA_.siteKey,
				theme: _MASTERIYO_RECAPTCHA_.theme,
				size: _MASTERIYO_RECAPTCHA_.size,
			});
		}
	});
})(jQuery);
