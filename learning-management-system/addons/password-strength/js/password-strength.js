// Password strength.
'use strict';

const { __, sprintf } = wp.i18n;

(function ($, _MASTERIYO_, _MASTERIYO_PASSWORD_STRENGTH_) {
	if (undefined === _MASTERIYO_PASSWORD_STRENGTH_.policyList) {
		_MASTERIYO_PASSWORD_STRENGTH_.policyList = function (config) {
			let message = [];

			message.push({
				label: sprintf(
					__('Minimum of %d characters', 'learning-management-system'),
					config.minLength,
				),
				className: 'min-length',
			});

			message.push({
				label: sprintf(
					__('Maximum of %d characters', 'learning-management-system'),
					config.maxLength,
				),
				className: 'max-length',
			});

			if (
				'low' === config.strength ||
				'medium' === config.strength ||
				'high' === config.strength
			) {
				message.push({
					label: sprintf(
						__('At least one uppercase letter', 'learning-management-system'),
					),
					className: 'uppercase',
				});
			}

			if ('medium' === config.strength || 'high' === config.strength) {
				message.push({
					label: sprintf(
						__('At least one number', 'learning-management-system'),
					),
					className: 'number',
				});
			}

			if ('high' === config.strength) {
				message.push({
					label: sprintf(
						__('At least one special character', 'learning-management-system'),
					),
					className: 'special-character',
				});
			}

			return message;
		};
	}

	// Display the message under the password.
	if (undefined === _MASTERIYO_PASSWORD_STRENGTH_.displayPolicyList) {
		_MASTERIYO_PASSWORD_STRENGTH_.displayPolicyList = function (element) {
			var html = ['<div class="masteriyo-password-strength-information"><ul>'];

			var policyList = _MASTERIYO_PASSWORD_STRENGTH_.policyList(
				_MASTERIYO_PASSWORD_STRENGTH_,
			);
			policyList.forEach(function (info) {
				html.push('<li class="' + info.className + '">' + info.label + '</li>');
			});

			html.push('</ul></div>');

			$(element).after(html.join(''));
		};
	}

	if (undefined === _MASTERIYO_PASSWORD_STRENGTH_.validate) {
		_MASTERIYO_PASSWORD_STRENGTH_.validate = function (password, config) {
			var errors = [];

			if (password.length >= config.minLength) {
				errors.push({
					valid: true,
					type: 'min-length',
				});
			} else {
				errors.push({
					valid: false,
					type: 'min-length',
				});
			}

			if (password.length <= config.maxLength) {
				errors.push({
					valid: true,
					type: 'max-length',
				});
			} else {
				errors.push({
					valid: false,
					type: 'max-length',
				});
			}

			if (
				'low' === config.strength ||
				'medium' === config.strength ||
				'high' === config.strength
			) {
				if (password.match(/^(?=.*?[A-Z]).+/)) {
					errors.push({
						valid: true,
						type: 'uppercase',
					});
				} else {
					errors.push({
						valid: false,
						type: 'uppercase',
					});
				}
			}

			if ('medium' === config.strength || 'high' === config.strength) {
				if (password.match(/^(?=.*?[\d+]).+/)) {
					errors.push({
						valid: true,
						type: 'number',
					});
				} else {
					errors.push({
						valid: false,
						type: 'number',
					});
				}
			}

			if ('high' === config.strength) {
				if (password.match(/^(?=.*?[\W+]).+/)) {
					errors.push({
						valid: true,
						type: 'special-character',
					});
				} else {
					errors.push({
						valid: false,
						type: 'special-character',
					});
				}
			}

			// Only valid if all are valid.
			var isValid = errors.reduce(function (prev, curr) {
				return prev && curr.valid;
			}, true);

			return {
				valid: isValid,
				errors: errors,
			};
		};
	}

	if (undefined === _MASTERIYO_PASSWORD_STRENGTH_.createMessage) {
		_MASTERIYO_PASSWORD_STRENGTH_.createMessage = function (message, type) {
			var html =
				'<div class="masteriyo-password-strength-response masteriyo-notify-message masteriyo-alert ' +
				'masteriyo-' +
				type +
				'-msg">';
			html += '<span>' + message + '</span>';
			html += '</div>';

			return html;
		};
	}

	if (undefined === _MASTERIYO_PASSWORD_STRENGTH_.displayMessage) {
		_MASTERIYO_PASSWORD_STRENGTH_.displayMessage = function (element, html) {
			if (_MASTERIYO_PASSWORD_STRENGTH_.showStrength) {
				$(element).after(html);
			}
		};
	}

	/**
	 * Calculate password strength.
	 */
	if (undefined === _MASTERIYO_PASSWORD_STRENGTH_.calculatePasswordStrength) {
		_MASTERIYO_PASSWORD_STRENGTH_.calculatePasswordStrength = function (
			password,
			dictionary,
		) {
			return zxcvbn(password, dictionary);
		};
	}

	/**
	 * Render password strength meter container.
	 */
	if (
		undefined ===
		_MASTERIYO_PASSWORD_STRENGTH_.renderPasswordStrengthMeterContainer
	) {
		_MASTERIYO_PASSWORD_STRENGTH_.renderPasswordStrengthMeterContainer =
			function (element) {
				$(element).after(
					'<div class="masteriyo-password-strength-meter"></div>',
				);
			};
	}

	/**
	 * Render password strength meter.
	 */
	if (undefined === _MASTERIYO_PASSWORD_STRENGTH_.renderPasswordStrengthMeter) {
		_MASTERIYO_PASSWORD_STRENGTH_.renderPasswordStrengthMeter = function (
			element,
			password,
			strength,
		) {
			if (0 === password.length) {
				$(element)
					.attr('class', '')
					.addClass('masteriyo-password-strength-meter');
			} else {
				$(element)
					.attr('class', '')
					.addClass('masteriyo-password-strength-meter strength-' + strength);
			}
		};
	}

	/**
	 * Highlight password policy which are satisfied.
	 */
	if (undefined === _MASTERIYO_PASSWORD_STRENGTH_.highlightPasswordPolicy) {
		_MASTERIYO_PASSWORD_STRENGTH_.highlightPasswordPolicy = function (
			password,
		) {
			var policies = _MASTERIYO_PASSWORD_STRENGTH_.validate(
				password,
				_MASTERIYO_PASSWORD_STRENGTH_,
			);

			policies.errors.forEach(function (policy) {
				var color = policy.valid ? 'green' : '';
				$('.masteriyo-password-strength-information' + ' .' + policy.type).css(
					'color',
					color,
				);
			});
		};
	}
	/**
	 * Convert password strength to text.
	 */
	if (undefined === _MASTERIYO_PASSWORD_STRENGTH_.convertStrengthToText) {
		_MASTERIYO_PASSWORD_STRENGTH_.convertStrengthToText = function (score) {
			var message = '';

			switch (score) {
				case 0:
					message = __('Very Weak', 'learning-management-system');
					break;
				case 1:
					message = __('Weak', 'learning-management-system');
					break;
				case 2:
					message = __('Good', 'learning-management-system');
					break;
				case 3:
					message = __('Strong', 'learning-management-system');
					break;
				case 4:
					message = __('Very Strong', 'learning-management-system');
					break;
				default:
					message = __('Very Weak', 'learning-management-system');
					break;
			}

			return message;
		};
	}

	// Allow to submit form only when the password policy is fulfilled.
	$('#masteriyo-signup--form, #masteriyo-instructor-registration--form').on(
		'submit',
		function (e) {
			var password = $('#password').val();
			if (
				!_MASTERIYO_PASSWORD_STRENGTH_.validate(
					password,
					_MASTERIYO_PASSWORD_STRENGTH_,
				).valid
			) {
				e.preventDefault();
			}
		},
	);

	function validatePassword(password) {
		// Remove previous message.
		$('.masteriyo-password-strength-response').remove();

		// Check for the password strength.
		var result =
			_MASTERIYO_PASSWORD_STRENGTH_.calculatePasswordStrength(password);

		_MASTERIYO_PASSWORD_STRENGTH_.highlightPasswordPolicy(password);
		_MASTERIYO_PASSWORD_STRENGTH_.renderPasswordStrengthMeter(
			'.masteriyo-password-strength-meter',
			password,
			result.score,
		);

		if (0 === password.length) {
			$('.masteriyo-password-strength-information').find('li').css('color', '');
			return;
		}

		var message = _MASTERIYO_PASSWORD_STRENGTH_.convertStrengthToText(
			result.score,
		);

		var type = 0 === result.score || 1 === result.score ? 'danger' : 'success';
		var html = _MASTERIYO_PASSWORD_STRENGTH_.createMessage(message, type);

		const passwordField = $('#password');
		_MASTERIYO_PASSWORD_STRENGTH_.displayMessage(passwordField, html);
	}

	$(document).ready(function () {
		const passwordField = $(
			'#masteriyo-signup--form, #masteriyo-instructor-registration--form',
		).find('#password');

		if (!passwordField.length) return;

		if (passwordField.val().trim() !== '') {
			validatePassword(passwordField.val().trim());
		}
	});

	$('#masteriyo-signup--form, #masteriyo-instructor-registration--form').on(
		'input',
		'#password',
		function () {
			var password = $(this).val().trim();

			validatePassword(password);
		},
	);

	// Initialize
	_MASTERIYO_PASSWORD_STRENGTH_.displayPolicyList(
		'#masteriyo-signup--form #password',
	);
	_MASTERIYO_PASSWORD_STRENGTH_.displayPolicyList(
		'#masteriyo-instructor-registration--form #password',
	);

	if (!_MASTERIYO_PASSWORD_STRENGTH_.showStrength) {
		_MASTERIYO_PASSWORD_STRENGTH_.renderPasswordStrengthMeterContainer(
			'#password',
		);
	}
})(jQuery, window._MASTERIYO_, window._MASTERIYO_PASSWORD_STRENGTH_);
