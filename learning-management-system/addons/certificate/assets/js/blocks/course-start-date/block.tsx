import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Icon } from '../../../../../../assets/js/blocks/components';
import Edit from './Edit';
import Save from './Save';
import attributes from './attributes';

export function registerCourseStartDateBlock() {
	registerBlockType('masteriyo/course-start-date', {
		title: __('Course Start Date', 'learning-management-system'),
		description: __(
			'Displays the start date of the course on the certificate.',
			'learning-management-system',
		),
		icon: <Icon type="blockIcon" name="courseStartDate" size={24} />,
		category: 'masteriyo',
		keywords: ['start', 'date'],
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
