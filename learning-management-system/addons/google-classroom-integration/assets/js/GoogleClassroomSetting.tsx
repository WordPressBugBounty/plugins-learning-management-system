import {
	Container,
	IconButton,
	Menu,
	MenuButton,
	MenuItem,
	MenuList,
	Stack,
	Text,
	useClipboard,
	useToast,
} from '@chakra-ui/react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';

import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Col, Row } from 'react-grid-system';
import { useForm } from 'react-hook-form';
import { BiBook, BiCog, BiDotsHorizontalRounded } from 'react-icons/bi';
import { NavLink } from 'react-router-dom';
import ButtonsGroup from '../../../../assets/js/back-end/components/common/ButtonsGroup';
import DisplayModal from '../../../../assets/js/back-end/components/common/DisplayModal';
import {
	Header,
	HeaderLeftSection,
	HeaderLogo,
	HeaderTop,
} from '../../../../assets/js/back-end/components/common/Header';
import {
	NavMenu,
	NavMenuItem,
	NavMenuLink,
} from '../../../../assets/js/back-end/components/common/Nav';
import {
	headerResponsive,
	navActiveStyles,
	navLinkStyles,
} from '../../../../assets/js/back-end/config/styles';
import { Gear } from '../../../../assets/js/back-end/constants/images';
import routes from '../../../../assets/js/back-end/constants/routes';
import API from '../../../../assets/js/back-end/utils/api';
import localized from '../../../../assets/js/back-end/utils/global';
import http from '../../../../assets/js/back-end/utils/http';
import googleClassroomUrls from '../../constants/urls';
import { GoogleClassroomSettingsSkeleton } from './GoogleClassroomSkeleton';
import ClassroomErrorConsentScreen from './components/ClassroomErrorConsentScreen';
import ClassroomSuccessConsentScreen from './components/ClassroomSuccessConsentScreen';
import ClassroomTextAndLinkSection from './components/ClassroomTextAndLinkSection';
import DNDJson from './components/DNDJson';

export interface GoogleClassroomSettingsSchema {
	client_id: string;
	client_secret: string;
	access_code?: boolean;
	refresh_token?: string;
	access_token?: string;
	token_available?: boolean;
}

export const defaultCopyValue = `${localized.adminUrl}admin.php?page=masteriyo`;

