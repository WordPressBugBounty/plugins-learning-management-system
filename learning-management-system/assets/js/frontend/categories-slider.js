/**
 * Initializes a Swiper slider for the categories section on the frontend.
 *
 * @param {jQuery} $ - The jQuery object.
 * @param {Object} sliderData - The data for configuring the slider.
 * @param {number} [sliderData.columns=4] - The number of slides to display per view.
 * @param {number} [sliderData.space_between=30] - The space between each slide.
 * @param {number} [sliderData.delay=2500] - The delay in milliseconds between each slide transition.
 */
(function ($, sliderData) {
	var swiper = new Swiper('.swiper', {
		direction: 'horizontal',
		slidesPerView:
			undefined !== sliderData.columns ? parseInt(sliderData.columns) : 4,
		spaceBetween:
			undefined !== sliderData.space_between
				? parseInt(sliderData.space_between)
				: 30,
		autoplay: {
			reverseDirection:
				undefined !== sliderData.reverse_direction
					? 'true' === sliderData.reverse_direction
						? true
						: false
					: false,
			delay: undefined !== sliderData.delay ? parseInt(sliderData.delay) : 2500,
		},
	});
})(jQuery, window._MASTERIYO_CATEGORIES_SLIDER_DATA_);
