import {
	Badge,
	Box,
	Button,
	Container,
	Flex,
	Heading,
	HStack,
	Icon,
	Link,
	Stack,
	Text,
	Tooltip,
	useColorMode,
	useMediaQuery,
	useToast,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import humanizeDuration from 'humanize-duration';
import React, { useEffect, useMemo, useState } from 'react';
import { BiCalendar } from 'react-icons/bi';
import {
	BsArrowsCollapseVertical,
	BsArrowsExpandVertical,
} from 'react-icons/bs';
import { RiLiveLine } from 'react-icons/ri';
import { useMutation, useQuery, useQueryClient } from 'react-query';
import { useNavigate, useParams } from 'react-router-dom';
import urls from '../../../../assets/js/back-end/constants/urls';
import { ContentQueryError } from '../../../../assets/js/back-end/schemas';
import API from '../../../../assets/js/back-end/utils/api';
import { getWordpressLocalTime } from '../../../../assets/js/back-end/utils/utils';
import ContentErrorDisplay from '../../../../assets/js/interactive/components/ContentErrorDisplay';
import ContentNav from '../../../../assets/js/interactive/components/ContentNav';
import FloatingNavigation from '../../../../assets/js/interactive/components/FloatingNavigation';
import { COLORS_BASED_ON_SCREEN_COLOR_MODE } from '../../../../assets/js/interactive/constants/general';
import { useCourseContext } from '../../../../assets/js/interactive/context/CourseContext';
import { CourseProgressItemsMap } from '../../../../assets/js/interactive/schemas';
import LessonSkeleton from '../../../../assets/js/interactive/skeleton/LessonSkeleton';
import localized from '../../../../assets/js/interactive/utils/global';
import { getContentWidths } from '../../../../assets/js/interactive/utils/helper';
import RedirectNavigation, {
	navigationProps,
} from '../../../../assets/js/interactive/utils/RedirectNavigation';
import GoogleMeetUrls from '../../constants/urls';
import { GoogleMeetStatus } from '../Enums/Enum';
import MeetingTimer from './MeetingTimer';

const InteractiveGoogleMeet = () => {
	const { googleMeetId, courseId }: any = useParams();
	const GoogleMeetAPI = new API(GoogleMeetUrls.googleMeets);
	const courseAPI = new API(urls.courses);
	const progressItemAPI = new API(urls.courseProgressItem);
	const toast = useToast();
	const queryClient = useQueryClient();
	const [meetingStarted, setMeetingStarted] = useState(false);
	const navigate = useNavigate();
	const [status, setStatus] = React.useState<string>('');
	const { colorMode } = useColorMode();
	const {
		courseProgress,
		courseData,
		setActiveIndex,
		setContentData,
		isSidebarOpen,
		setActiveContentId,
	} = useCourseContext();

	const [isLargerThan1100] = useMediaQuery('(min-width: 1100px)');
	const [isLargerThan1400] = useMediaQuery('(min-width: 1400px)');
	const isFocusModeEnabled = useMemo(() => {
		return Boolean(localized?.enableFocusMode === 'yes');
	}, []);

	const [contentWidth, setContentWidth] = useState<string>(
		getContentWidths(isLargerThan1400)[isFocusModeEnabled ? 1 : 0],
	);

	const isLargestContentWidthEnabledBasedOnScreenSize = useMemo(() => {
		const contentWidths = getContentWidths(isLargerThan1400);
		return Boolean(contentWidth === contentWidths[contentWidths?.length - 1]);
	}, [contentWidth, isLargerThan1400]);

	// To set active bg on sidebar item.
	useEffect(() => {
		setActiveContentId(googleMeetId);
	}, [googleMeetId, setActiveContentId]);

	const googleMeetQuery = useQuery<any, ContentQueryError>(
		[`google-meet${googleMeetId}`, googleMeetId],
		() => GoogleMeetAPI.get(googleMeetId),
		{
			onSuccess: (data) => {
				setActiveIndex(data?.parent_menu_order);
				setContentData(data);
			},
		},
	);

	const completeQuery = useQuery<CourseProgressItemsMap>(
		[`completeQuery${googleMeetId}`, googleMeetId],
		() =>
			progressItemAPI.list({
				item_id: googleMeetId,
				courseId: courseId,
			}),
	);

	const completeMutation = useMutation((data: CourseProgressItemsMap) =>
		progressItemAPI.store(data),
	);

	const onCompletePress = () => {
		completeMutation.mutate(
			{
				course_id: courseId,
				item_id: googleMeetQuery.data.id,
				item_type: 'google-meet',
				completed: true,
			},
			{
				onSuccess: () => {
					queryClient.invalidateQueries(`completeQuery${googleMeetId}`);
					queryClient.invalidateQueries(`courseProgress${courseId}`);

					toast({
						title: __('Mark as Completed', 'learning-management-system'),
						description: __(
							'Google Meet Meeting has been marked as completed.',
							'learning-management-system',
						),
						isClosable: true,
						status: 'success',
					});
					const navigation = googleMeetQuery?.data
						?.navigation as navigationProps;
					RedirectNavigation(navigation, courseId, navigate);
				},
			},
		);
	};

	const start_at: Date = new Date(googleMeetQuery?.data?.starts_at);
	const end_at: Date = new Date(googleMeetQuery?.data?.ends_at);

	const updateContentWidth = () => {
		const contentWidths = getContentWidths(isLargerThan1400);
		const indexOfCurrentWidth = contentWidths.indexOf(contentWidth);

		if (indexOfCurrentWidth === contentWidths?.length - 1) {
			setContentWidth(contentWidths[0]);
		} else {
			setContentWidth(contentWidths[indexOfCurrentWidth + 1]);
		}
	};

	React.useEffect(() => {
		googleMeetStatus();
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [start_at, end_at]);

	const googleMeetStatus = () => {
		if (start_at >= new Date()) {
			setStatus(GoogleMeetStatus.UpComing);
		} else if (start_at < new Date() && end_at > new Date()) {
			setStatus(GoogleMeetStatus.Active);
		} else if (end_at < new Date()) {
			setStatus(GoogleMeetStatus.Expired);
		} else {
			setStatus(GoogleMeetStatus.All);
		}
	};

	if (
		courseProgress.isSuccess &&
		googleMeetQuery.isSuccess &&
		courseData.isSuccess
	) {
		const previousPage = googleMeetQuery?.data?.navigation?.previous;
		const localStartTime = googleMeetQuery?.data?.starts_at;

		return (
			<Container
				centerContent
				maxW={
					contentWidth !== 'full' ? `container.${contentWidth}` : contentWidth
				}
				py="16"
			>
				{' '}
				<Box
					bg={
						COLORS_BASED_ON_SCREEN_COLOR_MODE[colorMode]
							?.interactiveGoogleMeetBgColor
					}
					shadow="box"
					w="full"
				>
					{localStartTime && !meetingStarted ? (
						<MeetingTimer
							startAt={localStartTime}
							duration={googleMeetQuery?.data.duration}
							onTimeout={() => setMeetingStarted(true)}
						/>
					) : null}
					<Box p={['5', null, '14']}>
						<Stack direction="column" spacing="8">
							<HStack
								alignItems={'center'}
								mt={
									contentWidth === 'full' && localStartTime && !meetingStarted
										? '5'
										: '0'
								}
							>
								<Heading as="h5" flex={1}>
									{googleMeetQuery?.data?.name}
								</Heading>
								<Tooltip
									label={
										!isLargestContentWidthEnabledBasedOnScreenSize
											? __('Expand Content Width', 'learning-management-system')
											: __(
													'Collapse Content Width',
													'learning-management-system',
												)
									}
								>
									<Flex
										display={isLargerThan1100 ? 'flex' : 'none'}
										justifyContent={'center'}
										alignItems={'center'}
										p={2}
										borderWidth={1}
										borderColor={'transparent'}
										cursor={'pointer'}
										sx={{
											background:
												colorMode === 'dark'
													? 'rgba(0, 0, 0, 0.1)'
													: 'rgba(255, 255, 255, 0.1)',
											backdropFilter: 'blur(10px)',
											borderRadius: '50%',
											transition: 'all 0.3s ease-in-out',
											'&:hover': {
												borderColor:
													colorMode === 'dark' ? 'gray.600' : 'gray.100',
												boxShadow: '0 2px 4px rgba(0, 0, 0, 0.1)',
												'.icon': {
													color:
														colorMode === 'dark' ? 'yellow.500' : 'blue.500',
												},
											},
										}}
										onClick={updateContentWidth}
									>
										<Icon
											as={
												!isLargestContentWidthEnabledBasedOnScreenSize
													? BsArrowsExpandVertical
													: BsArrowsCollapseVertical
											}
											fontSize={'larger'}
											className={'icon'}
										/>
									</Flex>
								</Tooltip>
							</HStack>{' '}
							<Stack spacing={4}>
								<Stack>
									<HStack spacing={4}>
										<HStack
											color={
												COLORS_BASED_ON_SCREEN_COLOR_MODE[colorMode]
													?.interactiveGoogleMeetTextColor
											}
											fontSize="sm"
										>
											<Text fontWeight="medium">
												{__('Time:', 'learning-management-system')}
											</Text>
											<Stack
												direction="row"
												spacing="2"
												alignItems="center"
												color={
													COLORS_BASED_ON_SCREEN_COLOR_MODE[colorMode]
														?.interactiveGoogleMeetTextColor
												}
											>
												<Icon as={BiCalendar} />
												<Text fontSize="15px">
													{getWordpressLocalTime(
														localStartTime,
														'Y-m-d, h:i A',
													)}
												</Text>
											</Stack>
										</HStack>
										{status === GoogleMeetStatus.Active ? (
											<Badge bg="green.500" color="white" fontSize="10px">
												{__('Ongoing', 'learning-management-system')}
											</Badge>
										) : null}
										{status === GoogleMeetStatus.Expired ? (
											<Badge bg="red.500" color="white" fontSize="10px">
												{__('Expired', 'learning-management-system')}
											</Badge>
										) : null}
										{status === GoogleMeetStatus.UpComing ? (
											<Badge bg="primary.500" color="white" fontSize="10px">
												{__('UpComing', 'learning-management-system')}
											</Badge>
										) : null}
									</HStack>

									{+googleMeetQuery.data?.duration ? (
										<Stack>
											<HStack
												color={
													COLORS_BASED_ON_SCREEN_COLOR_MODE[colorMode]
														?.interactiveGoogleMeetTextColor
												}
												fontSize="sm"
											>
												<Text fontWeight="medium">
													{__('Duration:', 'learning-management-system')}
												</Text>
												<Text>
													{humanizeDuration(
														(googleMeetQuery.data?.duration || 0) * 60 * 1000,
													)}
												</Text>
											</HStack>
										</Stack>
									) : null}

									<Stack>
										<HStack
											color={
												COLORS_BASED_ON_SCREEN_COLOR_MODE[colorMode]
													?.interactiveGoogleMeetTextColor
											}
											fontSize="sm"
										>
											<Text fontWeight="medium">
												{__('Meeting ID:', 'learning-management-system')}
											</Text>
											<Text>{googleMeetQuery.data?.meeting_id}</Text>
										</HStack>
									</Stack>

									{googleMeetQuery.data?.password ? (
										<Stack>
											<HStack
												color={
													COLORS_BASED_ON_SCREEN_COLOR_MODE[colorMode]
														?.interactiveGoogleMeetTextColor
												}
												fontSize="sm"
											>
												<Text fontWeight="medium">
													{__('Password:', 'learning-management-system')}
												</Text>
												<Text>{googleMeetQuery.data?.password}</Text>
											</HStack>
										</Stack>
									) : null}
								</Stack>

								{status === GoogleMeetStatus.Active ||
								status === GoogleMeetStatus.UpComing ? (
									<HStack>
										<Link
											href={googleMeetQuery?.data?.meet_url}
											target="_blank"
										>
											<Button
												colorScheme="blue"
												size="xs"
												leftIcon={<RiLiveLine />}
												fontWeight="semibold"
											>
												{__('Join Meeting', 'learning-management-system')}
											</Button>
										</Link>
									</HStack>
								) : null}
							</Stack>
							<Text
								className="masteriyo-interactive-description"
								dangerouslySetInnerHTML={{
									__html: googleMeetQuery?.data?.description,
								}}
							/>
						</Stack>
					</Box>

					<FloatingNavigation
						navigation={googleMeetQuery?.data?.navigation}
						courseId={courseId}
						isSidebarOpened={isSidebarOpen}
						completed={completeQuery?.data?.completed}
					/>
				</Box>
				<ContentNav
					navigation={googleMeetQuery?.data?.navigation}
					courseId={courseId}
					onCompletePress={onCompletePress}
					isButtonLoading={completeMutation.isLoading}
					isButtonDisabled={completeQuery?.data?.completed}
				/>
			</Container>
		);
	} else if (googleMeetQuery.isError) {
		return (
			<ContentErrorDisplay
				code={googleMeetQuery?.error?.code}
				message={googleMeetQuery?.error?.message}
				bg={COLORS_BASED_ON_SCREEN_COLOR_MODE[colorMode]?.lessonBG}
			/>
		);
	}

	return <LessonSkeleton />;
};

export default InteractiveGoogleMeet;
