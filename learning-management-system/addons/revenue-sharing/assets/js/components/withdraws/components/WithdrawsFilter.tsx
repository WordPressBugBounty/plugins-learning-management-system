import { Box, Grid, GridItem, Input } from '@chakra-ui/react';
import { useQuery } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React from 'react';
import ReactDatePicker from 'react-datepicker';
import { Controller, useForm } from 'react-hook-form';
import AsyncSelect from '../../../../../../../assets/js/back-end/components/common/AsyncSelect';
import urls from '../../../../../../../assets/js/back-end/constants/urls';
import { UsersApiResponse } from '../../../../../../../assets/js/back-end/types/users';
import API from '../../../../../../../assets/js/back-end/utils/api';
import {
	deepClean,
	deepMerge,
	isEmpty,
} from '../../../../../../../assets/js/back-end/utils/utils';

type FilterParams = {
	status?: string;
	after?: Date;
	before?: Date;
	instructor?: number;
};

type Props = {
	setFilterParams: (params: any) => void;
};

const WithdrawsFilter: React.FC<Props> = (props) => {
	const { setFilterParams } = props;
	const { handleSubmit, setValue, control } = useForm();
	const usersAPI = new API(urls.users);

	const usersQuery = useQuery<UsersApiResponse>({
		queryKey: ['instructors'],
		queryFn: () =>
			usersAPI.list({
				orderby: 'display_name',
				order: 'asc',
				per_page: 10,
				role: 'masteriyo_instructor',
			}),
	});

	const onChange = (data: FilterParams) => {
		setFilterParams(
			deepClean(
				deepMerge(data, {
					before: data?.before?.toISOString(),
					after: data?.after?.toISOString(),
					status: data?.status,
				}),
			),
		);
	};

	return (
		<Box px={{ base: 6, md: 12 }}>
			<form onChange={handleSubmit(onChange)}>
				<Grid gridTemplateColumns={{ md: 'repeat(3, 1fr)' }} gap="4">
					<GridItem>
						<Controller
							control={control}
							name="after"
							render={({ field: { onChange: onDateChange, value } }) => (
								<ReactDatePicker
									dateFormat="yyyy-MM-dd"
									onChange={(value: Date) => {
										onDateChange(value);
										handleSubmit(onChange)();
									}}
									selected={value as unknown as Date}
									customInput={<Input />}
									placeholderText={__('From', 'learning-management-system')}
									autoComplete="off"
								/>
							)}
						/>
					</GridItem>
					<GridItem>
						<Controller
							control={control}
							name="before"
							render={({ field: { onChange: onDateChange, value } }) => (
								<ReactDatePicker
									dateFormat="yyyy-MM-dd"
									onChange={(value: Date) => {
										onDateChange(value);
										handleSubmit(onChange)();
									}}
									selected={value as unknown as Date}
									customInput={<Input />}
									placeholderText={__('To', 'learning-management-system')}
									autoComplete="off"
								/>
							)}
						/>
					</GridItem>
					<GridItem>
						<AsyncSelect
							cacheOptions={true}
							loadingMessage={() =>
								__('Searching...', 'learning-management-system')
							}
							noOptionsMessage={({ inputValue }) =>
								!isEmpty(inputValue)
									? __('Users not found.', 'learning-management-system')
									: usersQuery.isLoading
										? __('Loading...', 'learning-management-system')
										: __(
												'Please enter one or more characters.',
												'learning-management-system',
											)
							}
							isClearable={true}
							placeholder={__(
								'Search by instructor',
								'learning-management-system',
							)}
							onChange={(selectedOption: any) => {
								setValue('instructor', selectedOption?.value);
								handleSubmit(onChange)();
							}}
							defaultOptions={
								usersQuery.isSuccess
									? usersQuery.data?.data?.map((user) => {
											return {
												value: user?.id,
												label: `${user?.display_name} (#${user?.id} - ${user?.email})`,
												avatar_url: user?.avatar_url,
											};
										})
									: []
							}
							loadOptions={(searchValue, callback) => {
								if (isEmpty(searchValue)) {
									return callback([]);
								}
								usersAPI.list({ search: searchValue }).then((data: any) => {
									callback(
										data?.data?.map((user: any) => {
											return {
												value: user?.id,
												label: `${user?.display_name} (#${user?.id} - ${user?.email})`,
											};
										}),
									);
								});
							}}
						/>
					</GridItem>
				</Grid>
			</form>
		</Box>
	);
};

export default WithdrawsFilter;
