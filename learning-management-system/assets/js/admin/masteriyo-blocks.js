

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

	/**
	 * Show or hide the "Masteriyo LMS Single Course" panel in the block inserter.
	 *
	 * @param {boolean} show - Whether to show (`true`) or hide (`false`) the panel.
	 */
	function toggleMasteriyoPanel(show) {
		const $panelContent = $('[aria-label="Masteriyo LMS Single Course"]');

		const $header = $('.block-editor-inserter__panel-header h2')
			.filter(function () {
				return $(this).text().includes('Masteriyo LMS Single Course');
			})
			.closest('.block-editor-inserter__panel-header');

		if ($panelContent.length) {
			$panelContent.toggle(show);
		}
		if ($header.length) {
			$header.toggle(show);
		}
	}

	function handleSingleCourseBlockDetection() {
		if (typeof wp === 'undefined' || !wp.data) return;

		const { subscribe, select } = wp.data;

		subscribe(() => {
			const blocks = select('core/block-editor').getBlocks();
			const hasSingleCourse = blocks.some(
				(block) => block.name === 'masteriyo/single-course',
			);

			toggleMasteriyoPanel(hasSingleCourse);
		});
	}

	handleSingleCourseBlockDetection();


	wp.domReady(() => {
		const block = wp.blocks.getBlockType('masteriyo/single-course');
		if (block) {
			block.example = undefined; 
		}
	});
})(jQuery);
