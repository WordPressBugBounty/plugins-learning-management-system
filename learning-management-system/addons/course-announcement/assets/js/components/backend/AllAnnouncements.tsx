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
import React, { useState } from 'react';
import { BiSolidMegaphone } from 'react-icons/bi';
import { useNavigate } from 'react-router-dom';
import { Table, Tbody, Th, Thead, Tr } from 'react-super-responsive-table';
import ActionDialog from '../../../../../../assets/js/back-end/components/common/ActionDialog';
import EmptyInfo from '../../../../../../assets/js/back-end/components/common/EmptyInfo';
import FilterTabs from '../../../../../../assets/js/back-end/components/common/FilterTabs';
import FloatingBulkAction from '../../../../../../assets/js/back-end/components/common/FloatingBulkAction';
import {
	Header,
	HeaderLeftSection,
	HeaderLogo,
	HeaderPrimaryButton,
	HeaderRightSection,
	HeaderTop,
} from '../../../../../../assets/js/back-end/components/common/Header';
import MasteriyoPagination from '../../../../../../assets/js/back-end/components/common/MasteriyoPagination';
import {
	AddCourseIcon,
	DraftIcon,
	Published,
	Trash,
} from '../../../../../../assets/js/back-end/constants/images';
import routes from '../../../../../../assets/js/back-end/constants/routes';
import Sorting from '../../../../../../assets/js/back-end/screens/courses/components/Sorting';
import API from '../../../../../../assets/js/back-end/utils/api';
import {
	deepMerge,
	isEmpty,
	removeOperationInCache,
} from '../../../../../../assets/js/back-end/utils/utils';
import { urls } from '../../../../../course-announcement/assets/js/components/backend/constants/urls';
import AnnouncementList from './AnnouncementList';
import AnnouncementFilter from './components/AnnouncementFilter';
import { SkeletonAnnouncementList } from './components/AnnouncementSkeleton';

const tabButtons: FilterTabs = [
	{
		status: 'any',
		name: __('All Announcements', 'learning-management-system'),
		icon: <BiSolidMegaphone />,
	},
	{
		status: 'publish',
		name: __('Published', 'learning-management-system'),
		icon: <Published fill="currentColor" width="16" height="16" />,
	},
	{
		status: 'draft',
		name: __('Draft', 'learning-management-system'),
		icon: <DraftIcon fill="currentColor" width="16" height="16" />,
	},
	{
		status: 'trash',
		name: __('Trash', 'learning-management-system'),
		icon: <Trash fill="currentColor" width="16" height="16" />,
	},
];

interface FilterParams {
	search?: string;
	status?: string;
	per_page?: number;
	page?: number;
	orderby: string;
	order: 'asc' | 'desc';
}

