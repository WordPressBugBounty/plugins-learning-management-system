import {
	Box,
	Button,
	Checkbox,
	Container,
	Drawer,
	DrawerBody,
	DrawerCloseButton,
	DrawerContent,
	DrawerFooter,
	DrawerHeader,
	DrawerOverlay,
	IconButton,
	Stack,
	Text,
	Tooltip,
	useDisclosure,
	useMediaQuery,
	useToast,
} from '@chakra-ui/react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import { Add } from 'iconsax-react';
import React, { useEffect, useState } from 'react';
import { FormProvider, useForm } from 'react-hook-form';
import {
	BiBook,
	BiBookBookmark,
	BiBookOpen,
	BiCog,
	BiTrash,
} from 'react-icons/bi';
import { useNavigate } from 'react-router-dom';
import { Table, Tbody, Th, Thead, Tr } from 'react-super-responsive-table';
import ActionDialog from '../../../../../assets/js/back-end/components/common/ActionDialog';
import EmptyInfo from '../../../../../assets/js/back-end/components/common/EmptyInfo';
import FilterTabs from '../../../../../assets/js/back-end/components/common/FilterTabs';
import FloatingBulkAction from '../../../../../assets/js/back-end/components/common/FloatingBulkAction';
import {
	Header,
	HeaderLeftSection,
	HeaderLogo,
	HeaderPrimaryButton,
	HeaderRightSection,
	HeaderTop,
} from '../../../../../assets/js/back-end/components/common/Header';
import MasteriyoPagination from '../../../../../assets/js/back-end/components/common/MasteriyoPagination';
import FullScreenLoader from '../../../../../assets/js/back-end/components/layout/FullScreenLoader';
import { Certificate } from '../../../../../assets/js/back-end/constants/images';
import Sorting from '../../../../../assets/js/back-end/screens/courses/components/Sorting';
import API from '../../../../../assets/js/back-end/utils/api';
import { isEmpty } from '../../../../../assets/js/back-end/utils/utils';
import { CertificatesListSkeleton } from '../components/skeletons';
import {
	CertificateSettingsSchema,
	getAllCertificates,
} from '../utils/certificates';
import { certificateBackendRoutes } from '../utils/routes';
import { certificateAddonUrls } from '../utils/urls';
import CertificateRow from './CertificateRow';
import CertificateSetting from './CertificateSetting';

const statusBtnStyles = {
	mr: '10',
	py: '6',
	d: 'flex',
	gap: 1,
	justifyContent: 'flex-start',
	alignItems: 'center',
	fontWeight: 'medium',
	fontSize: ['xs', null, 'sm'],
};

interface FilterParams {
	per_page?: number;
	page?: number;
	status?: string;
	orderby: string;
	order: 'asc' | 'desc';
}

const tabButtons: FilterTabs = [
	{
		status: 'any',
		name: __('All Certificates', 'learning-management-system'),
		icon: <BiBook />,
	},
	{
		status: 'publish',
		name: __('Published', 'learning-management-system'),
		icon: <BiBookOpen />,
	},
	{
		status: 'draft',
		name: __('Draft', 'learning-management-system'),
		icon: <BiBookBookmark />,
	},
	{
		status: 'trash',
		name: __('Trash', 'learning-management-system'),
		icon: <BiTrash />,
	},
];

