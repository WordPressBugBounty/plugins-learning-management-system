import {
	Box,
	Button,
	ButtonGroup,
	Container,
	Icon,
	List,
	ListItem,
	Stack,
	useBreakpointValue,
	useMediaQuery,
	useToast,
} from '@chakra-ui/react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React, { useEffect } from 'react';
import { FormProvider, useForm } from 'react-hook-form';
import { BiChevronLeft, BiSolidMegaphone } from 'react-icons/bi';
import { useNavigate } from 'react-router';
import { Link, useParams } from 'react-router-dom';
import {
	Header,
	HeaderLeftSection,
	HeaderLogo,
} from '../../../../../../assets/js/back-end/components/common/Header';
import { navActiveStyles } from '../../../../../../assets/js/back-end/config/styles';
import routes from '../../../../../../assets/js/back-end/constants/routes';
import API from '../../../../../../assets/js/back-end/utils/api';
import {
	deepClean,
	editOperationInCache,
} from '../../../../../../assets/js/back-end/utils/utils';
import AnnouncementActionBtn from './components/AnnouncementActionBtn';
import { AnnouncementSkeleton } from './components/AnnouncementSkeleton';
import CourseSelect from './components/CourseSelect';
import Description from './components/Description';
import Name from './components/Name';
import { urls } from './constants/urls';
import { AnnouncementSchema } from './types/announcement';

const headerTabStyles = {
	mr: '10',
	py: '6',
	d: 'flex',
	gap: 1,
	justifyContent: 'flex-start',
	alignItems: 'center',
	fontWeight: 'medium',
	fontSize: ['xs', null, 'sm'],
};

const EditAnnouncement: React.FC = () => {
	const { courseAnnouncementId }: any = useParams();
	const toast = useToast();
	const queryClient = useQueryClient();
	const methods = useForm();
	const navigate = useNavigate();
	const announcementAPI = new API(urls.courseAnnouncement);
	const [isLargerThan992] = useMediaQuery('(min-width: 992px)');
	const buttonSize = useBreakpointValue(['sm', 'md']);

	const announcementQuery = useQuery<AnnouncementSchema>({
		queryKey: [`announcement${courseAnnouncementId}`, courseAnnouncementId],
		queryFn: () => announcementAPI.get(courseAnnouncementId, 'edit'),
	});

	useEffect(() => {
		if (announcementQuery?.isError) {
			navigate(routes.notFound);
		}
	}, [announcementQuery?.isError, navigate]);

	const updateAnnouncement = useMutation<AnnouncementSchema>({
		mutationFn: (data) => announcementAPI.update(courseAnnouncementId, data),
		...{
			onSuccess: (data: any) => {
				editOperationInCache(
					queryClient,
					[
						'announcementList',
						{
							order: 'desc',
							orderby: 'date',
						},
					],
					data,
				);
				queryClient.invalidateQueries({
					queryKey: [`announcement${courseAnnouncementId}`],
				});
				queryClient.invalidateQueries({ queryKey: [`announcementList`] });
				toast({
					title: __(
						'Announcement updated successfully.',
						'learning-management-system',
					),
					isClosable: true,
					status: 'success',
				});
				navigate(routes.courseAnnouncement.list);
			},

			onError: (error: any) => {
				const message: any = error?.message
					? error?.message
					: error?.data?.message;

				toast({
					title: __(
						'Failed to update the announcement.',
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
		updateAnnouncement.mutate(deepClean(data));
	};

	const FormButton = () => (
		<ButtonGroup>
			<AnnouncementActionBtn
				isLoading={updateAnnouncement.isPending}
				methods={methods}
				onSubmit={onSubmit}
				announcementStatus={announcementQuery?.data?.status}
			/>
			<Button
				size={buttonSize}
				variant="outline"
				isDisabled={updateAnnouncement.isPending}
				onClick={() =>
					navigate({
						pathname: routes.courseAnnouncement.list,
					})
				}
			>
				{__('Cancel', 'learning-management-system')}
			</Button>
		</ButtonGroup>
	);

	return (
		<Stack direction="column" spacing="8" alignItems="center">
			<Header>
				<HeaderLeftSection>
					<HeaderLogo />
					<List
						display={['none', 'flex', 'flex']}
						flexDirection={['column', 'row', 'row', 'row']}
					>
						<ListItem mb="0">
							<Link to={routes.courseAnnouncement.add}>
								<Button
									color="gray.600"
									variant="link"
									sx={headerTabStyles}
									_active={navActiveStyles}
									rounded="none"
									isActive
								>
									<Icon as={BiSolidMegaphone} />
									{__('Edit Announcement', 'learning-management-system')}
								</Button>
							</Link>
						</ListItem>
					</List>
				</HeaderLeftSection>
			</Header>
			<Container maxW="container.xl">
				<Stack direction="column" spacing="6">
					<ButtonGroup>
						<Link to={routes.courseAnnouncement.list}>
							<Button
								variant="link"
								_hover={{ color: 'primary.500' }}
								leftIcon={<Icon fontSize="xl" as={BiChevronLeft} />}
							>
								{__('Back to Announcements', 'learning-management-system')}
							</Button>
						</Link>
					</ButtonGroup>
					{announcementQuery.isSuccess ? (
						<FormProvider {...methods}>
							<form onSubmit={methods.handleSubmit(onSubmit)}>
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
											<Name defaultValue={announcementQuery?.data?.title} />
											<Description
												defaultValue={announcementQuery?.data?.description}
											/>

											{isLargerThan992 ? <FormButton /> : null}
										</Stack>
									</Box>
									<Box w={{ lg: '400px' }} bg="white" p="10" shadow="box">
										<Stack direction="column" spacing="6">
											<CourseSelect
												defaultData={announcementQuery?.data?.course}
											/>
											{!isLargerThan992 ? <FormButton /> : null}
										</Stack>
									</Box>
								</Stack>
							</form>
						</FormProvider>
					) : (
						<AnnouncementSkeleton />
					)}
				</Stack>
			</Container>
		</Stack>
	);
};

export default EditAnnouncement;
