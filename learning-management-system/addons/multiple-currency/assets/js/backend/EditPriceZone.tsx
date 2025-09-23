import {
	Box,
	Button,
	ButtonGroup,
	Container,
	Flex,
	Heading,
	Stack,
	Text,
	useBreakpointValue,
	useMediaQuery,
	useToast,
} from '@chakra-ui/react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React, { useEffect } from 'react';
import { FormProvider, useForm } from 'react-hook-form';
import { BiCog } from 'react-icons/bi';
import { useNavigate } from 'react-router';
import { Link, NavLink, useParams } from 'react-router-dom';
import BackButton from '../../../../../assets/js/back-end/components/common/BackButton';
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
import { useWarnUnsavedChanges } from '../../../../../assets/js/back-end/hooks/useWarnUnSavedChanges';
import API from '../../../../../assets/js/back-end/utils/api';
import { deepClean } from '../../../../../assets/js/back-end/utils/utils';
import { urls } from '../constants/urls';
import { multipleCurrencyBackendRoutes } from '../routes/routes';
import { PriceZoneSchema } from '../types/multiCurrency';
import SkeletonEdit from './Skeleton/SkeletonEdit';
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

const EditPriceZone: React.FC = () => {
	const { pricingZoneID }: any = useParams();
	const toast = useToast();
	const queryClient = useQueryClient();
	const methods = useForm();
	const navigate = useNavigate();
	const pricingZoneAPI = new API(urls.pricingZones);
	const [isLargerThan992] = useMediaQuery('(min-width: 992px)');
	const buttonSize = useBreakpointValue(['sm', 'md']);

	const pricingZoneQuery = useQuery<PriceZoneSchema>({
		queryKey: [`pricingZone${pricingZoneID}`, pricingZoneID],
		queryFn: () => pricingZoneAPI.get(pricingZoneID, 'edit'),
	});

	useEffect(() => {
		if (pricingZoneQuery?.isError) {
			navigate(routes.notFound);
		}
	}, [pricingZoneQuery?.isError, navigate]);

	const updatePriceZone = useMutation<PriceZoneSchema>({
		mutationFn: (data) => pricingZoneAPI.update(pricingZoneID, data),
		...{
			onSuccess: () => {
				queryClient.invalidateQueries({
					queryKey: [`pricingZone${pricingZoneID}`],
				});
				queryClient.invalidateQueries({ queryKey: [`pricingZonesList`] });
				toast({
					title: __(
						'PriceZone updated successfully.',
						'learning-management-system',
					),
					isClosable: true,
					status: 'success',
				});
				navigate(multipleCurrencyBackendRoutes.list);
			},
			onError: (error: any) => {
				const message: any = error?.message
					? error?.message
					: error?.data?.message;

				toast({
					title: __(
						'Failed to update the pricing zone.',
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
		updatePriceZone.mutate(deepClean(data));
	};

	useWarnUnsavedChanges(methods.formState.isDirty);

	useEffect(() => {
		if (pricingZoneQuery?.isSuccess && pricingZoneQuery?.data) {
			methods.reset(methods.getValues());
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [pricingZoneQuery?.data]);

	const FormButton = () => (
		<ButtonGroup>
			<PriceZoneActionBtn
				isLoading={updatePriceZone.isPending}
				methods={methods}
				onSubmit={onSubmit}
				pricingZoneStatus={pricingZoneQuery?.data?.status}
			/>
			<Button
				size={buttonSize}
				variant="outline"
				isDisabled={updatePriceZone.isPending}
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
				<HeaderTop
					display={'flex'}
					flexWrap={'wrap'}
					justifyContent={{ base: 'center', md: 'space-between' }}
				>
					<HeaderLeftSection gap={7}>
						<HeaderLogo />

						<NavMenu>
							<NavMenuItem key={'new-price'} display="flex">
								<NavMenuLink
									as={NavLink}
									_activeLink={navActiveStyles}
									fontSize="sm"
									fontWeight="semibold"
								>
									<Text
										fontSize="sm"
										fontWeight="semibold"
										_groupHover={{ color: 'primary.500' }}
									>
										{__('Edit Pricing Zone', 'learning-management-system')}
									</Text>
								</NavMenuLink>
							</NavMenuItem>
							<NavMenuItem key={multipleCurrencyBackendRoutes.settings}>
								<NavMenuLink
									fontSize="sm"
									fontWeight="semibold"
									as={NavLink}
									_activeLink={navActiveStyles}
									to={multipleCurrencyBackendRoutes.settings}
									leftIcon={<BiCog />}
								>
									<Text
										fontSize="sm"
										fontWeight="semibold"
										_groupHover={{ color: 'primary.500' }}
									>
										<Text>{__('Settings', 'learning-management-system')}</Text>
									</Text>
								</NavMenuLink>
							</NavMenuItem>
						</NavMenu>
					</HeaderLeftSection>
				</HeaderTop>
			</Header>
			<Container maxW="container.xl">
				<Stack direction="column" spacing="6">
					<ButtonGroup>
						<Link to={multipleCurrencyBackendRoutes.list}>
							<BackButton />
						</Link>
					</ButtonGroup>
					{pricingZoneQuery.isSuccess ? (
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
														'Edit Pricing Zone',
														'learning-management-system',
													)}
												</Heading>
											</Flex>

											<Stack direction="column" spacing="6">
												<Name defaultValue={pricingZoneQuery?.data?.title} />
												<ExchangeRate
													defaultValue={pricingZoneQuery?.data?.exchange_rate}
												/>

												{isLargerThan992 ? <FormButton /> : null}
											</Stack>
										</Stack>
									</Box>
									<Box w={{ lg: '400px' }} bg="white" p="10" shadow="box">
										<Stack direction="column" spacing="6">
											<Countries
												defaultValue={pricingZoneQuery?.data?.countries}
												prizeZoneID={pricingZoneQuery?.data?.id}
											/>
											<Currency
												defaultValue={pricingZoneQuery?.data?.currency}
											/>
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

export default EditPriceZone;
