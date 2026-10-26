import React, { ReactElement } from 'react';
import { StyleContainer, StylesProps } from '@divi/module';
import { CourseCategoriesModuleAttrs } from './types';

/** Styles the module wrapper only — the design-tab sections aren't ported yet. */
export const ModuleStyles = ({
	elements,
	settings,
	mode,
	state,
	noStyleTag,
	defaultPrintedStyleAttrs,
}: StylesProps<CourseCategoriesModuleAttrs>): ReactElement => (
	<StyleContainer mode={mode} state={state} noStyleTag={noStyleTag}>
		{elements.style({
			attrName: 'module',
			styleProps: {
				defaultPrintedStyleAttrs: defaultPrintedStyleAttrs?.module?.decoration,
				disabledOn: {
					disabledModuleVisibility: settings?.disabledModuleVisibility,
				},
			},
		})}
	</StyleContainer>
);
