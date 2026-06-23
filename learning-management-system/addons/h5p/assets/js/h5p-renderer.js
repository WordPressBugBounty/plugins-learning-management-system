/* global masteriyoH5PRenderer */
/**
 * H5P renderer bridge: height notifications to parent page.
 * Loaded via wp_enqueue_script() with data injected via wp_localize_script().
 *
 * @since x.x.x
 */
( function() {
	'use strict';

	/* =========================================================================
	 * HEIGHT BRIDGE
	 * Posts the real content height to the parent so the <iframe> fits exactly.
	 * ========================================================================= */

	var mastH5PWrap  = null;
	var mastH5PLast  = 0;
	var mastH5PTimer = null;

	function masteriyoGetWrap() {
		return ( mastH5PWrap = mastH5PWrap || document.querySelector( '.masteriyo-h5p-wrap' ) );
	}

	// Measure the wrapper, not documentElement.scrollHeight, which is floored at the iframe viewport height.
	function masteriyoNotifyH5PHeight() {
		var wrap   = masteriyoGetWrap();
		var height = wrap ? Math.ceil( wrap.getBoundingClientRect().height ) : document.body.scrollHeight;
		if ( ! height || height === mastH5PLast ) {
			return;
		}
		mastH5PLast = height;
		if ( window.parent && window.parent !== window ) {
			window.parent.postMessage(
				{ type: 'masteriyo_h5p_height', height: height },
				window.location.origin
			);
		}
	}

	// Collapse rapid resize/mutation bursts into a single post.
	function masteriyoNotifyH5PHeightDebounced() {
		clearTimeout( mastH5PTimer );
		mastH5PTimer = setTimeout( masteriyoNotifyH5PHeight, 50 );
	}

	// H5P keeps reflowing after load; re-measure a couple of times to settle.
	window.addEventListener( 'load', function() {
		masteriyoNotifyH5PHeightDebounced();
		setTimeout( masteriyoNotifyH5PHeightDebounced, 800 );
		setTimeout( masteriyoNotifyH5PHeightDebounced, 2500 );
	} );

	// Watch the wrapper for size and DOM changes (fall back to body until ready).
	var mastH5PTarget = masteriyoGetWrap() || document.body;

	if ( typeof ResizeObserver !== 'undefined' ) {
		new ResizeObserver( masteriyoNotifyH5PHeightDebounced ).observe( mastH5PTarget );
	}

	if ( typeof MutationObserver !== 'undefined' ) {
		// childList+subtree catches H5P lazy-rendering new elements; ResizeObserver already handles size changes from attribute/style toggles.
		new MutationObserver( masteriyoNotifyH5PHeightDebounced ).observe( mastH5PTarget, {
			childList: true,
			subtree: true,
		} );
	}

}() );
