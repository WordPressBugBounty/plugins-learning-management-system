/**
 * global _MASTERIYO_
 */
(function ($, _MASTERIYO_) {
	/**
	 * Login form submission handler.
	 */
	$(document.body).on('submit', 'form.masteriyo-login--form', function (e) {
		e.preventDefault();

		const $form = $(this);

		$form
			.find('button[type=submit]')
			.text(_MASTERIYO_.labels.signing_in)
			.siblings('.masteriyo-notify-message')
			.first()
			.remove();

		$(this).find('#masteriyo-login-error-msg').hide();

		$.ajax({
			type: 'post',
			dataType: 'json',
			url: _MASTERIYO_.ajax_url,
			data: $form.serializeArray(),
			success: function (res) {
				if (res.data.user_id && res.data.mas_session_token) {
					$('#clear-sessions-link').data('sessionData', {
						mas_session_token: res.data.mas_session_token,
						user_id: res.data.user_id,
						_wpnonce: res.data._wpnonce,
					});
					$('#masteriyo-session-limit-warning').show();
				} else if (res.success) {
					window.location.replace(res.data.redirect);
				} else {
					$('#masteriyo-login-error-msg').show().html(res.data.message);
				}
			},
			error: function (xhr, status, error) {
				var message = xhr.responseJSON.message
					? xhr.responseJSON.message
					: error;
				$('#masteriyo-login-error-msg').show().html(message);
			},
			complete: function () {
				$form.find('button[type=submit]').text(_MASTERIYO_.labels.sign_in);
			},
		});
	});
	// Session Clearing Functionality
	$('#clear-sessions-link').on('click', function (e) {
		e.preventDefault();
		$.ajax({
			type: 'POST',
			dataType: 'json',
			url: _MASTERIYO_.ajax_url,
			data: {
				action: 'masteriyo_clear_sessions',
				user_info: $(this).data('sessionData'),
			},
			success: function (res) {
				if (res.success) {
					$('#masteriyo-session-limit-warning').hide();
					$('#masteriyo-session-limit-clear').show();
				} else {
					console.error('Error clearing sessions');
				}
			},
			error: function (xhr, status, error) {
				console.error('Error clearing sessions:', error);
			},
		});
	});
})(jQuery, window._MASTERIYO_);
