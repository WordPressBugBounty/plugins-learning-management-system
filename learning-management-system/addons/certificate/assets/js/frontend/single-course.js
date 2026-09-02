/**
 * Implements functionality for sharing certificate preview within the Masteriyo platform.
 *
 * @since 2.14.4 [free]
 *
 * @param {Object} $ - The jQuery object.
 */
(function ($) {
	'use strict';

	var masteriyoCertificateShare = {
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
				'#masteriyoCertificateShareButton .masteriyo-certificate-share__share-button',
			).on('click', this.openCertificateShareModal.bind(this));

			$(
				'#masteriyoCertificateShareModal .masteriyo-certificate-share__exit-popup',
			).on('click', this.closeCertificateShareModal.bind(this));
		},

		/**
		 * Show the certificate share modal.
		 *
		 * @since 2.14.4 [free]
		 *
		 * @param {Event} e - The event object.
		 */
		openCertificateShareModal: function (e) {
			e.preventDefault();
			$('#masteriyoCertificateShareModal').removeClass('masteriyo-hidden');
		},

		/**
		 * Hide the certificate share modal.
		 *
		 * @since 2.14.4 [free]
		 */
		closeCertificateShareModal: function () {
			$('#masteriyoCertificateShareModal').addClass('masteriyo-hidden');
		},
	};

	masteriyoCertificateShare.init();
})(jQuery);
