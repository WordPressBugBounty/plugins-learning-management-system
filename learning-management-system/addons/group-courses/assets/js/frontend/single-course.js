/**
 * Implements functionality for enrolling in group courses within the Masteriyo platform.
 *
 * @since 1.9.0
 *
 * @param {Object} $ - The jQuery object.
 * @param {Object} masteriyoData - Global data object containing API endpoints and nonces.
 */
(function ($, masteriyoData) {
	'use strict';

	var masteriyoGroupCourses = {
		/**
		 * Initialize group courses functionality.
		 *
		 * @since 1.9.0
		 */
		init: function () {
			this.bindUIActions();
		},

		/**
		 * Bind event listeners to UI elements.
		 *
		 * @since 1.9.0
		 */
		bindUIActions: function () {
			$(
				'#masteriyoGroupCoursesEnrollBtn .masteriyo-group-course__buy-now-button',
			).on('click', this.openGroupCoursesModal.bind(this));

			$(
				'#masteriyoGroupCoursesEnrollModal .masteriyo-group-course__exit-popup',
			).on('click', this.closeGroupCoursesModal.bind(this));

			$(
				'#masteriyoGroupCoursesEnrollModal .masteriyo-group-course__lists--sync-button',
			).on('click', this.fetchGroups.bind(this));

			$(
				'#masteriyoGroupCoursesEnrollModal .masteriyo-group-course__lists--footer_checkout-button',
			).on('click', this.addToCart.bind(this));

			$(document).on(
				'change',
				'#masteriyoGroupCoursesEnrollModal .masteriyo-group-course__lists--list-checkbox',
				this.toggleAddToCartButton,
			);
		},

		/**
		 * Toggle the "Add to Cart" button based on group selection.
		 *
		 * @since 1.9.0
		 */
		toggleAddToCartButton: function () {
			var $addToCartButton = $(
				'#masteriyoGroupCoursesEnrollModal .masteriyo-group-course__lists--footer_checkout-button',
			);

			var isChecked =
				$(
					'#masteriyoGroupCoursesEnrollModal .masteriyo-group-course__lists--list-checkbox:checked',
				).length > 0;

			$addToCartButton.attr('disabled', !isChecked);

			$addToCartButton.toggleClass(
				'masteriyo-group-course__lists--footer_checkout-button--disabled',
				!isChecked,
			);
		},

		/**
		 * Add selected groups to the cart.
		 *
		 * @since 1.9.0
		 */
		addToCart: function () {
			var checkedGroups = $(
				'#masteriyoGroupCoursesEnrollModal .masteriyo-group-course__lists--list-checkbox:checked',
			)
				.map(function () {
					return this.value;
				})
				.get();

			var courseId = masteriyoData.course_id;
			var addToCartButton = $(
				`#course-${courseId} .masteriyo-single-course--btn, .masteriyo-enroll-btn`,
			);

			if (checkedGroups.length === 0) {
				addToCartButton.addClass(
					'masteriyo-group-course__lists--footer_checkout-button--disabled',
				);
				return;
			} else {
				addToCartButton.removeClass(
					'masteriyo-group-course__lists--footer_checkout-button--disabled',
				);
			}

			var addToCartUrl = masteriyoData.add_to_cart_url;

			if (addToCartUrl) {
				var url = new URL(addToCartUrl, window.location.origin);
				url.searchParams.set('group_ids', checkedGroups.join(','));
				window.location.href = url.toString();
			}
		},

		/**
		 * Fetch groups from the server.
		 *
		 * @since 1.9.0
		 */
		fetchGroups: function () {
			var $fetchBtn = $(
				'#masteriyoGroupCoursesEnrollModal .masteriyo-group-course__lists--sync-button',
			);
			$fetchBtn.text($fetchBtn.data('fetching-text')).attr('disabled', true);

			var $loadingText = $(
				'#masteriyoGroupCoursesEnrollModal .masteriyo-groups-loading-text',
			);

			$loadingText.removeClass('masteriyo-hidden');

			$.ajax({
				url: masteriyoData.restUrl,
				type: 'GET',
				dataType: 'json',
				data: { per_page: 20, status: 'publish' },
				beforeSend: function (xhr) {
					xhr.setRequestHeader('X-WP-Nonce', masteriyoData.wp_rest_nonce);
				},
				success: function (response) {
					$fetchBtn.text($fetchBtn.data('fetch-text')).attr('disabled', false);
					$loadingText.addClass('masteriyo-hidden');

					masteriyoGroupCourses.renderGroupsList(response.data || []);
				},
				error: function (response) {
					$fetchBtn.text($fetchBtn.data('fetch-text')).attr('disabled', false);
					$loadingText.addClass('masteriyo-hidden');
					$(
						'#masteriyoGroupCoursesEnrollModal .masteriyo-group-course__lists',
					).hide();
				},
			});
		},

		/**
		 * Render the list of groups in the modal.
		 *
		 * @since 1.9.0
		 *
		 * @param {Array} groups - The groups to render.
		 */
		renderGroupsList: function (groups) {
			var $lists = $(
				'#masteriyoGroupCoursesEnrollModal .masteriyo-group-course__lists',
			);

			var $list = $(
				'#masteriyoGroupCoursesEnrollModal .masteriyo-group-course__lists--list',
			);

			$list.html('');

			var $emptyMessage = $(
				'#masteriyoGroupCoursesEnrollModal .masteriyo-group-course__empty-state',
			);

			if (groups.length === 0) {
				$emptyMessage.show();
				$lists.hide();
				return;
			} else {
				$emptyMessage.hide();
				$lists.show();
			}

			var maxGroupSize = parseInt(masteriyoData.max_group_size);

			groups.forEach(function (group) {
				var isGroupDisabled =
					maxGroupSize && group.emails.length > parseInt(maxGroupSize)
						? true
						: false;

				var $item = $(`
								<li class="masteriyo-group-course__lists--list-item ${
									isGroupDisabled
										? 'masteriyo-group-course__lists--list-item_disabled'
										: ''
								}">
									<input type="checkbox" class="masteriyo-group-course__lists--list-checkbox" name="group_ids[]" value="${
										group.id
									}" id="masteriyo-group-course-option-${group.id}" ${
										isGroupDisabled ? 'disabled' : ''
									}>
									<label class="masteriyo-group-course__lists--list-item_label ${
										isGroupDisabled
											? 'masteriyo-group-course__lists--list-item_label_disabled'
											: ''
									}" for="masteriyo-group-course-option-${group.id}">
									${group.title}
									</label>
					
									<div class="masteriyo-group-course__lists--list-item_members" title="${
										masteriyoData.members_text
									}">
										<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
											<path fill="#7A7A7A" d="M21 20.4c-.6 0-1-.4-1-1v-1.6c0-1-.7-1.9-1.7-2.2-.5-.1-.9-.7-.7-1.2.1-.5.7-.9 1.2-.7 1.9.5 3.2 2.2 3.2 4.1v1.6c0 .5-.4 1-1 1Zm-4.9 0c-.6 0-1-.4-1-1v-1.6c0-1.3-1-2.3-2.3-2.3H6.3c-1.3 0-2.3 1-2.3 2.3v1.6c0 .6-.4 1-1 1s-1-.4-1-1v-1.6c0-2.4 1.9-4.3 4.3-4.3h6.5c2.4 0 4.3 1.9 4.3 4.3v1.6c0 .5-.5 1-1 1Zm-6.6-8.2c-2.4 0-4.3-1.9-4.3-4.3s1.9-4.3 4.3-4.3 4.3 1.9 4.3 4.3-1.9 4.3-4.3 4.3Zm0-6.6c-1.3 0-2.3 1-2.3 2.3 0 1.3 1 2.3 2.3 2.3 1.3 0 2.3-1 2.3-2.3 0-1.2-1-2.3-2.3-2.3Zm5.8 6.5c-.4 0-.9-.3-1-.8-.1-.5.2-1.1.7-1.2.8-.2 1.4-.8 1.6-1.6.4-1.2-.4-2.5-1.6-2.8-.5-.1-.9-.7-.7-1.2.2-.5.7-.9 1.2-.7 2.3.6 3.7 2.9 3.1 5.2-.4 1.5-1.6 2.7-3.1 3.1h-.2Z" />
										</svg>
										${group.emails.length}
									</div>
								</li>
							`);
				$list.append($item);
			});
		},

		/**
		 * Show the group courses enrollment modal.
		 *
		 * @since 1.9.0
		 *
		 * @param {Event} e - The event object.
		 */
		openGroupCoursesModal: function (e) {
			e.preventDefault();
			$('#masteriyoGroupCoursesEnrollModal').removeClass('masteriyo-hidden');

			var $list = $(
				'#masteriyoGroupCoursesEnrollModal .masteriyo-group-course__lists--list',
			);

			var maxGroupSize = parseInt(masteriyoData.max_group_size);

			if (maxGroupSize) {
				$('#masteriyoGroupCoursesEnrollModal .masteriyo-info-msg')
					.show()
					.html(`<span>${masteriyoData.max_group_size_msg}</span>`);
			} else {
				$('#masteriyoGroupCoursesEnrollModal .masteriyo-info-msg')
					.hide()
					.html('');
			}

			if ($list.find('.masteriyo-group-courses-item').length === 0) {
				this.fetchGroups();
			}
		},

		/**
		 * Hide the group courses enrollment modal.
		 *
		 * @since 1.9.0
		 */
		closeGroupCoursesModal: function () {
			$('#masteriyoGroupCoursesEnrollModal').addClass('masteriyo-hidden');
		},
	};

	if (masteriyoData) {
		masteriyoGroupCourses.init();
	}
})(jQuery, window.MASTERIYO_GROUP_COURSES_DATA);
