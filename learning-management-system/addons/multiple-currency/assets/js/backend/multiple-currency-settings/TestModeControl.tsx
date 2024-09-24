import {
	Box,
	FormErrorMessage,
	FormLabel,
	Icon,
	Switch,
	Tooltip,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useEffect } from 'react';
import { useFormContext } from 'react-hook-form';
import { BiInfoCircle } from 'react-icons/bi';
import FormControlTwoCol from '../../../../../../assets/js/back-end/components/common/FormControlTwoCol';
import { infoIconStyles } from '../../../../../../assets/js/back-end/config/styles';
import { MultipleCurrencySettingsSchema } from '../../types/multiCurrency';

interface Props {
	defaultValue?: boolean;
	setTestModeWatch: (value: boolean) => void;
}

const TestModeControl: React.FC<Props> = (props) => {
	const { defaultValue, setTestModeWatch } = props;

	const {
		register,
		watch,
		formState: { errors },
	} = useFormContext<MultipleCurrencySettingsSchema>();

	const testMode = watch('test_mode.enabled');

	useEffect(() => {
		setTestModeWatch(testMode);
	}, [testMode, setTestModeWatch]);

	return (
		<>
			<FormControlTwoCol isInvalid={!!errors?.test_mode?.enabled}>
				<FormLabel>
					{__('Enable Test Mode', 'learning-management-system')}
					<Tooltip
						label={__(
							'Enable test mode to simulate pricing for a specific country.',
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
				<Switch
					defaultChecked={defaultValue || false}
					{...register('test_mode.enabled')}
				/>
				<FormErrorMessage>
					{errors?.test_mode?.enabled && errors?.test_mode?.message?.toString()}
				</FormErrorMessage>
			</FormControlTwoCol>
		</>
	);
};

export default TestModeControl;
