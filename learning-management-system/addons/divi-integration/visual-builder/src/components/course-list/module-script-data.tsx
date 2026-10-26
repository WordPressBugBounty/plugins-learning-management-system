import React, { Fragment, ReactElement } from 'react';
import { ModuleScriptDataProps } from '@divi/module';
import { CourseListModuleAttrs } from './types';

/**
 * Course List module's script data component.
 *
 * @param {ModuleScriptDataProps<CourseListModuleAttrs>} props React component props.
 *
 * @returns {ReactElement}
 */
export const ModuleScriptData = ({
	elements,
}: ModuleScriptDataProps<CourseListModuleAttrs>): ReactElement => (
	<Fragment>
		{elements.scriptData({
			attrName: 'module',
		})}
	</Fragment>
);
