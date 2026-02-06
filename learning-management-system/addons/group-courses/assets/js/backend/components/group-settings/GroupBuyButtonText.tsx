import { FormControl, FormLabel, Input } from '@chakra-ui/react';
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

	// Migration: Clean up obsolete {group_price} placeholder from saved settings
	// If the saved value contains the old placeholder, replace it with the new default
	let cleanedDefaultValue = defaultValue;
	if (defaultValue && defaultValue.includes('{group_price}')) {
		cleanedDefaultValue = __('Buy for Group', 'learning-management-system');
	}

	return (
		<FormControlTwoCol>
			<FormLabel>
				{__('Group Buy Button Text', 'learning-management-system')}
				<ToolTip
					label={__(
						'Customize the text displayed on the group buy button.',
						'learning-management-system',
					)}
				/>
			</FormLabel>
			<FormControl>
				<Input
					placeholder={__('Buy for Group', 'learning-management-system')}
					defaultValue={
						cleanedDefaultValue ||
						__('Buy for Group', 'learning-management-system')
					}
					{...register('group_buy_button_text')}
				/>
			</FormControl>
		</FormControlTwoCol>
	);
};

export default GroupBuyButtonText;
