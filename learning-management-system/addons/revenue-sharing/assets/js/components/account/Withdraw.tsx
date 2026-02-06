import { Stack } from '@chakra-ui/react';
import { useQuery } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React from 'react';
import PageSecondaryHeading from '../../../../../../assets/js/account/common/PageSecondaryHeading';
import PageTitle from '../../../../../../assets/js/account/common/PageTitle';
import urls from '../../../../../../assets/js/back-end/constants/urls';
import { UserSchema } from '../../../../../../assets/js/back-end/schemas';
import API from '../../../../../../assets/js/back-end/utils/api';
import WithdrawDetail from './components/WithdrawDetail';
import WithdrawRequestForm from './components/WithdrawRequestForm';
import WithdrawsHistory from './components/WithdrawsHistory';

const Withdraw: React.FC = () => {
	const userAPI = new API(urls.currentUser);

	const userDataQuery = useQuery<UserSchema>({
		queryKey: ['userProfile'],
		queryFn: () => userAPI.get(),
	});
	return (
		<Stack gap={10}>
			<Stack gap={'30px'}>
				<PageTitle title={__('Withdraw', 'learning-management-system')} />
				<WithdrawDetail userDataQuery={userDataQuery} />
			</Stack>

			<Stack gap={'30px'}>
				<PageSecondaryHeading
					title={__('Withdraw Requests History', 'learning-management-system')}
				>
					<WithdrawRequestForm data={userDataQuery.data as UserSchema} />
				</PageSecondaryHeading>

				<WithdrawsHistory />
			</Stack>
		</Stack>
	);
};

export default Withdraw;
