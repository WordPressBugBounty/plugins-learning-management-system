import { FormControl, FormLabel, Input } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { useFormContext } from 'react-hook-form';

interface Props {
	defaultValue?: string;
}
const Title: React.FC<Props> = (props) => {
	const { defaultValue } = props;

	const {
		register,
		formState: { errors },
	} = useFormContext();

	return (
		<FormControl isInvalid={!!errors?.name}>
			<FormLabel>{__('Title', 'learning-management-system')}</FormLabel>
			<Input
				defaultValue={defaultValue}
				placeholder={__('Your Meeting Title', 'learning-management-system')}
				{...register('summary', {
					required: __(
						'You must provide title for the google meet meeting.',
						'learning-management-system',
					),
				})}
			/>
		</FormControl>
	);
};

export default Title;
