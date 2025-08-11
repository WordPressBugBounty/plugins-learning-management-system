import { useAddonsStore } from '@addons/add-ons/store/useAddons';
import {
	Center,
	Collapse,
	FormErrorMessage,
	FormLabel,
	NumberDecrementStepper,
	NumberIncrementStepper,
	NumberInput,
	NumberInputField,
	NumberInputStepper,
	Spinner,
	Stack,
	Switch,
	Text,
	useToast,
} from '@chakra-ui/react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { dispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import React, { useEffect, useState } from 'react';
import { Controller, useFormContext, useWatch } from 'react-hook-form';
import FormControlTwoCol from '../../../../../../../assets/js/back-end/components/common/FormControlTwoCol';
import { activateAddon } from '../../../../../../../assets/js/back-end/screens/add-ons/addons-api';
import { addAndRemoveMenuItem } from '../../../../../../../assets/js/back-end/screens/add-ons/api/addons';
import ToolTip from '../../../../../../../assets/js/back-end/screens/settings/components/ToolTip';
import { CourseDataMap } from '../../../../../../../assets/js/back-end/types/course';

interface Props {
	courseData?: CourseDataMap;
	isAddonActive?: boolean;
}

const GroupCoursesSetting: React.FC<Props> = ({
	courseData,
	isAddonActive: initialAddonActive = false,
}) => {
	const {
		control,
		setValue,
		formState: { errors },
	} = useFormContext();

	const toast = useToast();
	const queryClient = useQueryClient();

	const [isAddonActive, setIsAddonActive] = useState(initialAddonActive);
	const [isActivating, setIsActivating] = useState(false);

	const watchSellToGroups = useWatch({
		name: 'group_courses.enabled',
		control,
	});

	const watchGroupPrice = useWatch({
		name: 'group_courses.group_price',
		control,
	});

	const isAddonReallyActive = isAddonActive && !!courseData?.group_courses;
	const showLoadingSpinner =
		isAddonActive &&
		!courseData?.group_courses &&
		!isActivating &&
		watchSellToGroups;

	// Sync form values when courseData changes
	useEffect(() => {
		if (courseData?.group_courses) {
			setValue('group_courses.enabled', !!courseData.group_courses.enabled, {
				shouldDirty: true,
			});
			setValue(
				'group_courses.group_price',
				courseData.group_courses.group_price ?? '',
				{ shouldDirty: true },
			);
			setValue(
				'group_courses.max_group_size',
				courseData.group_courses.max_group_size ?? '',
				{ shouldDirty: true },
			);
		}
	}, [courseData, setValue]);

	const activateAddonMutation = useMutation({
		mutationFn: () => activateAddon('group-courses'),
		onSuccess: (data) => {
			setIsAddonActive(true);
			setIsActivating(false);
			setValue('group_courses.enabled', true, { shouldDirty: true });

			queryClient.invalidateQueries({ queryKey: ['allAddons'] });
			addAndRemoveMenuItem(data);
			if (courseData?.id) {
				queryClient.invalidateQueries({ queryKey: [`course${courseData.id}`] });
			}

			toast({
				title: __('Groups addon activated', 'learning-management-system'),
				status: 'success',
				isClosable: true,
			});
		},
		onError: () => {
			setIsActivating(false);
			setValue('group_courses.enabled', false, { shouldDirty: true });
			toast({
				title: __('Failed to activate addon', 'learning-management-system'),
				description: __(
					'Please try again or activate it from the Addons page.',
					'learning-management-system',
				),
				status: 'error',
				isClosable: true,
			});
		},
	});

	const handleSwitchChange = (e: React.ChangeEvent<HTMLInputElement>) => {
		const isChecked = e.target.checked;
		setValue('group_courses.enabled', isChecked, { shouldDirty: true });
		if (!isAddonReallyActive && isChecked) {
			setIsActivating(true);
			activateAddonMutation.mutate();
			dispatch(useAddonsStore).updateAddons('group-courses', true);
		}
	};

	const showOptions = isAddonReallyActive && !isActivating && watchSellToGroups;

	return (
		<Stack direction="column" spacing={8}>
			<FormControlTwoCol>
				<FormLabel>
					{__('Sell to Groups', 'learning-management-system')}
					{(!isAddonReallyActive || !watchSellToGroups) && (
						<ToolTip
							label={__(
								'Clicking this will activate the Groups addon.',
								'learning-management-system',
							)}
						/>
					)}
				</FormLabel>
				<Switch
					isChecked={watchSellToGroups}
					onChange={handleSwitchChange}
					isDisabled={activateAddonMutation.isPending || isActivating}
				/>
			</FormControlTwoCol>

			{(activateAddonMutation.isPending || isActivating) && (
				<Center py={10}>
					<Stack spacing={4} align="center">
						<Spinner size="lg" color="blue.500" thickness="4px" />
						<Text>
							{__('Activating Groups addon...', 'learning-management-system')}
						</Text>
					</Stack>
				</Center>
			)}

			{showLoadingSpinner && (
				<Center py={10}>
					<Stack spacing={4} align="center">
						<Spinner size="md" color="blue.500" />
						<Text>
							{__('Loading group settings...', 'learning-management-system')}
						</Text>
					</Stack>
				</Center>
			)}

			<Collapse in={showOptions} animateOpacity>
				<Stack direction="column" spacing={6}>
					<FormControlTwoCol>
						<FormLabel>
							{__('Group Price', 'learning-management-system')}
							<ToolTip
								label={__(
									'Set the price for enrolling as a group. This allows multiple students to enroll together at a discounted rate compared to individual enrollments.',
									'learning-management-system',
								)}
							/>
						</FormLabel>
						<Controller
							name="group_courses.group_price"
							control={control}
							defaultValue=""
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

					<Controller
						name="group_courses.max_group_size"
						control={control}
						defaultValue=""
						rules={{
							required:
								watchSellToGroups &&
								watchGroupPrice &&
								parseFloat(watchGroupPrice) > 0
									? __(
											'Group size is required when group price is set',
											'learning-management-system',
										)
									: false,
							min: {
								value: 0,
								message: __(
									'Group size cannot be negative',
									'learning-management-system',
								),
							},
						}}
						render={({ field, fieldState: { error } }) => (
							<FormControlTwoCol isInvalid={!!error}>
								<FormLabel>
									{__('Group Size', 'learning-management-system')}
									<ToolTip
										label={__(
											'Specify the maximum number of students that can enroll as part of a single group. Set to 0 for unlimited group size.',
											'learning-management-system',
										)}
									/>
								</FormLabel>
								<Stack spacing={2} w="full">
									<NumberInput {...field} w="full" min={0}>
										<NumberInputField borderRadius="sm" shadow="input" />
										<NumberInputStepper>
											<NumberIncrementStepper />
											<NumberDecrementStepper />
										</NumberInputStepper>
									</NumberInput>
									{error && (
										<FormErrorMessage>{error.message}</FormErrorMessage>
									)}
								</Stack>
							</FormControlTwoCol>
						)}
					/>
				</Stack>
			</Collapse>
		</Stack>
	);
};

export default GroupCoursesSetting;