const GoogleClassroomSetting = () => {
	const methods = useForm<GoogleClassroomSettingsSchema>();
	const googleClassroomSetting = new API(googleClassroomUrls.settings);
	const toast = useToast();
	const queryClient = useQueryClient();
	const [resetCredentialsModal, setResetCredentialsModal] =
		useState<boolean>(false);
	const { reset } = useForm();
	const {
		hasCopied: googleCopy,
		onCopy: clipToCopyForGoogle,
		value,
		setValue,
	} = useClipboard(defaultCopyValue);

	const googleClassroomSettingQuery = useQuery({
		queryKey: ['googleClassroomSettings'],
		queryFn: () => googleClassroomSetting.list(),
	});

	const onClearData = () => {
		return http({ path: googleClassroomUrls.settings, method: 'DELETE' });
	};

	const onCopyValueChange = (copyText: string) => {
		setValue(copyText);
	};

	const handleFileUpload = useMutation({
		mutationFn: (data: any) => {
			const formData = new FormData();
			formData.append('file', data);
			return http({
				path: googleClassroomUrls.settings,
				method: 'POST',
				body: formData,
			});
		},

		...{
			onSuccess() {
				toast({
					title: __('Import complete', 'learning-management-system'),
					status: 'success',
					duration: 3000,
					isClosable: true,
				});
				reset();
				queryClient.invalidateQueries({
					queryKey: ['googleClassroomSettings'],
				});
			},
			onError(data: any) {
				toast({
					title: __('Import failed!', 'learning-management-system'),
					description: data?.message,
					status: 'error',
					duration: 3000,
					isClosable: true,
				});
			},
		},
	});

	const onResetCredentialsModalChange = useCallback((value: boolean) => {
		return setResetCredentialsModal(value);
	}, []);

	const onHandleConsentScreen = (data: any) => {
		const url = `https://accounts.google.com/o/oauth2/v2/auth?client_id=${data?.client_id}&redirect_uri=${defaultCopyValue}&response_type=code&access_type=offline&scope=https://www.googleapis.com/auth/classroom.courses.readonly+https://www.googleapis.com/auth/classroom.rosters.readonly+https://www.googleapis.com/auth/classroom.profile.emails&state=masteriyo_google_classroom&prompt=consent`;
		window.location.href = url;
	};

	const onResetCredentialConfirmationClick = useCallback(() => {
		onClearData();
		window.location.reload();
		return onResetCredentialsModalChange(false);
	}, [onResetCredentialsModalChange]);

	const resetCredentialConfirmationButtons = useMemo(() => {
		return [
			{
				title: __(`No I am not`, 'learning-management-system'),
				variant: 'outline',
				onClick: () => onResetCredentialsModalChange(false),
			},
			{
				title: __('Yes I am sure', 'learning-management-system'),
				onClick: () => onResetCredentialConfirmationClick(),
				colorScheme: 'primary',
			},
		];
	}, [onResetCredentialConfirmationClick, onResetCredentialsModalChange]);

	useEffect(() => {
		if (handleFileUpload.isSuccess) {
			toast({
				title: __(`File uploaded successfully`, 'learning-management-system'),
				status: 'success',
				isClosable: true,
			});
		}
	}, [handleFileUpload.isSuccess, toast]);

	return (
		<>
			<Stack direction="column" spacing={8} alignItems="center">
				<Header>
					<HeaderTop>
						<HeaderLeftSection gap={7}>
							<HeaderLogo />

							<NavMenu sx={headerResponsive.larger}>
								<NavMenuItem key={routes.googleClassroom.list} display="flex">
									<NavMenuLink
										as={NavLink}
										_hover={{ textDecoration: 'none' }}
										_activeLink={navActiveStyles}
										to={routes.googleClassroom.list}
									>
										<Text
											fontSize="sm"
											fontWeight="semibold"
											_groupHover={{ color: 'primary.500' }}
										>
											{__('Google Courses', 'learning-management-system')}
										</Text>
									</NavMenuLink>
								</NavMenuItem>

								<NavMenuLink
									as={NavLink}
									sx={{ ...navLinkStyles, borderBottom: '2px solid white' }}
									_hover={{ textDecoration: 'none' }}
									_activeLink={navActiveStyles}
									to={routes.googleClassroom.setting}
									leftIcon={
										<Gear height="20px" width="20px" fill="currentColor" />
									}
								>
									<Text
										fontSize="sm"
										fontWeight="semibold"
										_groupHover={{ color: 'primary.500' }}
									>
										{__('Settings', 'learning-management-system')}
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
												_hover={{ textDecoration: 'none' }}
												_activeLink={navActiveStyles}
												to={routes.googleClassroom.list}
												leftIcon={<BiBook />}
											>
												{__('Google Courses', 'learning-management-system')}
											</NavMenuLink>
										</MenuItem>

										<MenuItem>
											<NavMenuLink
												as={NavLink}
												sx={{ color: 'black', height: '20px' }}
												_activeLink={{ color: 'primary.500' }}
												// to={routes}
												leftIcon={<BiCog />}
											>
												{__('Settings', 'learning-management-system')}
											</NavMenuLink>
										</MenuItem>
									</MenuList>
								</Menu>
							</NavMenu>
						</HeaderLeftSection>
					</HeaderTop>
				</Header>
			</Stack>

			<Container
				mt={8}
				maxW="container.xl"
				width={'100%'}
				borderRadius={10}
				bg={'white'}
				boxShadow={'md'}
				padding={10}
			>
				{resetCredentialsModal && (
					<>
						<DisplayModal
							isOpen={resetCredentialsModal}
							onClose={() => onResetCredentialsModalChange(false)}
							title={'Do you want to delete this permanently?'}
							size={'lg'}
							extraInfo={__(
								'You cannot restore after you reset your credentials.',
								'learning-management-system',
							)}
						>
							{/* Can conditionally render different buttons based on which consent screen is enabled */}
							<ButtonsGroup buttons={resetCredentialConfirmationButtons} />
						</DisplayModal>
					</>
				)}
				{!googleClassroomSettingQuery.isFetching &&
				googleClassroomSettingQuery?.data?.access_token &&
				googleClassroomSettingQuery?.data?.refresh_token ? (
					<ClassroomSuccessConsentScreen
						onCopy={clipToCopyForGoogle}
						value={value}
						hasCopied={googleCopy}
						onCopyValueChange={onCopyValueChange}
						onResetCredentialsModalChange={onResetCredentialsModalChange}
					/>
				) : googleClassroomSettingQuery.isFetching &&
				  (!googleClassroomSettingQuery?.data ||
						(googleClassroomSettingQuery?.data?.access_token &&
							googleClassroomSettingQuery?.data?.refresh_token)) ? (
					<GoogleClassroomSettingsSkeleton />
				) : googleClassroomSettingQuery.isSuccess &&
				  googleClassroomSettingQuery?.data.client_id ? (
					<ClassroomErrorConsentScreen
						onResetCredentialsModalChange={onResetCredentialsModalChange}
						onHandleConsentScreen={onHandleConsentScreen}
					/>
				) : (
					<Row align={'center'}>
						<Col xs={12} md={6}>
							<ClassroomTextAndLinkSection
								onCopyValueChange={onCopyValueChange}
								hasCopied={googleCopy}
								value={value}
								onCopy={clipToCopyForGoogle}
							/>
						</Col>
						<Col xs={12} md={6}>
							<DNDJson handleFileUpload={handleFileUpload} />
						</Col>
					</Row>
				)}
			</Container>
		</>
	);
};

export default GoogleClassroomSetting;
