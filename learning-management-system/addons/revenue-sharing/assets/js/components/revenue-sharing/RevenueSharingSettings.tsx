import {
	Box,
	Collapse,
	Flex,
	FormLabel,
	Input,
	InputGroup,
	InputRightAddon,
	NumberDecrementStepper,
	NumberIncrementStepper,
	NumberInput,
	NumberInputField,
	NumberInputStepper,
	Radio,
	RadioGroup,
	Slider,
	SliderThumb,
	SliderTrack,
	Stack,
	Switch,
	Text,
	Textarea,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useEffect } from 'react';
import { Controller, useFormContext, useWatch } from 'react-hook-form';
import FormControlTwoCol from '../../../../../../assets/js/back-end/components/common/FormControlTwoCol';
import Select from '../../../../../../assets/js/back-end/components/common/Select';
import SingleComponentsWrapper from '../../../../../../assets/js/back-end/screens/settings/components/SingleComponentsWrapper';
import ToolTip from '../../../../../../assets/js/back-end/screens/settings/components/ToolTip';
import { RevenueSharingSettingMap } from '../../../../../../assets/js/back-end/types';

type Props = {
	data?: RevenueSharingSettingMap;
};

const PAYOUT_METHODS = [
	{ label: 'Paypal', value: 'paypal' },
	{ label: 'E-Check', value: 'e_check' },
	{ label: 'Bank Account', value: 'bank_transfer' },
];

