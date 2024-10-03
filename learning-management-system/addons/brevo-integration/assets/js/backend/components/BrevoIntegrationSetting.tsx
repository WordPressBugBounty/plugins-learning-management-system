import {
	Box,
	Button,
	ButtonGroup,
	Collapse,
	Flex,
	FormLabel,
	HStack,
	Icon,
	Input,
	Select,
	Skeleton,
	Spinner,
	Stack,
	Switch,
	Tooltip,
	useToast,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useEffect, useState } from 'react';
import { Controller, useFormContext, useWatch } from 'react-hook-form';
import { BiHide, BiShow, BiSync } from 'react-icons/bi';
import { useMutation, useQuery } from 'react-query';
import FormControlTwoCol from '../../../../../../assets/js/back-end/components/common/FormControlTwoCol';
import urls from '../../../../../../assets/js/back-end/constants/urls';
import SingleComponentsWrapper from '../../../../../../assets/js/back-end/screens/settings/components/SingleComponentsWrapper';
import ToolTip from '../../../../../../assets/js/back-end/screens/settings/components/ToolTip';
import { IntegrationsSettingsMap } from '../../../../../../assets/js/back-end/types';
import API from '../../../../../../assets/js/back-end/utils/api';
import { isEmpty } from '../../../../../../assets/js/back-end/utils/utils';
import BrevoAlertMessage from './BrevoAlertMessage';

interface Props {
	brevoIntegration?: IntegrationsSettingsMap['brevo_integration'];
}

type BrevoList = {
	id: string;
	name: string;
};

