import {
	Box,
	FormControl,
	FormErrorMessage,
	FormLabel,
	Icon,
	InputGroup,
	InputLeftAddon,
	NumberDecrementStepper,
	NumberIncrementStepper,
	NumberInput,
	NumberInputField,
	NumberInputStepper,
	Tooltip,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Controller, useFormContext } from 'react-hook-form';
import { BiInfoCircle } from 'react-icons/bi';
import { infoIconStyles } from '../../../../../../assets/js/back-end/config/styles';
import localized from '../../../../../../assets/js/back-end/utils/global';

interface Props {
	defaultValue?: number;
}

const ExchangeRate: React.FC<Props> = (props) => {
	const { defaultValue } = props;
	const {
		formState: { errors },
	} = useFormContext();
	return (
		<>
			<FormControl isInvalid={!!errors?.exchange_rate}>
				<FormLabel>
					{__('Exchange Rate', 'learning-management-system')}{' '}
					<Tooltip
						label={__(
							'Enter the exchange rate manually.',
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
				<Controller
					name="exchange_rate"
					defaultValue={defaultValue || undefined}
					rules={{
						required: __(
							'Pricing zone exchange rate must be a positive number.',
							'learning-management-system',
						),
					}}
					render={({ field }) => (
						<InputGroup display="flex" flexDirection="row">
							<InputLeftAddon>
								{__(
									`1 ${localized.currency.code} =`,
									'learning-management-system',
								)}
							</InputLeftAddon>
							<NumberInput {...field} w="100%">
								<NumberInputField rounded="sm" />
								<NumberInputStepper>
									<NumberIncrementStepper />
									<NumberDecrementStepper />
								</NumberInputStepper>
							</NumberInput>
						</InputGroup>
					)}
				/>

				<FormErrorMessage>
					{errors?.exchange_rate && errors?.exchange_rate?.message?.toString()}
				</FormErrorMessage>
			</FormControl>
		</>
	);
};

export default ExchangeRate;
