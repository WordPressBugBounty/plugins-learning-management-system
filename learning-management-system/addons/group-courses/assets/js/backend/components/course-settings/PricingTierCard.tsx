import { Box, Button, FormLabel, Input, Stack, Text } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useEffect } from 'react';
import { Controller, useFormContext, useWatch } from 'react-hook-form';
import FormControlTwoCol from '../../../../../../../assets/js/back-end/components/common/FormControlTwoCol';
import ToolTip from '../../../../../../../assets/js/back-end/screens/settings/components/ToolTip';
import { isProPlan } from '../../../../../../../assets/js/back-end/utils/utils';
import { CustomChakraRadio } from '../../../../../../../assets/js/getting-started/components/CustomChakraRadio';
import SeatConfiguration from './SeatConfiguration';
import StandardPricing from './StandardPricing';
import TieredPricing from './TieredPricing';

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
	const { control, setValue } = useFormContext();

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

	const watchPricingModel = useWatch({
		name: `group_courses.pricing_tiers.${index}.pricing_model`,
		control,
	});

	// Variable seats (and the tiered pricing that rides on them) are a paid
	// feature — CustomChakraRadio's isProText is only a badge, it does not
	// disable the option, so an unlicensed site is treated as fixed here.
	const effectiveSeatModel =
		isProPlan() && 'variable' === watchSeatModel ? 'variable' : 'fixed';

	// Initialize pricing_model when switching to variable seats
	useEffect(() => {
		if (watchSeatModel === 'variable' && !watchPricingModel) {
			setValue(
				`group_courses.pricing_tiers.${index}.pricing_model`,
				'per_seat',
				{ shouldDirty: true },
			);
		}
	}, [watchSeatModel, watchPricingModel, index, setValue]);

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

				{/* Seat Model Toggle */}
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
									isProText: !isProPlan(),
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

				{/* Seat Configuration (Inputs for Fixed/Variable) */}
				<SeatConfiguration
					nestIndex={index}
					seatModel={effectiveSeatModel}
					isFree={isFree}
					isGroupCoursesEnabled={watchGroupCoursesEnabled}
				/>

				{/* Standard Pricing Section */}
				{(effectiveSeatModel === 'fixed' ||
					(effectiveSeatModel === 'variable' &&
						watchPricingModel === 'per_seat')) && (
					<StandardPricing
						nestIndex={index}
						isFree={isFree}
						isVariableSeat={effectiveSeatModel === 'variable'}
						isGroupCoursesEnabled={watchGroupCoursesEnabled}
					/>
				)}

				{/* Tiered Pricing Section */}
				{effectiveSeatModel === 'variable' &&
					watchPricingModel === 'tiered' && (
						<TieredPricing
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
