import http from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { reactSelectStyles } from '../../../back-end/config/styles';
import { formatParams, isEmpty } from '../../../back-end/utils/utils';
import AsyncSelect from './async-select';

export interface CourseSchema {
	id: number;
	name: string;
	slug: string;
	permalink: string;
	preview_permalink: string;
	date_created: string;
	date_created_gmt: string;
	date_modified: string;
	date_modified_gmt: string;
	status: string;
	featured: boolean;
	catalog_visibility: string;
	description: string;
	short_description: string;
	price: string;
	author: {
		id: number;
		display_name: string;
		avatar_url: string;
	};
	_links: {
		self: [
			{
				href: string;
			},
		];
		collection: [
			{
				href: string;
			},
		];
		first: [
			{
				href: string;
			},
		];
	};
}
export interface CoursesResponse {
	data: CourseSchema[];
	meta: {
		current_page: number;
		pages: number;
		per_page: number;
		total: number;
	};
}

function CourseFilterForBlocks(props) {
	const { course, setAttributes, setCourseId } = props;

	const handleChange = (selectedOption) => {
		setCourseId(selectedOption.value);
		setAttributes({ course: selectedOption });
	};

	return (
		<AsyncSelect
			onChange={handleChange}
			placeholder={__('Filter by Course', 'masteriyo')}
			isClearable={false}
			cacheOptions={true}
			styles={reactSelectStyles}
			defaultValue={course}
			loadOptions={(inputValue, callback) => {
				if (isEmpty(inputValue)) {
					return callback([]);
				}
				http({
					path: `/masteriyo/v1/courses?${formatParams({
						order_by: 'name',
						order: 'asc',
						per_page: 5,
						search: inputValue,
					})}`,
					method: 'get',
				}).then((data: any) => {
					callback(
						data?.data?.map((course) => {
							return {
								value: course.id,
								label: `#${course.id} ${course.name}`,
							};
						}),
					);
				});
			}}
		/>
	);
}
export default CourseFilterForBlocks;
