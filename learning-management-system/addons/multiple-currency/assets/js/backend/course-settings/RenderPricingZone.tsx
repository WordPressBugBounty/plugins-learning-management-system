import {
	Collapse,
	Divider,
	FormControl,
	FormLabel,
	NumberDecrementStepper,
	NumberIncrementStepper,
	NumberInput,
	NumberInputField,
	NumberInputStepper,
	Radio,
	RadioGroup,
	Stack,
	Switch,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Controller, useFormContext, useWatch } from 'react-hook-form';
import FormControlTwoCol from '../../../../../../assets/js/back-end/components/common/FormControlTwoCol';
import ToolTip from '../../../../../../assets/js/back-end/screens/settings/components/ToolTip';
import { decodeEntity } from '../../../../../../assets/js/back-end/utils/utils';
import { isAddonActive } from '../../../../../add-ons/api/addons';
import { ActivePricingZone } from '../../types/multiCurrency';

interface Props {
	zone: ActivePricingZone;
	zoneId: string;
}

const RenderPricingZone: React.FC<Props> = ({ zone, zoneId }) => {
	const { register, control } = useFormContext();

	const enabledWatch = useWatch({
		name: `multiple_currency.${zoneId}_key.enabled`,
		defaultValue: zone.enabled,
		control,
	});

	const priceTypeWatch = useWatch({
		name: `multiple_currency.${zoneId}_key.pricing_method`,
		defaultValue: zone.pricing_method || 'exchange_rate',
		control,
	});

	const groupPriceSection = () => {
		return (
			<>
				<FormLabel>{__('Group Price', 'learning-management-system')}</FormLabel>
				<Controller
					name={`multiple_currency.${zoneId}_key.group_price`}
					defaultValue={zone?.group_price || ''}
					render={({ field }) => (
						<NumberInput {...field} w="full" min={0}>
							<NumberInputField borderRadius="sm" shadow="input" />
							<NumberInputStepper>
								<NumberIncrementStepper />
								<NumberDecrementStepper />
							</NumberInputStepper>
						</NumberInput>
					)}
				/>
			</>
		);
	};

	return (
		<>
			<FormControlTwoCol key={zoneId} px="4">
				<FormLabel>
					{__('Enable ', 'learning-management-system')} {zone.title} (
					{decodeEntity(zone?.currency_symbol)})
					<ToolTip
						label={__(
							'Toggle to activate this pricing zone for the course. Once activated, you can set prices specifically for this course within this pricing zone.',
							'learning-management-system',
						)}
					/>
				</FormLabel>
				<Stack direction={'column'} gap={6}>
					<FormControl>
						<Switch
							{...register(`multiple_currency.${zoneId}_key.enabled`)}
							defaultChecked={zone.enabled}
						/>
					</FormControl>
					<Collapse in={enabledWatch} animateOpacity>
						<Stack direction={'column'}>
							<FormControl>
								<FormLabel>
									{__('Price Method', 'learning-management-system')}
									<ToolTip
										label={__(
											'Choose how prices are managed. "Calculate prices by the exchange rate" automatically converts prices based on exchange rates. "Set prices manually" allows you to define prices for each currency.',
											'learning-management-system',
										)}
									/>
								</FormLabel>
								<Controller
									control={control}
									name={`multiple_currency.${zoneId}_key.pricing_method`}
									render={({ field }) => (
										<RadioGroup {...field} defaultValue={priceTypeWatch}>
											<Stack spacing="3">
												<Radio value="exchange_rate">
													{__(
														'Calculate prices by the exchange rate.',
														'learning-management-system',
													)}
												</Radio>
												<Radio value="manual">
													{__(
														'Set prices manually.',
														'learning-management-system',
													)}
												</Radio>
											</Stack>
										</RadioGroup>
									)}
								/>
							</FormControl>
							{priceTypeWatch === 'manual' && (
								<Stack
									spacing="3"
									borderLeft="1px solid #ccc"
									pl="3"
									ml="3"
									mt="2"
								>
									<FormControlTwoCol>
										<FormLabel>
											{__('Regular Price', 'learning-management-system')}
										</FormLabel>
										<Controller
											name={`multiple_currency.${zoneId}_key.regular_price`}
											defaultValue={zone.regular_price || ''}
											render={({ field }) => (
												<NumberInput {...field} w="full" min={0}>
													<NumberInputField borderRadius="sm" shadow="input" />
													<NumberInputStepper>
														<NumberIncrementStepper />
														<NumberDecrementStepper />
													</NumberInputStepper>
												</NumberInput>
											)}
										/>
									</FormControlTwoCol>
									<FormControlTwoCol>
										<FormLabel>
											{__('Sale Price', 'learning-management-system')}
										</FormLabel>
										<Controller
											name={`multiple_currency.${zoneId}_key.sale_price`}
											defaultValue={zone.sale_price || ''}
											render={({ field }) => (
												<NumberInput {...field} w="full" min={0}>
													<NumberInputField borderRadius="sm" shadow="input" />
													<NumberInputStepper>
														<NumberIncrementStepper />
														<NumberDecrementStepper />
													</NumberInputStepper>
												</NumberInput>
											)}
										/>
									</FormControlTwoCol>

									{isAddonActive('group-courses') && (
										<FormControlTwoCol>{groupPriceSection()}</FormControlTwoCol>
									)}
								</Stack>
							)}
						</Stack>
					</Collapse>
				</Stack>
			</FormControlTwoCol>
			<Divider />
		</>
	);
};

export default RenderPricingZone;
