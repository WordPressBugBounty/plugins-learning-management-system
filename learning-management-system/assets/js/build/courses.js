/**
 * Masteriyo Courses JS.
 * @namespace
 */
(function ($, mto_data) {
	'use strict';

	var filtersSidebar = {
		openFiltersSidebar: function () {
			var $sidebar = $('.masteriyo-courses-filters');
			if (!$sidebar.length) {
				console.warn('.masteriyo-courses-filters element not found.');
				return;
			}

			var scrollTop =
				window.pageYOffset ||
				document.documentElement.scrollTop ||
				document.body.scrollTop;
			var sidebarTopPadding = 20;
			$sidebar.addClass('masteriyo-expanded');

			if (
				$('#wpadminbar').css('position') === 'fixed' ||
				scrollTop < $('#wpadminbar').height() - sidebarTopPadding
			) {
				$sidebar.addClass('masteriyo-add-admin-bar-margin');
			}

			if (typeof masteriyo_helper !== 'undefined') {
				masteriyo_helper.lockScrolling();
			}
		},

		closeFiltersSidebar: function () {
			var $sidebar = $('.masteriyo-courses-filters');
			if (!$sidebar.length) {
				return;
			}

			$sidebar.removeClass('masteriyo-expanded');
			$sidebar.removeClass('masteriyo-add-admin-bar-margin');

			if (typeof masteriyo_helper !== 'undefined') {
				masteriyo_helper.unlockScrolling();
			}
		},

		isFiltersSidebarOpen: function () {
			var $sidebar = $('.masteriyo-courses-filters');
			return $sidebar.length && $sidebar.hasClass('masteriyo-expanded');
		},

		toggleFiltersSidebar: function () {
			if (this.isFiltersSidebarOpen()) {
				// this.closeFiltersSidebar();
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
			});

			$(document).ready(function () {
				$('.masteriyo-course-archive--aside').each(function () {
					if ($(this).children().length === 0) {
						$(this).remove();
					}
				});
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
	};
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

	var filtersAndSorting = {
		currentPage: 0,
		refreshCallsCount: 0,

		refreshCourses: function () {
			filtersAndSorting.refreshCallsCount++;

			var data = {
				action: 'masteriyo_course_filter_and_sorting',
			};

			$('.masteriyo-courses-filters')
				.find('input,select,textarea')
				.each(function () {
					var type = $(this).prop('type');
					var name = $(this).prop('name');

					if (type === 'checkbox') {
						name = name.trim().replace(/\[\]$/, '');

						if (!Array.isArray(data[name])) {
							data[name] = [];
						}

						if ($(this).is(':checked')) {
							data[name].push($(this).val());
						}
					} else if (type === 'radio') {
						if ($(this).is(':checked')) {
							data[name] = $(this).val();
						}
					} else {
						data[name] = $(this).val();
					}
				});

			data._wpnonce = $(
				'[name="masteriyo_course_filter_and_sorting_nonce"]',
			).val();
			data.page = filtersAndSorting.currentPage;
			data.search = $('.search-field.masteriyo-input').val();
			data.orderby = $('select.masteriyo-courses-order-by').val();
			data.order = $('input.masteriyo-courses-sorting-order').val();
			data.layout = $('.masteriyo-course-list-display-section').data('layout');

			$.ajax({
				type: 'POST',
				url: masteriyo_data.ajaxURL,
				dataType: 'json',
				data: data,
				beforeSend: function (jqXHR) {
					$('.masteriyo-courses-wrapper')
						.addClass('masteriyo-applying-filter')
						.block(getBlockLoadingConfiguration());
				},
				success: function (response, textStatus, jqXHR) {
					var data = response.data;

					if (
						filtersAndSorting.refreshCallsCount <= 1 &&
						data &&
						data.fragments
					) {
						$.each(data.fragments, function (key, value) {
							if (!masteriyo.fragments || masteriyo.fragments[key] !== value) {
								$(key).replaceWith(value);
							}
							$(key).unblock();
						});
						masteriyo.fragments = data.fragments;
					}
					$('.masteriyo-group-course__group-button').addClass(
						'masteriyo-hidden',
					);
					MasteriyoCourses.init();
				},
				error: function (jqXHR, textStatus, errorThrown) {},
				complete: function (jqXHR, textStatus) {
					if (filtersAndSorting.refreshCallsCount <= 1) {
						$('.masteriyo-courses-wrapper')
							.removeClass('masteriyo-applying-filter')
							.unblock();
					}

					filtersAndSorting.refreshCallsCount--;
				},
			});
		},
	};

	var masteriyo = {
		init: function () {
			$(document).ready(function () {
				masteriyo.init_course_search();
				masteriyo.init_course_filters();
				masteriyo.init_course_sorting();
				masteriyo.toggleFiltersSidebar();
				masteriyo.init_price_slider();
				masteriyo.toggle_masteriyo_instructor();
				masteriyo.init_course_progress_chart();
			});
		},
		init_course_progress_chart: function () {
			$(document.body).on('click', '.progress-icon', function (e) {
				e.preventDefault();
				e.stopPropagation();

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

				var summaryData = $(this)
					.children('.masteriyo-summary-data')
					.attr('data-summary');
				var progress = summaryData ? JSON.parse(summaryData) : {};

				function computePct(data) {
					var total = Number(data.total) || 0;
					var completed = Number(data.completed) || 0;
					if (total <= 0) return 0;
					return (Math.max(completed, 0) / total) * 100;
				}

				function generateProgressHTML(component, data) {
					var total = Number(data.total);
					var completed = Number(data.completed);
					var pending = Number(data.pending);

					if (!Number.isFinite(total) || total < 0) total = 0;
					if (!Number.isFinite(completed) || completed < 0) completed = 0;
					if (!Number.isFinite(pending) || pending < 0) {
						pending = Math.max(total - completed, 0);
					}

					var pct = total > 0 ? (completed / total) * 100 : 0;
					var titleStr = String(component).toUpperCase();
					var radius = 90;
					var circumference = 2 * Math.PI * radius;
					var strokeDashoffset = circumference - (pct / 100) * circumference;

					return (
						'<div class="row">' +
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
						Math.round(pct) +
						'%</span>' +
						'</div>' +
						'</div>' +
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

				var progressHTML = '';
				Object.keys(progress).forEach(function (component) {
					if (component === 'total') return;
					var componentData = progress[component] || {};
					var total = Number(componentData.total);

					if (total > 0) {
						progressHTML += generateProgressHTML(component, componentData);
					}
				});

				if (!progressHTML) {
					$pop.remove();
					return;
				}

				$pop.html(progressHTML).addClass('visible');

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

					$(document)
						.off('keydown.mto_esc')
						.on('keydown.mto_esc', function (evt) {
							if (evt.key === 'Escape') {
								$panel.attr('hidden', 'hidden');
							}
						});

					$(document)
						.off('click.mto_outside')
						.on('click.mto_outside', function (evt) {
							if (
								!$panel.is(evt.target) &&
								$panel.has(evt.target).length === 0 &&
								!$btn.is(evt.target) &&
								$btn.has(evt.target).length === 0
							) {
								$panel.attr('hidden', 'hidden');
								$btn.attr('aria-expanded', 'false');
								$(document).off('click.mto_outside'); // cleanup
							}
						});
				},
			);
		},
		init_course_search: function () {
			$(document.body).on(
				'submit',
				'form.masteriyo-course-search',
				function (e) {
					e.preventDefault();
					filtersAndSorting.currentPage = 0;
					filtersAndSorting.refreshCourses();
				},
			);
		},

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
				'input',
				'.masteriyo-price-from-filter, .masteriyo-price-to-filter',
				(function () {
					let timer;
					return function () {
						clearTimeout(timer);
						timer = setTimeout(function () {
							filtersAndSorting.currentPage = 0;
							filtersAndSorting.refreshCourses();
						}, 500);
					};
				})(),
			);

			$(document.body).on('change', '.range-min, .range-max', function () {
				filtersAndSorting.currentPage = 0;
				filtersAndSorting.refreshCourses();
			});

			$(document.body).on(
				'change',
				'.masteriyo-courses-filters select, .masteriyo-courses-filters input[type="checkbox"],.masteriyo-courses-filters input[type="radio"]',
				function () {
					filtersAndSorting.currentPage = 0;
					filtersAndSorting.refreshCourses();
				},
			);

			$(document.body).on('click', '.masteriyo-clear-filters', function (e) {
				e.preventDefault();

				filtersAndSorting.currentPage = 0;

				$('.masteriyo-rating-filter-link').removeClass('active');
				$('.search-field.masteriyo-input').val('');
				$('.masteriyo-courses-filters select[name="price-type"]').val('');

				const minVal = $('.range-min').attr('min') || 0;
				const maxVal = $('.range-max').attr('max') || 100;

				const $priceFrom = $(
					'.masteriyo-courses-filters input[name="price-from"]',
				);
				const $priceTo = $('.masteriyo-courses-filters input[name="price-to"]');

				$priceFrom.val(minVal).trigger('input').trigger('change');
				$priceTo.val(maxVal).trigger('input').trigger('change');

				$('.masteriyo-price-progress').removeAttr('style');

				$(
					'.masteriyo-courses-filters input[name="categories[]"], ' +
						'.masteriyo-courses-filters input[name="difficulties[]"], ' +
						'.masteriyo-courses-filters input[name="rating[]"], ' +
						'.masteriyo-courses-filters input[name="price-type"]',
				).prop('checked', false);

				$(document.body).trigger('masteriyo_clear_course_filters');

				filtersAndSorting.refreshCourses();
			});

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

		init_course_sorting: function () {
			$(document.body).on(
				'change',
				'select.masteriyo-courses-order-by',
				function () {
					var order = $(this).find('option:selected').data('order');
					$('input.masteriyo-courses-sorting-order').val(order);
					filtersAndSorting.currentPage = 0;
					filtersAndSorting.refreshCourses();
				},
			);
		},
		toggleFiltersSidebar: function () {
			$('.masteriyo-filter-section--heading').on('click', function () {
				var $section = $(this).closest('.masteriyo-filter-section');
				var $content = $section.find('.masteriyo-filter-wrapper');
				var $arrow = $section.find('.toggle-arrow');
				var $seeMore = $section.find('.masteriyo-see-more-categories');
				var $seeLess = $section.find('.masteriyo-see-less-categories');

				$content.slideToggle(200);
				$arrow.toggleClass('rotated');

				if ($arrow.hasClass('rotated')) {
					$seeMore.slideUp(200);
					$seeLess.slideUp(200);
				} else {

					var $hiddenCategories = $section.find(
						'.masteriyo-overflowed-category.masteriyo-hidden',
					);
					var $visibleCategories = $section.find(
						'.masteriyo-overflowed-category:not(.masteriyo-hidden)',
					);

					if ($hiddenCategories.length > 0) {
						$seeMore.slideDown(200);
						$seeLess.hide();
					} else if ($visibleCategories.length > 0) {

						$seeMore.hide();
						$seeLess.slideDown(200);
					}
				}
			});

			$('.masteriyo-see-more-categories').on('click', function (e) {
				e.preventDefault();
				var $section = $(this).closest('.masteriyo-filter-section');
				var $hiddenCategories = $section.find('.masteriyo-overflowed-category');
				var $seeMore = $(this);
				var $seeLess = $section.find('.masteriyo-see-less-categories');

				$hiddenCategories.removeClass('masteriyo-hidden');

				$seeMore.hide();
				$seeLess.show();
			});


			$('.masteriyo-see-less-categories').on('click', function (e) {
				e.preventDefault();
				var $section = $(this).closest('.masteriyo-filter-section');
				var $hiddenCategories = $section.find('.masteriyo-overflowed-category');
				var $seeMore = $section.find('.masteriyo-see-more-categories');
				var $seeLess = $(this);

				$hiddenCategories.addClass('masteriyo-hidden');

				$seeMore.show();
				$seeLess.hide();
			});
		},
		init_price_slider: function () {
			const $rangeInput = $('.masteriyo-price-range-input input');
			const $priceInput = $('.masteriyo-price-filter--input input');
			const $range = $('.masteriyo-price-progress');

			const maxAttr = parseFloat($rangeInput.eq(1).attr('max'));
			const priceGap = Math.max(1, Math.floor(maxAttr * 0.05));

			$priceInput.on('input', function (e) {
				let minPrice = parseFloat($priceInput.eq(0).val());
				let maxPrice = parseFloat($priceInput.eq(1).val());

				if (maxPrice - minPrice >= priceGap && maxPrice <= maxAttr) {
					if ($(e.target).hasClass('masteriyo-price-from-filter')) {
						$rangeInput.eq(0).val(minPrice);
						$range.css('left', (minPrice / maxAttr) * 100 + '%');
					} else {
						$rangeInput.eq(1).val(maxPrice);
						$range.css('right', 100 - (maxPrice / maxAttr) * 100 + '%');
					}
				}
			});

			$rangeInput.on('input', function (e) {
				let minVal = parseFloat($rangeInput.eq(0).val());
				let maxVal = parseFloat($rangeInput.eq(1).val());

				if (maxVal - minVal < priceGap) {
					if ($(e.target).hasClass('range-min')) {
						$rangeInput.eq(0).val(maxVal - priceGap);
						minVal = maxVal - priceGap;
					} else {
						$rangeInput.eq(1).val(minVal + priceGap);
						maxVal = minVal + priceGap;
					}
				}

				$priceInput.eq(0).val(minVal);
				$priceInput.eq(1).val(maxVal);
				$range.css('left', (minVal / maxAttr) * 100 + '%');
				$range.css('right', 100 - (maxVal / maxAttr) * 100 + '%');
			});
		},
	};

	/**
	 * Initialization.
	 */
	MasteriyoCourses.init();
	masteriyo.init();

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
