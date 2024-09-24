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
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import { Controller, useFormContext, useWatch } from 'react-hook-form';
import { BiHide, BiShow } from 'react-icons/bi';
import FormControlTwoCol from '../../../../../../assets/js/back-end/components/common/FormControlTwoCol';
import Select from '../../../../../../assets/js/back-end/components/common/Select';
import { reactSelectStyles } from '../../../../../../assets/js/back-end/config/styles';
import SingleComponentsWrapper from '../../../../../../assets/js/back-end/screens/settings/components/SingleComponentsWrapper';
import ToolTip from '../../../../../../assets/js/back-end/screens/settings/components/ToolTip';
import { LemonSqueezySettingMap } from '../../../../../../assets/js/back-end/types';

interface Props {
	lemon_squeezy_integration?: LemonSqueezySettingMap;
}

const UNENROLLMENT_STATUS_OPTIONS = [
	{
		value: 'refunded',
		label: __('Refunded', 'learning-management-system'),
	},
];

const LemonSqueezyIntegrationSetting: React.FC<Props> = ({
	lemon_squeezy_integration,
}) => {
	const { register, control } = useFormContext();

	const showLemonSqueezyIntegrationOptions = useWatch({
		name: 'payments.lemon_squeezy_integration.enable',
		defaultValue: lemon_squeezy_integration?.enable,
		control,
	});

	const { hasCopied, onCopy } = useClipboard(
		lemon_squeezy_integration?.webhook_url || '',
	);

	const [show, setShow] = useState({ apiKey: false });

	return (
		<SingleComponentsWrapper
			title={__('Lemon Squeezy Integration', 'learning-management-system')}
		>
			<Stack direction="column" spacing="6" w="full">
				<FormControlTwoCol>
					<Stack direction="row">
						<FormLabel minW="160px">
							{__('Enable', 'learning-management-system')}
							<ToolTip
								label={__(
									'Enable Lemon Squeezy Integration.',
									'learning-management-system',
								)}
							/>
						</FormLabel>
						<Controller
							name="payments.lemon_squeezy_integration.enable"
							render={({ field }) => (
								<Switch
									{...field}
									defaultChecked={lemon_squeezy_integration?.enable || false}
								/>
							)}
						/>
					</Stack>
				</FormControlTwoCol>

				<Collapse in={showLemonSqueezyIntegrationOptions}>
					<Stack direction="column" spacing="6">
						<FormControlTwoCol>
							<FormLabel minW="160px">
								{__('Title', 'learning-management-system')}
							</FormLabel>
							<Input
								type="text"
								{...register('payments.lemon_squeezy_integration.title')}
								defaultValue={lemon_squeezy_integration?.title}
							/>
						</FormControlTwoCol>

						<FormControlTwoCol>
							<FormLabel minW="160px">
								{__('Description', 'learning-management-system')}
							</FormLabel>
							<Textarea
								bg="white"
								{...register('payments.lemon_squeezy_integration.description')}
								defaultValue={lemon_squeezy_integration?.description}
							/>
						</FormControlTwoCol>
						<FormControlTwoCol>
							<FormLabel minW="160px">
								{__('API Key', 'learning-management-system')}
								<ToolTip
									label={__(
										'Get your API key from Lemon Squeezy.',
										'learning-management-system',
									)}
								/>
							</FormLabel>
							<InputGroup>
								<Input
									type={show.apiKey ? 'text' : 'password'}
									{...register('payments.lemon_squeezy_integration.api_key')}
									defaultValue={lemon_squeezy_integration?.api_key}
								/>
								<InputRightElement>
									{!show.apiKey ? (
										<IconButton
											onClick={() => setShow({ ...show, apiKey: true })}
											size="lg"
											variant="unstyled"
											aria-label="Show API key"
											icon={<BiShow />}
										/>
									) : (
										<IconButton
											onClick={() => setShow({ ...show, apiKey: false })}
											size="lg"
											variant="unstyled"
											aria-label="Hide API key"
											icon={<BiHide />}
										/>
									)}
								</InputRightElement>
							</InputGroup>
						</FormControlTwoCol>

						<FormControlTwoCol>
							<FormLabel minW="160px">
								{__('Store ID', 'learning-management-system')}
								<ToolTip
									label={__(
										'Get your store ID from Lemon Squeezy.',
										'learning-management-system',
									)}
								/>
							</FormLabel>
							<Input
								type="text"
								{...register('payments.lemon_squeezy_integration.store_id')}
								defaultValue={lemon_squeezy_integration?.store_id}
							/>
						</FormControlTwoCol>

						<FormControlTwoCol>
							<FormLabel>
								{__('Unenrollment Status', 'learning-management-system')}
								<ToolTip
									label={__(
										'List of Lemon Squeezy order status for which the students should be unenrolled.',
										'learning-management-system',
									)}
								/>
							</FormLabel>
							<Controller
								name="payments.lemon_squeezy_integration.unenrollment_status"
								control={control}
								defaultValue={UNENROLLMENT_STATUS_OPTIONS.map((status: any) => {
									return {
										value: status.value,
										label: status.label,
									};
								})}
								render={({ field: { onChange, value } }) => (
									<Select
										onChange={onChange}
										value={value}
										styles={reactSelectStyles}
										closeMenuOnSelect={false}
										isMulti
										isSearchable={false}
										options={UNENROLLMENT_STATUS_OPTIONS}
									/>
								)}
							/>
						</FormControlTwoCol>

						<FormControlTwoCol>
							<FormLabel minW="160px">
								{__('Webhook URL', 'learning-management-system')}
								<ToolTip
									label={__(
										'Add this webhook URL to your Lemon Squeezy webhook URL to verify payment status.',
										'learning-management-system',
									)}
								/>
							</FormLabel>
							<Flex mb={2}>
								<Input
									type="text"
									readOnly
									defaultValue={lemon_squeezy_integration?.webhook_url}
								/>
								<Button colorScheme="blue" onClick={onCopy} ml={2}>
									{hasCopied
										? __('Copied', 'learning-management-system')
										: __('Copy', 'learning-management-system')}
								</Button>
							</Flex>
						</FormControlTwoCol>

						<FormControlTwoCol>
							<FormLabel>
								{__('Webhook Secret', 'learning-management-system')}
								<ToolTip
									label={__(
										'A string used by Lemon Squeezy to sign requests for increased security.',
										'learning-management-system',
									)}
								/>
							</FormLabel>

							<Input
								defaultValue={lemon_squeezy_integration?.webhook_secret || ''}
								{...register(
									'payments.lemon_squeezy_integration.webhook_secret',
								)}
							/>
						</FormControlTwoCol>
					</Stack>
				</Collapse>
			</Stack>
		</SingleComponentsWrapper>
	);
};

export default LemonSqueezyIntegrationSetting;
