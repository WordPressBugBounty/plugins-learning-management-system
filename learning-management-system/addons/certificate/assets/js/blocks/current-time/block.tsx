import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Icon } from '../../../../../../assets/js/blocks/components';
import Edit from './Edit';
import Save from './Save';
import attributes from './attributes';

export function registerCurrentTimeBlock() {
	registerBlockType('masteriyo/current-time', {
		title: __('Current Time', 'learning-management-system'),
		description: __(
			'Displays the current time when the certificate is generated.',
			'learning-management-system',
		),
		icon: <Icon type="blockIcon" name="currentTime" size={24} />,
		category: 'masteriyo',
		keywords: ['current', 'time'],
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
