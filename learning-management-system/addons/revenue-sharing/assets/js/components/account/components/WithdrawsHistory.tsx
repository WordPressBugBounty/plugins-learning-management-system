import { Badge, Box, Stack, Text } from '@chakra-ui/react';
import { useQuery } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import { Table, Tbody, Td, Th, Thead, Tr } from 'react-super-responsive-table';
import EmptyTableData from '../../../../../../../assets/js/account/common/EmptyTableData';
import localized from '../../../../../../../assets/js/account/utils/global';
import MasteriyoPagination from '../../../../../../../assets/js/back-end/components/common/MasteriyoPagination';
import API from '../../../../../../../assets/js/back-end/utils/api';
import { isEmpty } from '../../../../../../../assets/js/back-end/utils/utils';
import { urls } from '../../../constants/urls';
import { WithdrawStatus } from '../../../enums/Enum';
import { WithdrawResponseDataMap } from '../../../types/withdraw';
import SkeletonWithdrawsList from './SkeletonWithdrawsList';

const withdrawMethods = {
	e_check: __('E-Check', 'learning-management-system'),
	bank_transfer: __('Bank Transfer', 'learning-management-system'),
	paypal: __('PayPal', 'learning-management-system'),
};

const WithdrawsHistory: React.FC = () => {
	const withdrawAPI = new API(urls.withdraws);

	const [filterParams, setFilterParams] = useState({
		instructor: localized.current_user_id,
	});

	const withdrawsQuery = useQuery<WithdrawResponseDataMap>({
		queryKey: ['withdrawsList', filterParams],
		queryFn: () => withdrawAPI.list(filterParams),
		...{
			keepPreviousData: true,
		},
	});

	return (
		<Stack spacing="8">
			<Box mt="4">
				<Table>
					<Thead>
						<Tr>
							<Th>{__('Requested On', 'learning-management-system')}</Th>
							<Th>{__('Amount', 'learning-management-system')}</Th>
							<Th>{__('Withdraw Method', 'learning-management-system')}</Th>
							<Th>{__('Status', 'learning-management-system')}</Th>
						</Tr>
					</Thead>
					<Tbody>
						{withdrawsQuery.isLoading || !withdrawsQuery.isFetched ? (
							<SkeletonWithdrawsList />
						) : withdrawsQuery.isSuccess &&
						  !isEmpty(withdrawsQuery?.data?.data) ? (
							withdrawsQuery.data?.data?.map((withdraw) => (
								<Tr key={withdraw?.id}>
									<Td>
										<Text fontSize="sm" color="gray.600">
											{withdraw?.date_created}
										</Text>
									</Td>
									<Td>
										<Text fontSize="sm" color="gray.600">
											{withdraw?.withdraw_amount}
										</Text>
									</Td>
									<Td>
										<Text fontSize="sm" color="gray.600">
											{withdrawMethods?.[
												withdraw?.withdraw_method?.method ?? ''
											] ?? ''}
										</Text>
									</Td>
									<Td>
										<Badge
											colorScheme={
												withdraw.status === WithdrawStatus.Approved
													? 'green'
													: withdraw.status === WithdrawStatus.Rejected
														? 'red'
														: withdraw.status === WithdrawStatus.Pending
															? 'yellow'
															: 'gray'
											}
										>
											{withdraw.status}
										</Badge>
									</Td>
								</Tr>
							))
						) : (
							<EmptyTableData
								span={6}
								label={__(
									'No withdraw requests found.',
									'learning-management-system',
								)}
							/>
						)}
					</Tbody>
				</Table>
			</Box>
			{withdrawsQuery.isSuccess &&
				!isEmpty(withdrawsQuery.data.meta) &&
				withdrawsQuery.data?.data.length > 0 && (
					<MasteriyoPagination
						metaData={withdrawsQuery.data.meta}
						setFilterParams={setFilterParams}
						perPageText={__(
							'Withdraws Per Page:',
							'learning-management-system',
						)}
						extraFilterParams={{
							instructor: localized.current_user_id,
						}}
					/>
				)}
		</Stack>
	);
};

export default WithdrawsHistory;
