import {
	Box,
	Button,
	ButtonGroup,
	FormLabel,
	IconButton,
	NumberDecrementStepper,
	NumberIncrementStepper,
	NumberInput,
	NumberInputField,
	NumberInputStepper,
	Stack,
	Text,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useEffect } from 'react';
import { Controller, useFieldArray, useFormContext } from 'react-hook-form';
import { BiPlus, BiX } from 'react-icons/bi';

interface TieredPricingProps {
	nestIndex: number; // The index of the pricing tier in the main array
	isFree: boolean;
	isGroupCoursesEnabled: boolean;
}

const TieredPricing: React.FC<TieredPricingProps> = ({
	nestIndex,
	isFree,
	isGroupCoursesEnabled,
}) => {
	const { control } = useFormContext();

	const {
		fields: tierFields,
		append: appendTier,
		remove: removeTier,
	} = useFieldArray({
		control,
		name: `group_courses.pricing_tiers.${nestIndex}.tiers`,
	});

	const addPriceTier = () => {
		appendTier({
			from: 0,
			to: 0,
			per_seat_price: '',
		});
	};

	// Add one tier row by default if list is empty
	useEffect(() => {
		if (tierFields.length === 0) {
			appendTier({
				from: 0,
				to: 0,
				per_seat_price: '',
			});
		}
	}, [tierFields.length, appendTier]);

	return (
		<Stack spacing={4}>
			{tierFields.map((tierField, tierIndex) => (
				<Stack key={tierField.id} direction="row" spacing={3} align="end">
					<Box flex={1}>
						<FormLabel fontSize="sm" mb={2}>
							{__('From', 'learning-management-system')}
						</FormLabel>
						<Controller
							name={`group_courses.pricing_tiers.${nestIndex}.tiers.${tierIndex}.from`}
							control={control}
							rules={{
								required:
									isGroupCoursesEnabled && !isFree
										? __('Required', 'learning-management-system')
										: false,
								min:
									isGroupCoursesEnabled && !isFree
										? {
												value: 0,
												message: __('Min 0', 'learning-management-system'),
											}
										: undefined,
							}}
							defaultValue={0}
							render={({ field, fieldState: { error } }) => (
								<Stack spacing={1}>
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
									</NumberInput>
									{error && (
										<Text fontSize="xs" color="red.500">
											{error.message}
										</Text>
									)}
								</Stack>
							)}
						/>
					</Box>

					<Box flex={1}>
						<FormLabel fontSize="sm" mb={2}>
							{__('To', 'learning-management-system')}
						</FormLabel>
						<Controller
							name={`group_courses.pricing_tiers.${nestIndex}.tiers.${tierIndex}.to`}
							control={control}
							rules={{
								required:
									isGroupCoursesEnabled && !isFree
										? __('Required', 'learning-management-system')
										: false,
								min:
									isGroupCoursesEnabled && !isFree
										? {
												value: 0,
												message: __('Min 0', 'learning-management-system'),
											}
										: undefined,
							}}
							defaultValue={0}
							render={({ field, fieldState: { error } }) => (
								<Stack spacing={1}>
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
											placeholder="e.g., 25"
										/>
									</NumberInput>
									{error && (
										<Text fontSize="xs" color="red.500">
											{error.message}
										</Text>
									)}
								</Stack>
							)}
						/>
					</Box>

					<Box flex={1}>
						<FormLabel fontSize="sm" mb={2}>
							{__('Per Seat Price', 'learning-management-system')}
						</FormLabel>
						<Controller
							name={`group_courses.pricing_tiers.${nestIndex}.tiers.${tierIndex}.per_seat_price`}
							control={control}
							rules={{
								required:
									isGroupCoursesEnabled && !isFree
										? __('Required', 'learning-management-system')
										: false,
							}}
							defaultValue=""
							render={({ field, fieldState: { error } }) => (
								<Stack spacing={1}>
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
											placeholder="Price"
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
					</Box>

					<IconButton
						aria-label="Remove tier"
						icon={<BiX fontSize="20px" />}
						size="md"
						variant="outline"
						onClick={() => removeTier(tierIndex)}
						isDisabled={isFree}
						_hover={{
							bg: 'red.50',
							borderColor: 'red.500',
							color: 'red.500',
						}}
					/>
				</Stack>
			))}

			<ButtonGroup justifyContent="center">
				<Button
					onClick={addPriceTier}
					variant="outline"
					size="sm"
					isDisabled={isFree}
					leftIcon={<BiPlus />}
				>
					{__('Add Tier', 'learning-management-system')}
				</Button>
			</ButtonGroup>
		</Stack>
	);
};

export default TieredPricing;
