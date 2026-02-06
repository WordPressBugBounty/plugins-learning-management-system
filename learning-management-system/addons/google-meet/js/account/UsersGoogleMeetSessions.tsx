import GoogleMeetUrls from '@addons/google-meet/constants/urls';
import { Stack } from '@chakra-ui/react';
import { useQuery } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Table, Tbody, Td, Th, Thead, Tr } from 'react-super-responsive-table';
import EmptyTableData from '../../../../assets/js/account/common/EmptyTableData';
import PageTitle from '../../../../assets/js/account/common/PageTitle';
import MasteriyoPagination from '../../../../assets/js/back-end/components/common/MasteriyoPagination';
import API from '../../../../assets/js/back-end/utils/api';
import { isEmpty } from '../../../../assets/js/back-end/utils/utils';
import { GoogleMeetSchema } from '../schemas';
import UsersGoogleMeetListItem from './UsersGoogleMeetListItem';
import { SkeletonAccountGoogleMeetSessions } from './skeleton';

interface FilterParams {
	per_page?: number;
	page?: number;
	user_id?: number;
}

const UserGoogleMeetSessions = () => {
	const [filterParams, setFilterParams] = React.useState<FilterParams>({});
	const meetingsAPI = new API(GoogleMeetUrls.googleMeets + '/mine');
	const googleMeetMeetingQuery = useQuery({
		queryKey: ['googleMeetList', filterParams],
		queryFn: () => meetingsAPI.list({ ...filterParams }),
	});

	return (
		<Stack gap={'30px'} className="mto-zoom-sessions-wrapper">
			<PageTitle
				title={__('Google Meet Sessions', 'learning-management-system')}
			/>

			<Table className="account_page_table">
				<Thead className="account_page_table_head">
					<Tr>
						<Th>{__('Info', 'learning-management-system')}</Th>
						<Th>{__('Author', 'learning-management-system')}</Th>
						<Th>{__('Start Date', 'learning-management-system')}</Th>
						<Th>{__('End Date', 'learning-management-system')}</Th>
						<Th>{__('Status', 'learning-management-system')}</Th>
						<Th>{__('Action', 'learning-management-system')}</Th>
					</Tr>
				</Thead>
				<Tbody className="account_page_table_body">
					{googleMeetMeetingQuery.isPending && (
						<SkeletonAccountGoogleMeetSessions />
					)}
					{googleMeetMeetingQuery.isSuccess &&
					isEmpty(googleMeetMeetingQuery?.data?.data) ? (
						<EmptyTableData
							span={6}
							label={__(
								'No Google Meet Sessions found.',
								'learning-management-system',
							)}
						/>
					) : (
						googleMeetMeetingQuery?.data?.data?.map(
							(googleMeet: GoogleMeetSchema) => (
								<UsersGoogleMeetListItem
									key={googleMeet.id}
									data={googleMeet}
								/>
							),
						)
					)}
					{googleMeetMeetingQuery.isSuccess &&
					!isEmpty(googleMeetMeetingQuery?.data?.data) ? (
						<Tr className={'account_page_table_footer'}>
							<Td colSpan={6}>
								<MasteriyoPagination
									stackProps={{ mt: 0, pb: 0 }}
									metaData={googleMeetMeetingQuery?.data?.meta}
									setFilterParams={setFilterParams}
									perPageText={__(
										'Google Meet Sessions Per Page:',
										'learning-management-system',
									)}
								/>
							</Td>
						</Tr>
					) : null}
				</Tbody>
			</Table>
		</Stack>
	);
};

export default UserGoogleMeetSessions;
