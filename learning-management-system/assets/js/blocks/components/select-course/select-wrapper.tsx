import http from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import React, { useEffect, useState } from 'react';
import { reactSelectStyles } from '../../../back-end/config/styles';
import { formatParams } from '../../../back-end/utils/utils';
import AsyncSelect from './async-select';

function CourseFilterForBlocks(props) {
	const { course, setAttributes, setCourseId } = props;
	const [defaultCourses, setDefaultCourses] = useState([]);

	const handleChange = (selectedOption) => {
		setCourseId(selectedOption.value);
		setAttributes({ course: selectedOption });
	};

	useEffect(() => {
		fetchCoursesFromAPI().then(setDefaultCourses);
	}, []);

	const loadOptions = (inputValue, callback) => {
		fetchCoursesFromAPI(inputValue).then(callback);
	};

	return (
		<div className="course-select-wrapper">
			<AsyncSelect
				onChange={handleChange}
				placeholder={__('Filter by Course', 'masteriyo')}
				isClearable={false}
				cacheOptions={true}
				defaultValue={course}
				styles={reactSelectStyles}
				loadOptions={loadOptions}
				defaultOptions={defaultCourses}
			/>
		</div>
	);
}

export default CourseFilterForBlocks;

const fetchCoursesFromAPI = async (search = '') => {
	const params = formatParams({
		order_by: 'name',
		order: 'asc',
		per_page: 5,
		search,
	});

	const response: any = await http({
		path: `/masteriyo/v1/courses?${params}`,
		method: 'get',
	});

	return (response?.data ?? [])
		.filter((course) => course.status === 'publish')
		.map((course) => ({
			value: course.id,
			label: `#${course.id} ${course.name}`,
		}));
};
