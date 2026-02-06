import {
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

interface SeatConfigurationProps {
	nestIndex: number;
	isFree: boolean;
	isGroupCoursesEnabled: boolean;
}

const SeatConfiguration: React.FC<SeatConfigurationProps> = ({
	nestIndex,
	isFree,
	isGroupCoursesEnabled,
}) => {
	const { control } = useFormContext();

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
};

export default SeatConfiguration;
