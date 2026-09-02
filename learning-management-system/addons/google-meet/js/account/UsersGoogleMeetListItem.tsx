import { Button, ButtonGroup, Icon, Link, Stack, Text } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useMemo } from 'react';
import { BiCalendar } from 'react-icons/bi';
import { RiCalendar2Line, RiLiveLine } from 'react-icons/ri';
import { Td, Tr } from 'react-super-responsive-table';
import StatusBadge from '../../../../assets/js/account/common/StatusBadge';
import AuthorList from '../../../../assets/js/back-end/components/common/AuthorList';
import { GoogleMeetStatus } from '../Enums/Enum';
import { GoogleMeetSchema } from '../schemas';

interface Props {
	data: GoogleMeetSchema;
}

const SessionDate: React.FC<{ value?: string | Date }> = ({ value }) => (
	<Stack direction="row" spacing="2" alignItems="center" color="gray.600">
		<Icon as={BiCalendar} />
		<Text as="span" fontSize="sm" fontWeight="normal" color="saint-blue">
			{value ? new Date(value).toLocaleString() : null}
		</Text>
	</Stack>
);

const UsersGoogleMeetListItem: React.FC<Props> = ({ data }) => {
	const status = useMemo(() => {
		const start_at = new Date(data?.starts_at ?? '');
		const end_at = new Date(data?.ends_at ?? '');
		const now = new Date();
		if (start_at >= now) {
			return GoogleMeetStatus.UpComing;
		} else if (start_at < now && end_at > now) {
			return GoogleMeetStatus.Active;
		} else if (end_at < now) {
			return GoogleMeetStatus.Expired;
		} else {
			return GoogleMeetStatus.All;
		}
	}, [data?.starts_at, data?.ends_at]);

	return (
		<Tr>
			<Td>
				<Stack direction="column" spacing="2">
					<Text fontSize="sm" fontWeight="semibold" color={'oxford-night'}>
						{data?.name}
					</Text>
					<Text color="saint-blue" fontSize="xs" fontWeight={'normal'}>
						{__('Course:', 'learning-management-system')} {data?.course_name}
					</Text>
				</Stack>
			</Td>
			<Td>
				<AuthorList authors={[data?.author]} />
			</Td>
			<Td>
				<SessionDate value={data?.starts_at} />
			</Td>
			<Td>
				<SessionDate value={data?.ends_at} />
			</Td>
			<Td>
				<StatusBadge status={status} />
			</Td>
			<Td>
				<Stack
					direction="column"
					spacing="2"
					alignItems={'start'}
					justifyContent="center"
				>
					<ButtonGroup alignItems="center">
						<Link
							_hover={{ textDecoration: 'none' }}
							href={data?.calender_url}
							isExternal
						>
							<Button
								colorScheme="button"
								size="md"
								leftIcon={<RiCalendar2Line />}
							>
								{__('Google Calendar', 'learning-management-system')}
							</Button>
						</Link>
						{(status === GoogleMeetStatus.UpComing ||
							status === GoogleMeetStatus.Active) && (
							<Link
								_hover={{ textDecoration: 'none' }}
								href={data?.meet_url}
								isExternal
							>
								<Button
									colorScheme="button"
									size="md"
									leftIcon={<RiLiveLine />}
								>
									{__('Start Meeting', 'learning-management-system')}
								</Button>
							</Link>
						)}
					</ButtonGroup>
				</Stack>
			</Td>
		</Tr>
	);
};

export default React.memo(UsersGoogleMeetListItem);