const RevenueSharingSettings: React.FC<Props> = (props) => {
	const { register, getValues, setValue, control } = useFormContext();

	const admin_rate = useWatch({
		name: 'payments.revenue_sharing.admin_rate',
		defaultValue: props.data?.admin_rate || 0,
	});

	const watchRevenueSharingEnable = useWatch({
		name: 'payments.revenue_sharing.enable',
		defaultValue: props.data?.enable ?? false,
		control,
	});

	const watchFeeEnable = useWatch({
		name: 'payments.revenue_sharing.deductible_fee.enable',
		defaultValue: props.data?.deductible_fee?.enable ?? false,
		control,
	});

	const watchFeeType = useWatch({
		name: 'payments.revenue_sharing.deductible_fee.type',
		defaultValue: props.data?.deductible_fee?.type ?? 'percentage',
		control,
	});

	const watchPayoutMethods = useWatch({
		name: 'payments.revenue_sharing.withdraw.methods',
		defaultValue: props.data?.withdraw?.methods ?? [],
		control,
	});

	const currency = getValues()?.payments?.currency?.currency ?? 'USD';

	useEffect(() => {
		if (admin_rate) {
			setValue('payments.revenue_sharing.instructor_rate', 100 - admin_rate);
		}
	}, [admin_rate, setValue]);

	return (
		<SingleComponentsWrapper
			title={__('Revenue Sharing', 'learning-management-system')}
		>
			<Stack direction="column" spacing="6" width="full">
				<FormControlTwoCol>
					<FormLabel>{__('Enable', 'learning-management-system')}</FormLabel>
					<Switch
						defaultChecked={props.data?.enable}
						{...register('payments.revenue_sharing.enable')}
					/>
				</FormControlTwoCol>
				<Collapse in={watchRevenueSharingEnable} style={{ width: '100%' }}>
					<Stack direction="column" spacing="6" width="full">
						<FormControlTwoCol>
							<FormLabel>
								{__('Commission Rate', 'learning-management-system')}
							</FormLabel>

							<Controller
								control={control}
								name={'payments.revenue_sharing.admin_rate'}
								defaultValue={props.data?.admin_rate}
								render={({ field: { onChange, value } }) => {
									const adminPercent = value;
									const instructorPercent = 100 - value;

									return (
										<Box flex={1} position="relative">
											<Slider
												value={adminPercent}
												min={0}
												max={100}
												step={1}
												onChange={(val) => {
													onChange(val);
												}}
												focusThumbOnChange={false}
											>
												<SliderTrack bg="primary.500" height={1}></SliderTrack>
												<SliderThumb
													boxSize={4}
													bg="white"
													borderWidth={5}
													borderColor="primary.500"
												/>
											</Slider>

											<Flex
												justify="space-between"
												position="absolute"
												w="full"
												top={5}
											>
												<Flex alignItems="center" gap={1}>
													<Text
														fontSize="onboard-xs"
														fontWeight="onboard-medium"
													>
														{__('Admin', 'learning-management-system')}
													</Text>
													<Text
														fontSize="onboard-xs"
														fontWeight="onboard-regular"
													>
														({adminPercent}%)
													</Text>
												</Flex>
												<Flex alignItems="center" gap={1}>
													<Text
														fontSize="onboard-xs"
														fontWeight="onboard-medium"
													>
														{__('Instructor', 'learning-management-system')}
													</Text>
													<Text
														fontSize="onboard-xs"
														fontWeight="onboard-regular"
													>
														({instructorPercent}%)
													</Text>
												</Flex>
											</Flex>
										</Box>
									);
								}}
							/>
						</FormControlTwoCol>
						<FormControlTwoCol mt={2}>
							<FormLabel>
								{__('Enable Deductible Fee', 'learning-management-system')}
								<ToolTip
									label={__(
										'Enabling this deducts a fee from the sale price before commission distribution.',
										'learning-management-system',
									)}
								/>
							</FormLabel>
							<Switch
								defaultChecked={props.data?.deductible_fee.enable}
								{...register('payments.revenue_sharing.deductible_fee.enable')}
							/>
						</FormControlTwoCol>
						<Collapse in={watchFeeEnable}>
							<Stack direction="column" spacing="6">
								<FormControlTwoCol>
									<FormLabel>
										{__('Deductible Fee Name', 'learning-management-system')}
									</FormLabel>
									<Input
										defaultValue={props.data?.deductible_fee.name}
										{...register(
											'payments.revenue_sharing.deductible_fee.name',
										)}
									/>
								</FormControlTwoCol>
								<FormControlTwoCol>
									<FormLabel>
										{__('Deductible Fee Type', 'learning-management-system')}
									</FormLabel>
									<Controller
										name="payments.revenue_sharing.deductible_fee.type"
										defaultValue={props.data?.deductible_fee.type}
										render={({ field }) => (
											<RadioGroup
												defaultValue={field.value}
												onChange={field.onChange}
												display="flex"
												gap="2"
											>
												<Radio value="percentage">{__('Percentage')}</Radio>
												<Radio value="fixed">{__('Fixed')}</Radio>
											</RadioGroup>
										)}
									/>
								</FormControlTwoCol>
								<FormControlTwoCol>
									<FormLabel>
										{__('Deductible Fee Amount', 'learning-management-system')}
									</FormLabel>
									<Controller
										name={'payments.revenue_sharing.deductible_fee.amount'}
										defaultValue={props.data?.deductible_fee.amount}
										render={({ field }) => (
											<InputGroup>
												<NumberInput
													width="full"
													min={0}
													max={
														'percentage' === (watchFeeType ?? 'percentage')
															? 100
															: undefined
													}
													defaultValue={field.value}
													onChange={field.onChange}
												>
													<NumberInputField />
													<NumberInputStepper>
														<NumberIncrementStepper />
														<NumberDecrementStepper />
													</NumberInputStepper>
												</NumberInput>
												<InputRightAddon>
													{'percentage' === (watchFeeType ?? 'percentage')
														? '%'
														: currency}
												</InputRightAddon>
											</InputGroup>
										)}
									/>
								</FormControlTwoCol>
							</Stack>
						</Collapse>
						<FormControlTwoCol>
							<FormLabel>
								{__('Minimum Payout Amount', 'learning-management-system')}
								<ToolTip
									label={__(
										'Defines the minimum earnings required for instructor withdrawals.',
										'learning-management-system',
									)}
								/>
							</FormLabel>
							<Controller
								name={'payments.revenue_sharing.withdraw.min_amount'}
								defaultValue={props.data?.withdraw.min_amount}
								render={({ field }) => (
									<InputGroup>
										<NumberInput
											width="full"
											min={1}
											defaultValue={field.value}
											onChange={field.onChange}
										>
											<NumberInputField />
											<NumberInputStepper>
												<NumberIncrementStepper />
												<NumberDecrementStepper />
											</NumberInputStepper>
										</NumberInput>
										<InputRightAddon>{currency}</InputRightAddon>
									</InputGroup>
								)}
							/>
						</FormControlTwoCol>
						<FormControlTwoCol>
							<FormLabel>
								{__('Maturity Period', 'learning-management-system')}
								<ToolTip
									label={__(
										'Specifies the required number of days sales revenue must remain in the account before withdrawal is allowed.',
										'learning-management-system',
									)}
								/>
							</FormLabel>
							<Controller
								name={'payments.revenue_sharing.withdraw.maturity_period'}
								defaultValue={props.data?.withdraw.maturity_period}
								render={({ field }) => (
									<InputGroup>
										<NumberInput
											width="full"
											min={0}
											defaultValue={field.value}
											onChange={field.onChange}
										>
											<NumberInputField />
											<NumberInputStepper>
												<NumberIncrementStepper />
												<NumberDecrementStepper />
											</NumberInputStepper>
										</NumberInput>
										<InputRightAddon>
											{__('DAY', 'learning-management-system')}
										</InputRightAddon>
									</InputGroup>
								)}
							/>
						</FormControlTwoCol>
						<FormControlTwoCol>
							<FormLabel>
								{__('Payout Methods', 'learning-management-system')}
								<ToolTip
									label={__(
										'Select how instructors can request withdrawals.',
										'learning-management-system',
									)}
								/>
							</FormLabel>
							<Controller
								name={'payments.revenue_sharing.withdraw.methods'}
								defaultValue={props.data?.withdraw.methods ?? []}
								render={({ field }) => (
									<Select
										isMulti
										value={PAYOUT_METHODS.filter(
											(x) => field.value?.includes(x.value) ?? false,
										)}
										onChange={(x) => {
											field.onChange(x.map((y) => y.value));
										}}
										placeholder={__(
											'Select Payout Methods',
											'learning-management-system',
										)}
										options={PAYOUT_METHODS}
									/>
								)}
							/>
						</FormControlTwoCol>
						<Collapse in={watchPayoutMethods?.includes('bank_transfer')}>
							<FormControlTwoCol>
								<FormLabel>
									{__('Bank Instructions', 'learning-management-system')}
								</FormLabel>
								<Textarea
									defaultValue={props.data?.withdraw.bank_instruction}
									{...register(
										'payments.revenue_sharing.withdraw.bank_instructions',
									)}
									resize="vertical"
									rows={5}
								/>
							</FormControlTwoCol>
						</Collapse>
					</Stack>
				</Collapse>
			</Stack>
		</SingleComponentsWrapper>
	);
};

export default RevenueSharingSettings;
