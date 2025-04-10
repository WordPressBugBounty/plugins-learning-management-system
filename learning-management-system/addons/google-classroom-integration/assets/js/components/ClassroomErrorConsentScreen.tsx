import { Stack, Text } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useMemo } from 'react';
import { IoIosLink } from 'react-icons/io';
import { RiRestartLine } from 'react-icons/ri';
// import ButtonsGroup from '../../../../assets/js/back-end/components/common/ButtonsGroup.tsx';
import { useQuery } from '@tanstack/react-query';
import ButtonsGroup from '../../../../../assets/js/back-end/components/common/ButtonsGroup';
import API from '../../../../../assets/js/back-end/utils/api';
import googleClassroomUrls from '../../../constants/urls';

interface Props {
	onResetCredentialsModalChange: (value: boolean) => void;
	onHandleConsentScreen: any;
}

const ClassroomErrorConsentScreen: React.FC<Props> = ({
	onResetCredentialsModalChange,
	onHandleConsentScreen,
}) => {
	const GoogleClassroomAPI = new API(googleClassroomUrls.settings);

	const settingQuery = useQuery({
		queryKey: ['googleClassroomSettings'],
		queryFn: () => GoogleClassroomAPI.list(),
		...{
			keepPreviousData: true,
		},
	});

	const consentScreenButtons = useMemo(() => {
		return [
			{
				title: 'Reset Credentials',
				Icon: RiRestartLine,
				onClick: () => onResetCredentialsModalChange(true),
				colorScheme: 'primary',
			},
			{
				title: `Go To Google's Consent Screen`,
				Icon: IoIosLink,
				onClick: () => onHandleConsentScreen(settingQuery?.data),
				variant: 'outline',
			},
		];
	}, []);

	return (
		<Stack justifyContent={'center'} gap={5}>
			<Text fontSize={'x-large'} fontWeight={400} textAlign={'center'}>
				{__(`The app is not permitted yet!`, 'learning-management-system')}
			</Text>
			<Text
				fontSize={'small'}
				color={'gray.500'}
				textAlign={'center'}
				lineHeight={6}
			>
				{__(
					`Press the button to grant access to your google classroom. Please allow all required permission to make this app working perfectly.`,
					'learning-management-system',
				)}
			</Text>
			<ButtonsGroup buttons={consentScreenButtons} />
		</Stack>
	);
};

export default ClassroomErrorConsentScreen;
