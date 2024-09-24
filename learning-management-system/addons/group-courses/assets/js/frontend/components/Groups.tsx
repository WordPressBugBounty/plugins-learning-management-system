import {
	Alert,
	AlertIcon,
	Box,
	Button,
	Tooltip,
	useDisclosure,
	useToast,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useMemo, useState } from 'react';
import { Col, Row } from 'react-grid-system';
import { IoIosArrowBack } from 'react-icons/io';
import { IoAddOutline } from 'react-icons/io5';
import { useMutation, useQuery, useQueryClient } from 'react-query';
import EmptyGroup from '../../../../../../assets/js/account/common/EmptyGroup';
import PageTitle from '../../../../../../assets/js/account/common/PageTitle';
import MasteriyoPagination from '../../../../../../assets/js/back-end/components/common/MasteriyoPagination';
import API from '../../../../../../assets/js/back-end/utils/api';
import {
	deepClean,
	isEmpty,
} from '../../../../../../assets/js/back-end/utils/utils';
import { urls } from '../../constants/urls';
import { GroupSchema } from '../../types/group';
import AddGroupForm from './AddGroupForm';
import EditGroupForm from './EditGroupForm';
import Group from './Group';
import GroupSkeleton from './skeleton/GroupSkeleton';

// isOpen specifies that new group is to be added
// groupToBeEdited specified that any one of the group is being edited
// In multiple conditions isOpen and groupToBeEdited is checked to be sure that only one of these UI is visible when they exist are or true

interface FilterParams {
	per_page?: number;
	page?: number;
	orderby: string;
	order: 'asc' | 'desc';
}

const groupAPI = new API(urls.groups);

const Groups: React.FC = () => {
	const queryClient = useQueryClient();
	const { isOpen, onToggle, onClose } = useDisclosure();
	const toast = useToast();
	const [expandedGroupId, setExpandedGroupId] = useState<number | null>(null);
	const [filterParams, setFilterParams] = React.useState<FilterParams>({
		order: 'desc',
		orderby: 'date',
	});

	const groupQuery = useQuery(
		['groupsList', filterParams],
		() => groupAPI.list(filterParams),
		{
			keepPreviousData: true,
		},
	);
	const addGroupMutation = useMutation<GroupSchema>((data) =>
		groupAPI.store(data),
	);

	const handleAddNewGroup = (data: GroupSchema) => {
		addGroupMutation.mutate(deepClean(data), {
			onSuccess: (data) => {
				onToggle();
				toast({
					title:
						data.title + __(' has been added.', 'learning-management-system'),
					status: 'success',
					isClosable: true,
				});
				queryClient.invalidateQueries(`groupsList`);
			},

			onError: (error: any) => {
				const message: any = error?.message
					? error?.message
					: error?.data?.message;

				toast({
					title: __('Failed to create group.', 'learning-management-system'),
					description: message ? `${message}` : undefined,
					status: 'error',
					isClosable: true,
				});
			},
		});
	};

	// This specifies that any one of the group is opened to be edited

	const groupToBeEdited = useMemo(() => {
		return groupQuery?.data?.data.find((d: any) => d?.id === expandedGroupId);
	}, [expandedGroupId, groupQuery]);

	return (
		<>
			<PageTitle
				title={__(
					groupToBeEdited ? 'Edit Group' : isOpen ? 'Create Group' : 'Groups',
					'learning-management-system',
				)}
				beforeTitle={
					isOpen || groupToBeEdited ? (
						<Tooltip label={__('Back To Groups', 'learning-management-system')}>
							<Box
								onClick={() =>
									groupToBeEdited ? setExpandedGroupId(null) : onToggle()
								}
								borderRadius={'lg'}
								bgColor={'muted'}
								p={3}
								mr={2}
								cursor={'pointer'}
							>
								<IoIosArrowBack />
							</Box>
						</Tooltip>
					) : null
				}
			>
				{!isEmpty(groupQuery?.data?.data) &&
					!isOpen && ( // Groups data should be empty and add new group fore shouldn't be enabled
						<Button
							colorScheme="primary"
							leftIcon={<IoAddOutline size={20} />}
							onClick={() => {
								setExpandedGroupId(null);
								onToggle();
							}}
							rounded={'md'}
						>
							{__('Add New Group', 'learning-management-system')}
						</Button>
					)}
			</PageTitle>

			{isOpen && (
				<AddGroupForm
					handleAddNewGroup={handleAddNewGroup}
					isLoading={addGroupMutation.isLoading}
					onClose={onClose}
				/>
			)}
			{groupQuery.isLoading || !groupQuery.isFetched ? (
				<GroupSkeleton />
			) : groupQuery.isError ? (
				<Alert status="error">
					<AlertIcon />
					{__('Error fetching groups.', 'learning-management-system')}
				</Alert>
			) : groupQuery.isSuccess && isEmpty(groupQuery?.data?.data) && !isOpen ? (
				<EmptyGroup
					text={__("You don't have any groups yet")}
					onButtonClick={() => {
						setExpandedGroupId(null);
						onToggle();
					}}
					visible={!isOpen}
				/>
			) : (
				!isOpen && ( // This condition specifies when new group is to be added the list of groups is not visible
					<Row>
						{!groupToBeEdited ? (
							groupQuery?.data?.data?.map((group: GroupSchema) => (
								<Col xs={12} md={6} key={group.id}>
									<Group
										group={group}
										onExpandedGroupsChange={(id) =>
											setExpandedGroupId((prevId) =>
												prevId === id ? null : id,
											)
										}
									/>
								</Col>
							))
						) : (
							<Col xs={12}>
								<EditGroupForm
									group={groupToBeEdited}
									onExpandedGroupsChange={setExpandedGroupId}
								/>
							</Col>
						)}
					</Row>
				)
			)}
			{!isOpen &&
				!groupToBeEdited &&
				groupQuery.isSuccess &&
				!isEmpty(groupQuery?.data?.data) && (
					<MasteriyoPagination
						metaData={groupQuery?.data?.meta}
						setFilterParams={setFilterParams}
						perPageText={__('Groups Per Page:', 'learning-management-system')}
					/>
				)}
		</>
	);
};

export default Groups;
