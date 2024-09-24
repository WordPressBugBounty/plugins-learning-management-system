import { Button, useBreakpointValue } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { UseFormReturn } from 'react-hook-form';
import { deepMerge } from '../../../../../../assets/js/back-end/utils/utils';

interface Props {
	methods: UseFormReturn<any>;
	isLoading: boolean;
	onSubmit: (arg1: any, arg2?: 'active' | 'inactive') => void;
	pricingZoneStatus?: string;
}

const PriceZoneActionBtn: React.FC<Props> = (props) => {
	const { methods, isLoading, onSubmit, pricingZoneStatus } = props;

	const buttonSize = useBreakpointValue(['sm', 'md']);

	const isActive = () => {
		return pricingZoneStatus && pricingZoneStatus === 'active';
	};

	const isInactive = () => {
		return pricingZoneStatus && pricingZoneStatus === 'inactive';
	};

	return (
		<>
			<Button
				size={buttonSize}
				colorScheme="primary"
				isLoading={isLoading}
				onClick={methods.handleSubmit((data: any) => {
					onSubmit(
						deepMerge(
							{ status: 'active' },
							{
								...data,
							},
						),
					);
				})}
			>
				{isActive()
					? __('Update', 'learning-management-system')
					: pricingZoneStatus
						? __('Activate', 'learning-management-system')
						: __('Create', 'learning-management-system')}
			</Button>
			<Button
				variant="outline"
				colorScheme="primary"
				isLoading={isLoading}
				onClick={methods.handleSubmit((data: any) => {
					onSubmit(
						deepMerge(
							{
								status: 'inactive',
							},
							{
								...data,
							},
						),
					);
				})}
			>
				{isInactive()
					? __('Save To Inactive', 'learning-management-system')
					: isActive()
						? __('Switch To Inactive', 'learning-management-system')
						: __('Save To Inactive', 'learning-management-system')}
			</Button>
		</>
	);
};

export default PriceZoneActionBtn;
