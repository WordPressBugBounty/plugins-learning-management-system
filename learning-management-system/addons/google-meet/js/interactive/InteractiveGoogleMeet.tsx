import {
	Container,
	Flex,
	Heading,
	Text,
	useColorMode,
	useToast,
	VStack,
} from '@chakra-ui/react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import humanizeDuration from 'humanize-duration';
import React, { useEffect, useState } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { CustomIcon } from '../../../../assets/js/back-end/components/common/CustomIcon';
import { GoogleMeet } from '../../../../assets/js/back-end/constants/images';
import urls from '../../../../assets/js/back-end/constants/urls';
import API from '../../../../assets/js/back-end/utils/api';
import { getSiteDate } from '../../../../assets/js/back-end/utils/utils';
import ContentErrorDisplay from '../../../../assets/js/interactive/components/ContentErrorDisplay';
import ContentNav from '../../../../assets/js/interactive/components/ContentNav';
import { COLORS_BASED_ON_SCREEN_COLOR_MODE } from '../../../../assets/js/interactive/constants/general';
import { useCourseContext } from '@interactive/context/CourseContext';
import { ContentQueryError } from '../../../../assets/js/back-end/schemas';
import { CourseProgressItemsMap } from '../../../../assets/js/interactive/schemas';
import LessonSkeleton from '../../../../assets/js/interactive/skeleton/LessonSkeleton';
import localized from '../../../../assets/js/interactive/utils/global';
import { previewFlag } from '../../../../assets/js/interactive/utils/helper';
import RedirectNavigation, {
	navigationProps,
} from '../../../../assets/js/interactive/utils/RedirectNavigation';
import { initializeTracking } from '../../../../assets/js/interactive/utils/RouterMonitor';
import GoogleMeetUrls from '../../constants/urls';
import { GoogleMeetStatus } from '../Enums/Enum';
import {
	JoinMeetingButton,
	MeetingCountdownPanel,
	MeetingDetails,
	MeetingStatusBadge,
	MeetingTime,
	MeetingVendor,
} from '@interactive/components/Meeting';
import {
	containerFor,
	useContentWidth,
} from '../../../../assets/js/interactive/hooks/useContentWidth';
import ContentWidthButton from '../../../../assets/js/interactive/components/card/ContentWidthButton';
import { toastCompletion } from '@interactive/components/rewards/completionToast';

