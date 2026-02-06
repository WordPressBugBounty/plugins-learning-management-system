import {
	Box,
	FormLabel,
	NumberDecrementStepper,
	NumberIncrementStepper,
	NumberInput,
	NumberInputField,
	NumberInputStepper,
	Radio,
	RadioGroup,
	Stack,
	Text,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Controller, useFormContext } from 'react-hook-form';
import FormControlTwoCol from '../../../../../../../assets/js/back-end/components/common/FormControlTwoCol';

interface StandardPricingProps {
	nestIndex: number; // The index of the pricing tier in the main array
	isFree: boolean;
	isGroupCoursesEnabled: boolean;
}

const StandardPricing: React.FC<StandardPricingProps> = ({
	nestIndex,
	isFree,
	isGroupCoursesEnabled,
}) => {
	const { control } = useFormContext();

	return (
		<Stack spacing={4}>
			<FormControlTwoCol>
				<FormLabel>
					{__('Group Pricing', 'learning-management-system')}
				</FormLabel>

				{/* Pricing Type Radio */}
				<Controller
					name={`group_courses.pricing_tiers.${nestIndex}.pricing_type`}
					control={control}
					defaultValue="one_time"
					render={({ field }) => (
						<RadioGroup {...field}>
							<Stack spacing={3}>
								<Radio value="one_time" isDisabled={isFree}>
									{__('One Time', 'learning-management-system')}
								</Radio>

								{field.value === 'one_time' && (
									<Box borderLeft="1px solid #ccc" pl="3" ml="5">
										<Stack spacing={4}>
											<FormControlTwoCol>
												<FormLabel>
													{__('Regular Price', 'learning-management-system')}
												</FormLabel>
												<Controller
													name={`group_courses.pricing_tiers.${nestIndex}.regular_price`}
													control={control}
													rules={{
														required:
															isGroupCoursesEnabled && !isFree
																? __(
																		'Regular Price is required',
																		'learning-management-system',
																	)
																: false,
													}}
													defaultValue=""
													render={({
														field: priceField,
														fieldState: { error },
													}) => (
														<Stack spacing={1} w="full">
															<NumberInput
																{...priceField}
																w="full"
																min={0}
																isDisabled={isFree}
																isInvalid={!!error}
															>
																<NumberInputField
																	borderRadius="sm"
																	shadow="input"
																	placeholder="0"
																/>
																<NumberInputStepper>
																	<NumberIncrementStepper />
																	<NumberDecrementStepper />
																</NumberInputStepper>
															</NumberInput>
															{error && (
																<Text fontSize="xs" color="red.500">
																	{error.message}
																</Text>
															)}
														</Stack>
													)}
												/>
											</FormControlTwoCol>

											<FormControlTwoCol>
												<FormLabel>
													{__('Sale Price', 'learning-management-system')}
												</FormLabel>
												<Controller
													name={`group_courses.pricing_tiers.${nestIndex}.sale_price`}
													control={control}
													defaultValue=""
													render={({ field: salePriceField }) => (
														<NumberInput
															{...salePriceField}
															w="full"
															min={0}
															isDisabled={isFree}
														>
															<NumberInputField
																borderRadius="sm"
																shadow="input"
																placeholder="0"
															/>
															<NumberInputStepper>
																<NumberIncrementStepper />
																<NumberDecrementStepper />
															</NumberInputStepper>
														</NumberInput>
													)}
												/>
											</FormControlTwoCol>
										</Stack>
									</Box>
								)}
							</Stack>
						</RadioGroup>
					)}
				/>
			</FormControlTwoCol>
		</Stack>
	);
};

export default StandardPricing;
