import { PaymentsSettingsMap } from '@addons/../assets/js/back-end/types';
import {
	AlertDialog,
	AlertDialogBody,
	AlertDialogContent,
	AlertDialogFooter,
	AlertDialogHeader,
	AlertDialogOverlay,
	Box,
	Button,
	Collapse,
	Flex,
	FormLabel,
	Icon,
	Input,
	InputGroup,
	InputRightAddon,
	Stack,
	Switch,
	Text,
	Textarea,
	VStack,
	chakra,
	useClipboard,
	useDisclosure,
} from '@chakra-ui/react';
import { useMutation } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import React, { useRef, useState } from 'react';
import { Controller, useFormContext, useWatch } from 'react-hook-form';
import { BiHide, BiShow } from 'react-icons/bi';
import FormControlTwoCol from '../../../assets/js/back-end/components/common/FormControlTwoCol';
import ToolTip from '../../../assets/js/back-end/screens/settings/components/ToolTip';
import localized from '../../../assets/js/back-end/utils/global';

interface StripePaymentsSettingsMap extends PaymentsSettingsMap {
	stripe?: {
		enable: boolean;
		enable_ideal: boolean;
		title: string;
		description: string;
		sandbox: boolean;
		test_publishable_key: string;
		test_secret_key: string;
		live_publishable_key: string;
		live_secret_key: string;
		webhook_secret: string;
		webhook_endpoint: string;
		account: Record<string, any> | null;
		method: 'connect' | 'manual';
		nonce: string;
	};
}

interface Props {
	paymentsData?: StripePaymentsSettingsMap;
}

