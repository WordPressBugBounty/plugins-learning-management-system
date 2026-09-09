(function () {
	var pill = document.getElementById('mto-preview-pill');
	var handle = pill ? pill.querySelector('.mto-preview-drag-handle') : null;

	if (!pill || !handle) {
		return;
	}

	var STORAGE_KEY = 'masteriyo_preview_pill_position';
	var KEYBOARD_STEP = 16;
	var dragging = false;
	var pointerOffsetX = 0;
	var pointerOffsetY = 0;

	function clamp(value, min, max) {
		return Math.min(Math.max(value, min), max);
	}

	function applyPosition(left, top) {
		var maxLeft = window.innerWidth - pill.offsetWidth;
		var maxTop = window.innerHeight - pill.offsetHeight;

		left = clamp(left, 0, Math.max(maxLeft, 0));
		top = clamp(top, 0, Math.max(maxTop, 0));

		pill.style.left = left + 'px';
		pill.style.top = top + 'px';
		pill.style.bottom = 'auto';
		pill.style.transform = 'none';

		return { left: left, top: top };
	}

	function currentPosition() {
		var rect = pill.getBoundingClientRect();
		return { left: rect.left, top: rect.top };
	}

	function restorePosition() {
		var saved;

		try {
			saved = JSON.parse(window.localStorage.getItem(STORAGE_KEY));
		} catch (e) {
			saved = null;
		}

		if (saved && typeof saved.left === 'number' && typeof saved.top === 'number') {
			applyPosition(saved.left, saved.top);
		}
	}

	function savePosition(left, top) {
		try {
			window.localStorage.setItem(STORAGE_KEY, JSON.stringify({ left: left, top: top }));
		} catch (e) {
			// Storage unavailable (private mode, quota, etc.) — position just won't persist.
		}
	}

	function getPointer(e) {
		if (e.touches && e.touches.length) {
			return { x: e.touches[0].clientX, y: e.touches[0].clientY };
		}

		return { x: e.clientX, y: e.clientY };
	}

	function onDragStart(e) {
		// Let the "Switch to Admin/Instructor" link handle its own click.
		if (e.target.closest('a')) {
			return;
		}

		// Only the primary mouse button starts a drag — right/middle clicks are
		// for the context menu, autoscroll, etc. Touch events have no `button`.
		if (typeof e.button === 'number' && e.button !== 0) {
			return;
		}

		var pointer = getPointer(e);
		var rect = pill.getBoundingClientRect();

		dragging = true;
		pointerOffsetX = pointer.x - rect.left;
		pointerOffsetY = pointer.y - rect.top;

		pill.classList.add('mto-preview-pill-dragging');

		document.addEventListener('mousemove', onDragMove);
		document.addEventListener('touchmove', onDragMove, { passive: false });
		document.addEventListener('mouseup', onDragEnd);
		document.addEventListener('touchend', onDragEnd);
		document.addEventListener('touchcancel', onDragEnd);
	}

	function onDragMove(e) {
		if (!dragging) {
			return;
		}

		e.preventDefault();

		var pointer = getPointer(e);
		applyPosition(pointer.x - pointerOffsetX, pointer.y - pointerOffsetY);
	}

	function onDragEnd() {
		if (!dragging) {
			return;
		}

		dragging = false;
		pill.classList.remove('mto-preview-pill-dragging');

		document.removeEventListener('mousemove', onDragMove);
		document.removeEventListener('touchmove', onDragMove);
		document.removeEventListener('mouseup', onDragEnd);
		document.removeEventListener('touchend', onDragEnd);
		document.removeEventListener('touchcancel', onDragEnd);

		savePosition(parseInt(pill.style.left, 10), parseInt(pill.style.top, 10));
	}

	function onHandleKeydown(e) {
		var deltas = {
			ArrowUp: { x: 0, y: -KEYBOARD_STEP },
			ArrowDown: { x: 0, y: KEYBOARD_STEP },
			ArrowLeft: { x: -KEYBOARD_STEP, y: 0 },
			ArrowRight: { x: KEYBOARD_STEP, y: 0 },
		};
		var delta = deltas[e.key];

		if (!delta) {
			return;
		}

		e.preventDefault();

		var position = currentPosition();
		var next = applyPosition(position.left + delta.x, position.top + delta.y);

		savePosition(next.left, next.top);
	}

	window.addEventListener('resize', function () {
		if (pill.style.left && pill.style.top) {
			applyPosition(parseInt(pill.style.left, 10), parseInt(pill.style.top, 10));
		}
	});

	pill.addEventListener('mousedown', onDragStart);
	pill.addEventListener('touchstart', onDragStart, { passive: true });
	handle.addEventListener('keydown', onHandleKeydown);

	restorePosition();
})();
