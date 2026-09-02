import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Icon } from '../../../../../../assets/js/blocks/components';
import Edit from './Edit';
import Save from './Save';
import attributes from './attributes';

export function registerInstructorNameBlock() {
	registerBlockType('masteriyo/instructor-name', {
		title: __('Instructor Name', 'learning-management-system'),
		description: __(
			'Displays the name of the instructor who conducted the course.',
			'learning-management-system',
		),
		icon: <Icon type="blockIcon" name="instructorName" size={24} />,
		category: 'masteriyo',
		keywords: ['instructor', 'name'],
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
