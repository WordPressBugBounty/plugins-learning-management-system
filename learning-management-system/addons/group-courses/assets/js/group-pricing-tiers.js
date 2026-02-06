/**
 * Group Pricing Tiers - Frontend Interactivity
 *
 * Handles tier selection, seat input, and price calculation for multi-tier group pricing.
 *
 * @since 2.1.0
 */

(function ($) {
	'use strict';

	/**
	 * Handle tier selection (fixed seats only)
	 */
	function selectTier($tier) {
		const $container = $tier.closest('.masteriyo-group-pricing-tiers');
		const $buyButton = $container.find('.masteriyo-group-tier-buy-button');

		// Deselect all tiers
		$container.find('.masteriyo-group-pricing-tier').removeClass('selected');

		// Select this tier
		$tier.addClass('selected');

		// Enable buy button
		$buyButton.prop('disabled', false);
	}

	/**
	 * Get checkout URL with tier and seat data (fixed seats only)
	 */
	function getCheckoutURL($tier) {
		const tierId = $tier.data('tier-id');
		const seatCount = parseInt($tier.data('group-size'));

		// Get course ID from localized data
		const courseId = masteriyoGroupPricing.courseId;

		// Build checkout URL
		const checkoutURL = masteriyoGroupPricing.urls.checkout || '/checkout';
		const url = new URL(checkoutURL, window.location.origin);
		url.searchParams.set('add-to-cart', courseId);
		url.searchParams.set('group_purchase', 'yes');
		url.searchParams.set('group_tier_id', tierId);
		url.searchParams.set('group_seats', seatCount);

		return url.toString();
	}

	/**
	 * Initialize when DOM is ready (fixed seats only)
	 */
	$(document).ready(function () {
		// Handle tier click
		$(document).on('click', '.masteriyo-group-pricing-tier', function (e) {
			selectTier($(this));
		});

		// Handle buy button click
		$(document).on('click', '.masteriyo-group-tier-buy-button', function (e) {
			e.preventDefault();

			const $container = $(this).closest('.masteriyo-group-pricing-tiers');
			const $selectedTier = $container.find(
				'.masteriyo-group-pricing-tier.selected',
			);

			if ($selectedTier.length === 0) {
				alert('Please select a pricing tier');
				return;
			}

			// Redirect to checkout
			const checkoutURL = getCheckoutURL($selectedTier);
			window.location.href = checkoutURL;
		});
	});
})(jQuery);
