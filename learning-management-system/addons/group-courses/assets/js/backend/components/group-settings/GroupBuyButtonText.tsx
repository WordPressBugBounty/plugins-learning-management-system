import { FormControl, FormLabel, Input, Text } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { useFormContext } from 'react-hook-form';
import FormControlTwoCol from '../../../../../../../assets/js/back-end/components/common/FormControlTwoCol';
import ToolTip from '../../../../../../../assets/js/back-end/screens/settings/components/ToolTip';

interface Props {
	defaultValue?: string;
}

const GroupBuyButtonText: React.FC<Props> = ({ defaultValue }) => {
	const { register } = useFormContext();

	return (
		<FormControlTwoCol>
			<FormLabel>
				{__('Group Buy Button Text', 'learning-management-system')}
				<ToolTip
					label={__(
						'Customize the text displayed on the group buy button. Use {group_price} as a placeholder for the group price.',
						'learning-management-system',
					)}
				/>
			</FormLabel>
			<FormControl>
				<Input
					placeholder={__(
						'Buy for Group at {group_price}',
						'learning-management-system',
					)}
					defaultValue={
						defaultValue ||
						__('Buy for Group at {group_price}', 'learning-management-system')
					}
					{...register('group_buy_button_text')}
				/>
				<Text fontSize="xs" color="gray.500" mt={2}>
					{__(
						'Available placeholder: {group_price}',
						'learning-management-system',
					)}
				</Text>
			</FormControl>
		</FormControlTwoCol>
	);
};

export default GroupBuyButtonText;
