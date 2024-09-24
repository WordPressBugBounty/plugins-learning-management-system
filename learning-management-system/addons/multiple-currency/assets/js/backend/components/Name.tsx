import {
	Box,
	FormControl,
	FormErrorMessage,
	FormLabel,
	Icon,
	Input,
	Tooltip,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { useFormContext } from 'react-hook-form';
import { BiInfoCircle } from 'react-icons/bi';
import { infoIconStyles } from '../../../../../../assets/js/back-end/config/styles';

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
			<FormLabel>
				{__('Zone Name', 'learning-management-system')}
				<Tooltip
					label={__(
						'This is the name of the zone for your reference.',
						'learning-management-system',
					)}
					hasArrow
					fontSize="xs"
				>
					<Box as="span" sx={infoIconStyles}>
						<Icon as={BiInfoCircle} />
					</Box>
				</Tooltip>
			</FormLabel>
			<Input
				defaultValue={defaultValue}
				placeholder={__('Enter price zone name', 'learning-management-system')}
				{...register('title', {
					required: __(
						'Please provide name for the price zone.',
						'learning-management-system',
					),
				})}
			/>
			<FormErrorMessage>{errors?.title?.message + ''}</FormErrorMessage>
		</FormControl>
	);
};

export default Name;
