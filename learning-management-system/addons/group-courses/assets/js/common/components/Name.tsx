import {
	FormControl,
	FormErrorMessage,
	FormLabel,
	Input,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { useFormContext } from 'react-hook-form';

interface Props {
	defaultValue?: string;
}
const Name: React.FC<Props> = (props) => {
	const { defaultValue } = props;

	const {
		register,
		formState: { errors },
	} = useFormContext();
	return (
		<FormControl isInvalid={!!errors?.title}>
			<FormLabel>{__('Group Name', 'learning-management-system')}</FormLabel>
			<Input
				color={'gray.600'}
				defaultValue={defaultValue}
				placeholder={__('Enter group name', 'learning-management-system')}
				{...register('title', {
					required: __(
						'Please provide name for the group.',
						'learning-management-system',
					),
				})}
			/>
			<FormErrorMessage>{errors?.title?.message + ''}</FormErrorMessage>
		</FormControl>
	);
};

export default Name;
