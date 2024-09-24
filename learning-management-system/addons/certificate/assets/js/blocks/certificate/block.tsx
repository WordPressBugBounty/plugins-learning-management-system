import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Icon } from '../../../../../../assets/js/blocks/components';
import edit from './Edit';
import save from './Save';

export const registerCertificateBlock = () => {
	registerBlockType('masteriyo/certificate', {
		title: __('Certificate Background', 'learning-management-system'),
		description: __(
			'Add background image and adjust other design settings of your certificate.',
			'learning-management-system',
		),
		category: 'masteriyo',
		keywords: ['certificate', 'wrapper'],
		icon: <Icon type="blockIcon" name="certificate" size={24} />,
		attributes: {
			blockCSS: {
				type: String,
			},
			backgroundImageURL: {
				type: String,
			},
			backgroundImageID: {
				type: Number,
			},
			containerWidth: {
				type: Number,
			},
			paddingTop: {
				type: Object,
				default: {
					value: 100,
					unit: 'px',
				},
			},
			pageSize: {
				type: String,
			},
			pageOrientation: {
				type: String,
			},
		},
		supports: {
			className: false,
			multiple: false,
			lock: true,
			inserter: false,
			customClassName: false,
		},
		edit,
		save,
	});
};
