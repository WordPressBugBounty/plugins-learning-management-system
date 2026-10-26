import apiFetch from '@wordpress/api-fetch';
import { getWpApiConfig } from './wp-api';

/**
 * Shared request seam for this isolated project's own direct REST calls
 * (block-attribute-driven data goes through module.json fields instead) —
 * composes the root/nonce from getWpApiConfig() so no component re-derives
 * them, mirroring this plugin's own assets/js/back-end/utils/api.ts.
 *
 * @param path   REST route, e.g. a constants/urls.ts export — no leading slash.
 * @param params Query params appended to the request.
 */
export function fetchMasteriyoApi<T>(
	path: string,
	params?: Record<string, string | number>,
): Promise<T> {
	const { root, nonce } = getWpApiConfig();
	// Build a URL so params append to a root that already has a query
	// string (plain permalinks: `?rest_route=/`).
	const url = new URL(`${root}${path}`, window.location.href);
	Object.entries(params ?? {}).forEach(([key, value]) =>
		url.searchParams.set(key, String(value)),
	);

	return apiFetch<T>({
		url: url.toString(),
		headers: nonce ? { 'X-WP-Nonce': nonce } : undefined,
	});
}
