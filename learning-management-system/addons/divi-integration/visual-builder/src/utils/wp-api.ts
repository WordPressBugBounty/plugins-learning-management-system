import type { MasteriyoDivi5Strings } from './i18n';

interface WpApiSettings {
	root?: string;
	nonce?: string;
}

interface MasteriyoDivi5ModulesData {
	restRoot?: string;
	i18n?: Partial<MasteriyoDivi5Strings>;
}

declare global {
	interface Window {
		wpApiSettings?: WpApiSettings;
		MasteriyoDivi5ModulesData?: MasteriyoDivi5ModulesData;
	}
}

/**
 * The VB app window is its own iframe and doesn't have wpApiSettings (root
 * URL + nonce) set on it directly — read it from the parent wp-admin window
 * instead, same-origin. Shared by everything in this project that calls a
 * REST endpoint directly instead of going through a block attribute.
 *
 * The root falls back to `MasteriyoDivi5ModulesData.restRoot`, localized
 * onto this same app window from `rest_url()` by d5-loader.php — not to a
 * `location.origin`-based guess, which is wrong whenever WordPress is
 * installed in a subdirectory.
 */
export function getWpApiConfig(): { root: string; nonce?: string } {
	let wpApiSettings = window.wpApiSettings;
	try {
		wpApiSettings = window.parent?.wpApiSettings ?? wpApiSettings;
	} catch {
		// The parent is inaccessible; use the app-window configuration.
	}
	const root =
		wpApiSettings?.root ??
		window.MasteriyoDivi5ModulesData?.restRoot ??
		`${window.location.origin}/wp-json/`;
	return { root, nonce: wpApiSettings?.nonce };
}
