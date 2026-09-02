/**
 * Split a loginout label into its login and logout parts.
 *
 * Mirrors NavMenu::split_loginout_label() in PHP: exactly two pipe-separated
 * parts are used as custom labels (trimmed, empties included); anything else
 * falls back to the provided defaults.
 */
export const splitLoginoutLabel = (
	label: string,
	defaultLogin: string,
	defaultLogout: string,
): [string, string] => {
	const parts = (label || '').split('|');

	if (parts.length === 2) {
		return [parts[0].trim(), parts[1].trim()];
	}

	return [defaultLogin, defaultLogout];
};
