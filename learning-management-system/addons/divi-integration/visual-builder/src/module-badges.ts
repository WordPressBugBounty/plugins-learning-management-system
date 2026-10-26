import { getString } from './utils/i18n';

// The Add Module panel renders a "Legacy" pill next to any backward-compat
// D4 module (its own component, tied to its own isLegacyModule check — not
// something a third-party module can opt into). There's no equivalent hook
// for a custom badge, so this replicates its exact CSS (.et-vb-legacy-badge
// in Divi's modal-library.css), in green, targeting our own module tiles by
// the "value" attribute the panel already sets to each module's registered
// name.
const NATIVE_MODULE_NAMES = [
	'masteriyo/course-list',
	'masteriyo/course-categories-list',
];

const selector = NATIVE_MODULE_NAMES.map(
	(name) => `button.et-vb-module-picker-option[value="${name}"]::after`,
).join(', ');

const css = `
	${selector} {
		content: ${JSON.stringify(getString('newBadge'))};
		position: absolute;
		top: 4px;
		inset-inline-end: 4px;
		background-color: #28a745;
		color: #fff;
		font-size: 10px;
		font-weight: 600;
		padding: 2px 4px;
		border-radius: 3px;
		z-index: 2;
	}
`;

function injectInto(doc: Document | undefined): void {
	if (!doc || !doc.head) {
		return;
	}
	const style = doc.createElement('style');
	style.textContent = css;
	doc.head.appendChild(style);
}

// The Add Module panel is chrome around the canvas, not canvas content, so
// it may be rendered in the top wp-admin window rather than this script's
// own app-window iframe — inject into both (same-origin, so accessible)
// rather than guessing which one actually hosts it.
injectInto(document);
try {
	if (window.parent && window.parent.document !== document) {
		injectInto(window.parent.document);
	}
} catch {
	// Cross-origin or otherwise inaccessible — nothing to do.
}
