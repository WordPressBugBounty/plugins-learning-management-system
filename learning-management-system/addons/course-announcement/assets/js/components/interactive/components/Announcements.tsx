import {
	Alert,
	AlertIcon,
	Box,
	IconButton,
	Popover,
	PopoverArrow,
	PopoverBody,
	PopoverContent,
	PopoverHeader,
	PopoverTrigger,
	Progress,
	Stack,
	Tooltip,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import { BsMegaphone } from 'react-icons/bs';
import { FaPlus } from 'react-icons/fa';
import { useQuery } from 'react-query';
import { isEmpty } from '../../../../../../../assets/js/back-end/utils/utils';
import API from './../../../../../../../assets/js/back-end/utils/api';
import { urls } from './../../backend/constants/urls';
import { AnnouncementSchema } from './../../backend/types/announcement';
import Message from './Message';

interface Props {
	courseId: number;
}

const Announcements: React.FC<Props> = (props) => {
	const { courseId } = props;
	const announcementAPI = new API(urls.courseAnnouncement);
	const [readCount, setReadCount] = useState(0);

	const announcementQuery = useQuery(
		[`announcement${courseId}`, courseId],
		() =>
			announcementAPI.list({
				course_id: courseId,
				per_page: -1,
				status: 'publish',
				request_from: 'learn',
			}),
		{
			onSuccess: (announcements) => {
				const unreadAnnouncements = announcements?.data?.filter(
					(announcement: AnnouncementSchema) =>
						announcement[`has_user_read_${announcement?.id}`] === false,
				);
				setReadCount(unreadAnnouncements?.length);
			},
			refetchInterval: 300000,
		},
	);

	const isEmptyAnnouncement =
		announcementQuery.isSuccess && isEmpty(announcementQuery?.data?.data);

	// For adjusting height of less than 3 announcements.
	const isLimitedAnnouncement =
		announcementQuery.isSuccess &&
		!isEmpty(announcementQuery?.data?.data) &&
		announcementQuery?.data?.data.length < 3;

	return (
		<Popover placement="bottom-end">
			<PopoverTrigger>
				<span
					style={{
						display: 'flex',
						justifyContent: 'center',
						alignItems: 'center',
					}}
				>
					<Tooltip label={__('Announcements', 'learning-management-system')}>
						<IconButton
							aria-label={__(
								'Open Announcement Panel',
								'learning-management-system',
							)}
							icon={<BsMegaphone size={17} />}
							boxSize="5"
							color={readCount > 0 ? 'red.500' : 'gray.500'}
							cursor="pointer"
							variant={'outline'}
							border="none"
							outline="none"
							width="35px"
							height="35px"
							borderRadius="50%"
							size={'md'}
						/>
					</Tooltip>

					{readCount > 0 && (
						<Box
							position="relative"
							backgroundColor="red.500"
							w="22px"
							h="22px"
							top="-14px"
							right="12px"
							color="#ffff"
							fontSize="sm"
							fontWeight="bold"
							borderRadius="full"
							display={'flex'}
							justifyContent={'center'}
							alignItems={'center'}
							textAlign="center"
						>
							{readCount > 9 ? 9 : readCount}
							{readCount > 9 && <FaPlus size={8} />}
						</Box>
					)}
				</span>
			</PopoverTrigger>
			<PopoverContent
				width={['sm', 'md', 'lg']}
				h={
					isEmptyAnnouncement ||
					announcementQuery.isLoading ||
					isLimitedAnnouncement
						? 'fit-content'
						: 'xl'
				}
			>
				<PopoverArrow />
				<PopoverHeader fontWeight="semibold" fontSize="lg" textAlign="center">
					{__('Announcements', 'learning-management-system')}
				</PopoverHeader>
				<PopoverBody overflowY="auto">
					<Stack direction="column-reverse" spacing="4" px="4" py="4">
						{announcementQuery?.isLoading ? (
							<Progress size="sm" isIndeterminate />
						) : isEmptyAnnouncement ? (
							<Alert status="info">
								<AlertIcon />
								{__('No announcements found.', 'learning-management-system')}
							</Alert>
						) : (
							announcementQuery?.data?.data.map(
								(announcement: AnnouncementSchema) => (
									<Message key={announcement?.id} announcement={announcement} />
								),
							)
						)}
					</Stack>
				</PopoverBody>
			</PopoverContent>
		</Popover>
	);
};

export default Announcements;
