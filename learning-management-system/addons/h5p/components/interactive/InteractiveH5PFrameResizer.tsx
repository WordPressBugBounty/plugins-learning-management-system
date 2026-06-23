import { useEffect } from 'react';

/**
 * Auto-resizes H5P iframes on the learn page.
 *
 * The H5P compatibility renderer ([h5p] → iframe) posts its content height to
 * the parent window via `postMessage({ type: 'masteriyo_h5p_height', height })`.
 * This component listens for that message and applies the reported height to the
 * matching iframe, so embedded H5P content is never clipped or over-tall.
 *
 * Renders nothing — it only wires up the listener while mounted.
 */
const InteractiveH5PFrameResizer: React.FC = () => {
	useEffect(() => {
		const handler = (event: MessageEvent) => {
			if (event.origin !== window.location.origin) {
				return;
			}
			if (
				!event.data ||
				event.data.type !== 'masteriyo_h5p_height' ||
				typeof event.data.height !== 'number'
			) {
				return;
			}

			const iframes = document.querySelectorAll<HTMLIFrameElement>(
				'.masteriyo-h5p-iframe-wrap iframe',
			);
			iframes.forEach((iframe) => {
				// Only resize the iframe that actually sent the message.
				if (iframe.contentWindow === event.source) {
					iframe.style.height = `${event.data.height}px`;
				}
			});
		};

		window.addEventListener('message', handler);
		return () => window.removeEventListener('message', handler);
	}, []);

	return null;
};

export default InteractiveH5PFrameResizer;
