import {
	Box,
	Container,
	Grid,
	GridItem,
	Heading,
	Icon,
	IconButton,
	Menu,
	MenuButton,
	MenuItem,
	MenuList,
	Spinner,
	Text,
	Tooltip,
	useColorMode,
	useColorModeValue,
	useToast,
} from '@chakra-ui/react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { __, _x, sprintf } from '@wordpress/i18n';
import React from 'react';
import { RxDotFilled } from 'react-icons/rx';
import TimeAgo from 'timeago-react';
import { ThreeDots } from '../../../../../../../assets/js/back-end/constants/images';
import API from '../../../../../../../assets/js/back-end/utils/api';
import { COLORS_BASED_ON_SCREEN_COLOR_MODE } from '../../../../../../../assets/js/interactive/constants/general';
import { urls } from '../../backend/constants/urls';
import { AnnouncementSchema } from '../../backend/types/announcement';

interface Props {
	announcement: AnnouncementSchema;
	showBorderBottom?: boolean;
	courseId: number;
}

const Message: React.FC<Props> = ({
	announcement,
	showBorderBottom = true,
	courseId,
}) => {
	const id = announcement.id;
	const created_at = announcement.date_created;
	const title = announcement.title;
	const description = announcement.description;

	const queryClient = useQueryClient();
	const announcementAPI = new API(urls.courseAnnouncement);
	const { colorMode } = useColorMode();
	const toast = useToast();

	const listItemStyles = {
		display: 'flex',
		alignItems: 'center',
		paddingY: '3',
		paddingX: '14px !important',
		lastOfType: {
			borderColor: 'transparent',
		},
	};

	const isRead = !!announcement[`has_user_read_${id}`];

	const updateAnnouncement = useMutation({
		mutationFn: (data: object) => announcementAPI.update(id, data),
		onSuccess: (res: any, variables: any) => {
			queryClient.setQueryData(
				[`announcement${courseId}`, courseId],
				(old: any) => {
					if (!old || !old.pages) return old;
					const newPages = old.pages.map((page: any) => {
						if (!page || !page.data) return page;
						const newData = page.data.map((n: any) =>
							n.id === id ? { ...n, ...variables, ...(res || {}) } : n,
						);
						return { ...page, data: newData };
					});
					return { ...old, pages: newPages };
				},
			);
		},
		onError: (err: any) => {
			toast({
				title:
					err?.message ||
					__('Something went wrong', 'learning-management-system'),
				status: 'error',
				isClosable: true,
			});
		},
	});

	const onUpdatePress = (payload: any = {}) => {
		if (isRead) return;
		updateAnnouncement.mutate({
			has_read: true,
			request_from: 'learn',
			...payload,
		});
	};

	const markTextAsReadColor = useColorModeValue('gray.600', 'gray.200');
	const iconColor = useColorModeValue('#424360', 'white');
	const titleColor = useColorModeValue('oxford-night', 'white');

	return (
		<Container
			sx={listItemStyles}
			key={id}
			onClick={() => onUpdatePress()}
			cursor={isRead ? 'auto' : 'pointer'}
			bgColor={
				isRead
					? 'transparent'
					: COLORS_BASED_ON_SCREEN_COLOR_MODE[colorMode]
							?.singleNotificationBgColor
			}
			margin={0}
			width={'100%'}
			p={0}
			opacity={updateAnnouncement.isPending ? 0.5 : 1}
		>
			<Grid w="100%" m={0} templateColumns="40px 1fr 40px" alignItems="start">
				<GridItem>
					<Tooltip
						label={
							isRead
								? __('Already Read', 'learning-management-system')
								: __('Not Read', 'learning-management-system')
						}
					>
						<span>
							{updateAnnouncement.isPending ? (
								<Spinner size="sm" />
							) : (
								<Icon
									as={RxDotFilled}
									fontSize={'xl'}
									color={
										isRead
											? COLORS_BASED_ON_SCREEN_COLOR_MODE[colorMode]
													?.timeValueAndReadColorInSingleNotification
											: COLORS_BASED_ON_SCREEN_COLOR_MODE[colorMode]
													?.singleNotificationToBeReadColor
									}
								/>
							)}
						</span>
					</Tooltip>
				</GridItem>

				<GridItem>
					<Box>
						<Heading
							as="h3"
							fontSize="sm"
							mb="1"
							fontWeight={'semibold'}
							color={titleColor}
						>
							{title}
						</Heading>

						{description && (
							<Text
								fontSize="13px"
								fontWeight={'normal'}
								lineHeight={'23px'}
								dangerouslySetInnerHTML={{ __html: description }}
							/>
						)}
					</Box>

					<Box mt={1} display={'flex'}>
						<TimeAgo
							datetime={sprintf(
								/* translators: %s: date and time in UTC */
								_x(
									'%s UTC',
									'Date time displayed in UTC',
									'learning-management-system',
								),
								created_at,
							)}
							live={false}
							style={{
								fontWeight: 600,
								fontSize: 12,
								color: isRead
									? COLORS_BASED_ON_SCREEN_COLOR_MODE[colorMode]
											?.timeValueAndReadColorInSingleNotification
									: COLORS_BASED_ON_SCREEN_COLOR_MODE[colorMode]
											?.singleNotificationToBeReadColor,
								cursor: 'default',
								direction: 'ltr',
							}}
						/>
					</Box>
				</GridItem>

				{!isRead && (
					<GridItem justifySelf="end">
						<Menu placement="bottom-end">
							<MenuButton
								as={IconButton}
								icon={<Icon as={ThreeDots} fill={iconColor} />}
								variant="link"
								rounded="sm"
								fontSize="larger"
								size="xs"
								onClick={(e) => e.stopPropagation()}
								disabled={updateAnnouncement.isPending}
							/>
							<MenuList
								py={'10px'}
								px={'14px'}
								rounded={'base'}
								border={'none'}
								shadow={'0px 0px 20px 0px #00000012'}
							>
								{!isRead && (
									<MenuItem
										fontSize={'sm'}
										fontWeight={'semibold'}
										bg={'transparent'}
										_hover={{ color: 'green.500' }}
										color={markTextAsReadColor}
										onClick={(e) => {
											e.stopPropagation();
											onUpdatePress({});
										}}
									>
										{__('Mark as read', 'learning-management-system')}
									</MenuItem>
								)}
							</MenuList>
						</Menu>
					</GridItem>
				)}
			</Grid>
		</Container>
	);
};

export default Message;
