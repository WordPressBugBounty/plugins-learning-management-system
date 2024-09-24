import { Box, Grid, Input } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { useForm } from 'react-hook-form';
import { useQuery } from 'react-query';
import { useSearchParams } from 'react-router-dom';
import { useOnType } from 'use-ontype';
import AsyncSelect from '../../../../../../../assets/js/back-end/components/common/AsyncSelect';
import { reactSelectStyles } from '../../../../../../../assets/js/back-end/config/styles';
import urls from '../../../../../../../assets/js/back-end/constants/urls';
import { UsersApiResponse } from '../../../../../../../assets/js/back-end/types/users';
import API from '../../../../../../../assets/js/back-end/utils/api';
import {
	deepClean,
	deepMerge,
} from '../../../../../../../assets/js/back-end/utils/utils';

interface FilterParams {
	user?: string | number;
	search?: string;
}

interface Props {
	setFilterParams: any;
	filterParams: FilterParams;
}

const GroupFilter: React.FC<Props> = ({ filterParams, setFilterParams }) => {
	const userAPI = new API(urls.users);
	const [searchParams] = useSearchParams();
	const groupStatus = searchParams.get('status') || 'any';

	const userQueries = useQuery<UsersApiResponse>('userList', () =>
		userAPI.list({
			order_by: 'name',
			order: 'asc',
			per_page: 5,
		}),
	);

	const { handleSubmit, register, setValue } = useForm();

	const onSearchInput = useOnType(
		{
			onTypeFinish: (val: string) => {
				setFilterParams({
					parent: 0,
					user: filterParams.user,
					search: val,
					status: groupStatus,
				});
			},
		},
		800,
	);

	const onChange = (data: FilterParams) => {
		setFilterParams(
			deepClean(
				deepMerge(data, {
					search: filterParams.search,
					parent: 0,
					status: groupStatus,
				}),
			),
		);
	};

	return (
		<Box px={{ base: 6, md: 12 }}>
			<form onChange={handleSubmit(onChange)}>
				<Grid gridTemplateColumns={{ md: 'repeat(2, 1fr)' }} gap="4">
					<Input
						placeholder={__('Search groups', 'learning-management-system')}
						{...onSearchInput}
						height="40px"
					/>
					<AsyncSelect
						{...register('author_id')}
						onChange={(selectedOption: any) => {
							setValue('author_id', selectedOption?.value.toString());
							handleSubmit(onChange)();
						}}
						placeholder={__('Filter by Author', 'learning-management-system')}
						isClearable={true}
						styles={reactSelectStyles}
						cacheOptions={true}
						loadingMessage={() =>
							__('Searching author...', 'learning-management-system')
						}
						noOptionsMessage={({ inputValue }) =>
							inputValue.length > 0
								? __('Author not found.', 'learning-management-system')
								: userQueries.isLoading
									? __('Loading...', 'learning-management-system')
									: __(
											'Please enter 1 or more characters.',
											'learning-management-system',
										)
						}
						defaultOptions={
							userQueries.isSuccess
								? userQueries?.data?.data?.map((author) => {
										return {
											value: author.id,
											label: `${author.username} (#${author.id} - ${author.email})`,
										};
									})
								: []
						}
						loadOptions={(searchValue, callback) => {
							if (searchValue.length < 0) {
								return callback([]);
							}
							userAPI.list({ search: searchValue }).then((data) => {
								callback(
									data?.data?.map((author: any) => {
										return {
											value: author.id,
											label: `${author.username} (#${author.id} - ${author.email})`,
										};
									}),
								);
							});
						}}
					/>
				</Grid>
			</form>
		</Box>
	);
};

export default GroupFilter;
