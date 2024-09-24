import { Stack, Text } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useMemo } from 'react';
import { RiRestartLine } from 'react-icons/ri';
// import ButtonsGroup from '../../../../assets/js/back-end/components/common/ButtonsGroup.tsx';
import { Col, Row } from 'react-grid-system';
import ButtonsGroup from '../../../../assets/js/back-end/components/common/ButtonsGroup';
import MeetingsTextAndLinkSection from './MeetingsTextAndLinkSection';

interface Props {
	onResetCredentialsModalChange: (value: boolean) => void;
	onCopyValueChange: (value: string) => void;
	hasCopied: boolean;
	value: string;
	onCopy: () => void;
}

const MeetingSuccessConsentScreen: React.FC<Props> = ({
	onResetCredentialsModalChange,
	onCopyValueChange,
	hasCopied,
	value,
	onCopy,
}) => {
	const consentScreenButtons = useMemo(() => {
		return [
			{
				title: 'Reset Credentials',
				Icon: RiRestartLine,
				colorScheme: 'primary',
				onClick: () => onResetCredentialsModalChange(true),
			},
		];
	}, [onResetCredentialsModalChange]);
	return (
		<Stack justifyContent={'center'} gap={5} justifyItems={'space-between'}>
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
					<Stack
						my={{ base: 5, md: 0 }}
						borderWidth={'1px'}
						borderRadius={'lg'}
						padding={'50px'}
						lineHeight={'100'}
						gap={4}
					>
						<Text fontSize={'x-large'} fontWeight={400} textAlign={'center'}>
							{__(`Meet Account Activated`, 'learning-management-system')}
						</Text>
						<Text
							fontSize={'small'}
							color={'gray.500'}
							textAlign={'center'}
							lineHeight={6}
						>
							{__(
								`You can now start using the google meet addon.`,
								'learning-management-system',
							)}
						</Text>
						<ButtonsGroup buttons={consentScreenButtons} />
					</Stack>
				</Col>
			</Row>
		</Stack>
	);
};

export default MeetingSuccessConsentScreen;
