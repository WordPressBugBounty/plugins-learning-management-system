import {
	Box,
	FormLabel,
	Icon,
	Input,
	InputGroup,
	Tooltip,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { useFormContext } from 'react-hook-form';
import { BiInfoCircle } from 'react-icons/bi';
import FormControlTwoCol from '../../../../../../assets/js/back-end/components/common/FormControlTwoCol';
import { infoIconStyles } from '../../../../../../assets/js/back-end/config/styles';

interface Props {
	defaultValue?: string;
}

const ClientId: React.FC<Props> = (props) => {
	const { defaultValue } = props;
	const {
		register,
		formState: { errors },
	} = useFormContext();
	return (
		<FormControlTwoCol>
			<FormLabel>
				{__('Client ID', 'learning-management-system')}
				<Tooltip
					label={__(
						'The unique ID to your registered application, which is used to authorize requests to the google classroom API.',
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
			<InputGroup>
				<Input
					defaultValue={defaultValue}
					{...register('client_id')}
					placeholder=" Client Id"
					padding="2"
					autoComplete="off"
				/>
			</InputGroup>
		</FormControlTwoCol>
	);
};

export default ClientId;
