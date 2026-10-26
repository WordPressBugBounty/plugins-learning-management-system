// Divi dependencies.
import { type Metadata, type ModuleLibrary } from '@divi/types';

// Local dependencies.
import metadata from './module.json';
import { CourseCategoriesModuleEdit } from './edit';
import { CourseCategoriesModuleAttrs } from './types';
import { placeholderContent } from './placeholder-content';
import { getString } from '../../utils/i18n';

export const courseCategoriesModule: ModuleLibrary.Module.RegisterDefinition<CourseCategoriesModuleAttrs> =
	{
		// Imported json has no inferred type hence type-cast is necessary.
		metadata: {
			...metadata,
			// Translated server-side (see d5-loader.php) so the strings are
			// extractable; module.json's own copy is only the English fallback.
			title: getString('courseCategoriesTitle'),
			titles: getString('courseCategoriesTitle'),
		} as Metadata.Values<CourseCategoriesModuleAttrs>,
		defaultAttrs: {} as Metadata.DefaultAttributes<CourseCategoriesModuleAttrs>,
		defaultPrintedStyleAttrs:
			{} as Metadata.DefaultAttributes<CourseCategoriesModuleAttrs>,
		placeholderContent,
		renderers: {
			edit: CourseCategoriesModuleEdit,
		},
	};
