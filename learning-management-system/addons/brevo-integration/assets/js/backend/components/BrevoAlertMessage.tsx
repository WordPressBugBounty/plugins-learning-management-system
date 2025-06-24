import { Box, Link, Text, VStack } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import CustomAlert from '../../../../../../assets/js/back-end/components/common/CustomAlert';

const BrevoAlertMessage = () => {
	return (
		<CustomAlert status="info" mb="6" isAlertIconTop>
			<VStack alignItems={'flex-start'} spacing={4}>
				<Box display="flex" alignItems="center" mb={2}>
					<Text fontWeight="bold" fontSize="lg" mr={2}>
						{__('Brevo API Key Setup:', 'learning-management-system')}
					</Text>
				</Box>

				<Text>
					{__('1. ', 'learning-management-system')}
					<Link
						href="https://login.brevo.com/"
						isExternal
						textDecoration="underline"
						textUnderlineOffset={2}
					>
						{__('Log in to your Brevo account.', 'learning-management-system')}
					</Link>
					{__(' If you don’t have an account, ', 'learning-management-system')}
					<Link
						href="https://onboarding.brevo.com/account/register"
						isExternal
						textDecoration="underline"
						textUnderlineOffset={2}
					>
						{__('sign up here.', 'learning-management-system')}
					</Link>
				</Text>

				<Text>
					{__('2. ', 'learning-management-system')}
					{__(
						'After logging in, navigate to your API keys section to generate a new API key.',
						'learning-management-system',
					)}
					<Link
						href="https://app.brevo.com/settings/keys/api"
						isExternal
						textDecoration="underline"
						textUnderlineOffset={2}
					>
						{__(' Go to API Keys', 'learning-management-system')}
					</Link>
				</Text>

				<Text>
					{__('3. ', 'learning-management-system')}
					{__(
						'Copy the generated API key and paste it into the "Brevo API Key" field.',
						'learning-management-system',
					)}
				</Text>
			</VStack>
		</CustomAlert>
	);
};

export default BrevoAlertMessage;
