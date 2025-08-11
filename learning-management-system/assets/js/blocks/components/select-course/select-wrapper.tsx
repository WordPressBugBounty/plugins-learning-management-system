import http from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import React, { useEffect, useState } from 'react';
import { reactSelectStyles } from '../../../back-end/config/styles';
import { formatParams } from '../../../back-end/utils/utils';
import AsyncSelect from './async-select';

function CourseFilterForBlocks(props) {
	const { value: selectedCourseId, setAttributes, setCourseId } = props;

	const [defaultCourses, setDefaultCourses] = useState([]);
	const [selectedCourse, setSelectedCourse] = useState(null);

	useEffect(() => {
		fetchCoursesFromAPI().then(setDefaultCourses);
	}, []);

	useEffect(() => {
		if (selectedCourseId && !selectedCourse) {
			fetchCoursesFromAPI().then((courses) => {
				const match = courses.find((c) => c.value === selectedCourseId);
				if (match) {
					setSelectedCourse(match);
				} else {
					fetchSingleCourseById(selectedCourseId).then((course) => {
						if (course) {
							setSelectedCourse(course);
						}
					});
				}
			});
		}
	}, [selectedCourseId]);

	const handleChange = (selectedOption) => {
		setSelectedCourse(selectedOption);
		setCourseId(selectedOption.value);
	};

	const loadOptions = (inputValue, callback) => {
		fetchCoursesFromAPI(inputValue).then(callback);
	};

	return (
		<div className="course-select-wrapper">
			<AsyncSelect
				onChange={handleChange}
				value={selectedCourse}
				placeholder={__('Search Courses', 'masteriyo')}
				isClearable={false}
				cacheOptions={true}
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

	const response = await http({
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

const fetchSingleCourseById = async (id) => {
	try {
		const response = await http({
			path: `/masteriyo/v1/courses/${id}`,
			method: 'get',
		});

		if (response?.id) {
			return {
				value: response.id,
				label: `#${response.id} ${response.name}`,
			};
		}
		return null;
	} catch (error) {
		return null;
	}
};