const AllAnnouncements = () => {
	const announcementAPI = new API(urls.courseAnnouncement);
	const navigate = useNavigate();
	const toast = useToast();
	const [filterParams, setFilterParams] = useState<FilterParams>({
		order: 'desc',
		orderby: 'date',
	});
	const [deleteAnnouncementId, setDeleteAnnouncementId] = useState<number>();
	const queryClient = useQueryClient();
	const { onClose, onOpen, isOpen } = useDisclosure();
	const [active, setActive] = useState('any');
	const [bulkAction, setBulkAction] = useState<string>('');
	const [bulkIds, setBulkIds] = useState<string[]>([]);

	const [min360px] = useMediaQuery('(min-width: 360px)');

	const announcementQuery = useQuery({
		queryKey: ['announcementList', filterParams],
		queryFn: () => announcementAPI.list(filterParams),
		...{
			keepPreviousData: true,
		},
	});

	const deleteAnnouncement = useMutation({
		mutationFn: (id: number) =>
			announcementAPI.delete(id, { force: true, children: true }),
		...{
			onSuccess: (data: any) => {
				removeOperationInCache(
					queryClient,
					[
						'announcementList',
						{
							order: 'desc',
							orderby: 'date',
						},
					],
					data?.id,
				);
				queryClient.invalidateQueries({ queryKey: ['announcementList'] });
				onClose();
				toast({
					title: __(
						'Announcement deleted successfully!',
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

	const restoreAnnouncement = useMutation({
		mutationFn: (id: number) => announcementAPI.restore(id),
		...{
			onSuccess: () => {
				toast({
					title: __('Announcement Restored', 'learning-management-system'),
					isClosable: true,
					status: 'success',
				});
				queryClient.invalidateQueries({ queryKey: ['announcementList'] });
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

	const trashAnnouncement = useMutation({
		mutationFn: (id: number) => announcementAPI.delete(id),
		...{
			onSuccess: (data: any) => {
				removeOperationInCache(
					queryClient,
					[
						'announcementList',
						{
							order: 'desc',
							orderby: 'date',
						},
					],
					data?.id,
				);
				queryClient.invalidateQueries({ queryKey: ['announcementList'] });
				toast({
					title: __('Announcement Trashed', 'learning-management-system'),
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

	const onTrashPress = (courseId: number) => {
		courseId && trashAnnouncement.mutate(courseId);
	};

	const onDeletePress = (courseId: number) => {
		onOpen();
		setBulkAction('');
		setDeleteAnnouncementId(courseId);
	};

	const onDeleteConfirm = () => {
		deleteAnnouncementId
			? deleteAnnouncement.mutate(deleteAnnouncementId)
			: null;
	};

	const onRestorePress = (courseId: number) => {
		courseId ? restoreAnnouncement.mutate(courseId) : null;
	};

	const onChangeAnnouncementStatus = (status: string) => {
		setActive(status);
		setFilterParams(
			deepMerge(filterParams, {
				status: status,
			}),
		);
		setBulkIds([]);
		setBulkAction('');
	};

	const filterAnnouncementsBy = (order: 'asc' | 'desc', orderBy: string) =>
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
				announcementAPI.bulkDelete('delete', {
					ids: data,
					force: true,
					children: true,
				}),
			...{
				onSuccess() {
					queryClient.invalidateQueries({ queryKey: ['announcementList'] });
					onClose();
					setBulkIds([]);
					toast({
						title: __('Announcements Deleted', 'learning-management-system'),
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
			mutationFn: (data: any) =>
				announcementAPI.bulkDelete('delete', { ids: data }),
			...{
				onSuccess() {
					queryClient.invalidateQueries({ queryKey: ['announcementList'] });
					onClose();
					setBulkIds([]);
					toast({
						title: __('Announcements Trashed', 'learning-management-system'),
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
			mutationFn: (data: any) =>
				announcementAPI.bulkRestore('restore', { ids: data }),
			...{
				onSuccess() {
					queryClient.invalidateQueries({ queryKey: ['announcementList'] });
					onClose();
					setBulkIds([]);
					toast({
						title: __('Announcements Restored', 'learning-management-system'),
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
				<HeaderTop
					display={'flex'}
					flexWrap={'wrap'}
					justifyContent={{ base: 'center', lg: 'space-between' }}
				>
					<HeaderLeftSection>
						<HeaderLogo />
						<FilterTabs
							tabs={tabButtons}
							defaultActive="any"
							onTabChange={onChangeAnnouncementStatus}
							counts={announcementQuery.data?.meta?.announcement_count}
							isCounting={announcementQuery.isLoading}
						/>
					</HeaderLeftSection>
					<HeaderRightSection my={{ base: 2, lg: 0 }}>
						<HeaderPrimaryButton
							onClick={() => navigate(routes.courseAnnouncement.add)}
							leftIcon={
								min360px ? (
									<AddCourseIcon
										fill="currentColor"
										width="16px"
										height="16px"
									/>
								) : undefined
							}
						>
							{__('Add New Announcement', 'learning-management-system')}
						</HeaderPrimaryButton>
					</HeaderRightSection>
				</HeaderTop>
			</Header>

			<Container maxW="container.xl">
				<Box bg="white" py={{ base: 6, md: 12 }} shadow="box" mx="auto">
					<Stack direction="column" spacing="10">
						<AnnouncementFilter
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
													announcementQuery.isLoading ||
													announcementQuery.isFetching ||
													announcementQuery.isRefetching
												}
												isIndeterminate={
													announcementQuery?.data?.data?.length !==
														bulkIds?.length && bulkIds?.length > 0
												}
												isChecked={
													announcementQuery?.data?.data?.length ===
														bulkIds.length &&
													!isEmpty(announcementQuery?.data?.data as boolean)
												}
												onChange={(e) =>
													setBulkIds(
														e.target.checked
															? announcementQuery?.data?.data?.map(
																	(announcement: any) =>
																		announcement?.id?.toString(),
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
													filterContentBy={filterAnnouncementsBy}
													orderBy={'title'}
												/>
											</Stack>
										</Th>
										<Th>{__('Author', 'learning-management-system')}</Th>
										<Th>{__('Course', 'learning-management-system')}</Th>
										<Th>
											<Stack direction="row" alignItems="center">
												<Text fontSize="xs">
													{__('Date', 'learning-management-system')}
												</Text>
												<Sorting
													filterParams={filterParams}
													filterContentBy={filterAnnouncementsBy}
													orderBy={'date'}
												/>
											</Stack>
										</Th>
										<Th>{__('Actions', 'learning-management-system')}</Th>
									</Tr>
								</Thead>
								<Tbody>
									{announcementQuery.isLoading ||
									!announcementQuery.isFetched ? (
										<SkeletonAnnouncementList />
									) : announcementQuery.isSuccess &&
									  isEmpty(announcementQuery?.data?.data) ? (
										<EmptyInfo
											message={__(
												'No announcement found.',
												'learning-management-system',
											)}
										/>
									) : (
										announcementQuery?.data?.data?.map((announcement: any) => (
											<AnnouncementList
												key={announcement?.id}
												data={announcement}
												bulkIds={bulkIds}
												onDeletePress={onDeletePress}
												onRestorePress={onRestorePress}
												onTrashPress={onTrashPress}
												setBulkIds={setBulkIds}
												isLoading={
													announcementQuery.isLoading ||
													announcementQuery.isFetching ||
													announcementQuery.isRefetching
												}
											/>
										))
									)}
								</Tbody>
							</Table>
						</Stack>
					</Stack>
				</Box>
				{announcementQuery.isSuccess &&
					!isEmpty(announcementQuery?.data?.data) && (
						<MasteriyoPagination
							metaData={announcementQuery?.data?.meta}
							setFilterParams={setFilterParams}
							perPageText={__(
								'Announcements Per Page:',
								'learning-management-system',
							)}
							extraFilterParams={{
								order: filterParams?.order,
								orderby: filterParams?.orderby,
								search: filterParams?.search,
								status: filterParams?.status,
							}}
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
						? deleteAnnouncement.isPending
						: (onBulkActionApply?.[bulkAction]?.isLoading ?? false)
				}
				dialogTexts={{
					default: {
						header: __('Deleting announcement', 'learning-management-system'),
						body: __(
							'Are you sure? You can’t restore after deleting.',
							'learning-management-system',
						),
						confirm: __('Delete', 'learning-management-system'),
					},
					trash: {
						header: __(
							'Moving announcements to trash',
							'learning-management-system',
						),
						body: __(
							'Are you sure? The selected announcements will be moved to trash.',
							'learning-management-system',
						),
						confirm: __('Move to Trash', 'learning-management-system'),
					},
					delete: {
						header: __('Deleting Announcements', 'learning-management-system'),
						body: __('Are you sure? You can’t restore after deleting.'),
						confirm: __('Delete', 'learning-management-system'),
					},
					restore: {
						header: __('Restoring Announcements', 'learning-management-system'),
						body: __(
							'Are you sure? The selected announcements will be restored from the trash.',
							'learning-management-system',
						),
						confirm: __('Restore', 'learning-management-system'),
					},
				}}
			/>
		</Stack>
	);
};

export default AllAnnouncements;
