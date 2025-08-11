import { FormControl, FormLabel, Input, Text } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { useFormContext } from 'react-hook-form';
import FormControlTwoCol from '../../../../../../../assets/js/back-end/components/common/FormControlTwoCol';
import ToolTip from '../../../../../../../assets/js/back-end/screens/settings/components/ToolTip';

interface Props {
	defaultValue?: string;
}

const GroupBuyHelperText: React.FC<Props> = ({ defaultValue }) => {
	const { register } = useFormContext();

	return (
		<FormControlTwoCol>
			<FormLabel>
				{__('Group Buy Helper Text', 'learning-management-system')}
				<ToolTip
					label={__(
						'Add helper text that will appear below the group buy button. Use {group_size} as a placeholder for the maximum group size. If group size is 0, it will show "unlimited".',
						'learning-management-system',
					)}
				/>
			</FormLabel>
			<FormControl>
				<Input
					placeholder={__(
						'Perfect for teams up to {group_size} members',
						'learning-management-system',
					)}
					defaultValue={defaultValue || ''}
					{...register('group_buy_helper_text')}
				/>
				<Text fontSize="xs" color="gray.500" mt={2}>
					{__(
						'Available placeholder: {group_size} - Shows the group limit or "unlimited" if set to 0',
						'learning-management-system',
					)}
				</Text>
			</FormControl>
		</FormControlTwoCol>
	);
};

export default GroupBuyHelperText;
