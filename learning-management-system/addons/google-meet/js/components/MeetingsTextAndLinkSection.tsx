import { Button, Flex, Input, Stack, Text } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { BiCheck, BiSolidCopy } from 'react-icons/bi';

interface Props {
	onCopyValueChange: (value: string) => void;
	hasCopied: boolean;
	value: string;
	onCopy: () => void;
}

const MeetingsTextAndLinkSection: React.FC<Props> = ({
	onCopyValueChange,
	hasCopied,
	value,
	onCopy,
}) => {
	return (
		<Stack gap={5}>
			<Text fontSize={'x-large'} textDecoration={'underline'}>
				{__('Setup your', 'learning-management-system')}{' '}
				<span style={{ fontWeight: 600 }}>
					{__('Google Meet', 'learning-management-system')}
				</span>{' '}
				{__('integration', 'learning-management-system')}
			</Text>

			<Text
				fontSize={'small'}
				color={'gray.500'}
				textAlign={'justify'}
				lineHeight={6}
			>
				{__(
					'To seamlessly integrate with Google Meet, access the following',
					'learning-management-system',
				)}{' '}
				<span
					style={{ color: 'blue', cursor: 'pointer' }}
					onClick={() =>
						window.open('https://console.cloud.google.com/apis', '_blank')
					}
				>
					{__('link', 'learning-management-system')}
				</span>{' '}
				{__(
					`to generate your OAuth Access Credentials. Throughout this procedure, ensure that you copy the provided link below and use it as your Redirect URI. For detailed instructions, please refer to our`,
					'learning-management-system',
				)}{' '}
				<span
					style={{ color: 'blue', cursor: 'pointer' }}
					onClick={() =>
						window.open(
							'https://docs.masteriyo.com/free-addons/google-meet-integration',
							'_blank',
						)
					}
				>
					{__('documentation', 'learning-management-system')}
				</span>{' '}
				<span>
					{__(
						'on setting up OAuth Access Credentials.',
						'learning-management-system',
					)}
				</span>
			</Text>
			<Flex alignItems={'center'}>
				<Input
					placeholder={__('Your Link', 'learning-management-system')}
					borderRadius={6}
					flex={1}
					value={value}
					onChange={(e) => onCopyValueChange(e.target.value)}
				/>
				<Button
					width={'85px'}
					onClick={onCopy}
					colorScheme={'primary'}
					rightIcon={
						hasCopied ? <BiCheck color={'green'} size={18} /> : <BiSolidCopy />
					}
					mx={2}
					borderRadius={6}
					variant={'outline'}
				>
					{__(hasCopied ? 'Copied' : 'Copy', 'learning-management-system')}
				</Button>
			</Flex>
		</Stack>
	);
};

export default MeetingsTextAndLinkSection;
