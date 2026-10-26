import { addFilter } from '@wordpress/hooks';
import { courseList, courseCategories } from './icons';

// Add our native modules' icons to Divi's icon library (module.json's
// "moduleIcon" references these by name) — otherwise the Add Module panel
// falls back to Divi's generic module icon.
addFilter('divi.iconLibrary.icon.map', 'masteriyoDivi5Modules', (icons) => {
	return {
		...icons, // Without this, every other icon gets overwritten.
		[courseList.name]: courseList,
		[courseCategories.name]: courseCategories,
	};
});
