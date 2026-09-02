import http from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import React, { useEffect, useState } from 'react';
import { reactSelectStyles } from '../../../back-end/config/styles';
import { formatParams } from '../../../back-end/utils/utils';
import AsyncSelect from './async-select';

interface CourseOption {
	value: number;
	label: string;
}

interface CourseRow {
	id: number;
	name: string;
}

interface Props {
	value?: number;
	setAttributes: (attributes: Record<string, any>) => void;
	setCourseId: (courseId: number) => void;
}

function CourseFilterForBlocks(props: Props) {
	const { value: selectedCourseId, setCourseId } = props;

	const [defaultCourses, setDefaultCourses] = useState<CourseOption[]>([]);
	const [selectedCourse, setSelectedCourse] = useState<CourseOption | null>(
		null,
	);

	useEffect(() => {
		fetchCoursesFromAPI().then(setDefaultCourses);
	}, []);

	useEffect(() => {
		if (selectedCourseId) {
			// Check if the current selected course matches the selectedCourseId
			if (selectedCourse && selectedCourse.value === selectedCourseId) {
				return; // Already have the correct course selected
			}

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
		} else {
			// Clear selection if no course ID
			setSelectedCourse(null);
		}
	}, [selectedCourseId]);

	// forwardRef erases the AsyncSelect generic, so the option arrives as unknown.
	const handleChange = (selectedOption: unknown) => {
		const course = selectedOption as CourseOption;

		setSelectedCourse(course);
		setCourseId(course.value);
	};

	const loadOptions = (
		inputValue: string,
		callback: (options: CourseOption[]) => void,
	) => {
		fetchCoursesFromAPI(inputValue).then(callback);
	};

	return (
		<div className="course-select-wrapper">
			<AsyncSelect
				onChange={handleChange}
				value={selectedCourse}
				placeholder={__('Type to search courses', 'learning-management-system')}
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

const fetchCoursesFromAPI = async (search = ''): Promise<CourseOption[]> => {
	const params = formatParams({
		orderby: 'date',
		order: 'desc',
		per_page: 15,
		// Without this, drafts fill the cap and get discarded in the browser.
		status: 'publish',
		search,
	});

	const response = await http<{ data?: CourseRow[] }>({
		path: `/masteriyo/v1/courses?${params}`,
		method: 'get',
	});

	return (response?.data ?? []).map((course) => ({
		value: course.id,
		label: `#${course.id} ${course.name}`,
	}));
};

const fetchSingleCourseById = async (
	id: number,
): Promise<CourseOption | null> => {
	try {
		const response = await http<Partial<CourseRow>>({
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
