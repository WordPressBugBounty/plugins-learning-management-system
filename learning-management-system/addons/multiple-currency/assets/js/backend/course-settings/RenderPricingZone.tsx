import { isAddonActive } from '@addons/add-ons/api/addons';

import {
	Box,
	Collapse,
	Divider,
	Flex,
	FormControl,
	FormLabel,
	Icon,
	IconButton,
	NumberInput,
	NumberInputField,
	Radio,
	RadioGroup,
	SimpleGrid,
	Stack,
	Switch,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useEffect, useState } from 'react';
import { Controller, useFormContext, useWatch } from 'react-hook-form';
import { BiChevronDown, BiChevronUp } from 'react-icons/bi';
import FormControlTwoCol from '../../../../../../assets/js/back-end/components/common/FormControlTwoCol';
import ToolTip from '../../../../../../assets/js/back-end/screens/settings/components/ToolTip';
import { decodeEntity } from '../../../../../../assets/js/back-end/utils/utils';
import { ActivePricingZone } from '../../types/multiCurrency';

interface Props {
	zone: ActivePricingZone;
	zoneId: string;
	isCourseBundle?: boolean;
}

const PricingZonesData: React.FC<Props> = ({
	zone,
	zoneId,
	isCourseBundle,
}) => {
	const { register, control } = useFormContext();
	const [expanded, setExpanded] = useState<boolean>(true);

	const enabled = useWatch({
		name: `multiple_currency.${zoneId}_key.enabled`,
		defaultValue: zone.enabled,
		control,
	});

	const pricingMethod = useWatch({
		name: `multiple_currency.${zoneId}_key.pricing_method`,
		defaultValue: zone.pricing_method || 'exchange_rate',
		control,
	});

	useEffect(() => {
		setExpanded(enabled);
	}, [enabled]);

	return (
		<Box
			border="1px solid"
			borderColor={expanded ? 'primary.500' : 'gray.200'}
			borderRadius="base"
			pt="3"
			px="4"
			pb={expanded ? '4' : '3'}
		>
			<Flex align="center" justify="space-between">
				<FormLabel mb={0} fontSize="sm" fontWeight="medium">
					{zone.title} ({decodeEntity(zone.currency_symbol)})
					<ToolTip
						label={__(
							'Toggle to activate this pricing zone',
							'learning-management-system',
						)}
					/>
				</FormLabel>
				<Flex align="center">
					<Switch
						{...register(`multiple_currency.${zoneId}_key.enabled`)}
						defaultChecked={zone.enabled}
						mr={2}
					/>
					<IconButton
						variant={expanded ? 'solid' : 'link'}
						border="none"
						aria-label="Toggle"
						icon={
							<Icon
								as={expanded ? BiChevronUp : BiChevronDown}
								fontSize="2xl"
								fill={expanded ? 'primary.500' : 'black'}
							/>
						}
						onClick={() => setExpanded(!expanded)}
						size="sm"
						boxShadow="none"
						isDisabled={!enabled}
						borderRadius="base"
						bg={expanded ? 'gray.100' : 'transparent'}
						_hover={{ bg: expanded ? 'gray.100' : 'inherit' }}
					/>
				</Flex>
			</Flex>

			<Collapse in={expanded} animateOpacity>
				<Divider color="gray.200" my={3} />
				<Box>
					<FormControlTwoCol mb={4} alignItems="flex-start">
						<FormLabel mb={0} fontSize="sm" fontWeight="medium">
							{__('Pricing Method', 'learning-management-system')}
							<ToolTip
								label={__(
									'Choose how prices are managed',
									'learning-management-system',
								)}
							/>
						</FormLabel>
						<Box>
							<Controller
								control={control}
								name={`multiple_currency.${zoneId}_key.pricing_method`}
								render={({ field }) => (
									<RadioGroup {...field} defaultValue={pricingMethod}>
										<Stack spacing={3}>
											<Radio
												value="exchange_rate"
												fontWeight="normal"
												fontSize="sm"
											>
												{__(
													'Calculate prices by the exchange rate.',
													'learning-management-system',
												)}
											</Radio>
											<Radio value="manual" fontWeight="normal" fontSize="sm">
												{__(
													'Set prices manually.',
													'learning-management-system',
												)}
											</Radio>
										</Stack>
									</RadioGroup>
								)}
							/>
							{pricingMethod === 'manual' &&
								(() => {
									const columnCount =
										1 +
										(isCourseBundle ? 0 : 1) +
										(isAddonActive('group-courses') ? 1 : 0);

									return (
										<SimpleGrid
											columns={{ base: 1, md: columnCount }}
											spacing={4}
											mt={4}
										>
											<FormControl>
												<FormLabel>
													{__('Regular Price', 'learning-management-system')}
												</FormLabel>
												<Controller
													name={`multiple_currency.${zoneId}_key.regular_price`}
													defaultValue={zone.regular_price || ''}
													render={({ field }) => (
														<NumberInput {...field} min={0}>
															<NumberInputField />
														</NumberInput>
													)}
												/>
											</FormControl>

											{!isCourseBundle && (
												<FormControl>
													<FormLabel>
														{__('Sale Price', 'learning-management-system')}
													</FormLabel>
													<Controller
														name={`multiple_currency.${zoneId}_key.sale_price`}
														defaultValue={zone.sale_price || ''}
														render={({ field }) => (
															<NumberInput {...field} min={0}>
																<NumberInputField />
															</NumberInput>
														)}
													/>
												</FormControl>
											)}

											{isAddonActive('group-courses') && (
												<FormControl>
													<FormLabel>
														{__('Group Price', 'learning-management-system')}
													</FormLabel>
													<Controller
														name={`multiple_currency.${zoneId}_key.group_price`}
														defaultValue={zone.group_price || ''}
														render={({ field }) => (
															<NumberInput {...field} min={0}>
																<NumberInputField />
															</NumberInput>
														)}
													/>
												</FormControl>
											)}
										</SimpleGrid>
									);
								})()}
						</Box>
					</FormControlTwoCol>
				</Box>
			</Collapse>
		</Box>
	);
};

export default PricingZonesData;
