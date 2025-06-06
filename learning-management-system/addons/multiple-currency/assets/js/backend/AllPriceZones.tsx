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
import React, { useEffect, useState } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';
import { Table, Tbody, Th, Thead, Tr } from 'react-super-responsive-table';
import ActionDialog from '../../../../../assets/js/back-end/components/common/ActionDialog';
import EmptyInfo from '../../../../../assets/js/back-end/components/common/EmptyInfo';
import FloatingBulkAction from '../../../../../assets/js/back-end/components/common/FloatingBulkAction';
import {
	Header,
	HeaderPrimaryButton,
	HeaderRightSection,
	HeaderTop,
} from '../../../../../assets/js/back-end/components/common/Header';
import MasteriyoPagination from '../../../../../assets/js/back-end/components/common/MasteriyoPagination';
import { AddCourseIcon } from '../../../../../assets/js/back-end/constants/images';
import Sorting from '../../../../../assets/js/back-end/screens/courses/components/Sorting';
import API from '../../../../../assets/js/back-end/utils/api';
import {
	deepMerge,
	isEmpty,
} from '../../../../../assets/js/back-end/utils/utils';
import { urls } from '../constants/urls';
import { multipleCurrencyBackendRoutes } from '../routes/routes';
import { PriceZoneSchema } from '../types/multiCurrency';
import LeftHeader from './LeftHeader';
import PricingZoneList from './PriceZoneList';
import SkeletonList from './Skeleton/SkeletonList';
import PriceZoneFilter from './components/PriceZoneFilter';

interface FilterParams {
	search?: string;
	status?: string;
	per_page?: number;
	page?: number;
	orderby: string;
	order: 'asc' | 'desc';
}

