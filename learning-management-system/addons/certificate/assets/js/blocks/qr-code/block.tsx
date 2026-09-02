import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Icon } from '../../../../../../assets/js/blocks/components';
import Edit from './Edit';
import attributes from './attributes';

export function registerQrcode() {
	registerBlockType('masteriyo/qr-code', {
		title: __('QR Code', 'learning-management-system'),
		description: __(
			'The image of QR code will be replaced with the actual verification QR code information.',
			'learning-management-system',
		),
		icon: <Icon type="blockIcon" name="QRCode" size={24} />,
		category: 'masteriyo',
		keywords: ['qr code', 'verification'],
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
		// save: Save,
	});
}
