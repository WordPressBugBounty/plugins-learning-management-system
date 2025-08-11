import {
	IconButton,
	Menu,
	MenuButton,
	MenuItem,
	MenuList,
	Text,
} from '@chakra-ui/react';
import { UseQueryResult } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { BiDotsHorizontalRounded } from 'react-icons/bi';
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
				<HeaderLeftSection gap={7}>
					<HeaderLogo />

					<NavMenu sx={headerResponsive.larger}>
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
							count={
								googleMeetSetting &&
								googleMeetingQuery?.data?.meta?.googleMeetCounts !==
									undefined &&
								googleMeetingQuery?.data?.meta?.googleMeetCounts.any
							}
							isCounting={googleMeetingQuery?.isLoading}
						>
							<Text
								fontSize="sm"
								fontWeight="semibold"
								_groupHover={{ color: 'primary.500' }}
							>
								{__('Meetings', 'learning-management-system')}
							</Text>
						</NavMenuLink>

						<NavMenuLink
							as={NavLink}
							_hover={{ textDecoration: 'none' }}
							_activeLink={navActiveStyles}
							to={googleMeetRoutes.googleMeet.setAPI}
						>
							<Text
								fontSize="sm"
								fontWeight="semibold"
								_groupHover={{ color: 'primary.500' }}
							>
								{__('Set API', 'learning-management-system')}
							</Text>
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
										count={googleMeetingQuery?.data?.meta?.googleMeetCounts.all}
										isCounting={googleMeetingQuery?.isLoading}
									>
										{__('Meetings', 'learning-management-system')}
									</NavMenuLink>
								</MenuItem>

								<MenuItem>
									<NavMenuLink
										as={NavLink}
										sx={{ color: 'black', height: '20px' }}
										_activeLink={{ color: 'primary.500' }}
										to={googleMeetRoutes.googleMeet.setAPI}
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
