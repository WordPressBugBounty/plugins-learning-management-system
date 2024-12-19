import {
	Box,
	Button,
	Grid,
	GridItem,
	Stack,
	Text,
	useDisclosure,
} from '@chakra-ui/react';
import { useQuery } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { BiMoney, BiMoneyWithdraw } from 'react-icons/bi';
import { BsPersonFillGear } from 'react-icons/bs';
import localized from '../../../../../../../assets/js/account/utils/global';
import urls from '../../../../../../../assets/js/back-end/constants/urls';
import { UserSchema } from '../../../../../../../assets/js/back-end/schemas';
import API from '../../../../../../../assets/js/back-end/utils/api';
import CountBox from './CountBox';
import SkeletonWithdrawDetails from './SkeletonWithdrawDetails';
import WithdrawMethodForm from './WithdrawMethodForm';
import WithdrawRequestForm from './WithdrawRequestForm';

const withdrawMethods = {
	e_check: __('E-Check', 'learning-management-system'),
	bank_transfer: __('Bank Transfer', 'learning-management-system'),
	paypal: __('PayPal', 'learning-management-system'),
};

const WithdrawDetail: React.FC = () => {
	const userAPI = new API(urls.currentUser);

	const userDataQuery = useQuery<UserSchema>({
		queryKey: ['userProfile'],
		queryFn: () => userAPI.get(),
	});
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
					gridGap="30px"
					mb="4"
				>
					<GridItem>
						<CountBox
							title={__('Total Balance', 'learning-management-system')}
							subtitle={
								userDataQuery.data?.revenue_sharing
									?.available_amount_formatted ??
								localized?.currency?.symbol + '0'
							}
							colorScheme="primary"
							icon={<BiMoney />}
						/>
					</GridItem>
					<GridItem>
						<CountBox
							title={__('Withdrawable Balance', 'learning-management-system')}
							subtitle={
								userDataQuery.data?.revenue_sharing
									?.withdrawable_amount_formatted ??
								localized?.currency?.symbol + '0'
							}
							colorScheme="green"
							icon={<BiMoneyWithdraw />}
						/>
					</GridItem>
					<GridItem>
						<CountBox
							title={__('Withdraw Method', 'learning-management-system')}
							subtitle={
								<Stack direction="row" align="center" spacing="2">
									<Text>
										{withdrawMethods?.[withdrawPreference] ??
											__('Not set', 'learning-management-system')}
									</Text>
									<Button
										fontWeight="normal"
										size="xs"
										onClick={onOpen}
										colorScheme="primary"
										variant="outline"
									>
										{__('Edit', 'learning-management-system')}
									</Button>
								</Stack>
							}
							colorScheme="cyan"
							icon={<BsPersonFillGear />}
						/>
					</GridItem>
				</Grid>

				<WithdrawRequestForm data={userDataQuery.data as UserSchema} />
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
