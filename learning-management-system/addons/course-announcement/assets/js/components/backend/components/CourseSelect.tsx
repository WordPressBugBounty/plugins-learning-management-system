import { FormControl, FormLabel, Skeleton } from '@chakra-ui/react';
import { useQuery } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { useFormContext } from 'react-hook-form';
import AsyncSelect from '../../../../../../../assets/js/back-end/components/common/AsyncSelect';
import { reactSelectStyles } from '../../../../../../../assets/js/back-end/config/styles';
import urls from '../../../../../../../assets/js/back-end/constants/urls';
import API from '../../../../../../../assets/js/back-end/utils/api';
import { isEmpty } from '../../../../../../../assets/js/back-end/utils/utils';

interface Props {
	defaultData?: {
		id: number;
		name: string;
	};
}

const CourseSelect: React.FC<Props> = (props) => {
	const { defaultData } = props;
	const courseAPI = new API(urls.courses);
	const { setValue } = useFormContext();

	const courseQueries = useQuery<any>({
		queryKey: ['courseList'],
		queryFn: () =>
			courseAPI.list({
				order_by: 'name',
				order: 'asc',
				per_page: 10,
			}),
	});

	return (
		<FormControl>
			<FormLabel>{__('Course', 'learning-management-system')}</FormLabel>
			{!courseQueries.isLoading ? (
				courseQueries?.isSuccess &&
				!isEmpty(courseQueries.data?.data) && (
					<AsyncSelect
						styles={reactSelectStyles}
						cacheOptions={true}
						loadingMessage={() =>
							__('Searching...', 'learning-management-system')
						}
						noOptionsMessage={({ inputValue }) =>
							!isEmpty(inputValue)
								? __('Courses not found.', 'learning-management-system')
								: __(
										'Please enter one or more characters.',
										'learning-management-system',
									)
						}
						isClearable={true}
						placeholder={__(
							'Please select a course.',
							'learning-management-system',
						)}
						defaultValue={
							defaultData
								? {
										value: defaultData.id,
										label: defaultData.name,
									}
								: null
						}
						onChange={(selectedOption: any) => {
							setValue('course_id', selectedOption?.value);
						}}
						defaultOptions={
							courseQueries.isSuccess
								? courseQueries.data?.data?.map((course: any) => {
										return {
											value: course.id,
											label: course.name,
										};
									})
								: []
						}
						loadOptions={(searchValue, callback) => {
							if (isEmpty(searchValue)) {
								return callback([]);
							}
							courseAPI
								.list({
									search: searchValue,
								})
								.then((data) => {
									callback(
										data.data.map((course: any) => {
											return {
												value: course.id,
												label: course.name,
											};
										}),
									);
								});
						}}
					/>
				)
			) : (
				<Skeleton height="40px" width="100%" />
			)}
		</FormControl>
	);
};

export default CourseSelect;
