import {
	Badge,
	HStack,
	IconButton,
	Menu,
	MenuButton,
	MenuItem,
	MenuList,
	SkeletonCircle,
	Stack,
	Text,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useEffect, useState } from 'react';
import {
	BiCheckCircle,
	BiCog,
	BiDotsHorizontalRounded,
	BiGrid,
	BiPauseCircle,
	BiTrash,
} from 'react-icons/bi';
import { useQuery } from 'react-query';
import { NavLink, useLocation, useSearchParams } from 'react-router-dom';
import {
	HeaderLeftSection,
	HeaderLogo,
} from '../../../../../assets/js/back-end/components/common/Header';
import {
	NavMenu,
	NavMenuLink,
} from '../../../../../assets/js/back-end/components/common/Nav';
import {
	headerResponsive,
	navActiveStyles,
	navLinkStyles,
} from '../../../../../assets/js/back-end/config/styles';
import API from '../../../../../assets/js/back-end/utils/api';
import { urls } from '../constants/urls';
import { multipleCurrencyBackendRoutes } from '../routes/routes';
interface FilterParams {
	search?: string;
	status?: string;
	per_page?: number;
	page?: number;
	orderby: string;
	order: 'asc' | 'desc';
}

const tabButtons: FilterTabs = [
	{
		status: 'any',
		name: __('All Pricing Zones', 'learning-management-system'),
		icon: <BiGrid />,
	},
	{
		status: 'active',
		name: __('Active', 'learning-management-system'),
		icon: <BiCheckCircle />,
	},
	{
		status: 'inactive',
		name: __('Inactive', 'learning-management-system'),
		icon: <BiPauseCircle />,
	},
	{
		status: 'trash',
		name: __('Trash', 'learning-management-system'),
		icon: <BiTrash />,
	},
];

const LeftHeader: React.FC = (props) => {
	const location = useLocation();

	const [filterParams, setFilterParams] = useState<FilterParams>({
		order: 'desc',
		orderby: 'date',
	});

	const [searchParams] = useSearchParams();
	const { pathname } = useLocation();
	const currentTab =
		'/multiple-currency/settings' === pathname
			? ''
			: searchParams.get('status') ?? 'any';

	const pricingZoneAPI = new API(urls.pricingZones);

	useEffect(() => {
		if (currentTab) {
			setFilterParams((prevState) => ({
				...prevState,
				status: currentTab,
			}));
		}
	}, [currentTab]);

	const pricingZoneQuery = useQuery(
		['pricingZonesList', filterParams],
		() => pricingZoneAPI.list(filterParams),
		{
			keepPreviousData: true,
		},
	);

	const counts = pricingZoneQuery.data?.meta.pricing_zones_count;
	const isCounting = pricingZoneQuery.isLoading;

	const pricingZoneNavStyles = {
		...navLinkStyles,
		mr: '0px',
		borderBottom: '2px solid white',
	};

	return (
		<>
			<HeaderLeftSection>
				<Stack direction={['column', 'column', 'column', 'row']}>
					<HeaderLogo />
				</Stack>

				<NavMenu sx={headerResponsive.larger} color={'gray.600'}>
					{tabButtons.map((tab) => (
						<NavMenuLink
							key={tab.status}
							as={NavLink}
							to={`${multipleCurrencyBackendRoutes.list}?status=${tab.status}`}
							sx={{
								...pricingZoneNavStyles,
								...(currentTab === tab.status
									? {
											...navActiveStyles,
											_activeLink: {
												color: 'primary.500',
											},
										}
									: {}),
								_hover: { textDecoration: 'none' },
							}}
						>
							<HStack
								color={currentTab === tab.status ? 'primary.500' : 'gray.600'}
							>
								{tab.icon}
								<Text>{tab.name}</Text>
								{counts && counts[tab.status] ? (
									<Badge variant="count">{counts[tab.status]}</Badge>
								) : null}
								{isCounting && currentTab === tab.status ? (
									<SkeletonCircle size="4" />
								) : null}
							</HStack>
						</NavMenuLink>
					))}
					<NavMenuLink
						as={NavLink}
						to={multipleCurrencyBackendRoutes.settings}
						sx={{
							...pricingZoneNavStyles,
							...(location.pathname === multipleCurrencyBackendRoutes.settings
								? {
										...navActiveStyles,
										_activeLink: {
											color: 'primary.500',
										},
									}
								: {}),
							_hover: { textDecoration: 'none' },
						}}
					>
						<HStack
							color={
								location.pathname === multipleCurrencyBackendRoutes.settings
									? 'primary.500'
									: 'gray.600'
							}
						>
							<BiCog />
							<Text>{__('Settings', 'learning-management-system')}</Text>
						</HStack>
					</NavMenuLink>
				</NavMenu>

				<NavMenu sx={headerResponsive.smaller} color={'gray.600'}>
					<Menu>
						<MenuButton
							as={IconButton}
							icon={<BiDotsHorizontalRounded style={{ fontSize: 25 }} />}
							style={{
								background: '#FFFFFF',
								boxShadow: 'none',
							}}
							py={'35px'}
							color={'primary.500'}
						/>
						<MenuList color={'gray.600'}>
							{tabButtons.map((tab) => (
								<MenuItem key={tab.status}>
									<NavMenuLink
										as={NavLink}
										to={`${multipleCurrencyBackendRoutes.list}?status=${tab.status}`}
										sx={{ color: 'black', height: '20px' }}
										_activeLink={{ color: 'primary.500' }}
									>
										<HStack>
											{tab.icon}
											<Text>{tab.name}</Text>
											{counts && counts[tab.status] ? (
												<Badge color="inherit" ml="1" bg={'inherit'}>
													{counts[tab.status]}
												</Badge>
											) : null}
										</HStack>
									</NavMenuLink>
								</MenuItem>
							))}
							<MenuItem>
								<NavMenuLink
									as={NavLink}
									to={multipleCurrencyBackendRoutes.settings}
									sx={{ color: 'black', height: '20px' }}
									_activeLink={{ color: 'primary.500' }}
								>
									<HStack>
										<BiCog />
										<Text>{__('Settings', 'learning-management-system')}</Text>
									</HStack>
								</NavMenuLink>
							</MenuItem>
						</MenuList>
					</Menu>
				</NavMenu>
			</HeaderLeftSection>
		</>
	);
};

export default LeftHeader;
