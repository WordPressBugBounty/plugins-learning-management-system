/* global masteriyoH5PRenderer */
/**
 * H5P renderer bridge: height notifications and xAPI score events to parent page.
 * Loaded via wp_enqueue_script() with data injected via wp_localize_script().
 */
( function() {
	'use strict';

	var settings = window.masteriyoH5PRenderer || {};
	var h5pId   = parseInt( settings.h5pId, 10 ) || 0;

	/* =========================================================================
	 * 1. HEIGHT BRIDGE
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

	/* =========================================================================
	 * 2. xAPI BRIDGE
	 * Intercepts H5P xAPI events via externalDispatcher (Path A) and direct
	 * instance listeners (Path B). Stub externalDispatcher on parent to prevent
	 * errors from H5P's isFramed relay mechanism (which throws if parent has no H5P).
	 * ========================================================================= */

	/**
	 * Provide a no-op H5P.externalDispatcher stub on the parent learn page so
	 * H5P's isFramed relay does not throw a TypeError.
	 */
	if ( window.parent && window.parent !== window ) {
		try {
			window.parent.H5P = window.parent.H5P || {};
			if ( ! window.parent.H5P.externalDispatcher ) {
				window.parent.H5P.externalDispatcher = {
					trigger: function() {},
					on: function() {}
				};
			}
		} catch ( e ) {}
	}

	// xAPI verbs that carry a meaningful final score across H5P content types.
	var ACCEPTED_VERBS = [ 'completed', 'answered', 'scored', 'passed', 'failed' ];

	/**
	 * Validate, extract, and forward an xAPI statement to the parent page.
	 *
	 * @param {Object} statement xAPI statement object from H5P.
	 */
	function masteriyoSendXAPIResult( statement ) {
		if ( ! statement ) {
			return;
		}
		if ( ! window.parent || window.parent === window ) {
			return;
		}

		var verb = ( statement.verb && statement.verb.id ) || '';
		var verbMatched = ACCEPTED_VERBS.some( function( v ) {
			return verb.indexOf( v ) > -1;
		} );
		if ( ! verbMatched ) {
			return;
		}

		// Ignore sub-question events — only the top-level statement (no parent context) is the item's overall result.
		if (
			statement.context &&
			statement.context.contextActivities &&
			statement.context.contextActivities.parent &&
			statement.context.contextActivities.parent.length
		) {
			return;
		}

		var result = statement.result || {};
		var score  = result.score || {};

		// Infer success from score for content types that omit result.success (Flashcards, Drag & Drop, Summary, etc.).
		var successVal = null;
		if ( typeof result.success === 'boolean' ) {
			successVal = result.success;
		} else if (
			typeof score.raw === 'number' &&
			typeof score.max === 'number' &&
			score.max > 0
		) {
			successVal = score.raw >= score.max;
		}

		window.parent.postMessage(
			{
				type: 'masteriyo_h5p_xapi',
				h5pId: h5pId,
				completion: !! result.completion || verb.indexOf( 'completed' ) > -1,
				success: successVal,
				score: {
					raw: ( typeof score.raw === 'number' ) ? score.raw : null,
					max: ( typeof score.max === 'number' ) ? score.max : null,
					scaled: ( typeof score.scaled === 'number' ) ? score.scaled : null
				}
			},
			window.location.origin
		);
	}

	/* -- Path A: H5P.externalDispatcher ---------------------------------------- */

	/**
	 * Handler for H5P.externalDispatcher 'xAPI' events.
	 *
	 * @param {H5P.Event} event H5P event object.
	 */
	function masteriyoOnExternalXAPI( event ) {
		masteriyoSendXAPIResult( event && event.data && event.data.statement );
	}

	/**
	 * Attach to H5P.externalDispatcher if available.
	 *
	 * @return {boolean} True when successfully attached.
	 */
	function masteriyoAttachExternalDispatcher() {
		if ( window.H5P && H5P.externalDispatcher && H5P.externalDispatcher.on ) {
			H5P.externalDispatcher.on( 'xAPI', masteriyoOnExternalXAPI );
			return true;
		}
		return false;
	}

	if ( ! masteriyoAttachExternalDispatcher() ) {
		// Fallback for sites where an optimisation plugin defers H5P scripts: retry on DOMContentLoaded then every 100ms up to 5s.
		var mastExtTimer    = null;
		var mastExtAttempts = 0;

		function mastTryExternal() {
			if ( masteriyoAttachExternalDispatcher() || mastExtAttempts++ > 50 ) {
				clearInterval( mastExtTimer );
			}
		}

		document.addEventListener( 'DOMContentLoaded', function() {
			if ( ! masteriyoAttachExternalDispatcher() ) {
				mastExtTimer = setInterval( mastTryExternal, 100 );
			}
		} );
	}

	/* -- Path B: Direct H5P instance listeners ---------------------------------- */

	/**
	 * Attach an xAPI listener directly to a single H5P content instance.
	 *
	 * @param {H5P.ContentType} instance H5P content instance.
	 */
	function masteriyoHookInstance( instance ) {
		if ( ! instance || ! instance.on || instance.__mastH5PHooked ) {
			return;
		}
		instance.__mastH5PHooked = true;
		instance.on( 'xAPI', function( event ) {
			masteriyoSendXAPIResult( event && event.data && event.data.statement );
		} );
	}

	// Attach listeners to H5P.instances and patch H5P.newRunnable for future instances (runs after H5P initialization).

	var mastNewRunnablePatched = false;

	function masteriyoAttachInstances() {
		if ( ! window.H5P ) {
			return;
		}

		// Patch H5P.newRunnable once so every future instance is hooked.
		if ( ! mastNewRunnablePatched && typeof H5P.newRunnable === 'function' ) {
			mastNewRunnablePatched = true;
			var origNewRunnable = H5P.newRunnable;
			H5P.newRunnable = function() {
				var instance = origNewRunnable.apply( this, arguments );
				masteriyoHookInstance( instance );
				return instance;
			};
		}

		// Attach to instances that already exist.
		if ( Array.isArray( H5P.instances ) ) {
			H5P.instances.forEach( masteriyoHookInstance );
		}

		masteriyoReportMaxScore();

		// All setup done — stop polling early rather than running the full 30s.
		if ( mastNewRunnablePatched && mastMaxReported ) {
			clearInterval( mastInstTimer );
		}
	}

	/* -- Max-score bridge ------------------------------------------------------ */

	// Report max score to parent so skipped questions count toward the total.
	var mastMaxReported = false;

	function masteriyoReportMaxScore() {
		if ( mastMaxReported ) {
			return;
		}
		if ( ! window.H5P || ! Array.isArray( H5P.instances ) || ! H5P.instances.length ) {
			return;
		}
		if ( ! window.parent || window.parent === window ) {
			return;
		}

		var root = H5P.instances[ 0 ];
		if ( ! root || typeof root.getMaxScore !== 'function' ) {
			return;
		}

		var max;
		try {
			max = root.getMaxScore();
		} catch ( e ) {
			return;
		}

		if ( typeof max !== 'number' || ! ( max > 0 ) ) {
			return;
		}

		mastMaxReported = true;
		window.parent.postMessage(
			{ type: 'masteriyo_h5p_max', h5pId: h5pId, max: max },
			window.location.origin
		);
	}

	// Poll every 500ms (catches instances created before and after our script), stopping after 30s.
	var mastInstTimer = setInterval( masteriyoAttachInstances, 500 );
	setTimeout( function() { clearInterval( mastInstTimer ); }, 30000 );

}() );
