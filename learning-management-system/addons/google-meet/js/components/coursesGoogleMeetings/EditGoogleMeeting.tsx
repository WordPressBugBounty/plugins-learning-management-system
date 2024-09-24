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
	Flex,
	Heading,
	Icon,
	IconButton,
	Menu,
	MenuButton,
	MenuItem,
	MenuList,
	Stack,
	useToast,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import queryString from 'query-string';
import React, { useRef } from 'react';
import { FormProvider, useForm } from 'react-hook-form';
import { BiChevronLeft, BiDotsVerticalRounded, BiTrash } from 'react-icons/bi';
import { useMutation, useQuery, useQueryClient } from 'react-query';
import {
	Link as RouterLink,
	useLocation,
	useNavigate,
	useParams,
} from 'react-router-dom';
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
import urls from '../../../../../assets/js/back-end/constants/urls';
import CourseSkeleton from '../../../../../assets/js/back-end/skeleton/CourseSkeleton';
import { UsersApiResponse } from '../../../../../assets/js/back-end/types/users';
import API from '../../../../../assets/js/back-end/utils/api';
import { deepMerge } from '../../../../../assets/js/back-end/utils/utils';
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
	const { referrer } = queryString.parse(search);
	const cancelRef = useRef<any>();
	const { courseId, googleMeetId }: any = useParams();
	const googleMeetAPI = new API(GoogleMeetUrls.googleMeets);
	const navigate = useNavigate();
	const toast = useToast();
	const queryClient = useQueryClient();
	const usersAPI = new API(urls.users);

	const usersQuery = useQuery<UsersApiResponse>('users', () =>
		usersAPI.list({
			orderby: 'display_name',
			order: 'asc',
			per_page: 10,
		}),
	);

	const googleMeetQuery = useQuery([`/:${googleMeetId}`, googleMeetId], () =>
		googleMeetAPI.get(googleMeetId),
	);

	const updateGoogleMeet = useMutation(
		(data: GoogleMeetSchema) =>
			googleMeetAPI.update(googleMeetQuery.data.meeting_id, data),
		{
			onSuccess: () => {
				queryClient.invalidateQueries(`google-meetId`);
				toast({
					title: __('Google Meeting Updated', 'learning-management-system'),
					isClosable: true,
					status: 'success',
				});

				navigate({
					pathname: googleMeetRoutes.googleMeet.list,
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
	);

	const onSubmit = (data: any) => {
		const all_users = usersQuery?.data?.data?.map((user: any) => user.id);

		const newData = {
			meeting_id: googleMeetQuery.data.id,
			time_zone: 'UTC',
			starts_at: new Date(data.starts_at).toISOString(),
			ends_at: new Date(data.ends_at).toISOString(),
			attendees: all_users,
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
					<ButtonGroup>
						<RouterLink to={googleMeetRoutes.googleMeet.list}>
							<Button
								variant="link"
								_hover={{ color: 'primary.500' }}
								leftIcon={<Icon fontSize="xl" as={BiChevronLeft} />}
							>
								{__('Back to Google Meet', 'learning-management-system')}
							</Button>
						</RouterLink>
					</ButtonGroup>
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
										<Stack direction="column" spacing="8">
											<Flex align="center" justify="space-between">
												<Heading as="h1" fontSize="x-large">
													{__(
														'Edit Google Meeting',
														'learning-management-system',
													)}
												</Heading>
												<Menu placement="bottom-end">
													<MenuButton
														as={IconButton}
														icon={<BiDotsVerticalRounded />}
														variant="outline"
														rounded="sm"
														fontSize="large"
													/>
													<MenuList>
														<MenuItem icon={<BiTrash />} onClick={() => {}}>
															{__('Delete', 'learning-management-system')}
														</MenuItem>
													</MenuList>
												</Menu>
											</Flex>
											<Stack direction="column" spacing="6">
												<Title defaultValue={googleMeetQuery.data.name} />

												<Description
													defaultValue={googleMeetQuery.data.description}
													data={googleMeetQuery}
													methods={methods}
													onSubmit={onSubmit}
												/>

												<ButtonGroup>
													<GoogleMeetActionButton
														methods={methods}
														isLoading={updateGoogleMeet.isLoading}
														onSubmit={onSubmit}
														type="edit"
													/>
													<Button
														variant="outline"
														onClick={() => {
															navigate({
																pathname: googleMeetRoutes.googleMeet.list,
															});
														}}
													>
														{__('Cancel', 'learning-management-system')}
													</Button>
												</ButtonGroup>
											</Stack>
										</Stack>
									</Box>

									<Box w={{ lg: '400px' }} bg="white" p="10" shadow="box">
										<Stack direction="column" spacing="6">
											<StartTime
												defaultValue={googleMeetQuery.data.starts_at}
											/>
											<EndTime defaultValue={googleMeetQuery.data.ends_at} />
											<AddAttendees
												defaultValue={
													googleMeetQuery.data.add_all_students_as_attendee
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
