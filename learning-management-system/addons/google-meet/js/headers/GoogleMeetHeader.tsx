import {
	Badge,
	IconButton,
	Menu,
	MenuButton,
	MenuItem,
	MenuList,
	SkeletonCircle,
	Stack,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { BiBook, BiCog, BiDotsHorizontalRounded } from 'react-icons/bi';
import { MdCancelPresentation } from 'react-icons/md';
import { UseQueryResult } from 'react-query';
import { NavLink } from 'react-router-dom';
import {
	Header,
	HeaderLeftSection,
	HeaderLogo,
	HeaderTop,
} from '../../../../assets/js/back-end/components/common/Header';
import {
	NavMenu,
	NavMenuLink,
} from '../../../../assets/js/back-end/components/common/Nav';
import {
	headerResponsive,
	navActiveStyles,
	navLinkStyles,
} from '../../../../assets/js/back-end/config/styles';
import googleMeetRoutes from '../../constants/routes';
interface Props {
	googleMeetingQuery?: UseQueryResult<any, unknown>;
	googleMeetSetting?: boolean;
}

const GoogleMeetHeader: React.FC<Props> = (props) => {
	const { googleMeetingQuery, googleMeetSetting } = props;

	return (
		<Header>
			<HeaderTop>
				<HeaderLeftSection>
					<Stack direction={['column', 'column', 'column', 'row']}>
						<HeaderLogo />
					</Stack>

					<NavMenu sx={headerResponsive.larger} color={'gray.600'}>
						<NavMenuLink
							as={NavLink}
							sx={{
								...navLinkStyles,
								borderBottom: '2px solid white',
								marginRight: 0,
							}}
							_hover={{ textDecoration: 'none' }}
							_activeLink={navActiveStyles}
							to={googleMeetRoutes.googleMeet.list}
							leftIcon={<BiBook />}
							iconSx={{ fontSize: 'lg', marginTop: '2px' }}
						>
							{__('Meetings', 'learning-management-system')}
							{googleMeetSetting &&
							googleMeetingQuery?.data?.meta?.googleMeetCounts !== undefined ? (
								<Badge
									color="inherit"
									bg={'inherit'}
									fontSize={'13px'}
									marginLeft={'3px'}
								>
									{googleMeetingQuery?.data?.meta?.googleMeetCounts.any}
								</Badge>
							) : null}
							{googleMeetingQuery?.isLoading ? (
								<SkeletonCircle size="4" rounded="2xl" ml="10px" />
							) : null}
						</NavMenuLink>

						<NavMenuLink
							as={NavLink}
							sx={{
								...navLinkStyles,
								borderBottom: '2px solid white',
								marginRight: 0,
							}}
							_hover={{ textDecoration: 'none' }}
							iconSx={{ fontSize: 'lg', marginTop: '2px' }}
							_activeLink={navActiveStyles}
							to={googleMeetRoutes.googleMeet.setAPI}
							leftIcon={<BiCog />}
						>
							{__('Set API', 'learning-management-system')}
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
								<MenuItem>
									<NavMenuLink
										as={NavLink}
										sx={{ color: 'black', height: '20px' }}
										_activeLink={{ color: 'primary.500' }}
										to={googleMeetRoutes.googleMeet.list}
										leftIcon={<BiBook />}
									>
										{__('Meetings', 'learning-management-system')}
										{googleMeetSetting && (
											<Badge color="inherit" bg={'inherit'}>
												{googleMeetingQuery?.data?.meta?.googleMeetCounts.all}
											</Badge>
										)}
									</NavMenuLink>
								</MenuItem>

								<MenuItem>
									<NavMenuLink
										as={NavLink}
										sx={{ color: 'black', height: '20px' }}
										_activeLink={{ color: 'primary.500' }}
										to={googleMeetRoutes.googleMeet.setAPI}
										leftIcon={<MdCancelPresentation />}
									>
										{__('Set API', 'learning-management-system')}
									</NavMenuLink>
								</MenuItem>
							</MenuList>
						</Menu>
					</NavMenu>
				</HeaderLeftSection>
			</HeaderTop>
		</Header>
	);
};

export default GoogleMeetHeader;
