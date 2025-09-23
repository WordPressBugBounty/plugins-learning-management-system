(function ($, mto_data) {
	var masteriyo_api = {
		deleteCourseReview: function (id, options) {
			var url = mto_data.rootApiUrl + 'masteriyo/v1/courses/reviews/' + id;

			if (options.force_delete) {
				url += '?force_delete=true';
			} else {
				url += '?force_delete=false';
			}

			$.ajax({
				type: 'delete',
				headers: {
					'X-WP-Nonce': mto_data.nonce,
				},
				url: url,
				success: options.onSuccess,
				error: options.onError,
				complete: options.onComplete,
			});
		},
		createCourseReview: function (data, options) {
			var url = mto_data.rootApiUrl + 'masteriyo/v1/courses/reviews';
			$.ajax({
				type: 'post',
				headers: {
					'X-WP-Nonce': mto_data.nonce,
				},
				url: url,
				data: data,
				success: options.onSuccess,
				error: options.onError,
				complete: options.onComplete,
			});
		},
		updateCourseReview: function (id, data, options) {
			var url = mto_data.rootApiUrl + 'masteriyo/v1/courses/reviews/' + id;
			$.ajax({
				type: 'put',
				headers: {
					'X-WP-Nonce': mto_data.nonce,
				},
				url: url,
				data: data,
				success: options.onSuccess,
				error: options.onError,
				complete: options.onComplete,
			});
		},
		getCourseReviewsPageHtml: function (data, options) {
			if (!mto_data.course_id || mto_data.course_id.trim() === "" || mto_data.course_id == 0) {
				var courseId = $("div[data-id]").attr("data-id");
				if (courseId && courseId !== "0") {
					mto_data.course_id = courseId;
				} else {
					return;
				}
			}

			$.ajax({
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'masteriyo_course_reviews_infinite_loading',
					nonce: mto_data.reviews_listing_nonce,
					page: data.page,
					search: data.search,
					rating: data.rating,
					course_id: mto_data.course_id,
				},
				url: mto_data.ajaxURL,
				success: options.onSuccess,
				error: options.onError,
				complete: options.onComplete,
			});
		},
		updateUserCourse: function () {
			$.ajax({
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'masteriyo_course_complete',
					nonce: mto_data.course_complete_nonce,
					course_id: mto_data.course_id,
				},
				url: mto_data.ajaxURL,
				success: function (response) {
					if (response.success) {
						location.reload();
					}
				},
				error: function (jqXHR, textStatus, errorThrown) {
					console.log(jqXHR);
				},
			});
		},
	};
	var masteriyo_utils = {
		getErrorNotice: function (message) {
			return (
				'<div class="masteriyo-notify-message masteriyo-alert masteriyo-danger-msg"><span>' +
				message +
				'</span></div>'
			);
		},
		getSuccessNotice: function (message) {
			return (
				'<div class="masteriyo-notify-message masteriyo-alert masteriyo-success-msg"><span>' +
				message +
				'</span></div>'
			);
		},
	};
	var masteriyo_helper = {
		confirm: function () {
			var res = window.prompt(mto_data.labels.type_confirm);

			if (null === res) return false;
			if ('CONFIRM' !== res) {
				alert(mto_data.labels.try_again);
				return false;
			}
			return true;
		},
		removeNotices: function ($element) {
			$element.find('.masteriyo-notify-message').remove();
		},
		get_rating_markup: function (rating) {
			rating = rating === '' ? 0 : rating;
			rating = parseFloat(rating);
			html = '';
			max_rating = mto_data.max_course_rating;
			rating = rating > max_rating ? max_rating : rating;
			rating = rating < 0 ? 0 : rating;
			stars = mto_data.rating_indicator_markup;

			rating_floor = Math.floor(rating);
			for (i = 1; i <= rating_floor; i++) {
				html += stars.full_star;
			}
			if (rating_floor < rating) {
				html += stars.half_star;
			}

			rating_ceil = Math.ceil(rating);
			for (i = rating_ceil; i < max_rating; i++) {
				html += stars.empty_star;
			}
			return html;
		},
	};
	var masteriyo_dialogs = {
		confirm_delete_course_review: function (options = {}) {
			$(document.body).append(
				$('.masteriyo-confirm-delete-course-review-modal-content').html(),
			);
			$('.masteriyo-modal-confirm-delete-course-review .masteriyo-cancel').on(
				'click',
				function () {
					$(this).closest('.masteriyo-overlay').remove();
				},
			);
			$('.masteriyo-modal-confirm-delete-course-review .masteriyo-delete').on(
				'click',
				function () {
					var $modal = $(this).closest('.masteriyo-overlay');

					$modal.find('.masteriyo-cancel').attr('disabled', true);
					$(this).text(mto_data.labels.deleting);

					if (typeof options.onConfirm === 'function') {
						options.onConfirm(function () {
							$modal.remove();
						});
					}
				},
			);
		},
	};
	var masteriyo = {
		$create_review_form: $('.masteriyo-submit-review-form'),
		create_review_form_class: '.masteriyo-submit-review-form',

		init: function () {
			$(document).ready(function () {
				masteriyo.init_sticky_sidebar();
				masteriyo.init_rating_widget();
				masteriyo.init_course_reviews_menu();
				masteriyo.init_curriculum_accordions_handler();
				masteriyo.init_create_reviews_handler();
				masteriyo.init_edit_reviews_handler();
				masteriyo.init_delete_reviews_handler();
				masteriyo.init_reply_btn_handler();
				masteriyo.init_course_reviews_loader();
				masteriyo.init_password_projected_form_handler();
				masteriyo.init_course_retake_handler();
				masteriyo.init_course_complete_handler();
				masteriyo.init_course_code_copy();
				masteriyo.init_layout_1_curriculum_accordions_handler();
				masteriyo.init_single_course_review_item_visibility();
				masteriyo.init_course_progress_chart();
				masteriyo.toggle_masteriyo_instructor();
				masteriyo.prerequisites_highlight();
			});
		},
		prerequisites_highlight: function () {
			$(document).on(
				'click',
				'.masteriyo-prerequisites-enroll-button',
				function (e) {
					e.preventDefault();

					$('.masteriyo-single-course--prerequisites').addClass(
						'masteriyo-single-course--prerequisites--required prerequisites-required',
					);

					setTimeout(function () {
						$('.masteriyo-single-course--prerequisites').removeClass(
							'masteriyo-single-course--prerequisites--required',
						);
					}, 1000);
				},
			);
		},
		toggle_masteriyo_instructor: function () {
			$(document).on(
				'click',
				'.additional-masteriyo-instructors-more',
				function (e) {
					e.preventDefault();

					var $btn = $(this);
					var id = $btn.attr('aria-controls');
					var $panel = $('#' + id);

					if (!$panel.length) return;

					var expanded = $btn.attr('aria-expanded') === 'true';
					$btn.attr('aria-expanded', String(!expanded));

					if (expanded) {
						$panel.attr('hidden', 'hidden');
					} else {
						$panel.removeAttr('hidden');
						$panel
							.find('a')
							.filter(function () {
								return (
									$.trim($(this).text()) === '' &&
									$(this).children().length === 0
								);
							})
							.remove();
					}
				},
			);
		},
		init_course_progress_chart: function () {
			if (window._courseProgressChartInitialized) return;
			window._courseProgressChartInitialized = true;

			$(document.body).on('click', '.progress-icon', function (e) {
				e.preventDefault();


				var $host = $(this).closest('.completed-component');
				if (!$host.length) return;

				var $existing = $host.find('.course-progress-popover');
				if ($existing.length) {
					$existing.remove();
					$(document).off('click.mto_outside keydown.mto_esc');
					return;
				}


				$('.course-progress-popover').remove();
				$(document).off('click.mto_outside keydown.mto_esc');

				var progress =
					typeof mto_data !== 'undefined' &&
					mto_data &&
					mto_data.course_progress
						? mto_data.course_progress
						: {};

				var keys = Object.keys(progress).filter(function (k) {
					var item = progress[k] || {};
					var total = Number(item.total);
					// Only show components where total > 0
					return total > 0 && k !== 'total';
				});

				function clampPct(v) {
					return Number.isFinite(v) ? Math.min(Math.max(v, 0), 100) : 0;
				}

				function ring(pct) {
					var pctClamped = clampPct(pct);
					var pctInt = Math.round(pctClamped);
					var radius = 90;
					var circumference = 2 * Math.PI * radius;
					var strokeDashoffset =
						circumference - (pctClamped / 100) * circumference;

					return (
						'<div class="masteriyo-course-progress-stats">' +
						'<svg class="masteriyo-course-progress-ring" viewBox="0 0 200 200">' +
						'<circle class="masteriyo-course-progress-ring__background" cx="100" cy="100" r="90"></circle>' +
						'<circle class="masteriyo-course-progress-ring__progress" cx="100" cy="100" r="90" style="stroke-dasharray: ' +
						circumference +
						'; stroke-dashoffset:' +
						strokeDashoffset +
						';"></circle>' +
						'</svg>' +
						'<div class="validity-countdown">' +
						'<span class="validity-countdown--number">' +
						pctInt +
						'%</span>' +
						'</div>' +
						'</div>'
					);
				}

				function row(title, data) {
					var total = Number(data && data.total);
					var completed = Number(data && data.completed);
					var pending = Number(data && data.pending);

					if (!Number.isFinite(total) || total < 0) total = 0;
					if (!Number.isFinite(completed) || completed < 0) completed = 0;
					if (!Number.isFinite(pending) || pending < 0) {
						pending = Math.max(total - completed, 0);
					}

					var pct = total > 0 ? (completed / total) * 100 : 0;
					var titleStr = String(title || '').toUpperCase();

					return (
						'<div class="row">' +
						ring(pct) +
						'<div class="course-progress-text">' +
						'<div class="label">' +
						titleStr +
						'</div>' +
						'<div class="progress-details">' +
						'<div class="progress-completed">' +
						completed +
						' Completed</div>' +
						'<div class="progress-left">' +
						pending +
						' Left</div>' +
						'</div>' +
						'</div>' +
						'</div>'
					);
				}

				$host.find('.course-progress-popover').remove();

				var $pop = $('<div class="course-progress-popover" />').appendTo($host);
				$pop
					.html(keys.map((k) => row(k, progress[k] || {})).join(''))
					.addClass('visible');

				$pop.off('click.mto_popstop').on('click.mto_popstop', function (evt) {
					evt.stopPropagation();
				});

				$(document)
					.off('click.mto_outside')
					.on('click.mto_outside', function (evt) {
						if (
							!$(evt.target).closest('.course-progress-popover, .progress-icon')
								.length
						) {
							$pop.remove();
							$(document).off('click.mto_outside keydown.mto_esc');
						}
					});

				$(document)
					.off('keydown.mto_esc')
					.on('keydown.mto_esc', function (evt) {
						if (evt.key === 'Escape') {
							$pop.remove();
							$(document).off('click.mto_outside keydown.mto_esc');
						}
					});
			});
		},

		init_single_course_review_item_visibility: function () {
			if ('yes' !== mto_data.review_form_enabled) {
				$('.masteriyo-reply-course-review').hide();
				$('.masteriyo-submit-container').hide();
				$('.masteriyo-single-body__main--review-form').hide();
				$('.masteriyo-single-body__main--review-list-content-reply-btn').hide();
			}
			if ('yes' !== mto_data.current_user_logged_in) {
				$('.masteriyo-login-msg').show();
			} else {
				$('.masteriyo-login-msg').hide();

				// Only hide review form if user has already reviewed.
				if ('yes' === mto_data.user_already_reviewed) {
					$('.masteriyo-submit-container').hide();
					$('.masteriyo-single-body__main--review-form').hide();

					// Show message based on review status.
					var message = '';
					if ('yes' === mto_data.user_has_pending_review && mto_data.labels.already_reviewed_pending) {
						message = mto_data.labels.already_reviewed_pending;
					} else if (mto_data.labels.already_reviewed) {
						message = mto_data.labels.already_reviewed;
					}

					if (message && !$('.masteriyo-already-reviewed-msg').length) {
						var messageHtml = '<div class="masteriyo-already-reviewed-msg masteriyo-notify-message masteriyo-alert masteriyo-info-msg" style="clear: both;"><span>' + message + '</span></div>';

						// Add message after the hidden form.
						if ($('.masteriyo-single-body__main--review-form').length) {
							$('.masteriyo-single-body__main--review-form').after(messageHtml);
						} else if ($('.masteriyo-submit-container').length) {
							$('.masteriyo-submit-container').after(messageHtml);
						}
					}
				}
			}
		},

		init_course_reviews_menu: function () {
			/**
			 * Menu toggle handler.
			 */
			$(document.body).on('click', '.menu-toggler', function () {
				if ($(this).siblings('.menu').height() == 0) {
					$(this).siblings('.menu').height('auto');
					$(this).siblings('.menu').css('max-height', '999px');
					return;
				}
				$(this).siblings('.menu').height(0);
			});

			/**
			 * Close menu on click menu item.
			 */
			$('.masteriyo-dropdown .menu li').on('click', function () {
				$(this).closest('.menu').height(0);
			});

			/**
			 * Close menu on outside click.
			 */
			$(document.body).click(function (e) {
				if ($('.masteriyo-dropdown').has(e.target).length > 0) {
					return;
				}
				$('.masteriyo-dropdown .menu').height(0);
			});
		},
		init_rating_widget: function () {
			const $form = masteriyo.$create_review_form;
			let isDoneReset = false;
			let lastSetRating = null;

			// Initial binding of click events to rating stars
			function bindStarClickEvents($starsParent) {
				$starsParent
					.find('.masteriyo-rating-input-icon')
					.each(function (index) {
						$(this)
							.off('click')
							.on('click', function (e) {
								e.stopPropagation(); // Prevent form click listener from firing

								const rating = index + 1;
								lastSetRating = rating;
								isDoneReset = false;

								$form.find('input[name="rating"]').val(rating);

								//update classes or render again safely
								renderStars(rating);
							});
					});
			}

			function renderStars(rating) {
				const $starsParent = $form.find('.masteriyo-rstar');

				// Preserve the input
				let $ratingInput = $form.find('input[name="rating"]');
				if ($ratingInput.length === 0) {
					// If it was removed, re-insert
					$ratingInput = $('<input>', {
						type: 'hidden',
						name: 'rating',
						value: rating,
					});
					$form.append($ratingInput);
				} else {
					$ratingInput.val(rating);
				}

				$starsParent.html(masteriyo_helper.get_rating_markup(rating));
				bindStarClickEvents($starsParent); // Rebind after rendering
			}

			// Handle clicks outside the stars — avoid resetting when unnecessary
			$form.on('click', function (e) {
				const $clickedStar = $(e.target).closest(
					'.masteriyo-rating-input-icon',
				);
				const $withinStars = $(e.target).closest('.masteriyo-rstar');

				// If clicked outside the rating UI
				if ($clickedStar.length === 0 && $withinStars.length === 0) {
					if (isDoneReset) return;

					const currentRating = $form.find('input[name="rating"]').val() || 0;
					renderStars(currentRating);

					isDoneReset = true;
					lastSetRating = null;
				}
			});

			// Initial bind
			bindStarClickEvents($form.find('.masteriyo-rstar'));
		},
		init_create_reviews_handler: function () {
			var isCreating = false;

			masteriyo.$create_review_form.on('submit', function (e) {
				e.preventDefault();

				var $form = masteriyo.$create_review_form;
				var $submit_button = $form.find('button[type="submit"]');
				var data = {
					title: $form.find('input[name="title"]').val(),
					rating: $form.find('input[name="rating"]').val(),
					content: $form.find('[name="content"]').val(),
					parent: $form.find('[name="parent"]').val(),
					course_id: $form.find('[name="course_id"]').val(),
				};

				if (isCreating || 'yes' === $form.data('edit-mode')) return;

				isCreating = true;
				$submit_button.text(mto_data.labels.submitting);
				masteriyo_helper.removeNotices($form);
				masteriyo_api.createCourseReview(data, {
					onSuccess: function () {
						$form.append(
							masteriyo_utils.getSuccessNotice(mto_data.labels.submit_success),
						);
						$form.trigger('reset');

						// Update the flag and hide the form since user has now reviewed.
						mto_data.user_already_reviewed = 'yes';
						setTimeout(function() {
							window.location.reload();
						}, 0);
					},
					onError: function (xhr, status, error) {
						var message = error;

						if (xhr.responseJSON && xhr.responseJSON.message) {
							message = xhr.responseJSON.message;
						}

						$form.append(masteriyo_utils.getErrorNotice(message));
						$submit_button.text(mto_data.labels.submit);
					},
					onComplete: function () {
						isCreating = false;
					},
				});
			});
		},
		init_reply_btn_handler: function () {
			$(document.body).on(
				'click',
				'.masteriyo-reply-course-review, .masteriyo-single-body__main--review-list-content-reply-btn',
				function (e) {
					e.preventDefault();

					// Show the form containers that might be hidden.
					$('.masteriyo-submit-container').show();
					$('.masteriyo-single-body__main--review-form').show();
					// Hide any already reviewed message when replying.
					$('.masteriyo-already-reviewed-msg').hide();

					var $form = masteriyo.$create_review_form;
					var $review = $(this).closest('.masteriyo-course-review');
					var review_id = $review.data('id');
					var $submit_button = $form.find('button[type="submit"]');
					var title = $review.find('.title').data('value');

					// Clear any edit mode data.
					$form.removeData('edit-mode');
					$form.removeData('review-id');

					// Reset form fields for reply.
					$form.find('input[name="title"]').val('');
					$form.find('input[name="rating"]').val(0);
					$form
						.find('.masteriyo-rstar')
						.html(masteriyo_helper.get_rating_markup(0));
					$form.find('[name="content"]').val('');
					$form.find('[name="parent"]').val(review_id);
					$submit_button.text(mto_data.labels.submit);

					$('.masteriyo-form-title').text(
						mto_data.labels.reply_to + ': ' + title,
					);
					$form.find('.masteriyo-title, .masteriyo-rating').hide();
					$form.find('[name="content"]').focus();
					$('html, body').animate(
						{
							scrollTop: $form.offset().top,
						},
						500,
					);
				},
			);
		},
		init_edit_reviews_handler: function () {
			$(document.body).on(
				'click',
				'.masteriyo-edit-course-review',
				function (e) {
					e.preventDefault();

					// Show the form containers that might be hidden.
					$('.masteriyo-submit-container').show();
					$('.masteriyo-single-body__main--review-form').show();
					// Hide any already reviewed message when replying.
					$('.masteriyo-already-reviewed-msg').hide();

					var $form = masteriyo.$create_review_form;
					var $review = $(this).closest('.masteriyo-course-review');
					var review_id = $review.data('id');
					var $submit_button = $form.find('button[type="submit"]');
					var title = $review.find('.title').data('value');
					var rating = $review.find('.rating').data('value');
					var content = $review.find('.content').data('value');
					var parent = $review.find('[name="parent"]').val();

					$form.data('edit-mode', 'yes');
					$form.data('review-id', review_id);
					$form.find('input[name="title"]').val(title);
					$form.find('input[name="rating"]').val(rating);
					$form
						.find('.masteriyo-rstar')
						.html(masteriyo_helper.get_rating_markup(rating));
					$form.find('[name="content"]').val(content);
					$form.find('[name="parent"]').val(parent);
					$submit_button.text(mto_data.labels.update);

					if ($review.is('.is-course-review-reply')) {
						$('.masteriyo-form-title').text(mto_data.labels.edit_reply);
						$form.find('.masteriyo-title, .masteriyo-rating').hide();
						$form.find('[name="content"]').focus();
						$('html, body').animate(
							{
								scrollTop: $form.offset().top,
							},
							500,
						);
					} else {
						$('.masteriyo-form-title').text(
							mto_data.labels.edit_review + ': ' + title,
						);
						$form.find('.masteriyo-title, .masteriyo-rating').show();
						$form.find('input[name="title"]').focus();
						$('html, body').animate(
							{
								scrollTop: $form.offset().top,
							},
							500,
						);
					}
				},
			);

			var isSubmitting = false;

			masteriyo.$create_review_form.on('submit', function (e) {
				e.preventDefault();

				var $form = masteriyo.$create_review_form;
				var review_id = $form.data('review-id');
				var $submit_button = $form.find('button[type="submit"]');
				var data = {
					title: $form.find('input[name="title"]').val(),
					rating: $form.find('input[name="rating"]').val(),
					content: $form.find('[name="content"]').val(),
					parent: $form.find('[name="parent"]').val(),
					course_id: $form.find('[name="course_id"]').val(),
				};

				if (isSubmitting || 'yes' !== $form.data('edit-mode')) return;

				isSubmitting = true;
				$submit_button.text(mto_data.labels.submitting);
				masteriyo_helper.removeNotices($form);
				masteriyo_api.updateCourseReview(review_id, data, {
					onSuccess: function () {
						$form.append(
							masteriyo_utils.getSuccessNotice(mto_data.labels.update_success),
						);
						$submit_button.text(mto_data.labels.update);
						$form.trigger('reset');
						window.location.reload();
					},
					onError: function (xhr, status, error) {
						var message = error;

						if (xhr.responseJSON && xhr.responseJSON.message) {
							message = xhr.responseJSON.message;
						}

						$form.append(masteriyo_utils.getErrorNotice(message));
						$submit_button.text(mto_data.labels.update);
					},
					onComplete: function () {
						isSubmitting = false;
					},
				});
			});
		},
		init_delete_reviews_handler: function () {
			var isDeletingFlags = {};

			$(document.body).on(
				'click',
				'.masteriyo-delete-course-review',
				function (e) {
					e.preventDefault();

					var $review = $(this).closest('.masteriyo-course-review');
					var $delete_button = $(this);
					var review_id = $review.data('id');
					var $replies = $('[name=parent][value=' + review_id + ']');

					if (isDeletingFlags[review_id]) return;

					masteriyo_dialogs.confirm_delete_course_review({
						onConfirm: function (closeModal) {
							isDeletingFlags[review_id] = true;

							masteriyo_api.deleteCourseReview(review_id, {
								force_delete: $replies.length === 0,

								onSuccess: function () {
									if ($review.hasClass('is-course-review-reply')) {
										var isDeleteReplyContainer =
											$review.siblings().length === 0;
										var $parentReview = $review
											.closest(
												'.masteriyo-course-review-replies, .masteriyo-single-body__main--reply-lists',
											)
											.prev();

										if (
											isDeleteReplyContainer &&
											$parentReview.hasClass('masteriyo-delete-review-notice')
										) {
											$parentReview.fadeOut(500, function () {
												$(this).remove();
											});
										}

										$review.fadeOut(500, function () {
											if (isDeleteReplyContainer) {
												$review
													.closest(
														'.masteriyo-course-review-replies, .masteriyo-single-body__main--reply-lists',
													)
													.remove();
											}
											$(this).remove();
										});
										return;
									}

									// Check if this is the user's main review (not a reply).
									// A main review has parent = 0, and if user can delete it, it's their review.
									var parentValue = $review.find('input[name="parent"]').val();
									var isUserMainReview = parentValue == '0' && !$review.hasClass('is-course-review-reply');

									if (
										$review
											.next()
											.hasClass(
												'masteriyo-course-review-replies, .masteriyo-single-body__main--reply-lists',
											)
									) {
										$review.after(mto_data.review_deleted_notice);
									}
									$review.remove();
									mto_data.course_reviews_count--;
									if (0 >= mto_data.course_reviews_count) {
										$('.masteriyo-stab--treviews').hide();
										$('.masteriyo-stab--turating').hide();
										$('.masteriyo-course-reviews-filters').hide();
									}

									// If user deleted their main review, show the review form again.
									if (isUserMainReview) {
										mto_data.user_already_reviewed = 'no';
										mto_data.user_has_pending_review = 'no';
										$('.masteriyo-submit-container').show();
										$('.masteriyo-single-body__main--review-form').show();
										// Hide any already reviewed message.
										$('.masteriyo-already-reviewed-msg').remove();

										// Reset the form to create mode
										var $form = masteriyo.$create_review_form;
										$form.removeData('edit-mode');
										$form.removeData('review-id');
										$form.find('input[name="title"]').val('');
										$form.find('input[name="rating"]').val(0);
										$form.find('.masteriyo-rstar').html(masteriyo_helper.get_rating_markup(0));
										$form.find('[name="content"]').val('');
										$form.find('[name="parent"]').val(0);
										$form.find('button[type="submit"]').text(mto_data.labels.submit);
										$('.masteriyo-form-title').text('');
										$form.find('.masteriyo-title, .masteriyo-rating').show();
									}
								},
								onError: function (xhr, status, error) {
									var message = error;

									if (xhr.responseJSON && xhr.responseJSON.message) {
										message = xhr.responseJSON.message;
									}

									$review.append(masteriyo_utils.getErrorNotice(message));
									$delete_button.find('.text').text(mto_data.labels.delete);
								},
								onComplete: function () {
									isDeletingFlags[review_id] = false;
									closeModal();
								},
							});
						},
					});
				},
			);
		},
		init_course_retake_handler: function () {
			$(document.body).on('click', '.masteriyo-retake-btn', function (e) {
				e.preventDefault();
				$(document.body).append(
					$('.masteriyo-confirm-retake-course-modal-content').html(),
				);
				$('.masteriyo-modal-confirm-retake-course .masteriyo-cancel').on(
					'click',
					function () {
						$(this).closest('.masteriyo-overlay').remove();
					},
				);
				/**
				 * Restart a course. Delete all the previous data.
				 */
				$('.masteriyo-modal-confirm-retake-course .masteriyo-confirm').on(
					'click',
					function () {
						window.location.href = mto_data.retake_url;
						var $modal = $(this).closest('.masteriyo-overlay');

						$modal.find('.masteriyo-cancel').attr('disabled', true);
						$(this).text(mto_data.labels.loading);
					},
				);
			});
		},
		init_sticky_sidebar: function () {
			var $content_ref = $('.masteriyo-single-course--main').get(0);

			if ($content_ref) {
				$(window).scroll(function () {
					var scroll_position = $(window).scrollTop();
					var content_y = $content_ref.offsetTop;
					var content_y2 = content_y + $content_ref.offsetHeight;
					var isSticky = false;

					if (scroll_position > content_y && scroll_position < content_y2)
						isSticky = true;
					if (isSticky) {
						$('.masteriyo-single-course-stick').css({
							position: 'sticky',
							top: '7.5rem',
						});
					} else {
						$('.masteriyo-single-course-stick').css({
							position: 'relative',
							top: '0',
						});
					}
				});
			}
		},
		init_curriculum_accordions_handler: function () {
			// Curriculum Tab
			$(document.body).on('click', '.masteriyo-cheader', function () {
				$(this).parent('.masteriyo-stab--citems').toggleClass('active');
				if (
					$('.masteriyo-stab--citems').length ===
					$('.masteriyo-stab--citems.active').length
				) {
					expandAllSections();
				}
				if (
					$('.masteriyo-stab--citems').length ===
					$('.masteriyo-stab--citems').not('.active').length
				) {
					collapseAllSections();
				}
			});
			var isCollapsedAll = false;
			$(document.body).on(
				'click',
				'.masteriyo-expand-collapse-all',
				function () {
					if (isCollapsedAll) {
						expandAllSections();
					} else {
						collapseAllSections();
					}
				},
			);

			// Expand all
			function expandAllSections() {
				$('.masteriyo-stab--citems').addClass('active');
				$('.masteriyo-expand-collapse-all').text(mto_data.labels.collapse_all);
				isCollapsedAll = false;
			}

			// Collapse all
			function collapseAllSections() {
				$('.masteriyo-stab--citems').removeClass('active');
				$('.masteriyo-expand-collapse-all').text(mto_data.labels.expand_all);
				isCollapsedAll = true;
			}
		},
		init_course_reviews_loader: function () {
			var isLoadingReviews = false;
			var currentPage = 1;
			var searchText = '';
			var prevSearchVal = '';
			var rating = '';

			$('button#masteriyo-course-reviews-search-button').on('click', () => {
				var $button = $('button#masteriyo-course-reviews-search-button');

				prevSearchVal = $button.siblings('input').val();
			});

			$('button.masteriyo-load-more').on('click', function () {
				if (isLoadingReviews) {
					return;
				}
				const prevRatingState = $(
					'select#masteriyo-course-reviews-ratings-select',
				).val();
				var $button = $(this);

				isLoadingReviews = true;
				$button.text(mto_data.labels.loading);

				masteriyo_api.getCourseReviewsPageHtml(
					{
						page: currentPage + 1,
						search: searchText || prevSearchVal,
						rating: rating || prevRatingState,
					},
					{
						onSuccess: function (res) {
							if (res.success) {
								if (res.data.view_load_more_button) {
									$button.show();
								} else {
									$button.remove();
								}
								currentPage += 1;
								$('.masteriyo-course-reviews-list').append(res.data.html);
								$('.masteriyo-single-body__main--review-lists').append(
									res.data.html,
								);
								$('.course-reviews .masteriyo-danger-msg').remove();
							}
						},
						onError: function (xhr, status, error) {
							var message = error;

							if (
								xhr.responseJSON &&
								xhr.responseJSON.data &&
								xhr.responseJSON.data.message
							) {
								message = xhr.responseJSON.data.message;
							}

							if (!message) {
								message = mto_data.labels.see_more_reviews;
							}

							if (!$('.course-reviews .masteriyo-danger-msg').length) {
								$button.after(masteriyo_utils.getErrorNotice(message));
							}
						},
						onComplete: function () {
							isLoadingReviews = false;
							if (currentPage >= mto_data.course_review_pages) {
								$button.remove();
							}
							$button.text(mto_data.labels.see_more_reviews);
						},
					},
				);
			});

			$(document.body).on(
				'click change',
				'button#masteriyo-course-reviews-search-button, select#masteriyo-course-reviews-ratings-select',
				function () {
					masteriyo.handleCourseReviewSearchAndFilter();
				},
			);

			$('input#masteriyo-course-reviews-search-field').on(
				'keypress',
				function (event) {
					if (13 === event.which) {
						masteriyo.handleCourseReviewSearchAndFilter();
					}
				},
			);
		},

		/**
		 * Handles the search and filtering functionality for the course reviews.
		 *
		 * @since 1.9.3
		 */
		handleCourseReviewSearchAndFilter: function () {
			var $button = $('button#masteriyo-course-reviews-search-button');
			var $loadMoreButton = $('button.masteriyo-load-more');

			var searchValue = $button.siblings('input').val();
			var ratingValue = $(
				'select#masteriyo-course-reviews-ratings-select',
			).val();

			if (!searchValue && !ratingValue) {
				return;
			}

			isLoadingReviews = true;
			currentPage = 1;

			masteriyo_api.getCourseReviewsPageHtml(
				{ page: currentPage, search: searchValue, rating: ratingValue },
				{
					onSuccess: function (res) {
						if (res.success) {
							searchText = searchValue;
							rating = ratingValue;
							if (res.data.view_load_more_button) {
								$loadMoreButton.show();
							} else {
								$loadMoreButton.hide();
							}

							$(
								'.masteriyo-course-reviews-list, .masteriyo-single-body__main--review-lists',
							).html(res.data.html);
							$('.course-reviews .masteriyo-danger-msg').remove();
						}
					},
					onError: function (xhr, status, error) {
						var message = error;
						searchText = '';
						rating = '';

						if (
							xhr.responseJSON &&
							xhr.responseJSON.data &&
							xhr.responseJSON.data.message
						) {
							message = xhr.responseJSON.data.message;
						}

						if (!message) {
							message = mto_data.labels.see_more_reviews;
						}

						if (!$('.course-reviews .masteriyo-danger-msg').length) {
							$button
								.parents(
									'.masteriyo-course-reviews-filters, .masteriyo-single-body__main--user-review__search-rating',
								)
								.after(masteriyo_utils.getErrorNotice(message));
						}
					},
					onComplete: function () {
						isLoadingReviews = false;
					},
				},
			);
		},

		/**
		 * Initializes the password-protected form handler.
		 *
		 * This function sets up event listeners for the password-protected form modal,
		 * allowing users to enter a password and access a protected project.
		 *
		 * @since 1.8.0
		 */
		init_password_projected_form_handler: function () {
			var $passwordProjectedBtn = $('.masteriyo-password-protected');
			var protectedCourseId = 0;

			var $submitBtn = $(
				'#masteriyoCoursePasswordProtectedModal .masteriyo-submit',
			);
			var originalSubmitBtnText = $submitBtn.text();
			var submitBtnText = originalSubmitBtnText;

			/** Submit data. */
			function onSubmit(e) {
				e.preventDefault();

				submitBtnText = $submitBtn.data('loading-text');
				$submitBtn.text(submitBtnText);
				$submitBtn.prop('disabled', true);

				var password = $('#masteriyoPostPassword').val();
				if (!password) {
					if (mto_data.labels && mto_data.labels.password_not_empty) {
						$('#passwordError').text(mto_data.labels.password_not_empty).show();

						submitBtnText = originalSubmitBtnText;
						$submitBtn.text(submitBtnText);
						$submitBtn.prop('disabled', false);
					}
					return;
				}
				$.ajax({
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'masteriyo_course_password_protection',
						nonce: mto_data.password_protected_nonce,
						password,
						course_id: protectedCourseId,
					},
					url: mto_data.ajaxURL,
					success: function (response) {
						submitBtnText = originalSubmitBtnText;
						$submitBtn.text(submitBtnText);
						$submitBtn.prop('disabled', false);

						if (response.success) {
							$('#masteriyoPostPassword').val('');
							$('#passwordError').text('');
							$('#masteriyoCoursePasswordProtectedModal').addClass(
								'masteriyo-hidden',
							);

							if (response.data && response.data.start_url) {
								window.open(response.data.start_url);
							}
						} else {
							if (response.data && response.data.message) {
								$('#passwordError').text(response.data.message).show();
							}
						}
					},
					error: function (xhr) {
						submitBtnText = originalSubmitBtnText;
						$submitBtn.text(submitBtnText);
						$submitBtn.prop('disabled', false);

						var errorMessage = 'An error occurred';
						if (
							xhr.responseJSON &&
							xhr.responseJSON.data &&
							xhr.responseJSON.data.message
						) {
							errorMessage = xhr.responseJSON.data.message;
						}
						$('#passwordError').text(errorMessage).show();
					},
				});
			}

			$passwordProjectedBtn.on('click', function (e) {
				e.preventDefault();
				protectedCourseId = $(this).attr('href').split('=')[1];
				$('#masteriyoCoursePasswordProtectedModal').removeClass(
					'masteriyo-hidden',
				);
			});

			$('#masteriyoCoursePasswordProtectedModal .masteriyo-cancel').on(
				'click',
				function (e) {
					e.preventDefault();
					$('#masteriyoCoursePasswordProtectedModal').addClass(
						'masteriyo-hidden',
					);
				},
			);

			$('#masteriyoCoursePasswordProtectedModal form').on(
				'submit',
				function (e) {
					e.preventDefault();
				},
			);

			$('#masteriyoCoursePasswordProtectedModal .masteriyo-submit').on(
				'click',
				function (e) {
					onSubmit(e);
				},
			);

			$('#masteriyoCoursePasswordProtectedModal form').keypress(function (e) {
				if (e.which == 13) {
					onSubmit(e);
				}
			});
		},
		/**
		 * Initializes the course completion handler for single course.
		 *
		 * This function sets up event listeners for the course complete handler modal,
		 * allowing users to the status of the user course from active to inactive.
		 *
		 * @since 1.8.3
		 */
		init_course_complete_handler: function () {
			$(document.body).on('click', '.masteriyo-course-complete', function (e) {
				e.preventDefault();
				var courseId = $(this).data('course-id');
				masteriyo_api.updateUserCourse(courseId);
			});
		},
		/**
		 * Initializes the course for google classroom code handler.
		 *
		 * This function allows the user to copy the course code.
		 *
		 * @since 1.8.3
		 */
		init_course_code_copy() {
			$(document.body).on('click', '.copy-button-code', function (e) {
				e.preventDefault();
				const textToCopy = $('.masteriyo-copy-this-text').text();

				navigator.clipboard.writeText(textToCopy);

				var $svg_copied = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" ><path d="m12 15 2 2 4-4"/><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg>`;

				$(this).html($svg_copied);
				setTimeout(() => {
					var $svg = `<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/>
				<path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/>
			</svg>`;
					$(this).html($svg);
				}, 2000);
			});
		},

		/**
		 * Initializes the layout 1 curriculum accordions handler for single course.
		 *
		 * This function sets up click event listeners for the curriculum accordion headers
		 * to toggle the accordion body open/closed. It also handles the expand/collapse all
		 * accordion bodies functionality.
		 *
		 * @since 1.10.0
		 */
		init_layout_1_curriculum_accordions_handler: function () {
			var $accordionHeaders = $(
				'.masteriyo-single-body__main--curriculum-content-bottom__accordion--header',
			);
			var $expandButton = $(
				'.masteriyo-single-body__main--curriculum-content-top--expand-btn',
			);
			var $accordions = $(
				'.masteriyo-single-body__main--curriculum-content-bottom__accordion',
			);

			function toggleAccordion() {
				$(this)
					.parent(
						'.masteriyo-single-body__main--curriculum-content-bottom__accordion',
					)
					.toggleClass('active');
			}

			function toggleAllAccordions() {
				var isExpanded = $expandButton.data('expanded');
				var collapseText = $expandButton.data('collapse-all-text');
				var expandText = $expandButton.data('expand-all-text');

				if (!isExpanded) {
					$accordions.addClass('active');
					$expandButton.data('expanded', true).text(collapseText);
				} else {
					$accordions.removeClass('active');
					$expandButton.data('expanded', false).text(expandText);
				}
			}

			$accordionHeaders.on('click', toggleAccordion);
			$expandButton.on('click', toggleAllAccordions);
		},
	};

	masteriyo.init();
})(jQuery, window.masteriyo_data);

