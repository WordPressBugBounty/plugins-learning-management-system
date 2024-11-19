import { FormErrorMessage, FormLabel, Switch } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useEffect } from 'react';
import { useFormContext } from 'react-hook-form';
import FormControlTwoCol from '../../../../../../assets/js/back-end/components/common/FormControlTwoCol';
import ToolTip from '../../../../../../assets/js/back-end/screens/settings/components/ToolTip';
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
					<ToolTip
						label={__(
							'Enable test mode to simulate pricing for a specific country.',
							'learning-management-system',
						)}
					/>
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
