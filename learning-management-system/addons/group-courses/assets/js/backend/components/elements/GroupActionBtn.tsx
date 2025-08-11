import { Button, Tooltip, useBreakpointValue } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { UseFormReturn } from 'react-hook-form';
import { deepMerge } from '../../../../../../../assets/js/back-end/utils/utils';

interface Props {
	methods: UseFormReturn<any>;
	isLoading: boolean;
	onSubmit: (arg1: any, arg2?: 'publish' | 'draft') => void;
	groupStatus?: string;
	orderInfo?: {
		id: number;
		status: string;
	} | null;
}

const GroupActionBtn: React.FC<Props> = (props) => {
	const { methods, isLoading, onSubmit, groupStatus, orderInfo } = props;
	const buttonSize = useBreakpointValue(['sm', 'md']);

	const isGroupPublished = () => {
		if (groupStatus && groupStatus === 'publish') {
			return true;
		} else {
			return false;
		}
	};

	const isGroupDrafted = () => {
		if (groupStatus && groupStatus === 'draft') {
			return true;
		} else {
			return false;
		}
	};

	const isOrderIncomplete = () => {
		return orderInfo && orderInfo.status !== 'completed';
	};

	const shouldDisablePublish = () => {
		return isOrderIncomplete() && groupStatus === 'draft';
	};

	return (
		<>
			<Tooltip
				label={
					shouldDisablePublish()
						? __(
								'Complete the associated order to publish this group',
								'learning-management-system',
							)
						: ''
				}
				isDisabled={!shouldDisablePublish()}
			>
				<Button
					size={buttonSize}
					colorScheme="primary"
					isLoading={isLoading}
					isDisabled={shouldDisablePublish() || false}
					onClick={methods.handleSubmit((data: any) => {
						onSubmit(
							deepMerge({
								...data,
								emails: data.emails.map((email: any) => email?.label),
								status: 'publish',
							}),
						);
					})}
				>
					{isGroupPublished()
						? __('Update', 'learning-management-system')
						: __('Publish', 'learning-management-system')}
				</Button>
			</Tooltip>
			<Button
				variant="outline"
				colorScheme="primary"
				isLoading={isLoading}
				onClick={methods.handleSubmit((data: any) => {
					onSubmit(
						deepMerge({
							...data,
							emails: data.emails.map((email: any) => email?.label),
							status: 'draft',
						}),
					);
				})}
			>
				{isGroupDrafted()
					? __('Save To Draft', 'learning-management-system')
					: isGroupPublished()
						? __('Switch To Draft', 'learning-management-system')
						: __('Save To Draft', 'learning-management-system')}
			</Button>
		</>
	);
};

export default GroupActionBtn;