const BrevoIntegrationSetting: React.FC<Props> = ({ brevoIntegration }) => {
	const [showApiKey, setShowApiKey] = useState(false);
	const { register, control, setValue } = useFormContext();
	const toast = useToast();
	const brevoIntegrationAPI = new API(urls.brevoIntegrationLists);
	const brevoIntegrationConnectAPI = new API(urls.brevoIntegrationConnect);
	const brevoIntegrationDisconnectAPI = new API(
		urls.brevoIntegrationDisconnect,
	);
	const [isSyncing, setIsSyncing] = useState(false);
	const [isAPIKeyConnected, setIsAPIKeyConnected] = useState(
		Boolean(brevoIntegration?.is_connected),
	);

	const apiKeyWatchValue = useWatch({
		name: 'integrations.brevo_integration.api_key',
		defaultValue: brevoIntegration?.api_key,
		control,
	});

	const brevoListQuery = useQuery<BrevoList[]>(
		['brevoLists'],
		() => brevoIntegrationAPI.list({ force: isSyncing }),
		{
			enabled: isAPIKeyConnected,
			onError: (err: any) => {},
		},
	);

	const connectMutation = useMutation(
		(verifyAgain?: boolean) =>
			brevoIntegrationConnectAPI.store({
				api_key: verifyAgain ? '' : apiKeyWatchValue,
				verify_again: verifyAgain,
			}),
		{
			onSuccess(response) {
				setIsAPIKeyConnected(true);
				toast({
					title: response.message,
					status: 'success',
					isClosable: true,
				});
				setValue('integrations.brevo_integration.api_key', '');
			},
			onError: (err: any) => {
				toast({
					title:
						err.data.message ||
						__(
							'Unable to connect API key. Please check your key and try again.',
							'learning-management-system',
						),
					status: 'error',
					isClosable: true,
				});
				setIsAPIKeyConnected(false);
			},
		},
	);

	const disConnectMutation = useMutation(
		() => brevoIntegrationDisconnectAPI.deleteResource(),
		{
			onSuccess(response) {
				setIsAPIKeyConnected(false);
				toast({
					title: response.message,
					status: 'success',
					isClosable: true,
				});
			},
			onError: (err: any) => {
				toast({
					title:
						err.data.message ||
						__(
							'Failed to disconnect API key. Please try again later.',
							'learning-management-system',
						),
					status: 'error',
					isClosable: true,
				});
			},
		},
	);

	useEffect(() => {
		if (isSyncing) {
			brevoListQuery.refetch().then(() => {
				setIsSyncing(false);
				toast({
					title: __('Lists Synced Successfully.', 'learning-management-system'),
					status: 'success',
					isClosable: true,
				});
			});
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [isSyncing]);

	return (
		<SingleComponentsWrapper title={__('Brevo', 'learning-management-system')}>
			<Stack width="full">
				<Collapse in={!isAPIKeyConnected}>
					<BrevoAlertMessage />

					<FormControlTwoCol>
						<FormLabel>
							{__('Brevo V3 API Key', 'learning-management-system')}
							<ToolTip
								label={__(
									'Enter your Brevo API Key. If you don’t have one, please log in to your Brevo account to generate it.',
									'learning-management-system',
								)}
							></ToolTip>
						</FormLabel>

						<HStack spacing={0}>
							<Input
								mr={0}
								isDisabled={!isAPIKeyConnected && connectMutation?.isLoading}
								type={showApiKey ? 'text' : 'password'}
								defaultValue={brevoIntegration?.api_key}
								{...register('integrations.brevo_integration.api_key')}
							/>

							<Flex bg={'gray.100'} padding={3} cursor={'pointer'}>
								<Icon
									as={showApiKey ? BiHide : BiShow}
									fontSize={'large'}
									onClick={() => setShowApiKey(!showApiKey)}
								/>
							</Flex>
						</HStack>
					</FormControlTwoCol>
				</Collapse>

				<Collapse in={isAPIKeyConnected} style={{ width: '100%' }}>
					<Stack spacing={'6'} direction={'column'} mt={2}>
						<FormControlTwoCol>
							<FormLabel>
								{__('Forced Subscription', 'learning-management-system')}
								<ToolTip
									label={__(
										'Enable this option to automatically subscribe users to your email list.',
										'learning-management-system',
									)}
								></ToolTip>
							</FormLabel>
							<Switch
								{...register(
									'integrations.brevo_integration.enable_forced_email_subscription',
								)}
								defaultChecked={
									brevoIntegration?.enable_forced_email_subscription
								}
							/>
						</FormControlTwoCol>

						<FormControlTwoCol>
							<FormLabel>
								{__('Consent Message', 'learning-management-system')}
								<ToolTip
									label={__(
										'This message will be displayed to users in the registration form when asking for their email subscription consent.',
										'learning-management-system',
									)}
								></ToolTip>
							</FormLabel>
							<Input
								defaultValue={brevoIntegration?.subscriber_consent_message}
								{...register(
									'integrations.brevo_integration.subscriber_consent_message',
								)}
							/>
						</FormControlTwoCol>
					</Stack>
				</Collapse>

				{brevoListQuery.isLoading ? (
					<Stack spacing={6}>
						<Skeleton height="40px" />
					</Stack>
				) : (
					isAPIKeyConnected && (
						<FormControlTwoCol>
							<FormLabel>
								{__('Lists', 'learning-management-system')}
								<ToolTip
									label={__(
										'Select a list to add subscribers to.',
										'learning-management-system',
									)}
								/>
							</FormLabel>
							<Box w="100%">
								<Controller
									name="integrations.brevo_integration.list"
									control={control}
									defaultValue={brevoIntegration?.list}
									render={({ field: { onChange, value } }) => (
										<HStack spacing={0} cursor={'pointer'}>
											<Select
												isDisabled={brevoListQuery.isFetching}
												value={value || ''}
												onChange={(e) => onChange(e.target.value)}
												placeholder={
													brevoListQuery?.isError ||
													isEmpty(brevoListQuery?.data)
														? __(
																'No lists available',
																'learning-management-system',
															)
														: __('Select a list', 'learning-management-system')
												}
											>
												{brevoListQuery?.isSuccess &&
													brevoListQuery?.data?.map((list) => (
														<option key={list.id} value={list.id}>
															{list.name}
														</option>
													))}
											</Select>
											<Tooltip
												label={__(
													'Clear cached data and fetch the latest lists from Brevo.',
													'learning-management-system',
												)}
												hasArrow
												fontSize="xs"
											>
												<Flex
													bg={'gray.100'}
													padding={2}
													onClick={() => setIsSyncing(true)}
												>
													{brevoListQuery.isFetching ? (
														<Spinner fontSize={'x-large'} />
													) : (
														<Icon as={BiSync} fontSize={'x-large'} />
													)}
												</Flex>
											</Tooltip>
										</HStack>
									)}
								/>
							</Box>
						</FormControlTwoCol>
					)
				)}
				<ButtonGroup>
					{isAPIKeyConnected &&
						brevoIntegration?.is_connected &&
						!connectMutation?.isSuccess && (
							<Button
								size={'sm'}
								width={'fit-content'}
								colorScheme={'primary'}
								onClick={() => connectMutation.mutate(true)}
								isLoading={connectMutation?.isLoading}
								isDisabled={connectMutation?.isLoading}
							>
								{__('Verify Connection Again', 'learning-management-system')}
							</Button>
						)}
					<Button
						size={'sm'}
						width={'fit-content'}
						colorScheme={isAPIKeyConnected ? 'red' : 'primary'}
						onClick={() =>
							isAPIKeyConnected
								? disConnectMutation.mutate()
								: connectMutation.mutate(false)
						}
						isDisabled={
							(!isAPIKeyConnected && !apiKeyWatchValue) ||
							connectMutation?.isLoading ||
							disConnectMutation?.isLoading
						}
						isLoading={
							isAPIKeyConnected
								? disConnectMutation?.isLoading
								: connectMutation?.isLoading
						}
					>
						{isAPIKeyConnected
							? __('Disconnect', 'learning-management-system')
							: __('Connect', 'learning-management-system')}
					</Button>
				</ButtonGroup>
			</Stack>
		</SingleComponentsWrapper>
	);
};

export default BrevoIntegrationSetting;
