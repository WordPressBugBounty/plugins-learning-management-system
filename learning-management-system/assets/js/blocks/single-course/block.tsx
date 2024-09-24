import { __ } from '@wordpress/i18n';
import React from 'react';
import { Icon } from '../components';
import Edit from './Edit';
import Save from './Save';
import attributes from './attributes';
import './editor.scss';

export function registerSingleCourse() {
	wp.blocks.registerBlockType('masteriyo/single-course', {
		title: __('Single Course', 'masteriyo'),
		description: __(
			'The Single Course Block serves as the primary container for organizing and displaying your course content. This block is specifically designed to house additional blocks that provide enhanced functionality and detailed course information. To ensure proper functionality, make sure to add all related blocks within the Masteriyo Course Block section. Blocks placed outside of this section will not operate as intended.',
			'masteriyo',
		),
		icon: <Icon type="blockIcon" name="single-course" size={24} />,
		category: 'masteriyo',
		keywords: ['Single Course'],
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
		providesContext: {
			'masteriyo/course_id': 'courseId',
		},
		edit: Edit,
		save: Save,
	});
}
