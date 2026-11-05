import { Box, Link, Text, VStack } from '@chakra-ui/react';
import { createInterpolateElement } from '@wordpress/element';
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
					{createInterpolateElement(
						__(
							'1. <login>Log in to your Brevo account.</login> If you don’t have an account, <signup>sign up here.</signup>',
							'learning-management-system',
						),
						{
							login: (
								<Link
									href="https://login.brevo.com/"
									isExternal
									textDecoration="underline"
									textUnderlineOffset={2}
								/>
							),
							signup: (
								<Link
									href="https://onboarding.brevo.com/account/register"
									isExternal
									textDecoration="underline"
									textUnderlineOffset={2}
								/>
							),
						},
					)}
				</Text>

				<Text>
					{createInterpolateElement(
						__(
							'2. After logging in, navigate to your API keys section to generate a new API key. <a>Go to API Keys</a>',
							'learning-management-system',
						),
						{
							a: (
								<Link
									href="https://app.brevo.com/settings/keys/api"
									isExternal
									textDecoration="underline"
									textUnderlineOffset={2}
								/>
							),
						},
					)}
				</Text>

				<Text>
					{__(
						'3. Copy the generated API key and paste it into the "Brevo API Key" field.',
						'learning-management-system',
					)}
				</Text>
			</VStack>
		</CustomAlert>
	);
};

export default BrevoAlertMessage;
