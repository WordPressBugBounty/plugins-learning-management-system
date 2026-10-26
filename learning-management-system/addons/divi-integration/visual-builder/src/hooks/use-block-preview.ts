import { useEffect, useRef, useState } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { getWpApiConfig } from '../utils/wp-api';

interface BlockRendererResponse {
	rendered: string;
}

interface UseBlockPreviewResult {
	renderedHtml: string;
	fetchError: string;
	isPending: boolean;
}

/**
 * Each preview is its own REST request, and the server numbers the
 * per-instance id it scopes the show/hide-toggle CSS with from 1 every time,
 * so every module on the canvas would come back with the same id and one
 * module's hidden element would hide on all of them. Re-key it to the
 * module's own Visual Builder id, which is unique per module.
 */
function scopeInstanceId(html: string, instanceId: string): string {
	return html.replace(/mto-divi5-[a-z-]+-\d+/g, `mto-divi5-${instanceId}`);
}

/**
 * Renders a native module's canvas preview through WP core's block-renderer
 * REST endpoint (the same mechanism Gutenberg's ServerSideRender uses), so
 * the preview is the exact server-side render output with no markup
 * duplicated in React.
 *
 * @param blockName        Registered block name, e.g. "masteriyo/course-list".
 * @param attrs            The module's current attrs (Divi's edit props).
 * @param contentAttrNames Attribute keys the server render actually reads — only
 *                          these are sent, and only a change in one of them
 *                          triggers a re-fetch.
 * @param errorLabel       Prefix for the inline error message.
 * @param instanceId       The module's Visual Builder id, used to keep its
 *                          toggle CSS from leaking onto other modules.
 */
export function useBlockPreview<Attrs extends object>(
	blockName: string,
	attrs: Attrs | undefined,
	contentAttrNames: (keyof Attrs)[],
	errorLabel: string,
	instanceId: string,
): UseBlockPreviewResult {
	const [renderedHtml, setRenderedHtml] = useState('');
	const [fetchError, setFetchError] = useState('');
	// The key of the contentAttributes the currently-shown renderedHtml
	// corresponds to. Pending state is derived from this instead of its own
	// piece of state, so it can only ever agree with what's on screen.
	const [renderedKey, setRenderedKey] = useState('');
	const abortRef = useRef<AbortController>();
	const debounceRef = useRef<ReturnType<typeof setTimeout>>();
	// Keyed by the exact content-attrs signature — flipping a toggle back to a
	// combination already seen (or the same edit firing twice) renders from
	// cache instead of round-tripping to the server again.
	const cacheRef = useRef<Map<string, string>>(new Map());

	const contentAttributes: Partial<Attrs> = {};
	contentAttrNames.forEach((attrName) => {
		if (attrs?.[attrName] !== undefined) {
			contentAttributes[attrName] = attrs[attrName] as never;
		}
	});
	// Only these attributes affect the server render, so this is a narrower
	// (and more cache-friendly) key than the full attrs object would be.
	const contentAttributesKey = JSON.stringify(contentAttributes);

	useEffect(() => {
		const cached = cacheRef.current.get(contentAttributesKey);
		if (cached !== undefined) {
			setFetchError('');
			setRenderedHtml(scopeInstanceId(cached, instanceId));
			setRenderedKey(contentAttributesKey);
			return;
		}

		if (debounceRef.current) {
			clearTimeout(debounceRef.current);
		}

		debounceRef.current = setTimeout(() => {
			if (abortRef.current) {
				abortRef.current.abort();
			}

			const controller = new AbortController();
			abortRef.current = controller;

			const { root, nonce } = getWpApiConfig();

			apiFetch<BlockRendererResponse>({
				url: `${root}wp/v2/block-renderer/${blockName}`,
				method: 'POST',
				headers: nonce ? { 'X-WP-Nonce': nonce } : undefined,
				data: {
					context: 'edit',
					attributes: JSON.parse(contentAttributesKey),
				},
				signal: controller.signal,
			})
				.then((response) => {
					// A newer request superseded this one — never let it overwrite
					// the preview that replaced it.
					if (controller.signal.aborted) {
						return;
					}
					const html = response.rendered ?? '';
					cacheRef.current.set(contentAttributesKey, html);
					setFetchError('');
					setRenderedHtml(scopeInstanceId(html, instanceId));
					setRenderedKey(contentAttributesKey);
				})
				.catch((error) => {
					if (controller.signal.aborted) {
						return;
					}
					console.error(`${errorLabel} preview fetch failed`, error);
					setFetchError(
						typeof error?.message === 'string'
							? error.message
							: JSON.stringify(error),
					);
				});
		}, 300);

		return () => {
			if (debounceRef.current) {
				clearTimeout(debounceRef.current);
			}
			if (abortRef.current) {
				abortRef.current.abort();
			}
		};
	}, [contentAttributesKey, blockName, errorLabel, instanceId]);

	return {
		renderedHtml,
		fetchError,
		isPending: renderedKey !== contentAttributesKey,
	};
}
