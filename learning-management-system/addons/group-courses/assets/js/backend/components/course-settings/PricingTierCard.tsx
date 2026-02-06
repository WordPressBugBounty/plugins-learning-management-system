import { Box, Button, FormLabel, Input, Stack, Text } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Controller, useFormContext, useWatch } from 'react-hook-form';
import FormControlTwoCol from '../../../../../../../assets/js/back-end/components/common/FormControlTwoCol';
import ToolTip from '../../../../../../../assets/js/back-end/screens/settings/components/ToolTip';
import { CustomChakraRadio } from '../../../../../../../assets/js/getting-started/components/CustomChakraRadio';
import SeatConfiguration from './SeatConfiguration';
import StandardPricing from './StandardPricing';

interface PricingTierCardProps {
	index: number;
	onRemove: () => void;
	isFree: boolean;
}

const PricingTierCard: React.FC<PricingTierCardProps> = ({
	index,
	onRemove,
	isFree,
}) => {
	const { control } = useFormContext();

	const watchGroupCoursesEnabled = useWatch({
		name: 'group_courses.enabled',
		control,
	});

	const watchSeatModel = useWatch({
		name: `group_courses.pricing_tiers.${index}.seat_model`,
		control,
	});

	const watchGroupName = useWatch({
		name: `group_courses.pricing_tiers.${index}.group_name`,
		control,
	});

	return (
		<Box border="1px" borderColor="gray.200" borderRadius="md" p={6}>
			<Stack spacing={6}>
				{/* Card Header */}
				<Stack direction="row" justify="space-between" align="center">
					<Text fontSize="md" fontWeight="semibold" color="gray.700">
						{watchGroupName}
					</Text>
					<Button
						size="sm"
						variant="outline"
						colorScheme="red"
						onClick={onRemove}
					>
						{__('Remove', 'learning-management-system')}
					</Button>
				</Stack>

				{/* Seat Model Toggle - Shows Variable Seats as Pro feature */}
				<FormControlTwoCol>
					<FormLabel>
						{__('Seat Model', 'learning-management-system')}
					</FormLabel>
					<Box>
						<CustomChakraRadio
							name={`group_courses.pricing_tiers.${index}.seat_model`}
							options={[
								{
									value: 'fixed',
									label: __('Fixed Seats', 'learning-management-system'),
								},
								{
									value: 'variable',
									label: __('Variable Seats', 'learning-management-system'),
									isProText: true,
								},
							]}
							isDisabled={isFree}
						/>
					</Box>
				</FormControlTwoCol>

				{/* Group Name */}
				<FormControlTwoCol>
					<FormLabel>
						{__('Group Name', 'learning-management-system')}
						<ToolTip
							label={__(
								'Give this pricing tier a descriptive name (e.g., Small Team, Enterprise)',
								'learning-management-system',
							)}
						/>
					</FormLabel>
					<Controller
						name={`group_courses.pricing_tiers.${index}.group_name`}
						control={control}
						rules={{
							required:
								watchGroupCoursesEnabled && !isFree
									? __('Group Name is required', 'learning-management-system')
									: false,
						}}
						defaultValue=""
						render={({ field, fieldState: { error } }) => (
							<Stack spacing={1} w="full">
								<Input
									{...field}
									placeholder={__(
										'e.g., Small Team, Enterprise',
										'learning-management-system',
									)}
									isDisabled={isFree}
									isInvalid={!!error}
								/>
								{error && (
									<Text fontSize="xs" color="red.500">
										{error.message}
									</Text>
								)}
							</Stack>
						)}
					/>
				</FormControlTwoCol>

				{/* Seat Configuration - Only show for fixed seats */}
				{watchSeatModel === 'fixed' && (
					<SeatConfiguration
						nestIndex={index}
						isFree={isFree}
						isGroupCoursesEnabled={watchGroupCoursesEnabled}
					/>
				)}

				{/* Standard Pricing Section - Only show for fixed seats */}
				{watchSeatModel === 'fixed' && (
					<StandardPricing
						nestIndex={index}
						isFree={isFree}
						isGroupCoursesEnabled={watchGroupCoursesEnabled}
					/>
				)}
			</Stack>
		</Box>
	);
};

export default PricingTierCard;