function masteriyo_select_single_course_page_tab(e, tabContentSelector) {
	jQuery(
		'.masteriyo-single-course--main__content .masteriyo-tab.active-tab',
	).removeClass('active-tab');
	jQuery('.masteriyo-single-course--main__content .tab-content').addClass(
		'masteriyo-hidden',
	);

	jQuery(e.target).addClass('active-tab');
	jQuery(
		'.masteriyo-single-course--main__content ' + tabContentSelector,
	).removeClass('masteriyo-hidden');

	if (tabContentSelector === '#overview') {
		setTimeout(() => {
			if (!document.querySelector('#masteriyo-custom-fields').initialized) {
				initCustomFieldsRenderer();
				document.querySelector('#masteriyo-custom-fields').initialized = true;
			}
		}, 50);
	}
}

/**
 * Switches the active tab on the single course page.
 *
 * @since 1.10.0
 *
 * @param {Object} e - The event object.
 */
function masteriyoSelectSingleCoursePageTabById(e) {
	const $clickedItem = jQuery(e.target);

	const $parentContainer = $clickedItem.closest(
		'.masteriyo-single-body__main--tabbar',
	);

	$parentContainer.find('.active-item').removeClass('active-item');
	$clickedItem.addClass('active-item');

	const tabId = $clickedItem.data('tab-id');
	const $tabContent = jQuery(`#${tabId}`);

	$tabContent
		.closest('.masteriyo-single-body__main--content')
		.children()
		.addClass('masteriyo-hidden');
	$tabContent.removeClass('masteriyo-hidden');
}

