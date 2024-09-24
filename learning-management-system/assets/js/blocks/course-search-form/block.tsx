import { __ } from '@wordpress/i18n';
import React from 'react';
import { Icon } from '../components';
import Edit from './Edit';
import attributes from './attributes';
import './editor.scss';

export function registerCourseSearchForm() {
	wp.blocks.registerBlockType('masteriyo/course-search-form', {
		title: __('Course Search Form', 'masteriyo'),
		description: __(
			"The dummy content 'Course Search Form' will be replaced by the actual course search from for which the course provides.",
			'masteriyo',
		),
		icon: <Icon type="blockIcon" name="course-search" size={24} />,
		category: 'masteriyo',
		keywords: ['Course Search Form'],
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
		save: () => null,
	});
}
