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
import { __ } from '@wordpress/i18n';
import React from 'react';
import { FormProvider, useForm } from 'react-hook-form';
import { BiChevronLeft, BiCog, BiGroup } from 'react-icons/bi';
import { useMutation, useQueryClient } from 'react-query';
import { useNavigate } from 'react-router';
import { Link, NavLink } from 'react-router-dom';
import {
	Header,
	HeaderLeftSection,
	HeaderLogo,
} from '../../../../../assets/js/back-end/components/common/Header';
import {
	NavMenu,
	NavMenuLink,
} from '../../../../../assets/js/back-end/components/common/Nav';
import {
	navActiveStyles,
	navLinkStyles,
} from '../../../../../assets/js/back-end/config/styles';
import API from '../../../../../assets/js/back-end/utils/api';
import { deepClean } from '../../../../../assets/js/back-end/utils/utils';
import { urls } from '../constants/urls';
import { multipleCurrencyBackendRoutes } from '../routes/routes';
import { PriceZoneSchema } from '../types/multiCurrency';
import Countries from './components/Countries';
import Currency from './components/Currency';
import ExchangeRate from './components/ExchangeRate';
import Name from './components/Name';
import PriceZoneActionBtn from './components/PriceZoneActionBtn';

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

const AddPriceZone: React.FC = () => {
	const toast = useToast();
	const queryClient = useQueryClient();
	const methods = useForm();
	const navigate = useNavigate();
	const pricingZoneAPI = new API(urls.pricingZones);
	const [isLargerThan992] = useMediaQuery('(min-width: 992px)');
	const buttonSize = useBreakpointValue(['sm', 'md']);

	const addGroup = useMutation<PriceZoneSchema>((data) =>
		pricingZoneAPI.store(data),
	);

	const onSubmit = (data: any) => {
		addGroup.mutate(deepClean(data), {
			onSuccess: (data) => {
				toast({
					title:
						data.title + __(' has been added.', 'learning-management-system'),
					status: 'success',
					isClosable: true,
				});
				queryClient.invalidateQueries(`pricingZonesList`);
				navigate({
					pathname: multipleCurrencyBackendRoutes.edit.replace(
						':pricingZoneID',
						data.id + '',
					),
				});
			},

			onError: (error: any) => {
				const message: any = error?.message
					? error?.message
					: error?.data?.message;

				toast({
					title: __(
						'Failed to create pricing zone.',
						'learning-management-system',
					),
					description: message ? `${message}` : undefined,
					status: 'error',
					isClosable: true,
				});
			},
		});
	};

	const FormButton = () => (
		<ButtonGroup>
			<PriceZoneActionBtn
				isLoading={addGroup.isLoading}
				methods={methods}
				onSubmit={onSubmit}
			/>
			<Button
				size={buttonSize}
				variant="outline"
				isDisabled={addGroup.isLoading}
				onClick={() =>
					navigate({
						pathname: multipleCurrencyBackendRoutes.list,
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
							<Link to={multipleCurrencyBackendRoutes.add}>
								<Button
									color="gray.600"
									variant="link"
									sx={headerTabStyles}
									_active={navActiveStyles}
									rounded="none"
									isActive
								>
									<Icon as={BiGroup} />
									{__('Add New Pricing Zone', 'learning-management-system')}
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
							to={multipleCurrencyBackendRoutes.settings}
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
						<Link to={multipleCurrencyBackendRoutes.list}>
							<Button
								variant="link"
								_hover={{ color: 'primary.500' }}
								leftIcon={<Icon fontSize="xl" as={BiChevronLeft} />}
							>
								{__('Back to Pricing Zones', 'learning-management-system')}
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
									<Stack direction="column" spacing="8">
										<Flex align="center" justify="space-between">
											<Heading as="h1" fontSize="x-large">
												{__(
													'Add New Pricing Zone',
													'learning-management-system',
												)}
											</Heading>
										</Flex>

										<Stack direction="column" spacing="6">
											<Name />
											<ExchangeRate />

											{isLargerThan992 ? <FormButton /> : null}
										</Stack>
									</Stack>
								</Box>
								<Box w={{ lg: '400px' }} bg="white" p="10" shadow="box">
									<Stack direction="column" spacing="6">
										<Countries />
										<Currency />
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

export default AddPriceZone;
