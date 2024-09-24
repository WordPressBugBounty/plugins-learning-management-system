import {
	Box,
	Button,
	ButtonGroup,
	Container,
	Flex,
	Heading,
	Stack,
	useMediaQuery,
	useToast,
} from '@chakra-ui/react';
import { __, sprintf } from '@wordpress/i18n';
import React from 'react';
import { FormProvider, useForm } from 'react-hook-form';
import { useMutation, useQuery, useQueryClient } from 'react-query';
import { useNavigate, useParams } from 'react-router-dom';
import BackToBuilder from '../../../../../assets/js/back-end/components/common/BackToBuilder';
import routes from '../../../../../assets/js/back-end/constants/routes';
import urls from '../../../../../assets/js/back-end/constants/urls';
import { UsersApiResponse } from '../../../../../assets/js/back-end/types/users';
import API from '../../../../../assets/js/back-end/utils/api';
import { deepMerge } from '../../../../../assets/js/back-end/utils/utils';
import GoogleMeetUrls from '../../../constants/urls';
import GoogleMeetingHeader from '../../headers/CourseGoogleMeetingHeader';
import { GoogleMeetSchema } from '../../schemas';
import AddAttendees from '../AddAttendees';
import Description from '../Description';
import EndTime from '../EndTime';
import GoogleMeetActionButton from '../GoogleMeetActionButton';
import StartTime from '../StartTime';
import Title from '../Title';

interface Props {}

const AddNewGoogleMeeting: React.FC<Props> = () => {
	const methods = useForm<GoogleMeetSchema>();
	const { reset } = methods;
	const queryClient = useQueryClient();
	const toast = useToast();
	const { sectionId, courseId }: any = useParams();
	const courseAPI = new API(urls.courses);
	const sectionsAPI = new API(urls.sections);
	const googleMeetAPI = new API(GoogleMeetUrls.googleMeets);
	const [isLargerThan992] = useMediaQuery('(min-width: 992px)');
	const navigate = useNavigate();
	const usersAPI = new API(urls.users);

	const usersQuery = useQuery<UsersApiResponse>('users', () =>
		usersAPI.list({
			orderby: 'display_name',
			order: 'asc',
			per_page: 10,
		}),
	);

	const addGoogleMeetMutation = useMutation(
		(data: GoogleMeetSchema) => googleMeetAPI.store(data),
		{
			onSuccess: (data: GoogleMeetSchema) => {
				toast({
					title: sprintf(
						__('%s has been added.', 'learning-management-system'),
						data.summary,
					),
					status: 'success',
					isClosable: true,
				});
				queryClient.invalidateQueries(`course${courseId}`);
				navigate({
					pathname: routes.courses.edit.replace(':courseId', courseId),
					search: '?page=builder&view=' + sectionId,
				});
			},
			onError: (error: any) => {
				const message: any = error?.message
					? error?.message
					: error?.data?.message;

				toast({
					title: __(
						'Could not create google meeting.',
						'learning-management-system',
					),
					// description: message ? `${message}` : undefined,
					status: 'error',
					isClosable: true,
				});
			},
		},
	);

	const onSubmit = (data: any) => {
		const all_users = usersQuery?.data?.data?.map((user: any) => user.id);

		const newData = {
			course_id: courseId,
			section_id: sectionId,
			// time_zone: Intl.DateTimeFormat().resolvedOptions().timeZone,
			time_zone: 'UTC',
			starts_at: new Date(data.starts_at).toISOString(),
			ends_at: new Date(data.ends_at).toISOString(),
			attendees: all_users,
		};

		addGoogleMeetMutation.mutate(deepMerge(data, newData));
	};

	return (
		<Stack direction="column" spacing="8" alignItems="center">
			<GoogleMeetingHeader />

			<Container maxW="container.xl">
				<Stack direction="column" spacing="6">
					<BackToBuilder />

					<FormProvider {...methods}>
						<form
							onSubmit={methods.handleSubmit((data: GoogleMeetSchema) =>
								onSubmit(data),
							)}
						>
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
													'Add New Google Meeting',
													'learning-management-system',
												)}
											</Heading>
										</Flex>
										<Stack direction="column" spacing="6">
											<Title />
											<Description />

											<ButtonGroup>
												<GoogleMeetActionButton
													methods={methods}
													onSubmit={onSubmit}
													isLoading={addGoogleMeetMutation.isLoading}
													type="add"
												/>
												<Button
													variant="outline"
													onClick={() =>
														navigate({
															pathname: routes.courses.edit.replace(
																':courseId',
																courseId,
															),
															search: '?page=builder',
														})
													}
												>
													{__('Cancel', 'learning-management-system')}
												</Button>
											</ButtonGroup>
										</Stack>
									</Stack>
								</Box>

								<Box w={{ lg: '400px' }} bg="white" p="10" shadow="box">
									<Stack direction="column" spacing="6">
										<StartTime />

										<EndTime />

										<AddAttendees defaultValue={true} />
									</Stack>
								</Box>
							</Stack>
						</form>
					</FormProvider>
				</Stack>
			</Container>
		</Stack>
	);
};

export default AddNewGoogleMeeting;
