import {
	Badge,
	Box,
	Button,
	Container,
	Heading,
	HStack,
	Icon,
	Link,
	Stack,
	Text,
	useColorMode,
	useToast,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import humanizeDuration from 'humanize-duration';
import React, { useEffect, useState } from 'react';
import { BiCalendar } from 'react-icons/bi';
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
			<Container centerContent maxW="container.lg" py="16">
				<Box
					bg={
						COLORS_BASED_ON_SCREEN_COLOR_MODE[colorMode]
							?.interactiveGoogleMeetBgColor
					}
					p={['5', null, '14']}
					shadow="box"
					w="full"
				>
					<Stack direction="column" spacing="8">
						<Heading as="h5">{googleMeetQuery?.data?.name}</Heading>

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
												{getWordpressLocalTime(localStartTime, 'Y-m-d, h:i A')}
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
									<Link href={googleMeetQuery?.data?.meet_url} target="_blank">
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

					{localStartTime && !meetingStarted ? (
						<MeetingTimer
							startAt={localStartTime}
							duration={googleMeetQuery?.data.duration}
							onTimeout={() => setMeetingStarted(true)}
						/>
					) : null}

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
