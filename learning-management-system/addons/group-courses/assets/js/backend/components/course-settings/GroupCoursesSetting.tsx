import { useAddonsStore } from '@addons/add-ons/store/useAddons';
import {
	Alert,
	AlertIcon,
	Button,
	ButtonGroup,
	Center,
	Collapse,
	FormLabel,
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
import { useFieldArray, useFormContext, useWatch } from 'react-hook-form';
import { BiPlus } from 'react-icons/bi';
import FormControlTwoCol from '../../../../../../../assets/js/back-end/components/common/FormControlTwoCol';
import { ProText } from '../../../../../../../assets/js/back-end/components/common/pro/ProShowcaseComponent';
import { activateAddon } from '../../../../../../../assets/js/back-end/screens/add-ons/addons-api';
import { addAndRemoveMenuItem } from '../../../../../../../assets/js/back-end/screens/add-ons/api/addons';
import ToolTip from '../../../../../../../assets/js/back-end/screens/settings/components/ToolTip';
import { CourseDataMap } from '../../../../../../../assets/js/back-end/types/course';
import PricingTierCard from './PricingTierCard';

interface Props {
	courseData?: CourseDataMap;
	isAddonActive?: boolean;
	pricingType?: string;
}

interface PricingTier {
	id: string;
	seat_model: 'fixed' | 'variable';
	group_name: string;
	group_size?: number;
	min_seats?: number;
	max_seats?: number;
	pricing_model?: 'per_seat' | 'tiered';
	pricing_type: 'one_time' | 'recurring';
	regular_price: string;
	sale_price: string;
	tiers?: Array<{
		from: number;
		to: number;
		per_seat_price: string;
	}>;
}

const GroupCoursesSetting: React.FC<Props> = ({
	courseData,
	isAddonActive: initialAddonActive = false,
	pricingType = 'paid',
}) => {
	const { control, setValue, formState } = useFormContext();

	const toast = useToast();
	const queryClient = useQueryClient();

	const [isAddonActive, setIsAddonActive] = useState(initialAddonActive);
	const [isActivating, setIsActivating] = useState(false);

	const { fields, append, remove } = useFieldArray({
		control,
		name: 'group_courses.pricing_tiers',
	});

	const watchSellToGroups = useWatch({
		name: 'group_courses.enabled',
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
			const isEnabled = !!courseData.group_courses.enabled;
			setValue('group_courses.enabled', isEnabled, {
				shouldDirty: false,
			});

			// Handle pricing_tiers - either from new format or migrated from legacy
			const pricingTiers = courseData.group_courses.pricing_tiers;
			if (
				pricingTiers &&
				Array.isArray(pricingTiers) &&
				pricingTiers.length > 0
			) {
				setValue('group_courses.pricing_tiers', pricingTiers, {
					shouldDirty: false,
				});
			} else if (
				!pricingTiers &&
				(courseData.group_courses.group_price ||
					courseData.group_courses.max_group_size)
			) {
				// Migration: Convert legacy format to new format
				const migratedTier: PricingTier = {
					id: `tier_${Date.now()}`,
					seat_model: 'fixed',
					group_name: __('Group', 'learning-management-system'),
					group_size: parseInt(courseData.group_courses.max_group_size || '0'),
					pricing_type: 'one_time',
					regular_price: courseData.group_courses.group_price || '',
					sale_price: '',
				};
				setValue('group_courses.pricing_tiers', [migratedTier], {
					shouldDirty: false,
				});
			} else if (isEnabled) {
				const defaultTier: PricingTier = {
					id: `tier_${Date.now()}`,
					seat_model: 'fixed',
					group_name: '',
					group_size: 0,
					pricing_type: 'one_time',
					regular_price: '',
					sale_price: '',
				};
				setValue('group_courses.pricing_tiers', [defaultTier], {
					shouldDirty: false,
				});
			}
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

		if (isChecked && fields.length === 0) {
			const newTier: PricingTier = {
				id: `tier_${Date.now()}`,
				seat_model: 'fixed',
				group_name: '',
				group_size: 0,
				pricing_type: 'one_time',
				regular_price: '',
				sale_price: '',
			};
			append(newTier);
		}
	};

	const addNewPricingTier = () => {
		const newTier: PricingTier = {
			id: `tier_${Date.now()}`,
			seat_model: 'fixed',
			group_name: '',
			group_size: 0,
			pricing_type: 'one_time',
			regular_price: '',
			sale_price: '',
		};
		append(newTier);
	};

	const showOptions = isAddonReallyActive && !isActivating && watchSellToGroups;
	const isFree = pricingType === 'free';

	return (
		<Stack direction="column" spacing={8}>
			{isFree && (
				<Alert status="info">
					<AlertIcon />
					{__(
						'Group pricing is only available for paid courses. Set your course as "Paid" in the Pricing tab to enable these options.',
						'learning-management-system',
					)}
				</Alert>
			)}

			<FormControlTwoCol>
				<FormLabel>
					{__('Sell to Groups', 'learning-management-system')}
					{(!isAddonReallyActive || !watchSellToGroups) && !isFree && (
						<ToolTip
							label={__(
								'Clicking this will activate the Groups addon.',
								'learning-management-system',
							)}
						/>
					)}
					{isFree && (
						<ToolTip
							label={__(
								'Group selling is not available for free courses.',
								'learning-management-system',
							)}
						/>
					)}
				</FormLabel>
				<Switch
					isChecked={watchSellToGroups}
					onChange={handleSwitchChange}
					isDisabled={activateAddonMutation.isPending || isActivating || isFree}
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
					{/* Render all pricing tiers */}
					{fields.map((field, index) => (
						<PricingTierCard
							key={field.id}
							index={index}
							onRemove={() => remove(index)}
							isFree={isFree}
						/>
					))}

					{/* Add Another Group Pricing Button */}
					<ButtonGroup justifyContent="center">
						<Button
							onClick={fields?.length >= 1 ? undefined : addNewPricingTier}
							variant="outline"
							size="md"
							leftIcon={<BiPlus />}
						>
							{fields?.length >= 1
								? __('Add Another Group Pricing', 'learning-management-system')
								: __('Add Group Pricing', 'learning-management-system')}

							{fields?.length >= 1 && <ProText />}
						</Button>
					</ButtonGroup>
				</Stack>
			</Collapse>
		</Stack>
	);
};

export default GroupCoursesSetting;
