export interface MasteriyoDivi5Strings {
	newBadge: string;
	previewFailed: string;
	courseListTitle: string;
	courseListTitles: string;
	courseCategoriesTitle: string;
	courseList: string;
	courseCategories: string;
}

const FALLBACKS: MasteriyoDivi5Strings = {
	newBadge: 'New',
	previewFailed: '%s preview failed:',
	courseListTitle: 'Masteriyo Course List',
	courseListTitles: 'Masteriyo Course Lists',
	courseCategoriesTitle: 'Masteriyo Course Categories',
	courseList: 'Course List',
	courseCategories: 'Course Categories',
};

/**
 * A user-facing string this bundle renders itself, translated server-side
 * with the plugin's text domain and handed over in d5-loader.php — Divi only
 * loads script translations for its own loader, so @wordpress/i18n would have
 * no catalogue here. Falls back to English if the data isn't there.
 */
export function getString(key: keyof MasteriyoDivi5Strings): string {
	return window.MasteriyoDivi5ModulesData?.i18n?.[key] ?? FALLBACKS[key];
}
