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
	BiBookOpen,
	BiBookmarks,
	BiCog,
	BiDotsHorizontalRounded,
	BiGroup,
	BiTrash,
} from 'react-icons/bi';
import { useQuery } from 'react-query';
import { NavLink, useLocation, useSearchParams } from 'react-router-dom';
import {
	HeaderLeftSection,
	HeaderLogo,
} from '../../../../../../assets/js/back-end/components/common/Header';
import {
	NavMenu,
	NavMenuLink,
} from '../../../../../../assets/js/back-end/components/common/Nav';
import {
	headerResponsive,
	navActiveStyles,
	navLinkStyles,
} from '../../../../../../assets/js/back-end/config/styles';
import API from '../../../../../../assets/js/back-end/utils/api';
import { urls } from '../../constants/urls';
import { groupsBackendRoutes } from '../../routes/routes';
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
		name: __('All Groups', 'learning-management-system'),
		icon: <BiGroup />,
	},
	{
		status: 'publish',
		name: __('Published', 'learning-management-system'),
		icon: <BiBookOpen />,
	},
	{
		status: 'draft',
		name: __('Draft', 'learning-management-system'),
		icon: <BiBookmarks />,
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
		'/groups-settings' === pathname ? '' : searchParams.get('status') ?? 'any';

	const groupAPI = new API(urls.groups);

	useEffect(() => {
		if (currentTab) {
			setFilterParams((prevState) => ({
				...prevState,
				status: currentTab,
			}));
		}
	}, [currentTab]);

	const groupQuery = useQuery(
		['groupsList', filterParams],
		() => groupAPI.list(filterParams),
		{
			keepPreviousData: true,
		},
	);

	const counts = groupQuery.data?.meta.groups_count;
	const isCounting = groupQuery.isLoading;

	const groupNavStyles = {
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
							to={`${groupsBackendRoutes.list}?status=${tab.status}`}
							sx={{
								...groupNavStyles,
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
						to={groupsBackendRoutes.settings}
						sx={{
							...groupNavStyles,
							...(location.pathname === groupsBackendRoutes.settings
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
								location.pathname === groupsBackendRoutes.settings
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
										to={`${groupsBackendRoutes.list}?status=${tab.status}`}
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
									to={groupsBackendRoutes.settings}
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
