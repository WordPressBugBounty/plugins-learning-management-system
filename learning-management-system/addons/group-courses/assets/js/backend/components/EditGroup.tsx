import {
	Box,
	Button,
	ButtonGroup,
	Container,
	Flex,
	Heading,
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
import { BiChevronLeft, BiCog, BiGroup } from 'react-icons/bi';
import { useNavigate } from 'react-router';
import { Link, NavLink, useParams } from 'react-router-dom';
import {
	Header,
	HeaderLeftSection,
	HeaderLogo,
} from '../../../../../../assets/js/back-end/components/common/Header';
import {
	NavMenu,
	NavMenuLink,
} from '../../../../../../assets/js/back-end/components/common/Nav';
import {
	navActiveStyles,
	navLinkStyles,
} from '../../../../../../assets/js/back-end/config/styles';
import routes from '../../../../../../assets/js/back-end/constants/routes';
import API from '../../../../../../assets/js/back-end/utils/api';
import {
	deepClean,
	editOperationInCache,
} from '../../../../../../assets/js/back-end/utils/utils';
import Name from '../../common/components/Name';
import { urls } from '../../constants/urls';
import { groupsBackendRoutes } from '../../routes/routes';
import { GroupSchema } from '../../types/group';
import SkeletonEdit from './Skeleton/SkeletonEdit';
import Author from './elements/Author';
import Description from './elements/Description';
import Emails from './elements/Emails';
import GroupActionBtn from './elements/GroupActionBtn';

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

const EditGroup: React.FC = () => {
	const { groupId }: any = useParams();
	const toast = useToast();
	const queryClient = useQueryClient();
	const methods = useForm();
	const navigate = useNavigate();
	const groupAPI = new API(urls.groups);
	const [isLargerThan992] = useMediaQuery('(min-width: 992px)');
	const buttonSize = useBreakpointValue(['sm', 'md']);

	const groupQuery = useQuery<GroupSchema>({
		queryKey: [`group${groupId}`, groupId],
		queryFn: () => groupAPI.get(groupId, 'edit'),
	});

	useEffect(() => {
		if (groupQuery?.isError) {
			navigate(routes.notFound);
		}
	}, [groupQuery?.isError, navigate]);

	const updateGroup = useMutation<GroupSchema>({
		mutationFn: (data) => groupAPI.update(groupId, data),
		...{
			onSuccess: (data: any) => {
				editOperationInCache(
					queryClient,
					['groupsList', { order: 'desc', orderby: 'date' }],
					data,
				);
				queryClient.invalidateQueries({ queryKey: [`group${groupId}`] });
				queryClient.invalidateQueries({ queryKey: [`groupsList`] });
				toast({
					title: __(
						'Group updated successfully.',
						'learning-management-system',
					),
					isClosable: true,
					status: 'success',
				});
			},

			onError: (error: any) => {
				const message: any = error?.message
					? error?.message
					: error?.data?.message;

				toast({
					title: __(
						'Failed to update the group.',
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
		updateGroup.mutate(deepClean(data));
	};

	const FormButton = () => (
		<ButtonGroup>
			<GroupActionBtn
				isLoading={updateGroup.isPending}
				methods={methods}
				onSubmit={onSubmit}
				groupStatus={groupQuery?.data?.status}
			/>
			<Button
				size={buttonSize}
				variant="outline"
				isDisabled={updateGroup.isPending}
				onClick={() =>
					navigate({
						pathname: groupsBackendRoutes.list,
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
							<Link to={groupsBackendRoutes.add}>
								<Button
									color="gray.600"
									variant="link"
									sx={headerTabStyles}
									_active={navActiveStyles}
									rounded="none"
									isActive
								>
									<Icon as={BiGroup} />
									{__('Edit Group', 'learning-management-system')}
								</Button>
							</Link>
						</ListItem>
					</List>
					<NavMenu color={'gray.600'}>
						<NavMenuLink
							as={NavLink}
							sx={{ ...navLinkStyles, borderBottom: '2px solid white' }}
							_hover={{ textDecoration: 'none' }}
							_activeLink={navActiveStyles}
							to={groupsBackendRoutes.settings}
							leftIcon={<BiCog />}
						>
							{__('Settings', 'learning-management-system')}
						</NavMenuLink>
					</NavMenu>
				</HeaderLeftSection>
			</Header>
			<Container maxW="container.xl">
				<Stack direction="column" spacing="6">
					<ButtonGroup>
						<Link to={groupsBackendRoutes.list}>
							<Button
								variant="link"
								_hover={{ color: 'primary.500' }}
								leftIcon={<Icon fontSize="xl" as={BiChevronLeft} />}
							>
								{__('Back to Groups', 'learning-management-system')}
							</Button>
						</Link>
					</ButtonGroup>
					{groupQuery.isSuccess ? (
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
										<Stack direction="column" spacing="8">
											<Flex align="center" justify="space-between">
												<Heading as="h1" fontSize="x-large">
													{__('Edit Group', 'learning-management-system')}
												</Heading>
											</Flex>

											<Stack direction="column" spacing="6">
												<Name defaultValue={groupQuery?.data?.title} />
												<Description
													defaultValue={groupQuery?.data?.description}
												/>

												{isLargerThan992 ? <FormButton /> : null}
											</Stack>
										</Stack>
									</Box>
									<Box w={{ lg: '400px' }} bg="white" p="10" shadow="box">
										<Stack direction="column" spacing="6">
											<Author authorData={groupQuery?.data?.author} />
											<Emails defaultValue={groupQuery?.data?.emails || []} />
											{!isLargerThan992 ? <FormButton /> : null}
										</Stack>
									</Box>
								</Stack>
							</form>
						</FormProvider>
					) : (
						<SkeletonEdit />
					)}
				</Stack>
			</Container>
		</Stack>
	);
};

export default EditGroup;
