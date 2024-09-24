import {
	Box,
	Checkbox,
	Container,
	Icon,
	Stack,
	Text,
	useDisclosure,
	useToast,
} from '@chakra-ui/react';
import { MdOutlineArrowDropDown, MdOutlineArrowDropUp } from 'react-icons/md';
import { Table, Tbody, Th, Thead, Tr } from 'react-super-responsive-table';
import { deepMerge, isEmpty } from '../../../assets/js/back-end/utils/utils';

import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import { useMutation, useQuery, useQueryClient } from 'react-query';
import { useLocation, useParams } from 'react-router-dom';
import ActionDialog from '../../../assets/js/back-end/components/common/ActionDialog';
import EmptyInfo from '../../../assets/js/back-end/components/common/EmptyInfo';
import FloatingBulkAction from '../../../assets/js/back-end/components/common/FloatingBulkAction';
import MasteriyoPagination from '../../../assets/js/back-end/components/common/MasteriyoPagination';
import API from '../../../assets/js/back-end/utils/api';
import GoogleMeetUrls from '../constants/urls';
import GoogleMeetFilter from './components/GoogleMeetFilter';
import MeetingRow from './components/MeetingRow';
import GoogleMeetHeader from './headers/GoogleMeetHeader';
import { GoogleMeetMeetingsListSkeleton } from './skeletons';

interface FilterParams {
	search?: string;
	status?: string;
	per_page?: number;
	page?: number;
	// orderby: string;
	order: 'asc' | 'desc';
}

interface GoogleMeetCount {
	all: number | undefined;
}

