/**
 * Masteriyo Courses JS.
 * @namespace
 */
(function ($, mto_data) {
	'use strict';

	var filtersSidebar = {
		$sidebar: $('.masteriyo-courses-filters'),

		openFiltersSidebar: function () {
			var scrollTop =
				self.pageYOffset ||
				document.documentElement.scrollTop ||
				document.body.scrollTop;
			var sidebarTopPadding = 20;

			this.$sidebar.addClass('masteriyo-expanded');

			if (
				$('#wpadminbar').css('position') === 'fixed' ||
				scrollTop < $('#wpadminbar').height() - sidebarTopPadding
			) {
				this.$sidebar.addClass('masteriyo-add-admin-bar-margin');
			}
			masteriyo_helper.lockScrolling();
		},

		closeFiltersSidebar: function () {
			this.$sidebar.removeClass('masteriyo-expanded');
			this.$sidebar.removeClass('masteriyo-add-admin-bar-margin');
			masteriyo_helper.unlockScrolling();
		},

		isFiltersSidebarOpen: function () {
			return this.$sidebar.hasClass('masteriyo-expanded');
		},

		toggleFiltersSidebar: function () {
			if (this.isFiltersSidebarOpen()) {
				this.closeFiltersSidebar();
			} else {
				this.openFiltersSidebar();
			}
		},
	};

	/**
	 * MasteriyoCourses namespace.
	 * @type {Object}
	 */
	var MasteriyoCourses = {
		/**
		 * The current view mode of the courses.
		 * @type {string}
		 */
		currentViewMode: getCookie('MasteriyoCoursesViewMode'),

		/**
		 * The view mode items in the UI.
		 * @type {jQuery}
		 */
		viewModeItems: null,

		/**
		 * Initializes the MasteriyoCourses module.
		 */
		init: function () {
			this.viewModeItems = $('.masteriyo-courses-view-mode-item');
			this.bindUIActions();

			if (
				'grid-view' === this.currentViewMode ||
				'list-view' === this.currentViewMode
			) {
				this.setViewMode(this.currentViewMode);
			}

			$(document).ready(function () {
				MasteriyoCourses.init_password_projected_form_handler();
				MasteriyoCourses.init_course_filters();
				MasteriyoCourses.init_course_sorting();
			});
		},

		/**
		 * Binds event handlers to elements.
		 */
		bindUIActions: function () {
			this.viewModeItems.on('click', '.view-mode', function () {
				var mode = $(this).data('mode');

				MasteriyoCourses.setViewMode(mode);

				MasteriyoCourses.viewModeItems.removeClass('active');

				$(this).closest('.masteriyo-courses-view-mode-item').addClass('active');
			});
		},

		/**
		 * Sets the view mode for the courses.
		 * @param {string} mode - The view mode to set ('list-view' or 'grid-view').
		 */
		setViewMode: function (mode) {
			setCookie('MasteriyoCoursesViewMode', mode, 365);

			var coursesClass =
				$(
					'.masteriyo-courses-view-mode-section .masteriyo-courses-view-mode-item-lists',
				).data('courses-class') || 'masteriyo-course';

			var courseItems = $(
				`.masteriyo-course-list-display-section .${coursesClass}`,
			);

			courseItems.removeClass('list-view');
			courseItems.removeClass('grid-view');

			MasteriyoCourses.viewModeItems.removeClass('active');

			var activeItem = this.viewModeItems
				.find('.view-mode[data-mode="' + mode + '"]')
				.parent();
			activeItem.addClass('active');

			courseItems.addClass(mode);

			this.currentViewMode = mode;
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
		 * Initialize course filters.
		 *
		 * @since 1.16.0
		 */
		init_course_filters: function () {
			$(document.body).on(
				'click',
				'.masteriyo-toggle-course-filters-sidebar',
				function () {
					filtersSidebar.toggleFiltersSidebar();
				},
			);

			$(document.body).on(
				'click',
				'.masteriyo-close-filters-sidebar, .masteriyo-course-filter-sidebar-overlay',
				function () {
					filtersSidebar.closeFiltersSidebar();
				},
			);

			$(window).on('resize', function () {
				if ($(this).height() <= 768) {
					filtersSidebar.closeFiltersSidebar();
				}
			});

			$(document.body).on(
				'click',
				'button.masteriyo-apply-price-filter',
				function (e) {
					$(this).closest('form').submit();
				},
			);

			$(document.body).on(
				'change',
				'.masteriyo-courses-filters select, .masteriyo-courses-filters input[type="checkbox"]',
				function () {
					$(this).closest('form').submit();
				},
			);

			$(document.body).on(
				'change',
				'.masteriyo-courses-filters select[name="price-type"]',
				function () {
					if ($(this).val() === 'free') {
						$('.masteriyo-price-filter-section').addClass('masteriyo-hidden');
					} else {
						$('.masteriyo-price-filter-section').removeClass(
							'masteriyo-hidden',
						);
					}
				},
			);

			$(document.body).on(
				'click',
				'.masteriyo-see-more-categories',
				function (e) {
					e.preventDefault();
					$(this).addClass('masteriyo-hidden');
					$('.masteriyo-overflowed-category').removeClass('masteriyo-hidden');
					$('.masteriyo-see-less-categories').removeClass('masteriyo-hidden');
				},
			);

			$(document.body).on(
				'click',
				'.masteriyo-see-less-categories',
				function (e) {
					e.preventDefault();
					$(this).addClass('masteriyo-hidden');
					$('.masteriyo-overflowed-category').addClass('masteriyo-hidden');
					$('.masteriyo-see-more-categories').removeClass('masteriyo-hidden');
				},
			);
		},

		/**
		 * Initialize the course sorting.
		 *
		 * @since 1.16.0
		 */
		init_course_sorting: function () {
			$(document.body).on(
				'change',
				'select.masteriyo-courses-order-by',
				function () {
					var order = $(this).find('option:selected').data('order');

					$('input.masteriyo-courses-sorting-order').val(order);

					$(this).closest('form').submit();
				},
			);
		},
	};

	/**
	 * Initialize the MasteriyoCourses module.
	 */
	MasteriyoCourses.init();

	/**
	 * Helper function to set a cookie.
	 *
	 * @since 1.6.11
	 *
	 * @param {string} name - The name of the cookie.
	 * @param {string} value - The value to be stored in the cookie.
	 * @param {number} days - The number of days until the cookie expires.
	 */
	function setCookie(name, value, days) {
		var expires = '';
		var DAY_IN_MILLISECONDS = 24 * 60 * 60 * 1000;

		if (days) {
			var date = new Date();
			date.setTime(date.getTime() + days * DAY_IN_MILLISECONDS);
			expires = '; expires=' + date.toGMTString();
		}

		document.cookie = name + '=' + value + expires + '; path=/';
	}

	/**
	 * Helper function to get the value of a cookie by name.
	 *
	 * @since 1.6.11
	 *
	 * @param {string} name - The name of the cookie to retrieve.
	 * @returns {string|null} The value of the cookie, or null if not found.
	 */
	function getCookie(name) {
		var cookieName = name + '=';
		var cookieArray = document.cookie.split(';');
		for (var i = 0; i < cookieArray.length; i++) {
			var cookie = cookieArray[i];
			while (cookie.charAt(0) === ' ') {
				cookie = cookie.substring(1, cookie.length);
			}
			if (cookie.indexOf(cookieName) === 0) {
				return cookie.substring(cookieName.length, cookie.length);
			}
		}
		return null;
	}
})(jQuery, window.masteriyo_data);
