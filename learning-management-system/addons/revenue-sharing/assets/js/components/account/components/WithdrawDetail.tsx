import {
	Box,
	Button,
	Grid,
	GridItem,
	Icon,
	Stack,
	Text,
	useDisclosure,
} from '@chakra-ui/react';
import { UseQueryResult } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { BiMoney, BiMoneyWithdraw } from 'react-icons/bi';
import { BsPersonFillGear } from 'react-icons/bs';
import StatCard from '../../../../../../../assets/js/account/common/StatCard';
import localized from '../../../../../../../assets/js/account/utils/global';
import { UserSchema } from '../../../../../../../assets/js/back-end/schemas';
import SkeletonWithdrawDetails from './SkeletonWithdrawDetails';
import WithdrawMethodForm from './WithdrawMethodForm';

const withdrawMethods = {
	e_check: __('E-Check', 'learning-management-system'),
	bank_transfer: __('Bank Transfer', 'learning-management-system'),
	paypal: __('PayPal', 'learning-management-system'),
};

interface Props {
	userDataQuery: UseQueryResult<UserSchema, Error>;
}

const WithdrawDetail: React.FC<Props> = ({ userDataQuery }) => {
	const { isOpen, onOpen, onClose } = useDisclosure();

	const withdrawPreference =
		userDataQuery.data?.revenue_sharing?.withdraw_method_preference?.method ??
		'';

	if (userDataQuery.isLoading || !userDataQuery.isFetched) {
		return <SkeletonWithdrawDetails />;
	}

	return (
		<Box>
			<Stack>
				<Grid
					gridTemplateColumns="repeat(auto-fill, minmax(290px, 1fr))"
					gridGap={6}
					mb="4"
				>
					<GridItem>
						<StatCard
							label={__('Total Balance', 'learning-management-system')}
							value={
								userDataQuery.data?.revenue_sharing
									?.available_amount_formatted ??
								localized.currency.symbol + '0'
							}
							icon={<Icon as={BiMoney} fontSize="xl" fill="currentColor" />}
						/>
					</GridItem>
					<GridItem>
						<StatCard
							label={__('Withdrawable Balance', 'learning-management-system')}
							value={
								userDataQuery.data?.revenue_sharing
									?.withdrawable_amount_formatted ??
								localized.currency.symbol + '0'
							}
							icon={
								<Icon as={BiMoneyWithdraw} fontSize="xl" fill="currentColor" />
							}
						/>
					</GridItem>
					<GridItem>
						<StatCard
							label={__('Withdraw Method', 'learning-management-system')}
							value={
								<Stack direction="row" align="center" spacing="2">
									<Text>
										{withdrawMethods?.[withdrawPreference] ??
											__('Not set', 'learning-management-system')}
									</Text>
									<Button
										fontWeight="normal"
										size="xs"
										onClick={onOpen}
										colorScheme="button"
										variant="outline"
									>
										{__('Edit', 'learning-management-system')}
									</Button>
								</Stack>
							}
							icon={
								<Icon as={BsPersonFillGear} fontSize="xl" fill="currentColor" />
							}
						/>
					</GridItem>
				</Grid>
			</Stack>

			<WithdrawMethodForm
				data={userDataQuery.data?.revenue_sharing?.withdraw_method_preference}
				isOpen={isOpen}
				onClose={onClose}
			/>
		</Box>
	);
};

export default WithdrawDetail;