const AllCertificates: React.FC = () => {
	const [bulkIds, setBulkIds] = useState<string[]>([]);
	const [active, setActive] = useState('any');
	const [bulkAction, setBulkAction] = useState<string>('');
	const [deleteCertificateId, setDeleteCertificateId] = useState<number>();
	const navigate = useNavigate();
	const { onClose, onOpen, isOpen } = useDisclosure();
	const {
		onClose: onSettingClose,
		onOpen: onSettingOpen,
		isOpen: isSettingOpen,
	} = useDisclosure();
	const toast = useToast();
	const queryClient = useQueryClient();
	const [filterParams, setFilterParams] = useState<FilterParams>({
		order: 'desc',
		orderby: 'date',
		status: 'any',
	});
	const [statusCount, setStatusCount] = useState({
		any: null,
		publish: null,
		draft: null,
		trash: null,
	});
	const [min360px] = useMediaQuery('(min-width: 360px)');
	const methods = useForm<CertificateSettingsSchema>();

	const certificatesAPI = new API(certificateAddonUrls.certificates);
	const certificatesSettingAPI = new API(
		certificateAddonUrls.certificatesSetting,
	);

	const certificatesSettingQuery = useQuery({
		queryKey: ['certificatesSetting', filterParams],
		queryFn: () => certificatesSettingAPI.get(),
		...{
			enabled: isSettingOpen,
		},
	});

	const updateCertificateSettingsMutation = useMutation({
		mutationFn: (data: CertificateSettingsSchema) =>
			certificatesSettingAPI.store(data),
		...{
			onSuccess: () => {
				toast({
					title: __(
						'Certificate Settings Updated.',
						'learning-management-system',
					),
					isClosable: true,
					status: 'success',
				});
				queryClient.invalidateQueries({ queryKey: ['certificatesSetting'] });
			},
			onError: (error: any) => {
				const message: any = error?.message
					? error?.message
					: error?.data?.message;

				toast({
					title: __(
						'Could not update the certificate settings.',
						'learning-management-system',
					),
					description: message ? `${message}` : undefined,
					status: 'error',
					isClosable: true,
				});
			},
		},
	});

	const certificatesQuery = useQuery({
		queryKey: ['certificatesList', filterParams],
		queryFn: () => getAllCertificates(filterParams),
		...{
			keepPreviousData: true,
		},
	});

	const filterBy = (order: 'asc' | 'desc', orderBy: string) =>
		setFilterParams({
			...filterParams,
			order: order,
			orderby: orderBy,
		});

	const onChangeStatusFilter = (status: string) => {
		setFilterParams({ ...filterParams, status, page: 1 });
		setActive(status);
		setBulkIds([]);
		setBulkAction('');
	};

	const deleteCertificate = useMutation({
		mutationFn: (id: number) =>
			certificatesAPI.delete(id, { force: true, children: true }),
		...{
			onSuccess: () => {
				queryClient.invalidateQueries({ queryKey: ['certificatesList'] });
				onClose();
				setBulkIds([]);
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

	const onDeleteConfirm = () => {
		deleteCertificateId ? deleteCertificate.mutate(deleteCertificateId) : null;
	};

	const onBulkActionApply = {
		delete: useMutation({
			mutationFn: (data: any) =>
				certificatesAPI.bulkDelete('delete', {
					ids: data,
					force: true,
					children: true,
				}),
			...{
				onSuccess() {
					queryClient.invalidateQueries({ queryKey: ['certificatesList'] });
					onClose();
					setBulkIds([]);
					toast({
						title: __('Certificates Deleted', 'learning-management-system'),
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
				certificatesAPI.bulkDelete('delete', { ids: data }),
			...{
				onSuccess() {
					queryClient.invalidateQueries({ queryKey: ['certificatesList'] });
					onClose();
					setBulkIds([]);
					toast({
						title: __('Certificates Trashed', 'learning-management-system'),
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
				certificatesAPI.bulkRestore('restore', { ids: data }),
			...{
				onSuccess() {
					queryClient.invalidateQueries({ queryKey: ['certificatesList'] });
					onClose();
					setBulkIds([]);
					toast({
						title: __('Certificates Restored', 'learning-management-system'),
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

	const onSettingSubmit = (data: CertificateSettingsSchema) => {
		updateCertificateSettingsMutation.mutate(data);
	};

	useEffect(() => {
		if (certificatesQuery?.isSuccess) {
			setStatusCount({
				any: certificatesQuery?.data?.meta?.counts?.any,
				publish: certificatesQuery?.data?.meta?.counts?.publish,
				draft: certificatesQuery?.data?.meta?.counts?.draft,
				trash: certificatesQuery?.data?.meta?.counts?.trash,
			});
		}
	}, [certificatesQuery?.data, certificatesQuery?.isSuccess]);

	return (
		<Stack direction="column" spacing="8" alignItems="center">
			<Header>
				<HeaderTop>
					<HeaderLeftSection>
						<HeaderLogo />
						<FilterTabs
							tabs={tabButtons}
							defaultActive="any"
							onTabChange={onChangeStatusFilter}
							counts={certificatesQuery.data?.meta?.counts}
							isCounting={certificatesQuery.isLoading}
						/>
					</HeaderLeftSection>
					<HeaderRightSection>
						<HeaderPrimaryButton
							onClick={() => navigate(certificateBackendRoutes.certificate.add)}
							leftIcon={min360px ? <Add /> : undefined}
						>
							{__('Add New Certificate', 'learning-management-system')}
						</HeaderPrimaryButton>
						<Tooltip
							label={__('Certificate Settings', 'learning-management-system')}
							hasArrow
							fontSize="xs"
						>
							<IconButton
								variant="outline"
								colorScheme="primary"
								icon={<BiCog size="18" />}
								aria-label={'Certificate Settings'}
								onClick={onSettingOpen}
							/>
						</Tooltip>
					</HeaderRightSection>
				</HeaderTop>
			</Header>

			<Container maxW="container.xl">
				<Box bg="white" py={{ base: 6, md: 12 }} shadow="box" mx="auto">
					<Stack direction="column" spacing="10">
						<Stack direction="column" spacing="8">
							<Table>
								<Thead>
									<Tr>
										<Th>
											<Checkbox
												isDisabled={
													certificatesQuery.isLoading ||
													certificatesQuery.isFetching ||
													certificatesQuery.isRefetching
												}
												isIndeterminate={
													certificatesQuery?.data?.data?.length !==
														bulkIds.length && bulkIds.length > 0
												}
												isChecked={
													certificatesQuery?.data?.data?.length ===
														bulkIds.length &&
													!isEmpty(certificatesQuery?.data?.data)
												}
												onChange={(e) => {
													if (
														certificatesQuery &&
														certificatesQuery.data &&
														certificatesQuery.data.data
													) {
														setBulkIds(
															e.target.checked
																? certificatesQuery?.data?.data?.map(
																		(certificate: Certificate) =>
																			certificate.id.toString(),
																	)
																: [],
														);
													} else {
														setBulkIds([]);
													}
												}}
											/>
										</Th>
										<Th>
											<Stack direction="row" alignItems="center">
												<Text>{__('Title', 'learning-management-system')}</Text>
												<Sorting
													filterParams={filterParams}
													filterContentBy={filterBy}
													orderBy={'title'}
												/>
											</Stack>
										</Th>
										<Th>{__('Author', 'learning-management-system')}</Th>
										<Th>{__('Date', 'learning-management-system')}</Th>
										<Th>{__('Actions', 'learning-management-system')}</Th>
									</Tr>
								</Thead>
								<Tbody>
									{certificatesQuery.isLoading ||
									!certificatesQuery.isFetched ? (
										<CertificatesListSkeleton />
									) : certificatesQuery.isSuccess &&
									  isEmpty(certificatesQuery?.data?.data) ? (
										<EmptyInfo
											message={__(
												'No certificates found.',
												'learning-management-system',
											)}
										/>
									) : (
										certificatesQuery?.data?.data?.map((certificate) => (
											<CertificateRow
												key={certificate.id}
												data={certificate}
												bulkIds={bulkIds}
												setBulkIds={setBulkIds}
												isLoading={
													certificatesQuery.isLoading ||
													certificatesQuery.isFetching ||
													certificatesQuery.isRefetching
												}
											/>
										))
									)}
								</Tbody>
							</Table>
						</Stack>
					</Stack>
				</Box>
				{certificatesQuery.isSuccess &&
					!isEmpty(certificatesQuery?.data?.data) && (
						<MasteriyoPagination
							metaData={certificatesQuery?.data?.meta}
							setFilterParams={(newParams: any) =>
								setFilterParams({ ...filterParams, ...newParams })
							}
							perPageText={__(
								'Certificates Per Page:',
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
						? deleteCertificate.isPending
						: (onBulkActionApply?.[bulkAction]?.isLoading ?? false)
				}
				dialogTexts={{
					default: {
						header: __('Deleting Certificate', 'learning-management-system'),
						body: __(
							'Are you sure? You can’t restore after deleting.',
							'learning-management-system',
						),
						confirm: __('Delete', 'learning-management-system'),
					},
					trash: {
						header: __(
							'Moving Certificate to trash',
							'learning-management-system',
						),
						body: __(
							'Are you sure? The selected courses will be moved to trash.',
							'learning-management-system',
						),
						confirm: __('Move to Trash', 'learning-management-system'),
					},
					delete: {
						header: __('Deleting Certificate', 'learning-management-system'),
						body: __('Are you sure? You can’t restore after deleting.'),
						confirm: __('Delete', 'learning-management-system'),
					},
					restore: {
						header: __('Restoring Certificate', 'learning-management-system'),
						body: __(
							'Are you sure? The selected certificate will be restored from the trash.',
							'learning-management-system',
						),
						confirm: __('Restore', 'learning-management-system'),
					},
				}}
			/>
			<Drawer onClose={onSettingClose} isOpen={isSettingOpen} size="sm">
				<DrawerOverlay />
				<FormProvider {...methods}>
					<form onSubmit={methods.handleSubmit(onSettingSubmit)}>
						<DrawerContent>
							<DrawerCloseButton mt="8" />
							<DrawerHeader borderBottomWidth="1px" mt="6">
								{__('Certificate Settings', 'learning-management-system')}
							</DrawerHeader>
							<DrawerBody mt="6">
								{certificatesSettingQuery.isSuccess ? (
									<CertificateSetting
										certificateSetting={certificatesSettingQuery?.data}
									/>
								) : (
									<FullScreenLoader />
								)}
							</DrawerBody>
							<DrawerFooter borderTopWidth="1px">
								<Button variant="outline" mr={3} onClick={onClose}>
									{__('Cancel', 'learning-management-system')}
								</Button>
								<Button
									type="submit"
									colorScheme="blue"
									isLoading={updateCertificateSettingsMutation?.isPending}
								>
									{__('Save Setting', 'learning-management-system')}
								</Button>
							</DrawerFooter>
						</DrawerContent>
					</form>
				</FormProvider>
			</Drawer>
		</Stack>
	);
};

export default AllCertificates;
