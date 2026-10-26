import {
	Box,
	Flex,
	Icon,
	IconButton,
	Popover,
	PopoverArrow,
	PopoverBody,
	PopoverContent,
	PopoverHeader,
	PopoverTrigger,
	SimpleGrid,
	Spinner,
	Tooltip,
	useDisclosure,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useEffect, useRef } from 'react';
import CustomAlert from '../../../../../../../assets/js/back-end/components/common/CustomAlert';
import {
	Announcement,
	Cancel,
} from '../../../../../../../assets/js/back-end/constants/images';
import { isEmpty } from '../../../../../../../assets/js/back-end/utils/utils';
import BottomSheet from '../../../../../../../assets/js/interactive/components/shell/BottomSheet';
import { AnnouncementSchema } from './../../backend/types/announcement';
import { useAnnouncementsQuery } from '../hooks/useAnnouncementsQuery';
import Message from './Message';

interface Props {
	courseId: number;
	/** `sheet` renders the list in a bottom sheet the caller opens; no megaphone. */
	presentation?: 'popover' | 'sheet';
	isOpen?: boolean;
	onClose?: () => void;
}

const Announcements: React.FC<Props> = ({
	courseId,
	presentation = 'popover',
	isOpen: isOpenProp,
	onClose: onCloseProp,
}) => {
	const isSheet = presentation === 'sheet';

	const disclosure = useDisclosure();
	const isOpen = isSheet ? Boolean(isOpenProp) : disclosure.isOpen;
	const onClose = isSheet
		? (onCloseProp ?? disclosure.onClose)
		: disclosure.onClose;

	const scrollContainerRef = useRef<HTMLDivElement | null>(null);
	const sentinelRef = useRef<HTMLDivElement | null>(null);

	const {
		query: announcementQuery,
		announcements,
		unreadCount,
	} = useAnnouncementsQuery(courseId);

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
	}, [
		isOpen,
		announcementQuery.hasNextPage,
		announcementQuery.isFetchingNextPage,
	]);

	const iconColor = 'currentColor';

	const body = (
		<SimpleGrid
			columns={1}
			spacing={0}
			maxH={isSheet ? undefined : '400px'}
			flex={isSheet ? 1 : undefined}
			minH={0}
			overflowY="auto"
			overflowX={'hidden'}
			ref={scrollContainerRef}
		>
			{isEmptyAnnouncement ? (
				<Box m={6}>
					<CustomAlert status="info">
						{__('No announcements found.', 'learning-management-system')}
					</CustomAlert>
				</Box>
			) : (
				announcementQuery.isSuccess &&
				announcements.map((announcement: AnnouncementSchema, index: number) => (
					<Box
						key={announcement.id}
						p={0}
						borderBottomWidth={index !== announcements.length - 1 ? '1px' : '0'}
						borderBottomColor="player.border"
					>
						<Message announcement={announcement} courseId={courseId} />
					</Box>
				))
			)}

			<Box ref={sentinelRef} height={1} />

			{announcementQuery.isFetchingNextPage && (
				<Flex justify={'center'} align={'center'} py={4}>
					<Spinner />
				</Flex>
			)}
		</SimpleGrid>
	);

	if (isSheet) {
		return (
			<BottomSheet
				isOpen={isOpen}
				onClose={onClose}
				title={__('Announcements', 'learning-management-system')}
				label="announcements"
			>
				{body}
			</BottomSheet>
		);
	}

	return (
		<Box position="relative">
			<Popover
				placement="bottom-end"
				isOpen={isOpen}
				onOpen={disclosure.onOpen}
				onClose={onClose}
			>
				<PopoverTrigger>
					<Box position="relative" width="fit-content">
						<Tooltip
							label={__('Announcements', 'learning-management-system')}
							isDisabled={isOpen}
						>
							<IconButton
								aria-label={__('Announcements', 'learning-management-system')}
								icon={
									<Icon as={Announcement} fill={iconColor} fontSize="18px" />
								}
								variant="unstyled"
								display="flex"
								alignItems="center"
								justifyContent="center"
								w="32px"
								h="32px"
								minW={0}
								borderRadius="8px"
								border="1px solid"
								borderColor="player.border"
								bg="player.card"
								color="player.text"
								_hover={{ bg: 'player.bg' }}
								sx={{ '&:focus-visible': { boxShadow: 'outline' } }}
							/>
						</Tooltip>
						{unreadCount > 0 && (
							<Box
								position="absolute"
								top="-3px"
								insetEnd="-3px"
								w="9px"
								h="9px"
								borderRadius="full"
								bg="player.primary"
								border="2px solid"
								borderColor="player.card"
								pointerEvents="none"
								aria-hidden="true"
							/>
						)}
					</Box>
				</PopoverTrigger>

				<PopoverContent width="360px" maxW="calc(100vw - 24px)">
					<PopoverArrow />
					<PopoverHeader
						fontWeight="600"
						fontSize="15px"
						borderBottomWidth={'1px'}
						padding={'14px'}
						display={'flex'}
						justifyContent={'space-between'}
						alignItems={'center'}
					>
						{__('Announcements', 'learning-management-system')}
						<Icon
							as={Cancel}
							cursor={'pointer'}
							onClick={onClose}
							fill={iconColor}
							color="player.text3"
						/>
					</PopoverHeader>

					<PopoverBody padding={0}>{body}</PopoverBody>
				</PopoverContent>
			</Popover>
		</Box>
	);
};

export default Announcements;
