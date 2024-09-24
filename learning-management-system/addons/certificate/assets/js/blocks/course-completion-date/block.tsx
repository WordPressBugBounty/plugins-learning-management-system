import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Icon } from '../../../../../../assets/js/blocks/components';
import Edit from './Edit';
import Save from './Save';
import attributes from './attributes';

export function registerCourseCompletionDateBlock() {
	registerBlockType('masteriyo/course-completion-date', {
		title: __('Course Completion Date', 'learning-management-system'),
		description: __(
			"The text 'Completion Date' will be replaced by the actual course completion date of students when downloading.",
			'learning-management-system',
		),
		icon: <Icon type="blockIcon" name="courseCompletionDate" size={24} />,
		category: 'masteriyo',
		keywords: ['completion', 'date'],
		attributes,
		supports: {
			align: false,
			html: false,
			color: {
				background: false,
				gradient: false,
				text: false,
			},
			customClassName: false,
		},
		edit: Edit,
		save: Save,
	});
}