const GoogleMeetMeetings: React.FC = () => {
	const [bulkIds, setBulkIds] = useState<string[]>([]);
	const [bulkAction, setBulkAction] = useState<string>('');
	const [deleteGoogleMeetId, setDeleteGoogleMeetId] = useState<number>();
	const { status }: any = useParams();
	const meetingsAPI = new API(GoogleMeetUrls.googleMeets);
	const settingsAPI = new API(GoogleMeetUrls.settings);
	const { onClose, onOpen, isOpen } = useDisclosure();
	const [deleteCourseId, setDeleteCourseId] = useState<number>();
	const queryClient = useQueryClient();
	const [filterParams, setFilterParams] = useState<FilterParams>({
		order: 'desc',
	});
	const location = useLocation();

	const [googleMeetStatusCount, setGoogleMeetStatusCount] =
		useState<GoogleMeetCount>({
			all: undefined,
		});

	const cancelRef = React.useRef<any>();
	const toast = useToast();

	const googleMeetSettingQuery = useQuery(
		['googleMeetSetting'],
		() => settingsAPI.list(),
		{
			keepPreviousData: true,
		},
	);

	const googleMeetMeetingQuery = useQuery(
		['googleMeetList', filterParams, status],
		() => meetingsAPI.list({ status: status || 'all', ...filterParams }),
		{
			onSuccess: (data: any) => {
				if (data?.meta?.googleMeetCounts) {
					setGoogleMeetStatusCount({
						...data?.meta?.googleMeetCounts,
					});
					setBulkIds([]);
					setBulkAction('');
				}
			},
		},
	);

	const onDeleteConfirm = () => {
		googleMeetMeetingQuery.data?.id
			? deleteGoogleMeet.mutate(googleMeetMeetingQuery.data.id)
			: null;
	};

	const deleteGoogleMeet = useMutation(
		(id: any) => meetingsAPI.delete(id, { force: true }),
		{
			onSuccess: () => {
				queryClient.invalidateQueries('googleMeetList', status);
				onClose();
				setBulkIds([]);
			},
		},
	);

	const onBulkActionApply = {
		delete: useMutation(
			(data: any) =>
				meetingsAPI.bulkDelete('delete', {
					ids: data,
					force: true,
				}),
			{
				onSuccess() {
					queryClient.invalidateQueries('googleMeetList');
					onClose();
					setBulkIds([]);
					toast({
						title: __('Meetings Deleted', 'learning-management-system'),
						isClosable: true,
						status: 'success',
					});
				},
			},
		),
	};

	const filterMeetingsBy = (order: 'asc' | 'desc', orderBy?: string) =>
		setFilterParams(
			deepMerge({
				...filterParams,
				order: order,
			}),
		);

	return (
		<Stack direction="column" spacing="8" alignItems="center">
			<GoogleMeetHeader
				googleMeetingQuery={googleMeetMeetingQuery}
				googleMeetSetting={googleMeetSettingQuery?.data?.access_token !== ''}
			/>

			<Container maxW="container.xl">
				<Box bg="white" py={{ base: 6, md: 12 }} shadow="box" mx="auto">
					<GoogleMeetFilter
						setFilterParams={setFilterParams}
						filterParams={filterParams}
					/>
					<Stack direction="column" spacing="10">
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
													googleMeetMeetingQuery.isLoading ||
													googleMeetMeetingQuery.isFetching ||
													googleMeetMeetingQuery.isRefetching
												}
												isIndeterminate={
													googleMeetMeetingQuery?.data?.data?.length !==
														bulkIds.length && bulkIds.length > 0
												}
												isChecked={
													googleMeetMeetingQuery?.data?.data?.length ===
														bulkIds.length &&
													!isEmpty(googleMeetMeetingQuery?.data?.data)
												}
												onChange={(e) => {
													setBulkIds(
														e.target.checked
															? googleMeetMeetingQuery.data.data.map(
																	(meeting: any) => meeting.id.toString(),
																)
															: [],
													);
												}}
											/>
										</Th>
										<Th>
											<Stack direction="row" alignItems="center">
												<Text fontSize="xs">
													{__('Title', 'learning-management-system')}
												</Text>
												<Stack direction="column">
													<Icon
														as={
															filterParams?.order === 'desc'
																? MdOutlineArrowDropDown
																: MdOutlineArrowDropUp
														}
														h={6}
														w={6}
														cursor="pointer"
														transition="1s"
														_hover={{ color: 'black' }}
														onClick={() =>
															filterMeetingsBy(
																filterParams?.order === 'desc' ? 'asc' : 'desc',
																'title',
															)
														}
													/>
												</Stack>
											</Stack>
										</Th>

										<Th>{__('Status', 'learning-management-system')}</Th>

										<Th>{__('Calender Link', 'learning-management-system')}</Th>

										<Th>{__('Course', 'learning-management-system')}</Th>

										<Th>
											<Stack direction="row" alignItems="center">
												<Text fontSize="xs">
													{__('Start Time', 'learning-management-system')}
												</Text>
												<Stack direction="column">
													<Icon
														as={
															filterParams?.order === 'desc'
																? MdOutlineArrowDropDown
																: MdOutlineArrowDropUp
														}
														h={6}
														w={6}
														cursor="pointer"
														transition="1s"
														_hover={{ color: 'black' }}
														onClick={() =>
															filterMeetingsBy(
																filterParams?.order === 'desc' ? 'asc' : 'desc',
																'meta_value',
															)
														}
													/>
												</Stack>
											</Stack>
										</Th>
										<Th>{__('End Time', 'learning-management-system')}</Th>
										<Th>{__('Actions', 'learning-management-system')}</Th>
									</Tr>
								</Thead>
								<Tbody>
									{googleMeetMeetingQuery.isLoading && (
										<GoogleMeetMeetingsListSkeleton />
									)}
									{googleMeetSettingQuery?.data?.access_token === '' ? (
										<EmptyInfo
											message={__(
												'Add Google Meet Credentials.',
												'learning-management-system',
											)}
										/>
									) : googleMeetMeetingQuery.isSuccess &&
									  googleMeetMeetingQuery?.data?.meta?.googleMeetCounts
											?.all === 0 ? (
										<EmptyInfo
											message={__(
												'No meetings found.',
												'learning-management-system',
											)}
										/>
									) : (
										googleMeetMeetingQuery?.data?.data?.map((meeting: any) => (
											<MeetingRow
												key={meeting?.id}
												meeting={meeting}
												bulkIds={bulkIds}
												setBulkIds={setBulkIds}
												deleteCourseId={deleteCourseId}
												isLoading={
													googleMeetMeetingQuery.isLoading ||
													googleMeetMeetingQuery.isFetching ||
													googleMeetMeetingQuery.isRefetching
												}
											/>
										))
									)}
								</Tbody>
							</Table>
						</Stack>
					</Stack>
				</Box>
				{googleMeetMeetingQuery.isSuccess &&
					!isEmpty(googleMeetMeetingQuery?.data?.data) && (
						<MasteriyoPagination
							metaData={googleMeetMeetingQuery?.data?.meta}
							setFilterParams={setFilterParams}
							perPageText={__(
								'Meetings Per Page:',
								'learning-management-system',
							)}
							extraFilterParams={{
								order: filterParams?.order,
								search: filterParams?.search,
								status: filterParams?.status,
							}}
						/>
					)}
			</Container>
			<FloatingBulkAction
				openToast={onOpen}
				status={status}
				setBulkAction={setBulkAction}
				bulkIds={bulkIds}
				setBulkIds={setBulkIds}
				trashable={false}
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
						? deleteGoogleMeet.isLoading
						: onBulkActionApply?.[bulkAction]?.isLoading ?? false
				}
				dialogTexts={{
					default: {
						header: __(
							'Deleting Google Meetings',
							'learning-management-system',
						),
						body: __(
							'Are you sure? You can’t restore after deleting.',
							'learning-management-system',
						),
						confirm: __('Move to Trash', 'learning-management-system'),
					},
					delete: {
						header: __(
							'Deleting Google Meetings',
							'learning-management-system',
						),
						body: __('Are you sure? You can’t restore after deleting.'),
						confirm: __('Delete', 'learning-management-system'),
					},
				}}
			/>
		</Stack>
	);
};

export default GoogleMeetMeetings;
