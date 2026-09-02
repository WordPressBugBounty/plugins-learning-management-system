import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Icon } from '../../../../../../assets/js/blocks/components';
import Edit from './Edit';
import Save from './Save';
import attributes from './attributes';

export function registerCertificateVerificationCodeBlock() {
	registerBlockType('masteriyo/certificate-verification-code', {
		title: __('Certificate Verification Code', 'learning-management-system'),
		description: __(
			"The text 'Certificate verification code' will be replaced by the actual certificate verification code of students when downloading.",
			'learning-management-system',
		),
		icon: (
			<Icon type="blockIcon" name="certificationVerificationCode" size={24} />
		),
		category: 'masteriyo',
		keywords: ['certificate', 'code'],
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
