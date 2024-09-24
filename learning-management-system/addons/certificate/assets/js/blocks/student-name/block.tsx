import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Icon } from '../../../../../../assets/js/blocks/components';
import Edit from './Edit';
import Save from './Save';
import attributes from './attributes';

export function registerStudentNameBlock() {
	registerBlockType('masteriyo/student-name', {
		title: __('Student Name', 'learning-management-system'),
		description: __(
			"The text 'Student Name' will be replaced by the actual student name who completed the course when downloading.",
			'learning-management-system',
		),
		icon: <Icon type="blockIcon" name="studentName" size={24} />,
		category: 'masteriyo',
		keywords: ['student', 'name'],
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
