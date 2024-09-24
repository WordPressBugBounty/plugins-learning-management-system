import {
	FormControl,
	FormErrorMessage,
	FormLabel,
	Skeleton,
	Stack,
} from '@chakra-ui/react';
import { useSelect } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import React, { useMemo } from 'react';
import { Controller, useFormContext } from 'react-hook-form';
import { useQuery } from 'react-query';
import AsyncCreatableSelect from 'react-select/async-creatable';
import { reactSelectStyles } from '../../../../../../../assets/js/back-end/config/styles';
import urls from '../../../../../../../assets/js/back-end/constants/urls';
import { UsersApiResponse } from '../../../../../../../assets/js/back-end/types/users';
import API from '../../../../../../../assets/js/back-end/utils/api';
import { isEmpty } from '../../../../../../../assets/js/back-end/utils/utils';

interface SelectOption {
	value: string;
	label: string;
}

const validateEmail = (email: string): boolean => {
	const re =
		/^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
	return re.test(String(email).toLowerCase());
};

interface Props {
	defaultValue?: string[];
}

const Emails: React.FC<Props> = ({ defaultValue }) => {
	const {
		control,
		formState: { errors },
	} = useFormContext();
	const usersAPI = new API(urls.users);

	const canEditUsers =
		useSelect((select: any) => select('core').canUser('create', 'users'), []) ||
		false;

	const usersQuery = useQuery<UsersApiResponse>(
		'users',
		() =>
			usersAPI.list({
				roles: 'administrator,masteriyo_instructor,masteriyo_student',
				orderby: 'email',
				order: 'asc',
				per_page: 10,
			}),
		{
			enabled: canEditUsers,
		},
	);

	const options: SelectOption[] = useMemo(() => {
		return usersQuery.isSuccess
			? usersQuery.data?.data?.map((user) => {
					return {
						value: user.email,
						label: user.email,
					};
				})
			: [];
	}, [usersQuery.isSuccess, usersQuery.data]);

	const defaultOptions: SelectOption[] = useMemo(() => {
		return Array.isArray(defaultValue)
			? defaultValue?.map((email) => ({
					value: email,
					label: email,
				})) || []
			: [];
	}, [defaultValue]);

	return (
		<Stack spacing={2}>
			<FormControl isInvalid={!!errors.emails}>
				<FormLabel htmlFor="emails">
					{__('Emails', 'learning-management-system')}
				</FormLabel>
				{usersQuery.isLoading ? (
					<Skeleton height="40px" width="100%" />
				) : (
					<Controller
						name="emails"
						control={control}
						defaultValue={defaultOptions}
						render={({ field }) => (
							<AsyncCreatableSelect
								{...field}
								options={options}
								isMulti
								closeMenuOnSelect={false}
								isClearable
								styles={reactSelectStyles}
								formatCreateLabel={(inputValue) =>
									sprintf(
										__('Add "%s"', 'learning-management-system'),
										inputValue,
									)
								}
								defaultOptions={
									usersQuery.isSuccess
										? usersQuery.data?.data?.map((user) => {
												return {
													value: user.email,
													label: user.email,
												};
											})
										: []
								}
								loadOptions={(searchValue, callback) => {
									if (isEmpty(searchValue)) {
										return callback([]);
									}
									usersAPI
										.list({
											search: searchValue,
											roles:
												'administrator,masteriyo_instructor,masteriyo_student',
											orderby: 'email',
											order: 'asc',
											per_page: 10,
										})
										.then((data) => {
											callback(
												data.data.map((user: any) => {
													return {
														value: user.email,
														label: user.email,
													};
												}),
											);
										});
								}}
								noOptionsMessage={() =>
									__('No emails found.', 'learning-management-system')
								}
								isValidNewOption={(inputValue) => validateEmail(inputValue)}
							/>
						)}
					/>
				)}
				<FormErrorMessage>
					{errors.emails && errors.emails.message?.toString()}
				</FormErrorMessage>
			</FormControl>
		</Stack>
	);
};

export default Emails;
