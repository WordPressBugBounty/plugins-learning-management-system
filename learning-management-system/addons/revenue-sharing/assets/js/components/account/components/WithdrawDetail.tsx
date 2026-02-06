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
import AccountCountBox from '../../../../../../../assets/js/account/common/AccountCountBox';
import localized from '../../../../../../../assets/js/account/utils/global';
import urls from '../../../../../../../assets/js/back-end/constants/urls';
import { UserSchema } from '../../../../../../../assets/js/back-end/schemas';
import API from '../../../../../../../assets/js/back-end/utils/api';
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
	const userAPI = new API(urls.currentUser);

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
						<AccountCountBox
							title={__('Total Balance', 'learning-management-system')}
							description={
								userDataQuery.data?.revenue_sharing
									?.available_amount_formatted ??
								localized?.currency?.symbol + '0'
							}
							icon={
								<Icon
									as={BiMoney}
									color="primary.500"
									fontSize="xl"
									height="1.5em"
									width="1.5em"
								/>
							}
						/>
					</GridItem>
					<GridItem>
						<AccountCountBox
							title={__('Withdrawable Balance', 'learning-management-system')}
							description={
								userDataQuery.data?.revenue_sharing
									?.withdrawable_amount_formatted ??
								localized?.currency?.symbol + '0'
							}
							icon={
								<Icon
									as={BiMoneyWithdraw}
									color="primary.500"
									fontSize="xl"
									height="1.5em"
									width="1.5em"
								/>
							}
						/>
					</GridItem>
					<GridItem>
						<AccountCountBox
							title={__('Withdraw Method', 'learning-management-system')}
							description={
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
							icon={
								<Icon
									as={BsPersonFillGear}
									color="primary.500"
									fontSize="xl"
									height="1.5em"
									width="1.5em"
								/>
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
