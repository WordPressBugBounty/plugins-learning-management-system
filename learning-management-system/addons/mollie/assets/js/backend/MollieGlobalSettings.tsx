import {
	Alert,
	AlertDescription,
	AlertIcon,
	Collapse,
	FormLabel,
	HStack,
	IconButton,
	Input,
	InputGroup,
	InputRightElement,
	Link,
	Stack,
	Switch,
	Text,
	Textarea,
	VStack,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import { Controller, useFormContext, useWatch } from 'react-hook-form';
import { BiHide, BiShow } from 'react-icons/bi';
import FormControlTwoCol from '../../../../../assets/js/back-end/components/common/FormControlTwoCol';
import SingleComponentsWrapper from '../../../../../assets/js/back-end/screens/settings/components/SingleComponentsWrapper';
import ToolTip from '../../../../../assets/js/back-end/screens/settings/components/ToolTip';
import { PaymentsSettingsMap } from '../../../../../assets/js/back-end/types';

interface MolliePaymentsSettingsMap extends PaymentsSettingsMap {
	mollie?: {
		enable: boolean;
		title: string;
		description: string;
		sandbox: boolean;
		test_publishable_key: string;
		test_api_key: string;
		live_publishable_key: string;
		live_api_key: string;
		webhook_secret: string;
		error_message?: string;
	};
}

interface Props {
	paymentsData?: MolliePaymentsSettingsMap;
}

const MollieGlobalSettings: React.FC<Props> = (props) => {
	const { paymentsData } = props;

	const { register, control } = useFormContext();
	const [show, setShow] = useState({
		liveSandboxKey: false,
		webhookKey: false,
	});

	const showMollieOptions = useWatch({
		name: 'payments.mollie.enable',
		defaultValue: paymentsData?.mollie?.enable,
		control,
	});

	const showMollieSandBoxOptions = useWatch({
		name: 'payments.mollie.sandbox',
		defaultValue: paymentsData?.mollie?.sandbox,
		control,
	});

	return (
		<SingleComponentsWrapper title={__('Mollie', 'learning-management-system')}>
			<FormControlTwoCol>
				<Stack direction="row">
					<FormLabel minW="160px" mb={0}>
						{__('Enable', 'learning-management-system')}
						<ToolTip
							label={__(
								'Toggle to activate Mollie payment services, offering secure and seamless payment solutions for your customers.',
								'learning-management-system',
							)}
						/>
					</FormLabel>
					<Controller
						name="payments.mollie.enable"
						render={({ field }) => (
							<Switch
								{...field}
								defaultChecked={paymentsData?.mollie?.enable}
							/>
						)}
					/>
				</Stack>
			</FormControlTwoCol>

			<Collapse in={showMollieOptions} animateOpacity style={{ width: '100%' }}>
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
						<FormLabel m={0} minW="160px">
							{__('Title', 'learning-management-system')}
						</FormLabel>
						<Input
							type="text"
							{...register('payments.mollie.title')}
							defaultValue={paymentsData?.mollie?.title}
						/>
					</FormControlTwoCol>

					<FormControlTwoCol>
						<FormLabel m={0} minW="160px">
							{__('Description', 'learning-management-system')}
						</FormLabel>
						<Textarea
							bg="white"
							{...register('payments.mollie.description')}
							defaultValue={paymentsData?.mollie?.description}
						/>
					</FormControlTwoCol>

					<FormControlTwoCol>
						<Stack direction="row">
							<FormLabel m={0} minW="160px">
								{__('Sandbox', 'learning-management-system')}
								<ToolTip
									label={__(
										'Mollie payment sandbox can be used to test payments.',
										'learning-management-system',
									)}
								/>
							</FormLabel>
							<Controller
								name="payments.mollie.sandbox"
								render={({ field }) => (
									<Switch
										{...field}
										defaultChecked={paymentsData?.mollie?.sandbox}
									/>
								)}
							/>
						</Stack>
					</FormControlTwoCol>
					<Collapse in={showMollieSandBoxOptions} style={{ width: '100%' }}>
						<Stack direction="column" spacing="6">
							<FormControlTwoCol>
								<FormLabel m={0} minW="160px">
									{__('Test Api Key', 'learning-management-system')}
									<ToolTip
										label={__(
											'Get your API credentials from mollie.',
											'learning-management-system',
										)}
									/>
								</FormLabel>
								<InputGroup>
									<Input
										type={show.liveSandboxKey ? 'text' : 'password'}
										{...register('payments.mollie.test_api_key')}
										defaultValue={paymentsData?.mollie?.test_api_key}
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

					<Collapse in={!showMollieSandBoxOptions} style={{ width: '100%' }}>
						<Stack direction="column" spacing="6">
							<FormControlTwoCol>
								<FormLabel m={0} minW="160px">
									{__('Live Api Key', 'learning-management-system')}
									<ToolTip
										label={__(
											'Get your API credentials from mollie.',
											'learning-management-system',
										)}
									/>
								</FormLabel>
								<InputGroup>
									<Input
										type={show.liveSandboxKey ? 'text' : 'password'}
										{...register('payments.mollie.live_api_key')}
										defaultValue={paymentsData?.mollie?.live_api_key}
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

					{paymentsData?.mollie?.error_message && (
						<Alert status="error">
							<AlertIcon />
							<AlertDescription>
								<HStack spacing="1" color="gray.600">
									<Text>{paymentsData?.mollie?.error_message}</Text>
									<Link
										href={
											'https://www.mollie.com/dashboard/developers/api-keys'
										}
										target="_blank"
										textDecoration="underline"
										fontWeight="semibold"
									>
										<Text>
											{__('click here', 'learning-management-system')}
										</Text>
									</Link>
								</HStack>
							</AlertDescription>
						</Alert>
					)}
				</VStack>
			</Collapse>
		</SingleComponentsWrapper>
	);
};

export default MollieGlobalSettings;
