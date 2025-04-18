import {
	Box,
	Checkbox,
	Container,
	Stack,
	Text,
	useDisclosure,
	useMediaQuery,
	useToast,
} from '@chakra-ui/react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import { Add } from 'iconsax-react';
import React, { useEffect, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { Table, Tbody, Th, Thead, Tr } from 'react-super-responsive-table';
import ActionDialog from '../../../../../../assets/js/back-end/components/common/ActionDialog';
import EmptyInfo from '../../../../../../assets/js/back-end/components/common/EmptyInfo';
import FloatingBulkAction from '../../../../../../assets/js/back-end/components/common/FloatingBulkAction';
import {
	Header,
	HeaderPrimaryButton,
	HeaderRightSection,
	HeaderTop,
} from '../../../../../../assets/js/back-end/components/common/Header';
import MasteriyoPagination from '../../../../../../assets/js/back-end/components/common/MasteriyoPagination';
import Sorting from '../../../../../../assets/js/back-end/screens/courses/components/Sorting';
import API from '../../../../../../assets/js/back-end/utils/api';
import {
	deepMerge,
	isEmpty,
	removeOperationInCache,
} from '../../../../../../assets/js/back-end/utils/utils';
import { urls } from '../../constants/urls';
import { groupsBackendRoutes } from '../../routes/routes';
import { GroupSchema } from '../../types/group';
import GroupList from './GroupList';
import LeftHeader from './LeftHeader';
import SkeletonList from './Skeleton/SkeletonList';
import GroupFilter from './elements/GroupFilter';

interface FilterParams {
	search?: string;
	status?: string;
	per_page?: number;
	page?: number;
	orderby: string;
	order: 'asc' | 'desc';
}

const AllGroups = () => {
	const groupAPI = new API(urls.groups);
	const navigate = useNavigate();
	const toast = useToast();

	const [filterParams, setFilterParams] = useState<FilterParams>({
		order: 'desc',
		orderby: 'date',
	});

	const [searchParams] = useSearchParams();
	const currentTab = searchParams.get('status');

	useEffect(() => {
		if (currentTab) {
			setFilterParams((prevState) => ({
				...prevState,
				status: currentTab,
			}));
			setActive(currentTab);
		}
	}, [currentTab]);

	const [deleteGroupId, setDeleteGroupId] = useState<number>();
	const queryClient = useQueryClient();
	const { onClose, onOpen, isOpen } = useDisclosure();
	const [active, setActive] = useState('any');
	const [bulkAction, setBulkAction] = useState<string>('');
	const [bulkIds, setBulkIds] = useState<string[]>([]);

	const [min360px] = useMediaQuery('(min-width: 360px)');

	const groupQuery = useQuery({
		queryKey: ['groupsList', filterParams],
		queryFn: () => groupAPI.list(filterParams),
		...{
			keepPreviousData: true,
		},
	});

	const deleteGroup = useMutation({
		mutationFn: (id: number) => groupAPI.delete(id, { force: true }),
		...{
			onSuccess: (data: any) => {
				removeOperationInCache(
					queryClient,
					['groupsList', { order: 'desc', orderby: 'date' }],
					data?.id,
				);
				queryClient.invalidateQueries({ queryKey: ['groupsList'] });
				onClose();
				toast({
					title: __(
						'Group deleted successfully!',
						'learning-management-system',
					),
					isClosable: true,
					status: 'success',
				});
			},
			onError: (err: any) => {
				toast({
					title:
						err?.message ||
						__('Something went wrong', 'learning-management-system'),
					status: 'error',
					isClosable: true,
				});
			},
		},
	});

	const restoreGroup = useMutation({
		mutationFn: (id: number) => groupAPI.restore(id),
		...{
			onSuccess: () => {
				toast({
					title: __('Group Restored', 'learning-management-system'),
					isClosable: true,
					status: 'success',
				});
				queryClient.invalidateQueries({ queryKey: ['groupsList'] });
			},
			onError: (err: any) => {
				toast({
					title:
						err?.message ||
						__('Something went wrong', 'learning-management-system'),
					status: 'error',
					isClosable: true,
				});
			},
		},
	});

	const trashGroup = useMutation({
		mutationFn: (id: number) => groupAPI.delete(id),
		...{
			onSuccess: (data: any) => {
				removeOperationInCache(
					queryClient,
					['groupsList', { order: 'desc', orderby: 'date' }],
					data?.id,
				);
				queryClient.invalidateQueries({ queryKey: ['groupsList'] });
				toast({
					title: __('Group Trashed', 'learning-management-system'),
					isClosable: true,
					status: 'success',
				});
			},
			onError: (err: any) => {
				toast({
					title:
						err?.message ||
						__('Something went wrong', 'learning-management-system'),
					status: 'error',
					isClosable: true,
				});
			},
		},
	});

	const onTrashPress = (groupId: number) => {
		groupId && trashGroup.mutate(groupId);
	};

	const onDeletePress = (groupId: number) => {
		onOpen();
		setBulkAction('');
		setDeleteGroupId(groupId);
	};

	const onDeleteConfirm = () => {
		deleteGroupId ? deleteGroup.mutate(deleteGroupId) : null;
	};

	const onRestorePress = (groupId: number) => {
		groupId ? restoreGroup.mutate(groupId) : null;
	};

	const filterGroupsBy = (order: 'asc' | 'desc', orderBy: string) =>
		setFilterParams(
			deepMerge({
				...filterParams,
				order: order,
				orderby: orderBy,
			}),
		);

	const onBulkActionApply = {
		delete: useMutation({
			mutationFn: (data: any) =>
				groupAPI.bulkDelete('delete', {
					ids: data,
					force: true,
				}),
			...{
				onSuccess() {
					queryClient.invalidateQueries({ queryKey: ['groupsList'] });
					onClose();
					setBulkIds([]);
					toast({
						title: __('Groups Deleted', 'learning-management-system'),
						isClosable: true,
						status: 'success',
					});
				},
				onError: (err: any) => {
					toast({
						title:
							err?.message ||
							__('Something went wrong', 'learning-management-system'),
						status: 'error',
						isClosable: true,
					});
				},
			},
		}),
		trash: useMutation({
			mutationFn: (data: any) => groupAPI.bulkDelete('delete', { ids: data }),
			...{
				onSuccess() {
					queryClient.invalidateQueries({ queryKey: ['groupsList'] });
					onClose();
					setBulkIds([]);
					toast({
						title: __('Groups Trashed', 'learning-management-system'),
						isClosable: true,
						status: 'success',
					});
				},
				onError: (err: any) => {
					toast({
						title:
							err?.message ||
							__('Something went wrong', 'learning-management-system'),
						status: 'error',
						isClosable: true,
					});
				},
			},
		}),
		restore: useMutation({
			mutationFn: (data: any) => groupAPI.bulkRestore('restore', { ids: data }),
			...{
				onSuccess() {
					queryClient.invalidateQueries({ queryKey: ['groupsList'] });
					onClose();
					setBulkIds([]);
					toast({
						title: __('Groups Restored', 'learning-management-system'),
						isClosable: true,
						status: 'success',
					});
				},
				onError: (err: any) => {
					toast({
						title:
							err?.message ||
							__('Something went wrong', 'learning-management-system'),
						status: 'error',
						isClosable: true,
					});
				},
			},
		}),
	};

	return (
		<Stack direction="column" spacing="8" alignItems="center">
			<Header>
				<HeaderTop>
					<LeftHeader />
					<HeaderRightSection>
						<HeaderPrimaryButton
							onClick={() => navigate(groupsBackendRoutes.add)}
							leftIcon={min360px ? <Add /> : undefined}
						>
							{__('Add New Group', 'learning-management-system')}
						</HeaderPrimaryButton>
					</HeaderRightSection>
				</HeaderTop>
			</Header>

			<Container maxW="container.xl">
				<Box bg="white" py={{ base: 6, md: 12 }} shadow="box" mx="auto">
					<Stack direction="column" spacing="10">
						<GroupFilter
							setFilterParams={setFilterParams}
							filterParams={filterParams}
						/>
						<Stack
							direction="column"
							spacing="8"
							mt={{
								base: '15px !important',
								sm: '15px !important',
								md: '2.5rem !important',
								lg: '2.5rem !important',
							}}
						>
							<Table>
								<Thead>
									<Tr>
										<Th>
											<Checkbox
												isDisabled={
													groupQuery.isLoading ||
													groupQuery.isFetching ||
													groupQuery.isRefetching
												}
												isIndeterminate={
													groupQuery?.data?.data?.length !== bulkIds?.length &&
													bulkIds?.length > 0
												}
												isChecked={
													groupQuery?.data?.data?.length === bulkIds.length &&
													!isEmpty(groupQuery?.data?.data as boolean)
												}
												onChange={(e) =>
													setBulkIds(
														e.target.checked
															? groupQuery?.data?.data?.map((group: any) =>
																	group?.id?.toString(),
																)
															: [],
													)
												}
											/>
										</Th>
										<Th>
											<Stack direction="row" alignItems="center">
												<Text fontSize="xs">
													{__('Title', 'learning-management-system')}
												</Text>
												<Sorting
													filterParams={filterParams}
													filterContentBy={filterGroupsBy}
													orderBy={'title'}
												/>
											</Stack>
										</Th>
										<Th>{__('Author', 'learning-management-system')}</Th>
										<Th>{__('Members', 'learning-management-system')}</Th>
										<Th>
											{__('Enrolled Courses', 'learning-management-system')}
										</Th>
										<Th>
											<Stack direction="row" alignItems="center">
												<Text fontSize="xs">
													{__('Date', 'learning-management-system')}
												</Text>
												<Sorting
													filterParams={filterParams}
													filterContentBy={filterGroupsBy}
													orderBy={'date'}
												/>
											</Stack>
										</Th>
										<Th>{__('Actions', 'learning-management-system')}</Th>
									</Tr>
								</Thead>
								<Tbody>
									{groupQuery.isLoading || !groupQuery.isFetched ? (
										<SkeletonList />
									) : groupQuery.isSuccess &&
									  isEmpty(groupQuery?.data?.data) ? (
										<EmptyInfo
											message={__(
												'No groups found.',
												'learning-management-system',
											)}
										/>
									) : (
										groupQuery?.data?.data?.map((group: GroupSchema) => (
											<GroupList
												key={group?.id}
												data={group}
												bulkIds={bulkIds}
												onDeletePress={onDeletePress}
												onRestorePress={onRestorePress}
												onTrashPress={onTrashPress}
												setBulkIds={setBulkIds}
												isLoading={
													groupQuery.isLoading ||
													groupQuery.isFetching ||
													groupQuery.isRefetching
												}
											/>
										))
									)}
								</Tbody>
							</Table>
						</Stack>
					</Stack>
				</Box>
				{groupQuery.isSuccess && !isEmpty(groupQuery?.data?.data) && (
					<MasteriyoPagination
						metaData={groupQuery?.data?.meta}
						setFilterParams={setFilterParams}
						perPageText={__('Groups Per Page:', 'learning-management-system')}
					/>
				)}
			</Container>
			<FloatingBulkAction
				openToast={onOpen}
				status={active}
				setBulkAction={setBulkAction}
				bulkIds={bulkIds}
				setBulkIds={setBulkIds}
				trashable={true}
			/>
			<ActionDialog
				isOpen={isOpen}
				onClose={onClose}
				confirmButtonColorScheme={
					'restore' === bulkAction ? 'primary' : undefined
				}
				onConfirm={
					'' === bulkAction
						? onDeleteConfirm
						: () => {
								onBulkActionApply[bulkAction].mutate(bulkIds);
							}
				}
				action={bulkAction}
				isLoading={
					'' === bulkAction
						? deleteGroup.isPending
						: (onBulkActionApply?.[bulkAction]?.isLoading ?? false)
				}
				dialogTexts={{
					default: {
						header: __('Deleting group', 'learning-management-system'),
						body: __(
							'Are you sure? You can’t restore after deleting.',
							'learning-management-system',
						),
						confirm: __('Delete', 'learning-management-system'),
					},
					trash: {
						header: __('Moving groups to trash', 'learning-management-system'),
						body: __(
							'Are you sure? The selected groups will be moved to trash.',
							'learning-management-system',
						),
						confirm: __('Move to Trash', 'learning-management-system'),
					},
					delete: {
						header: __('Deleting Groups', 'learning-management-system'),
						body: __('Are you sure? You can’t restore after deleting.'),
						confirm: __('Delete', 'learning-management-system'),
					},
					restore: {
						header: __('Restoring Groups', 'learning-management-system'),
						body: __(
							'Are you sure? The selected groups will be restored from the trash.',
							'learning-management-system',
						),
						confirm: __('Restore', 'learning-management-system'),
					},
				}}
			/>
		</Stack>
	);
};

export default AllGroups;
