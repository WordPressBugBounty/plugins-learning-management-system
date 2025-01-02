import {
	AlertDialog,
	AlertDialogBody,
	AlertDialogContent,
	AlertDialogFooter,
	AlertDialogHeader,
	AlertDialogOverlay,
	Box,
	Button,
	ButtonGroup,
	Container,
	Stack,
	useToast,
} from '@chakra-ui/react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React, { useRef } from 'react';
import { FormProvider, useForm } from 'react-hook-form';
import { useLocation, useNavigate, useParams } from 'react-router-dom';
import BackToBuilder from '../../../../../assets/js/back-end/components/common/BackToBuilder';
import {
	Header,
	HeaderLeftSection,
	HeaderLogo,
	HeaderTop,
} from '../../../../../assets/js/back-end/components/common/Header';
import {
	NavMenu,
	NavMenuItem,
	NavMenuLink,
} from '../../../../../assets/js/back-end/components/common/Nav';
import { navActiveStyles } from '../../../../../assets/js/back-end/config/styles';
import routes from '../../../../../assets/js/back-end/constants/routes';
import urls from '../../../../../assets/js/back-end/constants/urls';
import CourseSkeleton from '../../../../../assets/js/back-end/skeleton/CourseSkeleton';
import { UsersApiResponse } from '../../../../../assets/js/back-end/types/users';
import API from '../../../../../assets/js/back-end/utils/api';
import {
	deepMerge,
	editContentInBuilderCache,
} from '../../../../../assets/js/back-end/utils/utils';
import googleMeetRoutes from '../../../constants/routes';
import GoogleMeetUrls from '../../../constants/urls';
import { GoogleMeetSchema } from '../../schemas';
import AddAttendees from '../AddAttendees';
import Description from '../Description';
import EndTime from '../EndTime';
import GoogleMeetActionButton from '../GoogleMeetActionButton';
import StartTime from '../StartTime';
import Title from '../Title';
interface Props {}

