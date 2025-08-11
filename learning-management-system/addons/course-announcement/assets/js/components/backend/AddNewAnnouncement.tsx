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
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { FormProvider, useForm } from 'react-hook-form';
import { BiChevronLeft } from 'react-icons/bi';
import { useNavigate } from 'react-router';
import { Link } from 'react-router-dom';
import {
	Header,
	HeaderLeftSection,
	HeaderLogo,
} from '../../../../../../assets/js/back-end/components/common/Header';
import { navActiveStyles } from '../../../../../../assets/js/back-end/config/styles';
import routes from '../../../../../../assets/js/back-end/constants/routes';
import { useWarnUnsavedChanges } from '../../../../../../assets/js/back-end/hooks/useWarnUnSavedChanges';
import API from '../../../../../../assets/js/back-end/utils/api';
import {
	addOperationInCache,
	deepClean,
} from '../../../../../../assets/js/back-end/utils/utils';
import AnnouncementActionBtn from './components/AnnouncementActionBtn';
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

const AddNewAnnouncement: React.FC = () => {
	const toast = useToast();
	const queryClient = useQueryClient();
	const methods = useForm();
	const navigate = useNavigate();
	const announcementAPI = new API(urls.courseAnnouncement);
	const [isLargerThan992] = useMediaQuery('(min-width: 992px)');
	const buttonSize = useBreakpointValue(['sm', 'md']);

	const addAnnouncement = useMutation<AnnouncementSchema>({
		mutationFn: (data) => announcementAPI.store(data),
	});

	const onSubmit = (data: any) => {
		addAnnouncement.mutate(deepClean(data), {
			onSuccess: (data) => {
				methods.reset(methods.getValues());
				addOperationInCache(
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
				toast({
					title:
						data.title + __(' has been added.', 'learning-management-system'),
					status: 'success',
					isClosable: true,
				});
				queryClient.invalidateQueries({ queryKey: [`announcementList`] });
				navigate(routes.courseAnnouncement.list);
			},

			onError: (error: any) => {
				const message: any = error?.message
					? error?.message
					: error?.data?.message;

				toast({
					title: __(
						'Failed to create announcement.',
						'learning-management-system',
					),
					description: message ? `${message}` : undefined,
					status: 'error',
					isClosable: true,
				});
			},
		});
	};

	useWarnUnsavedChanges(methods.formState.isDirty);

	const FormButton = () => (
		<ButtonGroup>
			<AnnouncementActionBtn
				isLoading={addAnnouncement.isPending}
				methods={methods}
				onSubmit={onSubmit}
			/>
			<Button
				size={buttonSize}
				variant="outline"
				isDisabled={addAnnouncement.isPending}
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
									{__('Add New Announcement', 'learning-management-system')}
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
										<Name />
										<Description />

										{isLargerThan992 ? <FormButton /> : null}
									</Stack>
								</Box>
								<Box w={{ lg: '400px' }} bg="white" p="10" shadow="box">
									<Stack direction="column" spacing="6">
										<CourseSelect />
										{!isLargerThan992 ? <FormButton /> : null}
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

export default AddNewAnnouncement;
