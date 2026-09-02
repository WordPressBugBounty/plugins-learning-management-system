import { Button, useBreakpointValue } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { UseFormReturn } from 'react-hook-form';
import { deepMerge } from '../../../../assets/js/back-end/utils/utils';
import { GoogleMeetSchema } from '../schemas';

interface Props {
	methods: UseFormReturn<GoogleMeetSchema>;
	isLoading?: boolean;
	onSubmit: (data: any) => void;
	type?: string;
	isDisabled?: boolean;
}

const GoogleMeetActionButton: React.FC<Props> = (props) => {
	const { methods, isLoading, onSubmit, type, isDisabled = false } = props;
	const buttonSize = useBreakpointValue(['sm', 'md']);

	return (
		<>
			<Button
				isDisabled={isDisabled}
				size={buttonSize}
				colorScheme="primary"
				isLoading={isLoading}
				onClick={methods.handleSubmit((data: any) => {
					onSubmit(deepMerge(data));
				})}
			>
				{type === 'edit'
					? __('Update Google Meeting', 'learning-management-system')
					: __('Add New Google Meeting', 'learning-management-system')}
			</Button>
		</>
	);
};

export default GoogleMeetActionButton;
