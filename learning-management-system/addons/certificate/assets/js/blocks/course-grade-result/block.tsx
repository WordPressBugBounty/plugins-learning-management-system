import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Icon } from '../../../../../../assets/js/blocks/components';
import Edit from './Edit';
import Save from './Save';
import attributes from './attributes';

export function registerCourseGradeResultBlock() {
	registerBlockType('masteriyo/course-grade-result', {
		title: __('Course Grade Result', 'learning-management-system'),
		description: __(
			'Displays the final grade of the course on the certificate.',
			'learning-management-system',
		),
		icon: <Icon type="blockIcon" name="courseGradeResult" size={24} />,
		category: 'masteriyo',
		keywords: ['course', 'grade', 'result'],
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
