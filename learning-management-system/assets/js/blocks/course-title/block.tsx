import { __ } from '@wordpress/i18n';
import React from 'react';
import { Icon } from '../components';
import Edit from './Edit';
import attributes from './attributes';
import './editor.scss';

export function registerCourseTitleBlock() {
	wp.blocks.registerBlockType('masteriyo/single-course-title', {
		title: __('Course Title', 'masteriyo'),
		description: __(
			'This block is designed to enhance the functionality of your LMS plugin by adding specific features within a Single Course Block. Please note that this block is only compatible when placed inside a Single Course Block. Using it outside of a Single Course Block will result in no functionality.',
			'masteriyo',
		),
		icon: <Icon type="blockIcon" name="course-title" size={24} />,
		category: 'masteriyo-single-course',
		keywords: ['Title'],
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
		usesContext: ['masteriyo/course_id'],
		edit: Edit,
		save: () => null,
	});
}
