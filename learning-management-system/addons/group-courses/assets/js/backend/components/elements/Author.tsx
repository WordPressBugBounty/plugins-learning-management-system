import {
	Avatar,
	Box,
	FormControl,
	FormLabel,
	HStack,
	Skeleton,
} from '@chakra-ui/react';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { ControlProps, OptionProps, components } from 'chakra-react-select';
import React from 'react';
import { useFormContext } from 'react-hook-form';
import { useQuery } from 'react-query';
import AsyncSelect from '../../../../../../../assets/js/back-end/components/common/AsyncSelect';
import { reactSelectStyles } from '../../../../../../../assets/js/back-end/config/styles';
import urls from '../../../../../../../assets/js/back-end/constants/urls';
import { AuthorMap } from '../../../../../../../assets/js/back-end/types/course';
import { UsersApiResponse } from '../../../../../../../assets/js/back-end/types/users';
import API from '../../../../../../../assets/js/back-end/utils/api';
import { isEmpty } from '../../../../../../../assets/js/back-end/utils/utils';

interface Props {
	authorData?: AuthorMap;
	tabIndex?: boolean;
}

interface AsyncSelectOption {
	value: string | number;
	label: string;
	avatar_url?: string;
}

const Control: React.FC<ControlProps<AsyncSelectOption, false>> = ({
	children,
	...rest
}) => {
	return (
		<components.Control {...rest}>
			<Avatar marginLeft="2" src={rest.getValue()?.[0]?.avatar_url} size="xs" />
			{children}
		</components.Control>
	);
};

const Option: React.FC<
	OptionProps<
		{
			value: any;
			label: any;
			avatar_url?: string;
		},
		false
	>
> = ({ children, ...rest }) => {
	return (
		<components.Option {...rest}>
			<HStack alignItems="center">
				<Avatar src={rest.data?.avatar_url} size="xs" />
				<Box>{children}</Box>
			</HStack>
		</components.Option>
	);
};

const Author: React.FC<Props> = (props) => {
	const canEditUsers =
		useSelect((select: any) => select('core').canUser('create', 'users'), []) ||
		false;
	const currentUser = useSelect(
		(select: any) => select('core').getCurrentUser(),
		[],
	);
	const defaultAuthor = isEmpty(currentUser)
		? null
		: {
				value: currentUser?.id,
				label: currentUser?.name,
				avatar_url: !isEmpty(currentUser?.avatar_urls)
					? currentUser?.avatar_urls['24']
					: '',
			};

	const { authorData, tabIndex } = props;
	const { setValue } = useFormContext();

	const usersAPI = new API(urls.users);

	const usersQuery = useQuery<UsersApiResponse>(
		'users',
		() =>
			usersAPI.list({
				roles: 'administrator,masteriyo_instructor,masteriyo_student',
				orderby: 'display_name',
				order: 'asc',
				per_page: 10,
			}),
		{
			enabled: canEditUsers,
		},
	);

	return (
		<FormControl>
			<FormLabel>{__('Author', 'learning-management-system')}</FormLabel>
			{!usersQuery.isLoading && defaultAuthor ? (
				<AsyncSelect
					isDisabled={!canEditUsers}
					components={{ Control: Control as any, Option: Option as any }}
					styles={reactSelectStyles}
					cacheOptions={true}
					loadingMessage={() =>
						__('Searching...', 'learning-management-system')
					}
					noOptionsMessage={({ inputValue }) =>
						!isEmpty(inputValue)
							? __('Users not found.', 'learning-management-system')
							: __(
									'Please enter one or more characters.',
									'learning-management-system',
								)
					}
					isClearable={true}
					placeholder={__(
						'Search by username or email',
						'learning-management-system',
					)}
					defaultValue={
						authorData
							? {
									value: authorData.id,
									label: authorData.display_name,
									avatar_url: authorData.avatar_url,
								}
							: defaultAuthor
					}
					onChange={(selectedOption: any) => {
						setValue('author_id', selectedOption?.value);
					}}
					defaultOptions={
						usersQuery.isSuccess
							? usersQuery.data?.data?.map((user) => {
									return {
										value: user.id,
										label: user.display_name,
										avatar_url: user.avatar_url,
									};
								})
							: [defaultAuthor]
					}
					loadOptions={(searchValue, callback) => {
						if (isEmpty(searchValue)) {
							return callback([]);
						}
						usersAPI
							.list({
								search: searchValue,
								roles: 'administrator,masteriyo_instructor,masteriyo_student',
							})
							.then((data) => {
								callback(
									data.data.map((user: any) => {
										return {
											value: user.id,
											label: user.display_name,
											avatar_url: user.avatar_url,
										};
									}),
								);
							});
					}}
				/>
			) : (
				<Skeleton height="40px" width="100%" />
			)}
		</FormControl>
	);
};

export default Author;
