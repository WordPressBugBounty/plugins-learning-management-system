import {
	IconButton,
	Menu,
	MenuButton,
	MenuItem,
	MenuList,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useMemo } from 'react';
import { BiDotsHorizontalRounded } from 'react-icons/bi';
import { NavLink, useParams } from 'react-router-dom';
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
} from '../../../../assets/js/back-end/config/styles';
import {
	AllCoursesIcon,
	Builder,
	Gear,
} from '../../../../assets/js/back-end/constants/images';
import routes from '../../../../assets/js/back-end/constants/routes';

interface Props {}

const CourseGoogleMeetingHeader: React.FC<Props> = ({}) => {
	const { sectionId, courseId }: any = useParams();

	const HeaderData = useMemo(() => {
		return [
			{
				routes: routes.courses.edit.replace(':courseId', courseId.toString()),
				name: __('Course', 'learning-management-system'),
				icon: <AllCoursesIcon width={20} height={20} fill="currentColor" />,
			},
			{
				routes:
					routes.courses.edit.replace(':courseId', courseId.toString()) +
					'?page=builder',
				name: __('Builder', 'learning-management-system'),
				icon: <Builder width={20} height={20} fill="currentColor" />,
			},
			{
				routes:
					routes.courses.edit.replace(':courseId', courseId.toString()) +
					'?page=settings',
				name: __('Settings', 'learning-management-system'),
				icon: <Gear width={20} height={20} fill="currentColor" />,
			},
		];
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, []);

	return (
		<Header>
			<HeaderTop>
				<HeaderLeftSection gap={7}>
					<HeaderLogo />
					<NavMenu sx={headerResponsive.larger}>
						{HeaderData.map((data) => (
							<NavMenuLink
								key={data.name}
								as={NavLink}
								_activeLink={navActiveStyles}
								to={data.routes}
							>
								{data.name}
							</NavMenuLink>
						))}
					</NavMenu>
					<NavMenu sx={headerResponsive.smaller}>
						<Menu>
							<MenuButton
								as={IconButton}
								icon={<BiDotsHorizontalRounded style={{ fontSize: 25 }} />}
								style={{
									background: '#FFFFFF',
									boxShadow: 'none',
								}}
								py={'45px'}
								color={'primary.500'}
							/>
							<MenuList>
								{HeaderData.map((data) => (
									<MenuItem key={data.name}>
										<NavMenuLink
											as={NavLink}
											sx={{ color: 'black', height: '20px' }}
											_activeLink={{ color: 'primary.500' }}
											to={data.routes}
											leftIcon={data.icon}
										>
											{data.name}
										</NavMenuLink>
									</MenuItem>
								))}
							</MenuList>
						</Menu>
					</NavMenu>
				</HeaderLeftSection>
			</HeaderTop>
		</Header>
	);
};

export default CourseGoogleMeetingHeader;
