import { Box, Container, Stack, Text, useDisclosure } from '@chakra-ui/react';
import { useQuery } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React, { useMemo, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { Table, Tbody, Th, Thead, Tr } from 'react-super-responsive-table';
import EmptyInfo from '../../../../../../assets/js/back-end/components/common/EmptyInfo';
import FilterTabs from '../../../../../../assets/js/back-end/components/common/FilterTabs';
import {
	Header,
	HeaderLeftSection,
	HeaderLogo,
	HeaderTop,
} from '../../../../../../assets/js/back-end/components/common/Header';
import MasteriyoPagination from '../../../../../../assets/js/back-end/components/common/MasteriyoPagination';
import Sorting from '../../../../../../assets/js/back-end/screens/courses/components/Sorting';
import API from '../../../../../../assets/js/back-end/utils/api';
import {
	deepMerge,
	isEmpty,
} from '../../../../../../assets/js/back-end/utils/utils';
import { urls } from '../../constants/urls';
import { WithdrawDataMap, WithdrawResponseDataMap } from '../../types/withdraw';
import ActionDialog from './components/ActionDialog';
import SkeletonWithdrawsList from './components/SkeletonWithdrawsList';
import WithdrawRow from './components/WithdrawRow';
import WithdrawsFilter from './components/WithdrawsFilter';

type FilterParams = {
	per_page?: number;
	page?: number;
	status: string;
	after?: string;
	before?: string;
	orderby: string;
	order: 'asc' | 'desc';
	instructor?: number;
};

type WithdrawCount = {
	any: number | undefined;
	approved: number | undefined;
	pending: number | undefined;
	rejected: number | undefined;
};

const WITHDRAWS_TABS = [
	{
		status: 'any',
		name: __('All', 'learning-management-system'),
	},
	{
		status: 'approved',
		name: __('Approved', 'learning-management-system'),
	},
	{
		status: 'pending',
		name: __('Pending', 'learning-management-system'),
	},
	{
		status: 'rejected',
		name: __('Rejected', 'learning-management-system'),
	},
];

const AllWithdraws: React.FC = () => {
	const [param] = useSearchParams();

	const [filterParams, setFilterParams] = useState<FilterParams>({
		status: param.get('status') ?? 'any',
		order: 'desc',
		orderby: 'date',
	});

	const [withdrawStatus, setWithdrawStatus] = useState<string>(
		param.get('status') ?? 'any',
	);
	const [withdrawStatusCount, setWithdrawStatusCount] = useState<WithdrawCount>(
		{
			any: undefined,
			approved: undefined,
			pending: undefined,
			rejected: undefined,
		},
	);
	const { isOpen, onClose, onOpen } = useDisclosure();
	const [action, setAction] = useState<string>('');
	const [actionId, setActionId] = useState<number>(0);

	const withdrawAPI = new API(urls.withdraws);
	const withdrawsQuery = useQuery<WithdrawResponseDataMap>({
		queryKey: ['withdrawsList', filterParams],
		queryFn: () => withdrawAPI.list(filterParams),
		...{
			keepPreviousData: true,
		},
	});

	const filterBy = (order: 'asc' | 'desc', orderBy: string) =>
		setFilterParams(
			deepMerge({
				...filterParams,
				order: order,
				orderby: orderBy,
			}),
		);

	const onChangeStatusFilter = (status: string) => {
		setWithdrawStatus(status);
		setFilterParams(
			deepMerge(filterParams, {
				status,
			}),
		);
	};

	const onUpdate = (id: number, action: string) => {
		setAction(action);
		setActionId(id);
		onOpen();
	};

	const selectedWithdraw = useMemo(() => {
		return withdrawsQuery.data?.data?.find((item) => item.id === actionId);
	}, [actionId, withdrawsQuery.data?.data]);

	return (
		<Stack direction="column" spacing={8} alignItems="center">
			<Header>
				<HeaderTop>
					<HeaderLeftSection gap={7}>
						<HeaderLogo />
						<FilterTabs
							tabs={WITHDRAWS_TABS}
							defaultActive={withdrawStatus}
							onTabChange={onChangeStatusFilter}
							counts={withdrawsQuery?.data?.meta?.withdraws_count}
							isCounting={withdrawsQuery.isLoading}
						/>
					</HeaderLeftSection>
				</HeaderTop>
			</Header>
			<Container maxW="container.xl" mt="6">
				<Box bg="white" py={{ base: 6, md: 12 }} shadow="box" mx="auto">
					<WithdrawsFilter setFilterParams={setFilterParams} />
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
											<Stack direction="row" alignItems="center">
												<Text fontSize="xs">
													{__('Requested On', 'learning-management-system')}
												</Text>
												<Sorting
													filterParams={filterParams}
													filterContentBy={filterBy}
													orderBy={'date'}
												/>
											</Stack>
										</Th>
										<Th>
											<Stack direction="row" alignItems="center">
												<Text fontSize="xs">
													{__('Requested By', 'learning-management-system')}
												</Text>
												<Sorting
													filterParams={filterParams}
													filterContentBy={filterBy}
													orderBy={'id'}
												/>
											</Stack>
										</Th>
										<Th>{__('Amount', 'learning-management-system')}</Th>
										<Th>
											{__('Withdraw Method', 'learning-management-system')}
										</Th>
										<Th>{__('Status', 'learning-management-system')}</Th>
										<Th>{__('Actions', 'learning-management-system')}</Th>
									</Tr>
								</Thead>
								<Tbody>
									{withdrawsQuery.isLoading || !withdrawsQuery.isFetched ? (
										<SkeletonWithdrawsList />
									) : withdrawsQuery.isSuccess &&
									  !isEmpty(withdrawsQuery?.data?.data) ? (
										withdrawsQuery.data.data.map((withdraw: any) => (
											<WithdrawRow
												key={withdraw?.id}
												data={withdraw}
												onUpdate={onUpdate}
											/>
										))
									) : (
										<EmptyInfo
											message={__(
												'No withdraw requests found',
												'learning-management-system',
											)}
										/>
									)}
								</Tbody>
							</Table>
						</Stack>
					</Stack>
				</Box>
				{withdrawsQuery.isSuccess && !isEmpty(withdrawsQuery?.data?.data) && (
					<MasteriyoPagination
						extraFilterParams={{
							status: filterParams?.status,
							order: filterParams?.order,
							orderby: filterParams?.orderby,
						}}
						metaData={withdrawsQuery?.data?.meta}
						setFilterParams={setFilterParams}
						perPageText={__(
							'Withdraws Per Page:',
							'learning-management-system',
						)}
					/>
				)}
			</Container>
			<ActionDialog
				isOpen={isOpen}
				onClose={onClose}
				action={action}
				data={selectedWithdraw as WithdrawDataMap}
				id={actionId}
			/>
		</Stack>
	);
};

export default AllWithdraws;
