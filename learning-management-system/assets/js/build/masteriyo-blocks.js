

(function ($) {
	/**
	 * Automatically attempts to recover invalid blocks in the Gutenberg editor.
	 * This is useful when blocks become corrupted and Gutenberg can't render them properly.
	 */
	function autoBlockRecovery() {
		if (typeof wp !== 'undefined' && wp.data && wp.data.dispatch) {
			const attemptRecovery = () => {
				try {
					wp.data.dispatch('core/block-editor').recovery.attemptBlockRecovery();
				} catch (e) {}
			};

			if (document.readyState === 'complete') {
				attemptRecovery();
			} else {
				window.addEventListener('load', attemptRecovery);
			}
		}
	}

	autoBlockRecovery();

})(jQuery);
