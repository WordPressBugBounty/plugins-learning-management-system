import {
	FormControl,
	FormErrorMessage,
	FormLabel,
	Skeleton,
} from '@chakra-ui/react';
import { useQuery } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React, { useEffect } from 'react';
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
	const {
		register,
		setValue,
		formState: { errors },
	} = useFormContext();

	// The select writes through setValue, so the field has no input to carry the
	// rule; register it by hand and seed the course an edit arrives with.
	useEffect(() => {
		register('course_id', {
			required: __(
				'Please select a course for the announcement.',
				'learning-management-system',
			),
		});

		if (defaultData?.id) {
			setValue('course_id', defaultData.id);
		}
	}, [register, setValue, defaultData?.id]);

	const courseQueries = useQuery<any>({
		queryKey: ['courseList'],
		queryFn: () =>
			courseAPI.list({
				orderby: 'date',
				order: 'desc',
				per_page: 15,
			}),
	});

	return (
		<FormControl isInvalid={!!errors?.course_id} isRequired>
			<FormLabel>{__('Course', 'learning-management-system')}</FormLabel>
			{!courseQueries.isLoading ? (
				<AsyncSelect
					// isRequired on the control otherwise makes chakra-react-select
					// render a hidden required input. The browser cannot focus it, so
					// it blocks the submit with nothing on screen.
					required={false}
					styles={reactSelectStyles}
					cacheOptions={true}
					loadingMessage={() => __('Searching…', 'learning-management-system')}
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
						'Type to search courses',
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
						setValue('course_id', selectedOption?.value, {
							shouldDirty: true,
							shouldValidate: true,
						});
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
			) : (
				<Skeleton height="40px" width="100%" />
			)}
			{errors?.course_id && (
				<FormErrorMessage>
					{errors?.course_id?.message as string}
				</FormErrorMessage>
			)}
		</FormControl>
	);
};

export default CourseSelect;
