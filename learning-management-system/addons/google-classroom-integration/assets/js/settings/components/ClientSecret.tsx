import {
	Box,
	FormLabel,
	Icon,
	Input,
	InputGroup,
	InputRightElement,
	Tooltip,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import { useFormContext } from 'react-hook-form';
import { BiInfoCircle, BiShow } from 'react-icons/bi';
import FormControlTwoCol from '../../../../../../assets/js/back-end/components/common/FormControlTwoCol';
import { infoIconStyles } from '../../../../../../assets/js/back-end/config/styles';

interface Props {
	defaultValue?: string;
}

const ClientSecret: React.FC<Props> = (props) => {
	const [show, setShow] = useState(false);
	const { defaultValue } = props;

	const {
		register,
		formState: { errors },
	} = useFormContext();
	return (
		<FormControlTwoCol>
			<FormLabel>
				{__('Client Secret', 'learning-management-system')}
				<Tooltip
					label={__(
						'Client Secret is required for accessing the google classroom data.',
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
					type={show ? 'text' : 'password'}
					defaultValue={defaultValue}
					{...register('client_secret')}
					placeholder="Client Secret"
					autoComplete="off"
				/>
				<InputRightElement>
					<BiShow
						color="black"
						cursor="pointer"
						onClick={() => setShow(!show)}
					/>
				</InputRightElement>
			</InputGroup>
		</FormControlTwoCol>
	);
};

export default ClientSecret;
