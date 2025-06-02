import {
	Avatar,
	Badge,
	Button,
	ButtonGroup,
	Icon,
	Link,
	Stack,
	Text,
} from '@chakra-ui/react';
import { __, sprintf } from '@wordpress/i18n';
import React, { useMemo } from 'react';
import { BiCalendar } from 'react-icons/bi';
import { RiCalendar2Line, RiLiveLine } from 'react-icons/ri';
import { Td, Tr } from 'react-super-responsive-table';
import { GoogleMeetStatus } from '../Enums/Enum';
import { GoogleMeetSchema } from '../schemas';

interface Props {
	data: GoogleMeetSchema;
}

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
					<Text fontSize="sm" fontWeight="semibold">
						{data?.name}
					</Text>
					<Text color="gray.600" fontSize="xs">
						{__('Course:', 'learning-management-system')} {data?.course_name}
					</Text>
				</Stack>
			</Td>
			<Td>
				<Stack direction="row">
					<Avatar src={data.author?.avatar_url} size="xs" />
					<Text>
						{sprintf(
							/* translators: %s: Author display name */
							__('%s', 'learning-management-system'),
							data.author?.display_name,
						)}
					</Text>
				</Stack>
			</Td>
			<Td>
				<Stack direction="row" spacing="2" alignItems="center" color="gray.600">
					<Icon as={BiCalendar} />
					<Text as="span" fontSize="xs" fontWeight="medium" color="gray.600">
						{data?.starts_at
							? new Date(data?.starts_at).toLocaleString()
							: null}
					</Text>
				</Stack>
			</Td>
			<Td>
				<Stack direction="row" spacing="2" alignItems="center" color="gray.600">
					<Icon as={BiCalendar} />
					<Text as="span" fontSize="xs" fontWeight="medium" color="gray.600">
						{data?.ends_at ? new Date(data?.ends_at).toLocaleString() : null}
					</Text>
				</Stack>
			</Td>
			<Td>
				<Stack direction="column" spacing="2" justify="flex-start">
					<Stack
						direction="row"
						spacing="2"
						alignItems="center"
						color="gray.600"
					>
						<Badge
							textTransform="uppercase"
							colorScheme={
								status === GoogleMeetStatus.UpComing
									? 'yellow'
									: status === GoogleMeetStatus.Expired
										? 'orange'
										: 'green'
							}
						>
							{status}
						</Badge>
					</Stack>
				</Stack>
			</Td>
			<Td>
				<Stack
					direction="column"
					spacing="2"
					alignItems={'end'}
					justifyContent="center"
				>
					<ButtonGroup alignItems="center">
						<Link
							_hover={{ textDecoration: 'none' }}
							href={data?.calender_url}
							isExternal
						>
							<Button
								variant="outline"
								colorScheme="primary"
								size="xs"
								gap="2"
								fontWeight="semibold"
							>
								<RiCalendar2Line />
								{__('Google Calender', 'learning-management-system')}
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
									colorScheme="blue"
									size="xs"
									gap="2"
									fontWeight="semibold"
								>
									<RiLiveLine />
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
