import React, { Fragment, ReactElement } from 'react';
import { ModuleScriptDataProps } from '@divi/module';
import { CourseCategoriesModuleAttrs } from './types';

/**
 * Course Categories module's script data component.
 *
 * @param {ModuleScriptDataProps<CourseCategoriesModuleAttrs>} props React component props.
 *
 * @returns {ReactElement}
 */
export const ModuleScriptData = ({
	elements,
}: ModuleScriptDataProps<CourseCategoriesModuleAttrs>): ReactElement => (
	<Fragment>
		{elements.scriptData({
			attrName: 'module',
		})}
	</Fragment>
);
