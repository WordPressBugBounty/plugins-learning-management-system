import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Icon } from '../../../../../../assets/js/blocks/components';
import Edit from './Edit';
import Save from './Save';
import attributes from './attributes';

export function registerCurrentTimestampBlock() {
	registerBlockType('masteriyo/current-timestamp', {
		title: __('Current Timestamp', 'learning-management-system'),
		description: __(
			'Displays the current timestamp (date and time) when the certificate is generated.',
			'learning-management-system',
		),
		icon: <Icon type="blockIcon" name="currentTimestamp" size={24} />,
		category: 'masteriyo',
		keywords: ['current', 'timestamp', 'date', 'time'],
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
