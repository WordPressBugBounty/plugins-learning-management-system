import {
	Collapse,
	FormErrorMessage,
	FormLabel,
	IconButton,
	Input,
	InputGroup,
	InputRightElement,
	Link,
	Switch,
	Text,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import { useFormContext } from 'react-hook-form';
import { BiHide, BiShow } from 'react-icons/bi';
import CustomAlert from '../../../../../../assets/js/back-end/components/common/CustomAlert';
import FormControlTwoCol from '../../../../../../assets/js/back-end/components/common/FormControlTwoCol';
import ToolTip from '../../../../../../assets/js/back-end/screens/settings/components/ToolTip';
import { MultipleCurrencySettingsSchema } from '../../types/multiCurrency';

interface Props {
	maxmind: MultipleCurrencySettingsSchema['maxmind'];
}

const MaxMind: React.FC<Props> = (props) => {
	const { maxmind } = props;
	const {
		register,
		watch,
		formState: { errors },
	} = useFormContext<MultipleCurrencySettingsSchema>();
	const [show, setShow] = useState({ license_key: false });

	const enabledWatch = watch('maxmind.enabled');

	return (
		<>
			<CustomAlert>
				<Text>
					{__(
						'The MaxMind Geolocation integration enables accurate country detection for providing localized currency support to the customers.',
						'learning-management-system',
					)}
					<Link
						color="primary.600"
						href="https://docs.masteriyo.com/free-addons/multiple-currency"
						isExternal
						ml="2"
					>
						{__('Learn more', 'learning-management-system')}
					</Link>
				</Text>
			</CustomAlert>
			<FormControlTwoCol isInvalid={!!errors?.maxmind?.enabled}>
				<FormLabel>
					{__('Enable Geolocation Integration', 'learning-management-system')}
					<ToolTip
						label={__(
							'Enable Geolocation integration for accurate country detection using MaxMind.',
							'learning-management-system',
						)}
					/>
				</FormLabel>
				<Switch
					defaultChecked={maxmind?.enabled || false}
					{...register('maxmind.enabled')}
				/>
				<FormErrorMessage>
					{errors?.maxmind?.enabled && errors?.maxmind?.message?.toString()}
				</FormErrorMessage>
			</FormControlTwoCol>
			<Collapse in={enabledWatch} animateOpacity>
				<FormControlTwoCol>
					<FormLabel minW="160px">
						{__('MaxMind License Key', 'learning-management-system')}
						<ToolTip
							label={__(
								'Enter the license key for MaxMind Geolocation services.',
								'learning-management-system',
							)}
						/>
					</FormLabel>
					<InputGroup>
						<Input
							type={show.license_key ? 'text' : 'password'}
							{...register('maxmind.license_key')}
							defaultValue={maxmind?.license_key}
						/>
						<InputRightElement>
							{!show.license_key ? (
								<IconButton
									onClick={() => setShow({ ...show, license_key: true })}
									size="lg"
									variant="unstyled"
									aria-label="Show license key."
									icon={<BiShow />}
								/>
							) : (
								<IconButton
									onClick={() => setShow({ ...show, license_key: false })}
									size="lg"
									variant="unstyled"
									aria-label="Hide license key."
									icon={<BiHide />}
								/>
							)}
						</InputRightElement>
					</InputGroup>
				</FormControlTwoCol>
			</Collapse>
		</>
	);
};

export default MaxMind;
