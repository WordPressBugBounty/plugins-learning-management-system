import {
	Box,
	Flex,
	Icon,
	Popover,
	PopoverArrow,
	PopoverBody,
	PopoverContent,
	PopoverHeader,
	PopoverTrigger,
	SimpleGrid,
	Spinner,
	Tooltip,
	useColorModeValue,
	useDisclosure,
} from '@chakra-ui/react';
import { useInfiniteQuery, useQueryClient } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React, { useEffect, useRef } from 'react';
import CustomAlert from '../../../../../../../assets/js/back-end/components/common/CustomAlert';
import {
	Announcement,
	Cancel,
} from '../../../../../../../assets/js/back-end/constants/images';
import { isEmpty } from '../../../../../../../assets/js/back-end/utils/utils';
import API from './../../../../../../../assets/js/back-end/utils/api';
import { urls } from './../../backend/constants/urls';
import { AnnouncementSchema } from './../../backend/types/announcement';
import Message from './Message';

interface Props {
	courseId: number;
}

const Announcements: React.FC<Props> = ({ courseId }) => {
	const announcementAPI = new API(urls.courseAnnouncement);
	const perPage = 5;

	const { isOpen, onOpen, onClose } = useDisclosure();

	const scrollContainerRef = useRef<HTMLDivElement | null>(null);
	const sentinelRef = useRef<HTMLDivElement | null>(null);

	const announcementQuery = useInfiniteQuery({
		queryKey: [`announcement${courseId}`, courseId],
		queryFn: ({ pageParam = 1 }) =>
			announcementAPI.list({
				course_id: courseId,
				per_page: perPage,
				page: pageParam,
				status: 'publish',
				request_from: 'learn',
			}),
		initialPageParam: 1,
		getNextPageParam: (lastPage, allPages) => {
			if (!lastPage || !lastPage.data) return undefined;
			if (lastPage.data.length < perPage) return undefined;
			return allPages.length + 1;
		},
	});

	const announcements = announcementQuery.isSuccess
		? announcementQuery.data.pages.flatMap((p: any) => p.data)
		: [];

	const unreadCount = announcementQuery.isSuccess
		? announcements.filter(
				(announcement: AnnouncementSchema) =>
					announcement[`has_user_read_${announcement?.id}`] === false,
			).length
		: 0;

	const queryClient = useQueryClient();

	const announcementIds = announcementQuery.isSuccess
		? announcements.map((a: any) => a.id.toString())
		: null;

	const isEmptyAnnouncement =
		announcementQuery.isSuccess && isEmpty(announcements);

	useEffect(() => {
		if (!isOpen) return;

		const sentinel = sentinelRef.current;
		const container = scrollContainerRef.current;
		if (!sentinel || !container) return;

		const observer = new IntersectionObserver(
			(entries) => {
				entries.forEach((entry) => {
					if (
						entry.isIntersecting &&
						announcementQuery.hasNextPage &&
						!announcementQuery.isFetchingNextPage
					) {
						announcementQuery.fetchNextPage();
					}
				});
			},
			{
				root: container,
				rootMargin: '200px',
				threshold: 0.1,
			},
		);

		observer.observe(sentinel);

		return () => {
			observer.disconnect();
		};
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [
		isOpen,
		announcementQuery.hasNextPage,
		announcementQuery.isFetchingNextPage,
	]);

	const iconColor = useColorModeValue('currentColor', 'white');

	const titleColor = useColorModeValue('oxford-night', 'white');

	return (
		<Box sx={{ position: 'relative', top: '0', right: '0' }}>
			<Popover
				placement="bottom-end"
				isOpen={isOpen}
				onOpen={onOpen}
				onClose={onClose}
			>
				<PopoverTrigger>
					<span>
						<Tooltip label={__('Announcements', 'learning-management-system')}>
							<span>
								<Icon
									_hover={{
										color: 'primary.500',
									}}
									as={Announcement}
									fill={iconColor}
									color={'saint-blue'}
									cursor={'pointer'}
									fontSize={'xl'}
								/>
							</span>
						</Tooltip>
						{unreadCount > 0 && (
							<Box
								position="absolute"
								top="-16px"
								right="-10px"
								w={'22px'}
								h={'22px'}
								borderRadius="full"
								bg="red.500"
								color="white"
								display="flex"
								alignItems="center"
								justifyContent="center"
								fontSize="11px"
								fontWeight="bold"
								lineHeight="1"
								boxShadow="sm"
							>
								{unreadCount > 9 ? '9+' : String(unreadCount)}
							</Box>
						)}
					</span>
				</PopoverTrigger>

				<PopoverContent
					borderRadius={'6px'}
					width="sm"
					shadow="0px 0px 20px 0px #00000012 !important"
					border={'none'}
				>
					<PopoverArrow shadow="0px 0px 20px 0px #00000012 !important" />
					<PopoverHeader
						fontWeight="semibold"
						fontSize="lg"
						borderBottomWidth={'1px'}
						borderBottomColor={'icy-blue-gray'}
						padding={'14px'}
						display={'flex'}
						justifyContent={'space-between'}
						alignItems={'center'}
						color={titleColor}
					>
						{__('Announcements', 'learning-management-system')}
						<Icon
							as={Cancel}
							cursor={'pointer'}
							onClick={onClose}
							fill={iconColor}
							color={'saint-blue'}
						/>
					</PopoverHeader>

					<PopoverBody padding={0}>
						<SimpleGrid
							columns={1}
							spacing={0}
							maxH="400px"
							overflowY="auto"
							overflowX={'hidden'}
							ref={scrollContainerRef}
						>
							{isEmptyAnnouncement ? (
								<Box m={6}>
									<CustomAlert status="info">
										{__(
											'No announcements found.',
											'learning-management-system',
										)}
									</CustomAlert>
								</Box>
							) : (
								announcementQuery.isSuccess &&
								announcements.map(
									(announcement: AnnouncementSchema, index: number) => (
										<Box
											key={announcement.id}
											p={0}
											borderBottomWidth={
												index !== announcements.length - 1 ? '1px' : '0'
											}
											borderBottomColor={'icy-blue-gray'}
										>
											<Message
												announcement={announcement}
												courseId={courseId}
											/>
										</Box>
									),
								)
							)}

							<Box ref={sentinelRef} height={1} />

							{announcementQuery.isFetchingNextPage && (
								<Flex justify={'center'} align={'center'} py={4}>
									<Spinner />
								</Flex>
							)}
						</SimpleGrid>
					</PopoverBody>
				</PopoverContent>
			</Popover>
		</Box>
	);
};

export default Announcements;