const EditGoogleMeeting: React.FC<Props> = () => {
	const methods = useForm<any>();
	const { search } = useLocation();
	const cancelRef = useRef<any>();
	const { courseId, googleMeetId }: any = useParams();

	const googleMeetAPI = new API(GoogleMeetUrls.googleMeets);
	const navigate = useNavigate();
	const toast = useToast();
	const queryClient = useQueryClient();
	const usersAPI = new API(urls.users);

	const usersQuery = useQuery<UsersApiResponse>({
		queryKey: ['users'],
		queryFn: () =>
			usersAPI.list({
				orderby: 'display_name',
				order: 'asc',
				per_page: 10,
			}),
	});

	const googleMeetQuery = useQuery({
		queryKey: [`/:${googleMeetId}`, googleMeetId],
		queryFn: () => googleMeetAPI.get(googleMeetId),
	});

	const updateGoogleMeet = useMutation({
		mutationFn: (data: GoogleMeetSchema) =>
			googleMeetAPI.update(googleMeetQuery?.data?.meeting_id, data),
		...{
			onSuccess: (data: any) => {
				editContentInBuilderCache(
					queryClient,
					[`builder${courseId}`, courseId],
					data,
				);
				queryClient.invalidateQueries({ queryKey: [`google-meetId`] });
				toast({
					title: __('Google Meeting Updated', 'learning-management-system'),
					isClosable: true,
					status: 'success',
				});

				navigate({
					pathname: routes.courses.edit.replace(':courseId', courseId),
					search: '?page=builder',
				});
			},
			onError: (error: any) => {
				const message: any = error?.message
					? error?.message
					: error?.data?.message;

				toast({
					title: __(
						'Could not update the google meeting.',
						'learning-management-system',
					),
					description: message ? `${message}` : undefined,
					status: 'error',
					isClosable: true,
				});
			},
		},
	});

	const onSubmit = (data: any) => {
		const all_users = usersQuery?.data?.data?.map((user: any) => user?.id);

		const newData = {
			course_id: courseId,
			meeting_id: googleMeetQuery?.data?.id,
			time_zone: 'UTC',
			starts_at: new Date(data.starts_at)?.toISOString(),
			ends_at: new Date(data.ends_at)?.toISOString(),
			attendees: all_users,
			parent_id: googleMeetQuery?.data?.parent_id,
		};

		updateGoogleMeet.mutate(deepMerge(data, newData));
	};
	return (
		<Stack direction="column" spacing="8" alignItems="center">
			<Header>
				<HeaderTop>
					<HeaderLeftSection>
						<HeaderLogo />
						<NavMenu>
							<NavMenuItem>
								<NavMenuLink
									to={googleMeetRoutes.googleMeet.list}
									_activeLink={navActiveStyles}
								>
									{__('Edit Google Meeting', 'learning-management-system')}
								</NavMenuLink>
							</NavMenuItem>
						</NavMenu>
					</HeaderLeftSection>
				</HeaderTop>
			</Header>

			<Container maxW="container.xl">
				<Stack direction="column" spacing="6">
					<BackToBuilder />
					{googleMeetQuery.isSuccess ? (
						<FormProvider {...methods}>
							<form>
								<Stack
									direction={['column', 'column', 'column', 'row']}
									spacing="8"
								>
									<Box
										flex="1"
										bg="white"
										p="10"
										shadow="box"
										display="flex"
										flexDirection="column"
										justifyContent="space-between"
									>
										<Stack direction="column" spacing="6">
											<Title defaultValue={googleMeetQuery?.data?.name} />

											<Description
												defaultValue={googleMeetQuery?.data?.description}
												data={googleMeetQuery}
												methods={methods}
												onSubmit={onSubmit}
											/>

											<ButtonGroup>
												<GoogleMeetActionButton
													methods={methods}
													isLoading={updateGoogleMeet.isPending}
													onSubmit={onSubmit}
													type="edit"
												/>
												<Button
													variant="outline"
													onClick={() => {
														navigate({
															pathname: googleMeetRoutes?.googleMeet?.list,
														});
													}}
												>
													{__('Cancel', 'learning-management-system')}
												</Button>
											</ButtonGroup>
										</Stack>
									</Box>

									<Box w={{ lg: '400px' }} bg="white" p="10" shadow="box">
										<Stack direction="column" spacing="6">
											<StartTime
												defaultValue={googleMeetQuery?.data?.starts_at}
											/>
											<EndTime defaultValue={googleMeetQuery?.data?.ends_at} />
											<AddAttendees
												defaultValue={
													googleMeetQuery?.data?.add_all_students_as_attendee
												}
											/>
										</Stack>
									</Box>
								</Stack>
							</form>
							<AlertDialog
								isOpen={false}
								onClose={() => {}}
								isCentered
								leastDestructiveRef={cancelRef}
							>
								<AlertDialogOverlay>
									<AlertDialogContent>
										<AlertDialogHeader>
											{__(
												'Delete Google Meeting',
												'learning-management-system',
											)}
										</AlertDialogHeader>

										<AlertDialogBody>
											{__(
												'Are you sure? You can’t restore after deleting.',
												'learning-management-system',
											)}
										</AlertDialogBody>
										<AlertDialogFooter>
											<ButtonGroup>
												<Button variant="outline">
													{__('Cancel', 'learning-management-system')}
												</Button>
												<Button colorScheme="red">
													{__('Delete', 'learning-management-system')}
												</Button>
											</ButtonGroup>
										</AlertDialogFooter>
									</AlertDialogContent>
								</AlertDialogOverlay>
							</AlertDialog>
						</FormProvider>
					) : (
						<CourseSkeleton page={0} />
					)}
				</Stack>
			</Container>
		</Stack>
	);
};

export default EditGoogleMeeting;
