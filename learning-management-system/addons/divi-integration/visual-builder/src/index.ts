import { omit } from 'lodash';
import { addAction } from '@wordpress/hooks';
import { registerModule } from '@divi/module-library';

import { courseListModule } from './components/course-list';
import { courseCategoriesModule } from './components/course-categories';
import './module-icons';
import './module-badges';

addAction(
	'divi.moduleLibrary.registerModuleLibraryStore.after',
	'masteriyoDivi5Modules',
	() => {
		registerModule(
			courseListModule.metadata,
			omit(courseListModule, 'metadata'),
		);
		registerModule(
			courseCategoriesModule.metadata,
			omit(courseCategoriesModule, 'metadata'),
		);
	},
);
