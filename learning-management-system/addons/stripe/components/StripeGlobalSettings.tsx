// import { PaymentsSettingsMap } from '@addons/../assets/js/back-end/types';
import {
	Button,
	Collapse,
	Flex,
	FormLabel,
	IconButton,
	Input,
	InputGroup,
	InputRightElement,
	Stack,
	Switch,
	Textarea,
	useClipboard,
	VStack,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import { Controller, useFormContext, useWatch } from 'react-hook-form';
import { BiHide, BiShow } from 'react-icons/bi';
import FormControlTwoCol from '../../../assets/js/back-end/components/common/FormControlTwoCol';
import SingleComponentsWrapper from '../../../assets/js/back-end/screens/settings/components/SingleComponentsWrapper';
import ToolTip from '../../../assets/js/back-end/screens/settings/components/ToolTip';
import { PaymentsSettingsMap } from '../../../assets/js/back-end/types';

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
	};
}

interface Props {
	paymentsData?: StripePaymentsSettingsMap;
}

const StripeGlobalSettings: React.FC<Props> = (props) => {
	const { paymentsData } = props;

	const { register, control } = useFormContext();
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

	return (
		<SingleComponentsWrapper title={__('Stripe', 'learning-management-system')}>
			<FormControlTwoCol>
				<Stack direction="row">
					<FormLabel minW="160px" mb={0}>
						{__('Enable', 'learning-management-system')}
						<ToolTip
							label={__(
								'Use stripe payment on checkout.',
								'learning-management-system',
							)}
						/>
					</FormLabel>
					<Controller
						name="payments.stripe.enable"
						render={({ field }) => (
							<Switch
								{...field}
								defaultChecked={paymentsData?.stripe?.enable}
							/>
						)}
					/>
				</Stack>
			</FormControlTwoCol>

			<Collapse in={showStripeOptions} animateOpacity style={{ width: '100%' }}>
				<VStack
					alignItems="flex-start"
					gap={5}
					flexWrap={{ base: 'wrap', lg: 'nowrap' }}
					borderWidth={1}
					borderColor={'gray.200'}
					p={4}
					borderRadius={'md'}
				>
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
									/>
								)}
							/>
						</Stack>
					</FormControlTwoCol>
					<Collapse in={showStripeSandBoxOptions} style={{ width: '100%' }}>
						<Stack direction="column" spacing="6">
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
									<InputRightElement>
										{!show.liveSandboxKey ? (
											<IconButton
												onClick={() =>
													setShow({ ...show, liveSandboxKey: true })
												}
												size="lg"
												variant="unstyled"
												aria-label="Show sandbox secret key"
												icon={<BiShow />}
											/>
										) : (
											<IconButton
												onClick={() =>
													setShow({ ...show, liveSandboxKey: false })
												}
												size="lg"
												variant="unstyled"
												aria-label="Hide sandbox secret key"
												icon={<BiHide />}
											/>
										)}
									</InputRightElement>
								</InputGroup>
							</FormControlTwoCol>
						</Stack>
					</Collapse>

					<Collapse in={!showStripeSandBoxOptions} style={{ width: '100%' }}>
						<Stack direction="column" spacing="6">
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
									<InputRightElement>
										{!show.liveSandboxKey ? (
											<IconButton
												onClick={() =>
													setShow({ ...show, liveSandboxKey: true })
												}
												size="lg"
												variant="unstyled"
												aria-label="Show live secret key"
												icon={<BiShow />}
											/>
										) : (
											<IconButton
												onClick={() =>
													setShow({ ...show, liveSandboxKey: false })
												}
												size="lg"
												variant="unstyled"
												aria-label="Hide live secret key"
												icon={<BiHide />}
											/>
										)}
									</InputRightElement>
								</InputGroup>
							</FormControlTwoCol>
						</Stack>
					</Collapse>

					<FormControlTwoCol>
						<FormLabel m={0} minW="160px">
							{__('Webhook Secret Key', 'learning-management-system')}
							<ToolTip
								label={__(
									'Get your webhook secret key from stripe.',
									'learning-management-system',
								)}
							/>
						</FormLabel>
						<InputGroup>
							<Input
								type={show.webhookKey ? 'text' : 'password'}
								placeholder="Optional"
								{...register('payments.stripe.webhook_secret')}
								fontSize={'16px !important'}
								fontWeight={'normal !important'}
								pl={'4 !important'}
								defaultValue={paymentsData?.stripe?.webhook_secret}
							/>
							<InputRightElement>
								{!show.webhookKey ? (
									<IconButton
										onClick={() => setShow({ ...show, webhookKey: true })}
										size="lg"
										variant="unstyled"
										aria-label="Show webhook secret key"
										icon={<BiShow />}
									/>
								) : (
									<IconButton
										onClick={() => setShow({ ...show, webhookKey: false })}
										size="lg"
										variant="unstyled"
										aria-label="Hide webhook secret key"
										icon={<BiHide />}
									/>
								)}
							</InputRightElement>
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
				</VStack>
			</Collapse>
		</SingleComponentsWrapper>
	);
};

export default StripeGlobalSettings;
