import { FormControl, FormLabel, Input, Stack } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { useFormContext } from 'react-hook-form';

interface Props {
	defaultValue?: string;
}

const Name: React.FC<Props> = (props) => {
	const { defaultValue } = props;

	const { register } = useFormContext();

	return (
		<FormControl>
			<Stack direction={'row'} justify={'space-between'}>
				<FormLabel>
					{__('Certificate Name', 'learning-management-system')}
				</FormLabel>
			</Stack>
			<Input
				defaultValue={defaultValue}
				placeholder={__('Your Certificate Name', 'learning-management-system')}
				{...register('name')}
			/>
		</FormControl>
	);
};

export default Name;