/**
 * Initializes the custom fields renderer for the single course page.
 *
 * This function dynamically renders custom fields for a course based on the
 * field definitions provided by the `customFieldRegistry`. It ensures that
 * fields are displayed in the correct order, handles different field types
 * (e.g., text, password, boolean), and provides functionality for toggling
 * password visibility.
 *
 * @since 1.10.0 [Free]
 */

function initCustomFieldsRenderer() {
	const singleCourseElementTextContent = document.getElementById('masteriyo-course-values')?.textContent;
    if (!singleCourseElementTextContent) return;

	let customFieldsInitialized = false;

	if (customFieldsInitialized) return;

	const container = document.querySelector('.custom-fields-container');
	const courseValues = JSON.parse(
		singleCourseElementTextContent,
	);

	function nl2br(str) {
		return typeof str === 'string' ? str.replace(/\n/g, '<br>') : str;
	}

	function isEmptyValue(displayValue) {
		return (
			displayValue === undefined ||
			displayValue === null ||
			(!displayValue && displayValue !== false && displayValue !== 0)
		);
	}

	function checkRegistry() {
		if (window.customFieldRegistry) {
			const fields = window.customFieldRegistry.getFields('Basic', 'all');
			renderFields(fields);
			customFieldsInitialized = true;
			return true;
		}
		return false;
	}
	function renderFields(fields) {
		const sortedFields = fields.sort(
			(a, b) => (a.priority || 0) - (b.priority || 0),
		);

		sortedFields.forEach((field) => {
			const value = courseValues[field.name] ?? '';
			container.insertAdjacentHTML('beforeend', createFieldHTML(field, value));
		});

		initPasswordToggles();
		initSeeMoreButtons();
	}

	function createFieldHTML(field, value) {
		let displayValue = '';
		let isPassword = false;
		let isBoolean = false;

		switch (field.type) {
			case 'select':
				const selectOption = field.options.find(
					(opt) => opt.value === value?.value,
				);
				displayValue = selectOption?.label || '';
				break;
			case 'radio':
				const radioOption = field.options.find((opt) => opt.value === value);
				displayValue = radioOption?.label || '';
				break;

			case 'checkbox':
			case 'switch':
				displayValue = value ? 'Yes' : 'No';
				isBoolean = true;
				break;

			case 'password':
				displayValue = '•'.repeat(value?.length || 0);
				isPassword = true;
				break;

			case 'textarea':
				displayValue = value;
				break;

			default:
				displayValue = value;
		}

		if (isEmptyValue(displayValue)) {
			return '';
		}

		const maxTextLength = 80;
		const isTextareaOverflow =
			field.type === 'textarea' &&
			typeof displayValue === 'string' &&
			displayValue.length > maxTextLength;

		return `
		<div class="masteriyo-field" data-type="${field.type}">
			<div class="field-header">
				<span class="field-label">${field.label} </span>
				<div class="field-value">
					${
						isBoolean
							? `
						<span class="boolean-indicator ${value ? 'yes' : 'no'}">
							${value ? '✓' : '✕'}
						</span>
						<span class="boolean-text">${displayValue}</span>
					`
							: ''
					}
					${
						isPassword
							? `
						<span class="password-display">${displayValue}</span>
						<button class="toggle-password" data-value="${value}">
							Show
						</button>
					`
							: ''
					}
					${
						['select', 'radio'].includes(field.type)
							? `
						<span class="option-badge">${displayValue}</span>
					`
							: ''
					}
					${
						!isBoolean &&
						!isPassword &&
						!['select', 'radio'].includes(field.type)
							? isTextareaOverflow
								? `
									<span class="text-value">
										<span class="truncated-text">${nl2br(displayValue.substring(0, maxTextLength) + '...')}</span>
										<span class="full-text" style="display: none;">${nl2br(displayValue)}</span>
										<p class="see-more-less">See more</p>
									</span>
								`
								: `
									<span class="text-value">${nl2br(displayValue)}</span>
								`
							: ''
					}
				</div>
			</div>
		</div>
	`;
	}

	function initPasswordToggles() {
		document.querySelectorAll('.toggle-password').forEach((button) => {
			button.addEventListener('click', (e) => {
				e.preventDefault();
				const display = button.previousElementSibling;
				const realValue = button.dataset.value;

				if (display.textContent === realValue) {
					display.textContent = '•'.repeat(realValue.length);
					button.textContent = 'Show';
				} else {
					display.textContent = realValue;
					button.textContent = 'Hide';
				}
			});
		});
	}

	function initSeeMoreButtons() {
		document.querySelectorAll('.see-more-less').forEach((button) => {
			button.addEventListener('click', function (e) {
				e.preventDefault();
				const textValue = this.closest('.text-value');
				const truncated = textValue.querySelector('.truncated-text');
				const full = textValue.querySelector('.full-text');
				const isExpanded = truncated.style.display === 'none';

				if (isExpanded) {
					truncated.style.display = '';
					full.style.display = 'none';
					this.textContent = 'See more';
				} else {
					truncated.style.display = 'none';
					full.style.display = '';
					this.textContent = 'See less';
				}
			});
		});
	}

	if (!checkRegistry()) {
		const interval = setInterval(() => {
			if (checkRegistry()) {
				clearInterval(interval);
			}
		}, 100);
	}
}

/**
 * Initializes the custom fields renderer when the DOM content \is fully loaded.
 *
 * This ensures that the custom fields for the single course page are rendered
 * dynamically after the page has been fully loaded and the DOM is ready.
 *
 * @since 1.10.0 [Free]
 */

document.addEventListener('DOMContentLoaded', function () {
	initCustomFieldsRenderer();
});
