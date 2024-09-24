import { Container, useClipboard, useToast } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { Col, Row } from 'react-grid-system';
import { useForm } from 'react-hook-form';
import { useMutation, useQuery, useQueryClient } from 'react-query';
import ButtonsGroup from '../../../../assets/js/back-end/components/common/ButtonsGroup';
import DisplayModal from '../../../../assets/js/back-end/components/common/DisplayModal';
import API from '../../../../assets/js/back-end/utils/api';
import localized from '../../../../assets/js/back-end/utils/global';
import http from '../../../../assets/js/back-end/utils/http';
import GoogleMeetUrls from '../../constants/urls';
import DNDJson from './DNDJson';
import MeetingErrorConsentScreen from './MeetingErrorConsentScreen';
import MeetingSuccessConsentScreen from './MeetingSuccessConsentScreen';
import MeetingsTextAndLinkSection from './MeetingsTextAndLinkSection';

export const defaultCopyValue = `${localized.home_url}/wp-admin/admin.php?page=masteriyo`;

interface Props {}

const MeetingSetupSection: React.FC<Props> = () => {
	const { reset } = useForm();
	const queryClient = useQueryClient();
	const { onCopy, value, setValue, hasCopied } = useClipboard(defaultCopyValue);
	const [resetCredentialsModal, setResetCredentialsModal] =
		useState<boolean>(false);

	const toast = useToast();

	const GoogleMeetAPI = new API(GoogleMeetUrls.settings);

	const settingQuery = useQuery(
		['googleMeetSettings'],
		() => GoogleMeetAPI.list(),
		{
			keepPreviousData: true,
		},
	);

	const resetButton = () => {
		return http({ path: GoogleMeetUrls.settings, method: 'DELETE' });
	};

	const onResetCredentialsModalChange = useCallback((value: boolean) => {
		return setResetCredentialsModal(value);
	}, []);

	const onHandleConsentScreen = (data: any) => {
		const url = `https://accounts.google.com/o/oauth2/v2/auth?client_id=${data?.client_id}&redirect_uri=${defaultCopyValue}&response_type=code&access_type=offline&scope=https://www.googleapis.com/auth/calendar.events+https://www.googleapis.com/auth/calendar+https://www.googleapis.com/auth/calendar.events.readonly+https://www.googleapis.com/auth/calendar.readonly&state=masteriyo_google_meet&prompt=consent`;
		window.location.href = url;
	};

	const handleFileUpload = useMutation(
		(data: any) => {
			const formData = new FormData();
			formData.append('file', data);

			return http({
				path: GoogleMeetUrls.settings,
				method: 'POST',
				body: formData,
			});
		},
		{
			onSuccess() {
				toast({
					title: __('Import complete', 'learning-management-system'),
					status: 'success',
					duration: 3000,
					isClosable: true,
				});
				reset();
				queryClient.invalidateQueries('googleMeetSettings');
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
	);

	//  Reset Credential API is needed to be called here
	const onResetCredentialConfirmationClick = useCallback(() => {
		resetButton();
		window.location.reload();
		return onResetCredentialsModalChange(false);
	}, [onResetCredentialsModalChange]);

	const onCopyValueChange = (copyText: string) => {
		setValue(copyText);
	};

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
		<Container
			maxW="container.xl"
			width={'100%'}
			borderRadius={10}
			bg={'white'}
			boxShadow={'14px 14px 100px #f2f2f2,-14px -14px 100px #ffffff;'}
			padding={10}
		>
			{/* This modal is shown when ever reset credentials button is clicked on consent screens */}

			{resetCredentialsModal && (
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
			)}

			{settingQuery?.data?.refresh_token ? (
				<MeetingSuccessConsentScreen
					onCopyValueChange={onCopyValueChange}
					hasCopied={hasCopied}
					value={value}
					onCopy={onCopy}
					onResetCredentialsModalChange={onResetCredentialsModalChange}
				/>
			) : settingQuery?.data?.client_id ? (
				<MeetingErrorConsentScreen
					onResetCredentialsModalChange={onResetCredentialsModalChange}
					onHandleConsentScreen={onHandleConsentScreen}
				/>
			) : (
				<Row align={'center'}>
					<Col xs={12} md={6}>
						<MeetingsTextAndLinkSection
							onCopyValueChange={onCopyValueChange}
							hasCopied={hasCopied}
							value={value}
							onCopy={onCopy}
						/>
					</Col>
					<Col xs={12} md={6}>
						<DNDJson handleFileUpload={handleFileUpload} />
					</Col>
				</Row>
			)}
		</Container>
	);
};

export default MeetingSetupSection;
