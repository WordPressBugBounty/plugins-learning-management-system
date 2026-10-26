import React, { ReactElement } from 'react';
import { ModuleContainer } from '@divi/module';

import {
	CourseCategoriesModuleEditProps,
	CourseCategoriesModuleAttrs,
} from './types';
import { ModuleStyles } from './styles';
import { moduleClassnames } from './module-classnames';
import { ModuleScriptData } from './module-script-data';
import { getString } from '../../utils/i18n';
import { useBlockPreview } from '../../hooks/use-block-preview';
import { BlockPreviewContent } from '../block-preview-content';

// Every attribute here is content, not styling, so all of them go into the
// block-renderer request (see Course List's edit.tsx for why that split
// matters there — it doesn't apply here since there's no filter-tab field).
const CONTENT_ATTR_NAMES: (keyof CourseCategoriesModuleAttrs)[] = [
	'perPage',
	'columns',
	'include_sub_categories',
	'orderBy',
	'order',
	'show_thumbnail',
	'show_details',
	'show_title',
	'show_courses_count',
];

/**
 * Course Categories module's Visual Builder edit component. Renders through
 * WP core's block-renderer REST endpoint (the same mechanism Gutenberg's
 * ServerSideRender uses) so the canvas preview is the exact HTML the server render
 * produces on the frontend, with no markup duplicated in React.
 *
 * @param {CourseCategoriesModuleEditProps} props React component props.
 *
 * @returns {ReactElement}
 */
export const CourseCategoriesModuleEdit = (
	props: CourseCategoriesModuleEditProps,
): ReactElement => {
	const { attrs, elements, id, name, isLooped, loopIndex } = props;

	const { renderedHtml, fetchError, isPending } = useBlockPreview(
		'masteriyo/course-categories-list',
		attrs,
		CONTENT_ATTR_NAMES,
		'Masteriyo Course Categories',
		id,
	);

	return (
		<ModuleContainer
			attrs={attrs}
			elements={elements}
			id={id}
			name={name}
			stylesComponent={ModuleStyles}
			classnamesFunction={moduleClassnames}
			scriptDataComponent={ModuleScriptData}
			isLooped={isLooped}
			loopIndex={loopIndex}
		>
			{elements.styleComponents({
				attrName: 'module',
			})}
			<BlockPreviewContent
				classPrefix="masteriyo_divi5_course_categories"
				label={getString('courseCategories')}
				fetchError={fetchError}
				isPending={isPending}
				renderedHtml={renderedHtml}
			/>
		</ModuleContainer>
	);
};
