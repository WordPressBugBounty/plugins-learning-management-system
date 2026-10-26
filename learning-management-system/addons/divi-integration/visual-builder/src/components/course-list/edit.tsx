import React, { ReactElement } from 'react';
import { ModuleContainer } from '@divi/module';

import { CourseListModuleEditProps, CourseListModuleAttrs } from './types';
import { ModuleStyles } from './styles';
import { moduleClassnames } from './module-classnames';
import { ModuleScriptData } from './module-script-data';
import { getString } from '../../utils/i18n';
import { useBlockPreview } from '../../hooks/use-block-preview';
import { BlockPreviewContent } from '../block-preview-content';

// Only the content attributes the server render actually reads — leaves out Divi's
// own "module"/"lock"/"metadata"/"className"/"style" keys, which carry
// nested shapes WP core's own block-support schemas may reject on this route.
const CONTENT_ATTR_NAMES: (keyof CourseListModuleAttrs)[] = [
	'postsPerPage',
	'order',
	'orderBy',
	'columns',
	'show_pagination',
	'include_categories',
	'exclude_categories',
	'show_thumbnail',
	'show_difficulty_badge',
	'show_categories',
	'show_course_title',
	'show_author',
	'show_author_avatar',
	'show_author_name',
	'show_rating',
	'show_description',
	'show_metadata',
	'show_course_duration',
	'show_students_count',
	'show_lessons_count',
	'show_footer',
	'show_price',
	'show_enroll_button',
];

/**
 * Course List module's Visual Builder edit component. Renders through WP
 * core's block-renderer REST endpoint (the same mechanism Gutenberg's
 * ServerSideRender uses) so the canvas preview is the exact HTML the server render
 * produces on the frontend, with no markup duplicated in React.
 *
 * @param {CourseListModuleEditProps} props React component props.
 *
 * @returns {ReactElement}
 */
export const CourseListModuleEdit = (
	props: CourseListModuleEditProps,
): ReactElement => {
	const { attrs, elements, id, name, isLooped, loopIndex } = props;

	const { renderedHtml, fetchError, isPending } = useBlockPreview(
		'masteriyo/course-list',
		attrs,
		CONTENT_ATTR_NAMES,
		'Masteriyo Course List',
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
				classPrefix="masteriyo_divi5_course_list"
				label={getString('courseList')}
				fetchError={fetchError}
				isPending={isPending}
				renderedHtml={renderedHtml}
			/>
		</ModuleContainer>
	);
};