const InteractiveGoogleMeet = () => {
	const { googleMeetId, courseId }: any = useParams();
	const GoogleMeetAPI = new API(GoogleMeetUrls.googleMeets);
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
		setContentData,
		isSidebarOpen,
		setActiveContentId,
	} = useCourseContext();

	const [contentWidth, cycleContentWidth] = useContentWidth();

	// To set active bg on sidebar item.
	useEffect(() => {
		setActiveContentId(googleMeetId);
	}, [googleMeetId, setActiveContentId]);

	const googleMeetQuery = useQuery<any, ContentQueryError>({
		queryKey: [`google-meet${googleMeetId}`, googleMeetId],
		queryFn: () => GoogleMeetAPI.get(googleMeetId),
	});

	const completeQuery = useQuery<CourseProgressItemsMap>({
		queryKey: [`completeQuery${googleMeetId}`, googleMeetId],
		queryFn: () =>
			progressItemAPI.list({
				item_id: googleMeetId,
				courseId: courseId,
			}),
		// Guests 401 here; skip the fetch to avoid a retry storm. `enabled` needs a
		// real boolean (isUserLoggedIn is a string at runtime).
		...{
			enabled: Boolean(localized.isUserLoggedIn),
			retry: false,
		},
	});

	const completeMutation = useMutation({
		mutationFn: (data: CourseProgressItemsMap) =>
			progressItemAPI.store({ ...data, ...previewFlag() }),
	});

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
					queryClient.invalidateQueries({
						queryKey: [`completeQuery${googleMeetId}`],
					});
					queryClient.invalidateQueries({
						queryKey: [`courseProgress${courseId}`],
					});

					toastCompletion(
						toast,
						__('Google Meet completed.', 'learning-management-system'),
					);
					const navigation = googleMeetQuery?.data
						?.navigation as navigationProps;
					RedirectNavigation(navigation, courseId, navigate);
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
			},
		);
	};

	useEffect(() => {
		if (googleMeetQuery?.isSuccess) {
			setContentData(googleMeetQuery?.data);
		}
	}, [googleMeetQuery?.data, googleMeetQuery?.isSuccess, setContentData]);

	useEffect(() => {
		const cleanupTracking = initializeTracking(
			courseId,
			'google-meet',
			googleMeetId,
		);

		return () => {
			cleanupTracking && cleanupTracking();
		};
	}, [courseId, googleMeetId]);

	React.useEffect(() => {
		const start_at: Date = new Date(googleMeetQuery?.data?.starts_at);
		const end_at: Date = new Date(googleMeetQuery?.data?.ends_at);

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
		googleMeetStatus();
	}, [googleMeetQuery?.data?.starts_at, googleMeetQuery?.data?.ends_at]);

	if (
		courseProgress.isSuccess &&
		googleMeetQuery.isSuccess &&
		courseData.isSuccess
	) {
		const previousPage = googleMeetQuery?.data?.navigation?.previous;
		const localStartTime = googleMeetQuery?.data?.starts_at;
		const isLive =
			status === GoogleMeetStatus.Active ||
			(meetingStarted && status === GoogleMeetStatus.UpComing);
		const isUpcoming = !isLive && status === GoogleMeetStatus.UpComing;
		const statusBadge = isLive ? (
			<MeetingStatusBadge
				tone="live"
				label={__('Ongoing', 'learning-management-system')}
			/>
		) : isUpcoming ? (
			<MeetingStatusBadge
				label={__('Upcoming', 'learning-management-system')}
			/>
		) : status === GoogleMeetStatus.Expired ? (
			<MeetingStatusBadge label={__('Expired', 'learning-management-system')} />
		) : null;

		return (
			<VStack minH="100%" justify={'space-between'}>
				<Container
					centerContent
					position="relative"
					maxW={containerFor(contentWidth)}
					px={{ base: 0, md: '16px' }}
					py={{ base: 0, md: '40px' }}
				>
					<ContentWidthButton
						width={contentWidth}
						onCycle={cycleContentWidth}
					/>
					<Flex
						direction="column"
						gap="22px"
						bg="player.card"
						w="full"
						border="1px solid"
						borderColor="player.border"
						borderWidth={{ base: '0 0 1px', md: '1px' }}
						borderRadius={{ base: 0, md: '12px' }}
						px={{ base: '16px', md: '40px' }}
						py={{ base: '18px', md: '32px' }}
					>
						<Flex direction="column" gap="14px">
							<MeetingVendor
								icon={<CustomIcon icon={GoogleMeet} boxSize="26px" />}
								label={__('Google Meet', 'learning-management-system')}
							/>
							<Heading
								as="h1"
								color="player.text"
								fontSize={{ base: '22px', md: '28px' }}
								fontWeight="700"
								lineHeight="1.2"
							>
								{googleMeetQuery?.data?.name}
							</Heading>
						</Flex>
						{isUpcoming && localStartTime ? (
							<MeetingCountdownPanel
								startAt={localStartTime}
								onTimeout={() => setMeetingStarted(true)}
								warnUnderSeconds={30}
							/>
						) : null}
						<MeetingDetails
							rows={[
								{
									label: __('Time', 'learning-management-system'),
									value: (
										<MeetingTime
											time={getSiteDate(localStartTime, true)}
											badge={statusBadge}
										/>
									),
								},
								...(+googleMeetQuery.data?.duration
									? [
											{
												label: __('Duration', 'learning-management-system'),
												value: humanizeDuration(
													(googleMeetQuery.data?.duration || 0) * 60 * 1000,
												),
											},
										]
									: []),
								{
									label: __('Meeting ID', 'learning-management-system'),
									value: googleMeetQuery.data?.meeting_id,
									mono: true,
								},
								...(googleMeetQuery.data?.password
									? [
											{
												label: __('Password', 'learning-management-system'),
												value: googleMeetQuery.data.password,
												mono: true,
											},
										]
									: []),
							]}
						/>
						{isLive || isUpcoming ? (
							<JoinMeetingButton
								href={googleMeetQuery?.data?.meet_url}
								bg="player.successButton"
								hoverBg="player.successButtonHover"
							/>
						) : null}
						<Text
							className="masteriyo-interactive-description"
							dangerouslySetInnerHTML={{
								__html: googleMeetQuery?.data?.description,
							}}
						/>
					</Flex>
				</Container>
				<ContentNav
					navigation={googleMeetQuery?.data?.navigation}
					courseId={courseId}
					onCompletePress={onCompletePress}
					isButtonLoading={completeMutation.isPending}
					isButtonDisabled={completeQuery?.data?.completed}
				/>
			</VStack>
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
