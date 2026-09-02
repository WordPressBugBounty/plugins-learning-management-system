/**
 * Group Pricing Tiers - Frontend Interactivity
 *
 * Handles tier selection, seat input, and price calculation for multi-tier group pricing.
 *
 * @since 3.1.0
 */

(function ($) {
	'use strict';

	/**
	 * Format price with currency symbol
	 */
	function formatPrice(amount, currency) {
		// Use Masteriyo price format if available, otherwise use a simple format
		if (
			typeof masteriyoGroupPricing !== 'undefined' &&
			masteriyoGroupPricing.currency
		) {
			const symbol = masteriyoGroupPricing.currency.symbol || '$';
			const position = masteriyoGroupPricing.currency.position || 'left';
			const decimals = parseInt(masteriyoGroupPricing.currency.decimals) || 2;
			const decimalSeparator = masteriyoGroupPricing.currency.decimal_separator || '.';
			const thousandSeparator = masteriyoGroupPricing.currency.thousand_separator || ',';

			let number = parseFloat(amount).toFixed(decimals);
			
			// Split into integer and decimal parts
			let parts = number.split('.');
			let integerPart = parts[0];
			let decimalPart = parts.length > 1 ? decimalSeparator + parts[1] : '';

			// Add thousand separator
			integerPart = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, thousandSeparator);
			
			const formatted = integerPart + decimalPart;

			if (position === 'right' || position === 'right_space') {
				return formatted + (position === 'right_space' ? ' ' : '') + symbol;
			}
			return symbol + (position === 'left_space' ? ' ' : '') + formatted;
		}

		// Fallback simple format
		return '$' + parseFloat(amount).toFixed(2);
	}

	/**
	 * Calculate price for a tier based on seat count
	 */
	function calculateTierPrice($tier, seatCount) {
		const seatModel = $tier.data('seat-model');
		const pricingModel = $tier.data('pricing-model');
		const pricingType = $tier.data('pricing-type');
		const regularPrice = parseFloat($tier.data('regular-price')) || 0;
		const salePrice = parseFloat($tier.data('sale-price')) || 0;
		const basePrice = salePrice > 0 ? salePrice : regularPrice;

		let totalPrice = 0;
		let pricePerSeat = basePrice;
		let breakdown = [];

		if (seatModel === 'fixed') {
			// Fixed seats: total price is just the tier price
			totalPrice = basePrice;
		} else if (seatModel === 'variable') {
			if (pricingModel === 'tiered') {
				// Tiered pricing: find applicable tier
				const priceTiers = $tier.data('price-tiers');
				if (priceTiers && Array.isArray(priceTiers)) {
					for (let i = 0; i < priceTiers.length; i++) {
						const tier = priceTiers[i];
						if (seatCount >= tier.from && seatCount <= tier.to) {
							pricePerSeat = parseFloat(tier.per_seat_price) || 0;
							break;
						}
					}
					totalPrice = pricePerSeat * seatCount;

					// Create breakdown
					priceTiers.forEach(function (tier) {
						breakdown.push({
							range: tier.from + '-' + tier.to + ' seats',
							price: formatPrice(tier.per_seat_price) + '/seat',
						});
					});
				}
			} else {
				// Per seat pricing: simple multiplication
				totalPrice = basePrice * seatCount;
			}
		}

		return {
			totalPrice: totalPrice,
			pricePerSeat: pricePerSeat,
			breakdown: breakdown,
			pricingType: pricingType,
		};
	}

	/**
	 * Update seat hint text
	 */
	function updateSeatHint($tier, seatCount) {
		const $hint = $tier.find('.masteriyo-group-tier-seats-hint');
		const pricingModel = $tier.data('pricing-model');
		const pricingType = $tier.data('pricing-type');
		const calculation = calculateTierPrice($tier, seatCount);

		let hintHTML = '';

		if (pricingModel === 'tiered' && calculation.breakdown.length > 0) {
			// Show tier breakdown
			calculation.breakdown.forEach(function (item) {
				hintHTML +=
					'<div class="tier-breakdown">' +
					item.range +
					': ' +
					item.price +
					'</div>';
			});
		}

		// Add total
		const intervalText = pricingType === 'recurring' ? '/month' : '';
		const totalLabel = pricingType === 'one_time' ? '(one-time)' : '';
		hintHTML +=
			'<div class="tier-total"><div class="tier-total-label">Total:</div> ' +
			formatPrice(calculation.totalPrice) +
			intervalText +
			'</div>';

		$hint.html(hintHTML);
	}

	/**
	 * Handle tier selection
	 */
	function selectTier($tier) {
		const $container = $tier.closest('.masteriyo-group-pricing-tiers');
		const $buyButton = $container.find('.masteriyo-group-tier-buy-button');

		// Deselect all tiers
		$container.find('.masteriyo-group-pricing-tier').removeClass('selected');
		$container.find('.masteriyo-group-tier-seat-selector').hide();

		// Select this tier
		$tier.addClass('selected');

		// Show seat selector for variable seats
		const seatModel = $tier.data('seat-model');
		if (seatModel === 'variable') {
			const $seatSelector = $tier.find('.masteriyo-group-tier-seat-selector');
			$seatSelector.show();

			// Initialize hint
			const $input = $tier.find('.masteriyo-group-tier-seats-input');
			const seatCount =
				parseInt($input.val()) || parseInt($tier.data('min-seats'));
			updateSeatHint($tier, seatCount);
		}

		// Enable buy button
		$buyButton.prop('disabled', false);
	}

	/**
	 * Get checkout URL with tier and seat data
	 */
	function getCheckoutURL($tier) {
		const $input = $tier.find('.masteriyo-group-tier-seats-input');
		const tierId = $tier.data('tier-id');
		const seatModel = $tier.data('seat-model');
		let seatCount = 0;

		if (seatModel === 'variable') {
			seatCount = parseInt($input.val()) || parseInt($tier.data('min-seats'));
		} else {
			seatCount = parseInt($tier.data('group-size'));
		}

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
	 * Initialize when DOM is ready
	 */
	$(document).ready(function () {
		// Server-side has pre-selected the first tier, just initialize hint if needed
		const $firstTier = $('.masteriyo-group-pricing-tier.selected').first();
		if ($firstTier.length > 0 && $firstTier.data('seat-model') === 'variable') {
			// Initialize hint for variable seats that are pre-selected
			const $input = $firstTier.find('.masteriyo-group-tier-seats-input');
			const seatCount =
				parseInt($input.val()) || parseInt($firstTier.data('min-seats'));
			updateSeatHint($firstTier, seatCount);
		}

		// Handle tier click
		$(document).on('click', '.masteriyo-group-pricing-tier', function (e) {
			// Don't trigger if clicking on input
			if ($(e.target).is('input')) {
				return;
			}
			selectTier($(this));
		});

		// Handle seat input - update hint in real-time
		$(document).on('input', '.masteriyo-group-tier-seats-input', function () {
			const $tier = $(this).closest('.masteriyo-group-pricing-tier');
			const minSeats = parseInt($tier.data('min-seats'));
			const maxSeats = parseInt($tier.data('max-seats'));
			let seatCount = parseInt($(this).val());

			// Only update hint if value is valid number
			if (!isNaN(seatCount) && seatCount >= minSeats && seatCount <= maxSeats) {
				updateSeatHint($tier, seatCount);
			}
		});

		// Validate and enforce min/max when user leaves the field
		$(document).on('blur', '.masteriyo-group-tier-seats-input', function () {
			const $tier = $(this).closest('.masteriyo-group-pricing-tier');
			const minSeats = parseInt($tier.data('min-seats'));
			const maxSeats = parseInt($tier.data('max-seats'));
			let seatCount = parseInt($(this).val());

			// Validate range and enforce limits
			if (isNaN(seatCount) || seatCount < minSeats) {
				seatCount = minSeats;
				$(this).val(minSeats);
			} else if (seatCount > maxSeats) {
				seatCount = maxSeats;
				$(this).val(maxSeats);
			}

			updateSeatHint($tier, seatCount);
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

			// Validate seat count for variable seats
			const seatModel = $selectedTier.data('seat-model');
			if (seatModel === 'variable') {
				const $input = $selectedTier.find('.masteriyo-group-tier-seats-input');
				const seatCount = parseInt($input.val());
				const minSeats = parseInt($selectedTier.data('min-seats'));
				const maxSeats = parseInt($selectedTier.data('max-seats'));

				if (isNaN(seatCount) || seatCount < minSeats || seatCount > maxSeats) {
					alert(
						'Please enter a valid number of seats between ' +
							minSeats +
							' and ' +
							maxSeats,
					);
					return;
				}
			}

			// Redirect to checkout
			const checkoutURL = getCheckoutURL($selectedTier);
			window.location.href = checkoutURL;
		});
	});
})(jQuery);
