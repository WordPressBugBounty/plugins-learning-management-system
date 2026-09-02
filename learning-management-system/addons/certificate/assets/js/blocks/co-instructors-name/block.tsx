import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Icon } from '../../../../../../assets/js/blocks/components';
import Edit from './Edit';
import Save from './Save';
import attributes from './attributes';

export function registerCoInstructorsNameBlock() {
	registerBlockType('masteriyo/co-instructors-name', {
		title: __('Co-Instructors Names', 'learning-management-system'),
		description: __(
			'Displays the names of all co-instructors who contributed to the course.',
			'learning-management-system',
		),
		icon: <Icon type="blockIcon" name="coInstructorsName" size={24} />,
		category: 'masteriyo',
		keywords: ['co-instructors', 'names', 'instructor', 'name'],
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
