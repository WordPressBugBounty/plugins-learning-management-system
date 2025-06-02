import { Stack } from '@chakra-ui/react';
import { useQuery } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Table, Tbody, Th, Thead, Tr } from 'react-super-responsive-table';
import EmptyTableData from '../../../../assets/js/account/common/EmptyTableData';
import PageTitle from '../../../../assets/js/account/common/PageTitle';
import MasteriyoPagination from '../../../../assets/js/back-end/components/common/MasteriyoPagination';
import API from '../../../../assets/js/back-end/utils/api';
import { isEmpty } from '../../../../assets/js/back-end/utils/utils';
import GoogleMeetUrls from '../../constants/urls';
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
		<>
			<PageTitle
				title={__('Your Google Meet Sessions', 'learning-management-system')}
			/>

			<Stack
				direction="column"
				mt={6}
				spacing="8"
				width="full"
				className="mto-zoom-sessions-wrapper"
			>
				<Table className="account_section_table">
					<Thead className="account_section_table_head">
						<Tr>
							<Th>{__('Info', 'learning-management-system')}</Th>
							<Th>{__('Author', 'learning-management-system')}</Th>
							<Th>{__('Start Date', 'learning-management-system')}</Th>
							<Th>{__('End Date', 'learning-management-system')}</Th>
							<Th>{__('Status', 'learning-management-system')}</Th>
							<Th>{__('Action', 'learning-management-system')}</Th>
						</Tr>
					</Thead>
					<Tbody className="account_section_table_body">
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
					</Tbody>
				</Table>
				{googleMeetMeetingQuery.isSuccess &&
				!isEmpty(googleMeetMeetingQuery?.data?.data) ? (
					<MasteriyoPagination
						metaData={googleMeetMeetingQuery?.data?.meta}
						setFilterParams={setFilterParams}
						perPageText={__(
							'Google Meet Sessions Per Page:',
							'learning-management-system',
						)}
					/>
				) : null}
			</Stack>
		</>
	);
};

export default UserGoogleMeetSessions;
