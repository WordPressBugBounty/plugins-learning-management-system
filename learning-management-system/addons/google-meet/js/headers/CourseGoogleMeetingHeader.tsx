import {
	IconButton,
	Menu,
	MenuButton,
	MenuItem,
	MenuList,
	Stack,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useMemo } from 'react';
import { BiBook, BiCog, BiDotsHorizontalRounded, BiEdit } from 'react-icons/bi';
import { NavLink, useParams } from 'react-router-dom';
import {
	Header,
	HeaderLeftSection,
	HeaderLogo,
	HeaderText,
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
import routes from '../../../../assets/js/back-end/constants/routes';

interface Props {}

const CourseGoogleMeetingHeader: React.FC<Props> = ({}) => {
	const { sectionId, courseId }: any = useParams();

	const HeaderData = useMemo(() => {
		return [
			{
				routes: routes.courses.edit.replace(':courseId', courseId.toString()),
				name: __('Course', 'learning-management-system'),
				icon: <BiBook />,
			},
			{
				routes:
					routes.courses.edit.replace(':courseId', courseId.toString()) +
					'?page=builder',
				name: __('Builder', 'learning-management-system'),
				icon: <BiEdit />,
			},
			{
				routes:
					routes.courses.edit.replace(':courseId', courseId.toString()) +
					'?page=settings',
				name: __('Settings', 'learning-management-system'),
				icon: <BiCog />,
			},
		];
	}, []);

	return (
		<Header>
			<HeaderTop>
				<HeaderLeftSection>
					<Stack direction={['column', 'column', 'column', 'row']}>
						<HeaderLogo />
						<HeaderText
							isLoading={false}
							sx={{
								overflow: 'hidden',
								whiteSpace: 'nowrap',
								textOverflow: 'ellipsis',
							}}
							width={['80px', '100px', '110px', '130px']}
							textAlign="left"
						>
							Name
							{/* {courseQuery?.data?.name} */}
						</HeaderText>
					</Stack>
					<NavMenu sx={headerResponsive.larger}>
						{HeaderData.map((data) => (
							<NavMenuLink
								key={data.name}
								as={NavLink}
								_activeLink={navActiveStyles}
								to={data.routes}
								leftIcon={data.icon}
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
