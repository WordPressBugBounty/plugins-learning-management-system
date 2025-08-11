import { Box, Tooltip, useToast } from '@chakra-ui/react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React, { useMemo, useState } from 'react';
import { Col, Row } from 'react-grid-system';
import { IoIosArrowBack } from 'react-icons/io';
import EmptyGroup from '../../../../../../assets/js/account/common/EmptyGroup';
import PageTitle from '../../../../../../assets/js/account/common/PageTitle';
import CustomAlert from '../../../../../../assets/js/back-end/components/common/CustomAlert';
import MasteriyoPagination from '../../../../../../assets/js/back-end/components/common/MasteriyoPagination';
import API from '../../../../../../assets/js/back-end/utils/api';
import { isEmpty } from '../../../../../../assets/js/back-end/utils/utils';
import { urls } from '../../constants/urls';
import { GroupSchema } from '../../types/group';
import EditGroupForm from './EditGroupForm';
import Group from './Group';
import GroupSkeleton from './skeleton/GroupSkeleton';
const isRTL = document.documentElement.dir === 'rtl';

interface FilterParams {
	per_page?: number;
	page?: number;
	orderby: string;
	order: 'asc' | 'desc';
}

const groupAPI = new API(urls.groups);

const Groups: React.FC = () => {
	const queryClient = useQueryClient();
	const toast = useToast();
	const [expandedGroupId, setExpandedGroupId] = useState<number | null>(null);
	const [filterParams, setFilterParams] = React.useState<FilterParams>({
		order: 'desc',
		orderby: 'date',
	});

	const groupQuery = useQuery({
		queryKey: ['groupsList', filterParams],
		queryFn: () => groupAPI.list(filterParams),
		...{
			keepPreviousData: true,
		},
	});

	const groupToBeEdited = useMemo(() => {
		return groupQuery?.data?.data.find((d: any) => d?.id === expandedGroupId);
	}, [expandedGroupId, groupQuery]);

	return (
		<>
			<PageTitle
				title={__(
					groupToBeEdited ? 'Edit Group' : 'Groups',
					'learning-management-system',
				)}
				beforeTitle={
					groupToBeEdited ? (
						<Tooltip label={__('Back To Groups', 'learning-management-system')}>
							<Box
								onClick={() => setExpandedGroupId(null)}
								borderRadius={'lg'}
								bgColor={'muted'}
								p={3}
								mr={2}
								cursor={'pointer'}
							>
								<IoIosArrowBack
									style={{ transform: isRTL ? 'rotate(180deg)' : 'none' }}
								/>
							</Box>
						</Tooltip>
					) : null
				}
			></PageTitle>
			{groupQuery.isLoading || !groupQuery.isFetched ? (
				<GroupSkeleton />
			) : groupQuery.isError ? (
				<CustomAlert status="error">
					{__('Error fetching groups.', 'learning-management-system')}
				</CustomAlert>
			) : groupQuery.isSuccess && isEmpty(groupQuery?.data?.data) ? (
				<EmptyGroup
					text={__("You don't have any groups yet")}
					showButton={false}
					visible={true}
				/>
			) : (
				<Row>
					{!groupToBeEdited ? (
						groupQuery?.data?.data?.map((group: GroupSchema) => (
							<Col xs={12} md={6} key={group.id}>
								<Group
									group={group}
									onExpandedGroupsChange={(id) =>
										setExpandedGroupId((prevId) => (prevId === id ? null : id))
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
			)}
			{!groupToBeEdited &&
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