const StripeGlobalSettings: React.FC<Props> = (props) => {
	const { paymentsData } = props;

	const { register, control, formState, getValues } = useFormContext();
	const [show, setShow] = useState({
		liveSandboxKey: false,
		webhookKey: false,
	});

	const showStripeOptions = useWatch({
		name: 'payments.stripe.enable',
		defaultValue: paymentsData?.stripe?.enable,
		control,
	});

	const showStripeSandBoxOptions = useWatch({
		name: 'payments.stripe.sandbox',
		defaultValue: paymentsData?.stripe?.sandbox,
		control,
	});
	const { hasCopied, onCopy } = useClipboard(
		paymentsData?.stripe?.webhook_endpoint || '',
	);

	const connectionMutation = useMutation({
		mutationKey: ['stripeConnection'],
		mutationFn: async (
			data: Partial<Omit<StripePaymentsSettingsMap['stripe'], 'nonce'>>,
		) => {
			const formData = new FormData();
			formData.append('action', 'masteriyo_stripe_connect');
			formData.append('nonce', paymentsData?.stripe?.nonce ?? '');
			formData.append('current_page_uri', window.location.href);
			formData.append(
				'type',
				paymentsData?.stripe?.account ? 'disconnect' : 'connect',
			);
			formData.append('state', JSON.stringify(data));
			return apiFetch<{
				data:
					| {
							url: string;
							type: 'connect';
					  }
					| {
							type: 'disconnect';
					  };
				success: boolean;
			}>({
				url: localized.ajax_url,
				body: formData,
				method: 'POST',
			});
		},
		onSuccess(data) {
			if (data.data.type == 'connect') {
				window.location.href = data.data.url;
			} else {
				window.location.reload();
			}
		},
		onError(e) {},
	});

	const { isOpen, onOpen, onClose } = useDisclosure();

	const cancelRef = useRef<HTMLButtonElement>(null);

	return (
		<VStack
			alignItems="flex-start"
			gap={5}
			flexWrap={{ base: 'wrap', lg: 'nowrap' }}
			w="full"
		>
			<FormControlTwoCol>
				<FormLabel minW="160px">
					{__('Connection Status', 'learning-management-system')}
				</FormLabel>
				<Box>
					<Button
						onClick={() => {
							const formValues = getValues();
							const data = Object.entries(
								formState.dirtyFields?.payments?.stripe ?? {},
							).reduce(
								(acc, [k]) => {
									const key = k as keyof Omit<
										StripePaymentsSettingsMap['stripe'],
										'nonce'
									>;
									const value = formValues?.payments?.stripe?.[key];
									if (typeof value !== 'undefined') {
										acc[key] = value as Omit<
											StripePaymentsSettingsMap['stripe'],
											'nonce'
										>[typeof key];
									}
									return acc;
								},
								{} as {} & Partial<
									Omit<StripePaymentsSettingsMap['stripe'], 'nonce'>
								>,
							);
							connectionMutation.mutate(data);
						}}
						isLoading={connectionMutation.isPending}
						size="md"
						colorScheme={paymentsData?.stripe?.account ? 'red' : '#625afa'}
						bg={paymentsData?.stripe?.account ? 'red.500' : '#625afa'}
						gap={1}
					>
						{paymentsData?.stripe?.account ? (
							<Text>{__('Disconnect', 'learning-management-system')}</Text>
						) : (
							<>
								<Text fontSize="15px" fontWeight="700">
									{__('Connect with', 'learning-management-system')}
								</Text>
								<chakra.svg
									xmlns="http://www.w3.org/2000/svg"
									fillRule="evenodd"
									viewBox="0 0 512 214"
									preserveAspectRatio="xMidYMid"
									height="20px"
									fill="currentcolor"
									width="49px"
								>
									{/* eslint-disable-next-line max-len */}
									<chakra.path d="M35.982 83.484c0-5.546 4.551-7.68 12.09-7.68 10.808 0 24.461 3.272 35.27 9.103V51.484c-11.804-4.693-23.466-6.542-35.27-6.542C19.2 44.942 0 60.018 0 85.192c0 39.252 54.044 32.995 54.044 49.92 0 6.541-5.688 8.675-13.653 8.675-11.804 0-26.88-4.836-38.827-11.378v33.849c13.227 5.689 26.596 8.106 38.827 8.106 29.582 0 49.92-14.648 49.92-40.106-.142-42.382-54.329-34.845-54.329-50.774zm96.142-66.986l-34.702 7.395-.142 113.92c0 21.05 15.787 36.551 36.836 36.551 11.662 0 20.195-2.133 24.888-4.693V140.8c-4.55 1.849-27.022 8.391-27.022-12.658V77.653h27.022V47.36h-27.022l.142-30.862zm71.112 41.386L200.96 47.36h-30.72v124.444h35.556V87.467c8.39-10.951 22.613-8.96 27.022-7.396V47.36c-4.551-1.707-21.191-4.836-29.582 10.524zm38.257-10.524h35.698v124.444h-35.698V47.36zm0-10.809l35.698-7.68V0l-35.698 7.538V36.55zm109.938 8.391c-13.938 0-22.898 6.542-27.875 11.094l-1.85-8.818h-31.288v165.83l35.555-7.537.143-40.249c5.12 3.698 12.657 8.96 25.173 8.96 25.458 0 48.64-20.48 48.64-65.564-.142-41.245-23.609-63.716-48.498-63.716zm-8.533 97.991c-8.391 0-13.37-2.986-16.782-6.684l-.143-52.765c3.698-4.124 8.818-6.968 16.925-6.968 12.942 0 21.902 14.506 21.902 33.137 0 19.058-8.818 33.28-21.902 33.28zM512 110.08c0-36.409-17.636-65.138-51.342-65.138-33.85 0-54.33 28.73-54.33 64.854 0 42.808 24.179 64.426 58.88 64.426 16.925 0 29.725-3.84 39.396-9.244v-28.445c-9.67 4.836-20.764 7.823-34.844 7.823-13.796 0-26.027-4.836-27.591-21.618h69.547c0-1.85.284-9.245.284-12.658zm-70.258-13.511c0-16.071 9.814-22.756 18.774-22.756 8.675 0 17.92 6.685 17.92 22.756h-36.694z" />
								</chakra.svg>
							</>
						)}
					</Button>
				</Box>
			</FormControlTwoCol>
			<FormControlTwoCol>
				<Stack direction="row" align="center">
					<FormLabel m={0} minW="160px" htmlFor="enableStripeIDEAL">
						{__('Enable iDEAL Payments', 'learning-management-system')}
						<ToolTip
							label={__(
								'To enable iDEAL payments, ensure your Stripe account is activated and set to use the Euro (EUR) currency. iDEAL facilitates secure and swift transactions, catering specifically to European customers.',
								'learning-management-system',
							)}
						/>
					</FormLabel>
					<Controller
						name="payments.stripe.enable_ideal"
						render={({ field }) => (
							<Switch
								{...field}
								defaultChecked={paymentsData?.stripe?.enable_ideal}
							/>
						)}
					/>
				</Stack>
			</FormControlTwoCol>
			<FormControlTwoCol>
				<FormLabel m={0} minW="160px">
					{__('Title', 'learning-management-system')}
				</FormLabel>
				<Input
					type="text"
					{...register('payments.stripe.title')}
					defaultValue={paymentsData?.stripe?.title}
				/>
			</FormControlTwoCol>

			<FormControlTwoCol>
				<FormLabel m={0} minW="160px">
					{__('Description', 'learning-management-system')}
				</FormLabel>
				<Textarea
					bg="white"
					{...register('payments.stripe.description')}
					defaultValue={paymentsData?.stripe?.description}
				/>
			</FormControlTwoCol>

			<FormControlTwoCol mx={0} my={2}>
				<Stack direction="row">
					<FormLabel m={0} minW="160px">
						{__('Sandbox', 'learning-management-system')}
						<ToolTip
							label={__(
								'Stripe sandbox can be used to test payments.',
								'learning-management-system',
							)}
						/>
					</FormLabel>
					<Controller
						name="payments.stripe.sandbox"
						render={({ field }) => (
							<Switch
								{...field}
								defaultChecked={paymentsData?.stripe?.sandbox}
								onChange={(e) => {
									onOpen();
									field.onChange(e);
								}}
							/>
						)}
					/>
				</Stack>
			</FormControlTwoCol>
			<Collapse
				style={{ width: '100%' }}
				in={'manual' === paymentsData?.stripe?.method}
			>
				<Collapse in={showStripeSandBoxOptions}>
					<Stack direction="column" spacing="5">
						<FormControlTwoCol>
							<FormLabel m={0} minW="160px">
								{__('Test Publishable Key', 'learning-management-system')}
								<ToolTip
									label={__(
										'Get your API credentials from stripe.',
										'learning-management-system',
									)}
								/>
							</FormLabel>
							<Input
								type="text"
								{...register('payments.stripe.test_publishable_key')}
								defaultValue={paymentsData?.stripe?.test_publishable_key}
							/>
						</FormControlTwoCol>

						<FormControlTwoCol>
							<FormLabel m={0} minW="160px">
								{__('Test Secret Key', 'learning-management-system')}
								<ToolTip
									label={__(
										'Get your API credentials from stripe.',
										'learning-management-system',
									)}
								/>
							</FormLabel>
							<InputGroup>
								<Input
									type={show.liveSandboxKey ? 'text' : 'password'}
									{...register('payments.stripe.test_secret_key')}
									defaultValue={paymentsData?.stripe?.test_secret_key}
								/>
								<InputRightAddon>
									<Icon
										as={!show.liveSandboxKey ? BiShow : BiHide}
										onClick={() =>
											setShow({
												...show,
												liveSandboxKey: Boolean(!show.liveSandboxKey),
											})
										}
									/>
								</InputRightAddon>
							</InputGroup>
						</FormControlTwoCol>
					</Stack>
				</Collapse>

				<Collapse in={!showStripeSandBoxOptions}>
					<Stack direction="column" spacing="5">
						<FormControlTwoCol>
							<FormLabel m={0} minW="160px">
								{__('Live Publishable Key', 'learning-management-system')}
								<ToolTip
									label={__(
										'Get your API credentials from stripe.',
										'learning-management-system',
									)}
								/>
							</FormLabel>
							<Input
								type="text"
								{...register('payments.stripe.live_publishable_key')}
								defaultValue={paymentsData?.stripe?.live_publishable_key}
							/>
						</FormControlTwoCol>

						<FormControlTwoCol>
							<FormLabel m={0} minW="160px">
								{__('Live Secret Key', 'learning-management-system')}
								<ToolTip
									label={__(
										'Get your API credentials from stripe.',
										'learning-management-system',
									)}
								/>
							</FormLabel>
							<InputGroup>
								<Input
									type={show.liveSandboxKey ? 'text' : 'password'}
									{...register('payments.stripe.live_secret_key')}
									defaultValue={paymentsData?.stripe?.live_secret_key}
								/>
								<InputRightAddon>
									<Icon
										as={!show.liveSandboxKey ? BiShow : BiHide}
										onClick={() =>
											setShow({
												...show,
												liveSandboxKey: Boolean(!show.liveSandboxKey),
											})
										}
									/>
								</InputRightAddon>
							</InputGroup>
						</FormControlTwoCol>
					</Stack>
				</Collapse>
			</Collapse>
			<FormControlTwoCol>
				<FormLabel m={0} minW="160px">
					{__('Webhook Secret Key', 'learning-management-system')}
					<ToolTip
						label={__(
							'Get your API credentials from stripe.',
							'learning-management-system',
						)}
					/>
				</FormLabel>
				<InputGroup>
					<Input
						type={show.webhookKey ? 'text' : 'password'}
						placeholder="Optional"
						fontSize={'16px !important'}
						fontWeight={'normal !important'}
						pl={'4 !important'}
						{...register('payments.stripe.webhook_secret')}
						defaultValue={paymentsData?.stripe?.webhook_secret}
					/>
					<InputRightAddon>
						<Icon
							as={!show.webhookKey ? BiShow : BiHide}
							onClick={() =>
								setShow({
									...show,
									webhookKey: Boolean(!show.webhookKey),
								})
							}
						/>
					</InputRightAddon>
				</InputGroup>
			</FormControlTwoCol>

			<FormControlTwoCol>
				<FormLabel m={0} minW="160px">
					{__('Webhook Endpoint', 'learning-management-system')}
					<ToolTip
						label={__(
							'Add this webhook endpoint to your stripe webhook endpoint list to verify payment status.',
							'learning-management-system',
						)}
					/>
				</FormLabel>
				<Flex mb={2}>
					<Input
						type="text"
						readOnly
						defaultValue={paymentsData?.stripe?.webhook_endpoint}
					/>
					<Button colorScheme="blue" onClick={onCopy} ml={2}>
						{hasCopied
							? __('Copied', 'learning-management-system')
							: __('Copy', 'learning-management-system')}
					</Button>
				</Flex>
			</FormControlTwoCol>

			<AlertDialog
				isOpen={isOpen}
				leastDestructiveRef={cancelRef}
				onClose={onClose}
				closeOnOverlayClick={false}
				closeOnEsc={false}
				isCentered
			>
				<AlertDialogOverlay>
					<AlertDialogContent>
						<AlertDialogHeader fontSize="lg" fontWeight="bold">
							{__('Stripe Reconnection Needed', 'learning-management-system')}
						</AlertDialogHeader>
						<AlertDialogBody>
							{__(
								'Changing Sandbox Mode requires reconnecting your Stripe account.',
								'learning-management-system',
							)}
						</AlertDialogBody>
						<AlertDialogFooter>
							<Button colorScheme="primary" size="sm" onClick={onClose}>
								{__('Continue', 'masteriyo')}
							</Button>
						</AlertDialogFooter>
					</AlertDialogContent>
				</AlertDialogOverlay>
			</AlertDialog>
		</VStack>
	);
};

export default StripeGlobalSettings;
