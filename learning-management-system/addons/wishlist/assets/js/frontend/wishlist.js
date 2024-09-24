(function ($, wishListData) {
	'use strict';

	const { __, _x, _n, _nx } = wp.i18n;

	var wishListAddon = {
		toggleWishListIcon: function (btnDOM, set, isOptimistic = false) {
			var $button = $(btnDOM);
			var newAjaxAction;

			if (set) {
				$button.removeClass('masteriyo-add-to-wishlist');
				$button.addClass('masteriyo-remove-from-wishlist active');
				$button.data('is-added-to-wishlist', 'yes');
				$button.attr('title', wishListData.labels.remove_from_wishlist);

				newAjaxAction = 'masteriyo_remove_course_from_wishlist';
			} else {
				$button.removeClass('masteriyo-remove-from-wishlist active');
				$button.addClass('masteriyo-add-to-wishlist');
				$button.data('is-added-to-wishlist', 'no');
				$button.attr('title', wishListData.labels.add_to_wishlist);

				newAjaxAction = 'masteriyo_add_course_to_wishlist';
			}

			if (!isOptimistic) {
				$button
					.closest('.masteriyo-wishlist')
					.find('input[name="action"]')
					.val(newAjaxAction);
			}
		},

		init: function () {
			$(document).ready(function () {
				wishListAddon.initWishListToggleHandler();
			});
		},

		initWishListToggleHandler: function () {
			var inProgress = false;

			$(document.body).on('click', '.masteriyo-wishlist-toggle', function (e) {
				e.preventDefault();

				if (inProgress) {
					return;
				}

				inProgress = true;

				var btnDOM = e.currentTarget;
				var isAddedToWishlist = 'yes' === $(this).data('is-added-to-wishlist');
				var $form = $(this).closest('form');

				if (isAddedToWishlist) {
					wishListAddon.toggleWishListIcon(btnDOM, false, true);
					$.ajax({
						url: wishListData.ajax_url,
						type: 'POST',
						data: $form.serializeArray(),
						success: function (res) {
							if (res.success) {
								wishListAddon.toggleWishListIcon(btnDOM, false);
							} else {
								wishListAddon.toggleWishListIcon(btnDOM, true);
								alert(res.data.message);
							}
						},
						error: function (xhr, status, error) {
							wishListAddon.toggleWishListIcon(btnDOM, true);
							var message = error;

							if (xhr.responseJSON && xhr.responseJSON.message) {
								message = xhr.responseJSON.message;
							}
							if (!message) {
								message = __(
									'Sorry, the course could not be deleted from your wishlist',
									'learning-management-system'
								);
							}
							alert(message);
						},
						complete: function () {
							inProgress = false;
						},
					});
				} else {
					wishListAddon.toggleWishListIcon(btnDOM, true, true);
					$.ajax({
						url: wishListData.ajax_url,
						type: 'POST',
						data: $form.serializeArray(),
						success: function (res) {
							if (res.success) {
								wishListAddon.toggleWishListIcon(btnDOM, true);
							} else {
								wishListAddon.toggleWishListIcon(btnDOM, false);
								alert(res.data.message);
							}
						},
						error: function (xhr, status, error) {
							wishListAddon.toggleWishListIcon(btnDOM, false);
							var message = error;

							if (xhr.responseJSON && xhr.responseJSON.message) {
								message = xhr.responseJSON.message;
							}
							if (!message) {
								message = __(
									'Sorry, the course could not be added your wishlist',
									'learning-management-system'
								);
							}
							alert(message);
						},
						complete: function () {
							inProgress = false;
						},
					});
				}
			});
		},
	};

	wishListAddon.init();
})(jQuery, window._MASTERIYO_WISHLIST_ADDON_);
