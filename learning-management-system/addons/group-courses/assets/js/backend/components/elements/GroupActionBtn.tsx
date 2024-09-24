import { Button, useBreakpointValue } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { UseFormReturn } from 'react-hook-form';
import { deepMerge } from '../../../../../../../assets/js/back-end/utils/utils';

interface Props {
	methods: UseFormReturn<any>;
	isLoading: boolean;
	onSubmit: (arg1: any, arg2?: 'publish' | 'draft') => void;
	groupStatus?: string;
}

const GroupActionBtn: React.FC<Props> = (props) => {
	const { methods, isLoading, onSubmit, groupStatus } = props;
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

	return (
		<>
			<Button
				size={buttonSize}
				colorScheme="primary"
				isLoading={isLoading}
				onClick={methods.handleSubmit((data: any) => {
					onSubmit(
						deepMerge(
							{ status: 'publish' },
							{
								...data,
								emails: data.emails.map((email: any) => email?.label),
							},
						),
					);
				})}
			>
				{isGroupPublished()
					? __('Update', 'learning-management-system')
					: __('Publish', 'learning-management-system')}
			</Button>
			<Button
				variant="outline"
				colorScheme="primary"
				isLoading={isLoading}
				onClick={methods.handleSubmit((data: any) => {
					onSubmit(
						deepMerge(
							{ status: 'draft' },
							{
								...data,
								emails: data.emails.map((email: any) => email?.label),
							},
						),
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