const AllPriceZones = () => {
	const pricingZoneAPI = new API(urls.pricingZones);
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

	const [deletePricingZoneId, setDeletePricingZoneId] = useState<number>();
	const queryClient = useQueryClient();
	const { onClose, onOpen, isOpen } = useDisclosure();
	const [active, setActive] = useState('any');
	const [bulkAction, setBulkAction] = useState<string>('');
	const [bulkIds, setBulkIds] = useState<string[]>([]);

	const [min360px] = useMediaQuery('(min-width: 360px)');

	const pricingZoneQuery = useQuery({
		queryKey: ['pricingZonesList', filterParams],
		queryFn: () => pricingZoneAPI.list(filterParams),
		...{
			keepPreviousData: true,
		},
	});

	const deletePricingZone = useMutation({
		mutationFn: (id: number) => pricingZoneAPI.delete(id, { force: true }),
		...{
			onSuccess: () => {
				queryClient.invalidateQueries({ queryKey: ['pricingZonesList'] });
				onClose();
				toast({
					title: __(
						'Pricing zone deleted successfully!',
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

	const restorePricingZone = useMutation({
		mutationFn: (id: number) => pricingZoneAPI.restore(id),
		...{
			onSuccess: () => {
				toast({
					title: __('Pricing Zone Restored', 'learning-management-system'),
					isClosable: true,
					status: 'success',
				});
				queryClient.invalidateQueries({ queryKey: ['pricingZonesList'] });
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

	const trashPricingZone = useMutation({
		mutationFn: (id: number) => pricingZoneAPI.delete(id),
		...{
			onSuccess: () => {
				queryClient.invalidateQueries({ queryKey: ['pricingZonesList'] });
				toast({
					title: __('Pricing Zone Trashed', 'learning-management-system'),
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

	const onTrashPress = (pricingZoneID: number) => {
		pricingZoneID && trashPricingZone.mutate(pricingZoneID);
	};

	const onDeletePress = (pricingZoneID: number) => {
		onOpen();
		setBulkAction('');
		setDeletePricingZoneId(pricingZoneID);
	};

	const onDeleteConfirm = () => {
		deletePricingZoneId ? deletePricingZone.mutate(deletePricingZoneId) : null;
	};

	const onRestorePress = (pricingZoneID: number) => {
		pricingZoneID ? restorePricingZone.mutate(pricingZoneID) : null;
	};

	const filterPricingZonesBy = (order: 'asc' | 'desc', orderBy: string) =>
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
				pricingZoneAPI.bulkDelete('delete', {
					ids: data,
					force: true,
				}),
			...{
				onSuccess() {
					queryClient.invalidateQueries({ queryKey: ['pricingZonesList'] });
					onClose();
					setBulkIds([]);
					toast({
						title: __('Pricing zones Deleted', 'learning-management-system'),
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
				pricingZoneAPI.bulkDelete('delete', { ids: data }),
			...{
				onSuccess() {
					queryClient.invalidateQueries({ queryKey: ['pricingZonesList'] });
					onClose();
					setBulkIds([]);
					toast({
						title: __('Pricing zones Trashed', 'learning-management-system'),
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
				pricingZoneAPI.bulkRestore('restore', { ids: data }),
			...{
				onSuccess() {
					queryClient.invalidateQueries({ queryKey: ['pricingZonesList'] });
					onClose();
					setBulkIds([]);
					toast({
						title: __('Pricing zones Restored', 'learning-management-system'),
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
							onClick={() => navigate(multipleCurrencyBackendRoutes.add)}
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
							{__('Add New Pricing Zone', 'learning-management-system')}
						</HeaderPrimaryButton>
					</HeaderRightSection>
				</HeaderTop>
			</Header>

			<Container maxW="container.xl">
				<Box bg="white" py={{ base: 6, md: 12 }} shadow="box" mx="auto">
					<Stack direction="column" spacing="10">
						<PriceZoneFilter
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
													pricingZoneQuery.isLoading ||
													pricingZoneQuery.isFetching ||
													pricingZoneQuery.isRefetching
												}
												isIndeterminate={
													pricingZoneQuery?.data?.data?.length !==
														bulkIds?.length && bulkIds?.length > 0
												}
												isChecked={
													pricingZoneQuery?.data?.data?.length ===
														bulkIds.length &&
													!isEmpty(pricingZoneQuery?.data?.data as boolean)
												}
												onChange={(e) =>
													setBulkIds(
														e.target.checked
															? pricingZoneQuery?.data?.data?.map(
																	(pricingZone: any) =>
																		pricingZone?.id?.toString(),
																)
															: [],
													)
												}
											/>
										</Th>
										<Th>
											<Stack direction="row" alignItems="center">
												<Text fontSize="xs">
													{__('Name', 'learning-management-system')}
												</Text>
												<Sorting
													filterParams={filterParams}
													filterContentBy={filterPricingZonesBy}
													orderBy={'title'}
												/>
											</Stack>
										</Th>
										<Th>{__('Currency', 'learning-management-system')}</Th>
										<Th>{__('Exchange Rate', 'learning-management-system')}</Th>
										<Th>{__('Countries', 'learning-management-system')}</Th>
										<Th>
											<Stack direction="row" alignItems="center">
												<Text fontSize="xs">
													{__('Date', 'learning-management-system')}
												</Text>
												<Sorting
													filterParams={filterParams}
													filterContentBy={filterPricingZonesBy}
													orderBy={'date'}
												/>
											</Stack>
										</Th>
										<Th>{__('Status', 'learning-management-system')}</Th>
										<Th>{__('Actions', 'learning-management-system')}</Th>
									</Tr>
								</Thead>
								<Tbody>
									{pricingZoneQuery.isLoading || !pricingZoneQuery.isFetched ? (
										<SkeletonList />
									) : pricingZoneQuery.isSuccess &&
									  isEmpty(pricingZoneQuery?.data?.data) ? (
										<EmptyInfo
											message={__(
												'No pricing zones found.',
												'learning-management-system',
											)}
										/>
									) : (
										pricingZoneQuery?.data?.data?.map(
											(pricingZone: PriceZoneSchema) => (
												<PricingZoneList
													key={pricingZone?.id}
													data={pricingZone}
													bulkIds={bulkIds}
													onDeletePress={onDeletePress}
													onRestorePress={onRestorePress}
													onTrashPress={onTrashPress}
													setBulkIds={setBulkIds}
													isLoading={
														pricingZoneQuery.isLoading ||
														pricingZoneQuery.isFetching ||
														pricingZoneQuery.isRefetching
													}
												/>
											),
										)
									)}
								</Tbody>
							</Table>
						</Stack>
					</Stack>
				</Box>
				{pricingZoneQuery.isSuccess &&
					!isEmpty(pricingZoneQuery?.data?.data) && (
						<MasteriyoPagination
							metaData={pricingZoneQuery?.data?.meta}
							setFilterParams={setFilterParams}
							perPageText={__(
								'Pricing zones Per Page:',
								'learning-management-system',
							)}
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
						? deletePricingZone.isPending
						: (onBulkActionApply?.[bulkAction]?.isLoading ?? false)
				}
				dialogTexts={{
					default: {
						header: __('Deleting pricing zone', 'learning-management-system'),
						body: __(
							'Are you sure? You can’t restore after deleting.',
							'learning-management-system',
						),
						confirm: __('Delete', 'learning-management-system'),
					},
					trash: {
						header: __(
							'Moving pricing zones to trash',
							'learning-management-system',
						),
						body: __(
							'Are you sure? The selected pricing zones will be moved to trash.',
							'learning-management-system',
						),
						confirm: __('Move to Trash', 'learning-management-system'),
					},
					delete: {
						header: __('Deleting PricingZones', 'learning-management-system'),
						body: __('Are you sure? You can’t restore after deleting.'),
						confirm: __('Delete', 'learning-management-system'),
					},
					restore: {
						header: __('Restoring PricingZones', 'learning-management-system'),
						body: __(
							'Are you sure? The selected pricing zones will be restored from the trash.',
							'learning-management-system',
						),
						confirm: __('Restore', 'learning-management-system'),
					},
				}}
			/>
		</Stack>
	);
};

export default AllPriceZones;
