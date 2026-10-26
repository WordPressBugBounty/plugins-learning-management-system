// Divi dependencies.
import { type Metadata, type ModuleLibrary } from '@divi/types';

// Local dependencies.
import metadata from './module.json';
import { CourseListModuleEdit } from './edit';
import { SettingsContent } from './settings-content';
import { CourseListModuleAttrs } from './types';
import { placeholderContent } from './placeholder-content';
import { getString } from '../../utils/i18n';

export const courseListModule: ModuleLibrary.Module.RegisterDefinition<CourseListModuleAttrs> =
	{
		// Imported json has no inferred type hence type-cast is necessary.
		metadata: {
			...metadata,
			// Translated server-side (see d5-loader.php) so the strings are
			// extractable; module.json's own copy is only the English fallback.
			title: getString('courseListTitle'),
			titles: getString('courseListTitles'),
		} as Metadata.Values<CourseListModuleAttrs>,
		defaultAttrs: {} as Metadata.DefaultAttributes<CourseListModuleAttrs>,
		defaultPrintedStyleAttrs:
			{} as Metadata.DefaultAttributes<CourseListModuleAttrs>,
		placeholderContent,
		renderers: {
			edit: CourseListModuleEdit,
		},
		settings: {
			content: SettingsContent,
		},
	};
