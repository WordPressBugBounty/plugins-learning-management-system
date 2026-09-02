import {
	Box,
	FormLabel,
	NumberDecrementStepper,
	NumberIncrementStepper,
	NumberInput,
	NumberInputField,
	NumberInputStepper,
	Stack,
	Text,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Controller, useFormContext } from 'react-hook-form';
import FormControlTwoCol from '../../../../../../../assets/js/back-end/components/common/FormControlTwoCol';
import ToolTip from '../../../../../../../assets/js/back-end/screens/settings/components/ToolTip';
import { CustomChakraRadio } from '../../../../../../../assets/js/getting-started/components/CustomChakraRadio';

interface SeatConfigurationProps {
	nestIndex: number;
	seatModel: 'fixed' | 'variable';
	isFree: boolean;
	isGroupCoursesEnabled: boolean;
}

const SeatConfiguration: React.FC<SeatConfigurationProps> = ({
	nestIndex,
	seatModel,
	isFree,
	isGroupCoursesEnabled,
}) => {
	const { control } = useFormContext();

	// Fixed Seats Model
	if (seatModel === 'fixed') {
		return (
			<FormControlTwoCol>
				<FormLabel>
					{__('Group Size', 'learning-management-system')}
					<ToolTip
						label={__(
							'Number of seats in this group',
							'learning-management-system',
						)}
					/>
				</FormLabel>
				<Controller
					name={`group_courses.pricing_tiers.${nestIndex}.group_size`}
					control={control}
					rules={{
						min:
							isGroupCoursesEnabled && !isFree
								? {
										value: 1,
										message: __(
											'Group size must be at least 1',
											'learning-management-system',
										),
									}
								: undefined,
						required:
							isGroupCoursesEnabled && !isFree
								? __('Group size is required', 'learning-management-system')
								: false,
					}}
					defaultValue={0}
					render={({ field, fieldState: { error } }) => (
						<Stack spacing={1} w="full">
							<NumberInput
								{...field}
								w="full"
								min={0}
								isDisabled={isFree}
								isInvalid={!!error}
							>
								<NumberInputField
									borderRadius="sm"
									shadow="input"
									placeholder="e.g., 10"
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
		);
	}

	// Variable Seats Model
	return (
		<>
			<Stack direction={['column', 'column', 'row']} spacing={8}>
				<FormControlTwoCol flex={1}>
					<FormLabel>
						{__('Minimum Seats', 'learning-management-system')}
						<ToolTip
							label={__(
								'Minimum number of seats that can be purchased',
								'learning-management-system',
							)}
						/>
					</FormLabel>
					<Controller
						name={`group_courses.pricing_tiers.${nestIndex}.min_seats`}
						control={control}
						rules={{
							min:
								isGroupCoursesEnabled && !isFree
									? {
											value: 1,
											message: __(
												'Minimum seats must be at least 1',
												'learning-management-system',
											),
										}
									: undefined,
							required:
								isGroupCoursesEnabled && !isFree
									? __(
											'Minimum seats is required',
											'learning-management-system',
										)
									: false,
						}}
						defaultValue={0}
						render={({ field, fieldState: { error } }) => (
							<Stack spacing={1} w="full">
								<NumberInput
									{...field}
									w="full"
									min={0}
									isDisabled={isFree}
									isInvalid={!!error}
								>
									<NumberInputField
										borderRadius="sm"
										shadow="input"
										placeholder="e.g., 5"
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

				<FormControlTwoCol flex={1}>
					<FormLabel>
						{__('Maximum Seats', 'learning-management-system')}
						<ToolTip
							label={__(
								'Maximum number of seats that can be purchased',
								'learning-management-system',
							)}
						/>
					</FormLabel>
					<Controller
						name={`group_courses.pricing_tiers.${nestIndex}.max_seats`}
						control={control}
						rules={{
							min:
								isGroupCoursesEnabled && !isFree
									? {
											value: 1,
											message: __(
												'Maximum seats must be at least 1',
												'learning-management-system',
											),
										}
									: undefined,
							required:
								isGroupCoursesEnabled && !isFree
									? __(
											'Maximum seats is required',
											'learning-management-system',
										)
									: false,
						}}
						defaultValue={0}
						render={({ field, fieldState: { error } }) => (
							<Stack spacing={1} w="full">
								<NumberInput
									{...field}
									w="full"
									min={0}
									isDisabled={isFree}
									isInvalid={!!error}
								>
									<NumberInputField
										borderRadius="sm"
										shadow="input"
										placeholder="e.g., 50"
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
			</Stack>

			{/* Pricing Model Toggle */}
			<FormControlTwoCol>
				<FormLabel>
					{__('Pricing Model', 'learning-management-system')}
				</FormLabel>
				<Box>
					<CustomChakraRadio
						name={`group_courses.pricing_tiers.${nestIndex}.pricing_model`}
						options={[
							{
								value: 'per_seat',
								label: __('Per Seat', 'learning-management-system'),
							},
							{
								value: 'tiered',
								label: __('Create Tiers', 'learning-management-system'),
							},
						]}
						defaultValue="per_seat"
						isDisabled={isFree}
					/>
				</Box>
			</FormControlTwoCol>
		</>
	);
};

export default SeatConfiguration;
