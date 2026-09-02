import { FormControl, FormLabel } from '@chakra-ui/react';
import { useQuery } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { useFormContext } from 'react-hook-form';
import AsyncSelect from '../../../../../../../assets/js/back-end/components/common/AsyncSelect';
import { reactSelectStyles } from '../../../../../../../assets/js/back-end/config/styles';
import urls from '../../../../../../../assets/js/back-end/constants/urls';
import { CoursesApiResponse } from '../../../../../../../assets/js/back-end/types/course';
import API from '../../../../../../../assets/js/back-end/utils/api';

interface EditWishlistItemFormSchema {
	author_id?: number;
	course_id?: number;
}

interface Props {
	wishlistItem?: WishlistItemSchema;
	showFormLabel?: boolean;
	isDisabled?: boolean;
	size?: 'sm' | 'md' | 'lg';
}

const CourseInput: React.FC<Props> = (props) => {
	const {
		wishlistItem,
		showFormLabel = true,
		isDisabled = false,
		size,
	} = props;
	const formMethods = useFormContext<EditWishlistItemFormSchema>();
	const { setValue } = formMethods;
	const courseAPI = new API(urls.courses);

	const courseQueries = useQuery<CoursesApiResponse>({
		queryKey: ['courseList'],
		queryFn: () =>
			courseAPI.list({
				orderby: 'date',
				order: 'desc',
				per_page: 15,
			}),
	});

	return (
		<FormControl py={showFormLabel ? '3' : '0'}>
			{showFormLabel && (
				<FormLabel>{__('Course', 'learning-management-system')}</FormLabel>
			)}
			<AsyncSelect
				onChange={(selectedOption: any) => {
					setValue('course_id', selectedOption?.value);
				}}
				isDisabled={isDisabled}
				size={size}
				placeholder={__('Type to search courses', 'learning-management-system')}
				isClearable={true}
				styles={reactSelectStyles}
				cacheOptions={true}
				loadingMessage={() =>
					__('Searching course…', 'learning-management-system')
				}
				noOptionsMessage={({ inputValue }) =>
					inputValue.length > 0
						? __('Course not found.', 'learning-management-system')
						: courseQueries.isLoading
							? __('Loading…', 'learning-management-system')
							: __(
									'Please enter 1 or more characters.',
									'learning-management-system',
								)
				}
				menuPortalTarget={
					typeof document !== 'undefined' ? document.body : undefined
				}
				menuPosition="fixed"
				menuPlacement="auto"
				defaultValue={
					wishlistItem
						? {
								value: wishlistItem.course_id,
								label: `#${wishlistItem.id} - ${wishlistItem.course_title}`,
							}
						: undefined
				}
				defaultOptions={
					courseQueries.isSuccess
						? courseQueries?.data?.data?.map((course) => {
								return {
									value: course.id,
									label: `#${course.id} - ${course.name}`,
								};
							})
						: []
				}
				loadOptions={(searchValue, callback) => {
					if (searchValue.length < 0) {
						return callback([]);
					}
					courseAPI.list({ search: searchValue }).then((data) => {
						callback(
							data?.data?.map((course: any) => {
								return {
									value: course.id,
									label: `#${course.id} ${course.name}`,
								};
							}),
						);
					});
				}}
			/>
		</FormControl>
	);
};

export default CourseInput;
