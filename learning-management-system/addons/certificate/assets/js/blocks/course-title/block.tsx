import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Icon } from '../../../../../../assets/js/blocks/components';
import Edit from './Edit';
import Save from './Save';
import attributes from './attributes';

export function registerCourseTitleBlock() {
	registerBlockType('masteriyo/course-title', {
		title: __('Course Title', 'learning-management-system'),
		description: __(
			"The text 'Course Title' will be replaced by the actual course name for which the certificate is being provided.",
			'learning-management-system',
		),
		icon: <Icon type="blockIcon" name="courseName" size={24} />,
		category: 'masteriyo',
		keywords: ['title'],
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
