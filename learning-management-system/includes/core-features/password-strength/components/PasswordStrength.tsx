import {
	Collapse,
	FormControl,
	FormLabel,
	Select,
	Slider,
	SliderFilledTrack,
	SliderThumb,
	SliderTrack,
	Stack,
	Switch,
	Text,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Controller, useFormContext, useWatch } from 'react-hook-form';
import FormControlTwoCol from '../../../../assets/js/back-end/components/common/FormControlTwoCol';
import { PasswordStrengthValue } from '../../../../assets/js/back-end/enums/Enum';
import SingleComponentsWrapper from '../../../../assets/js/back-end/screens/settings/components/SingleComponentsWrapper';
import ToolTip from '../../../../assets/js/back-end/screens/settings/components/ToolTip';
import {
	AdvancedSettingsMap,
	PasswordStrengthType,
} from '../../../../assets/js/back-end/types';

interface Props {
	advancedSetting: AdvancedSettingsMap;
	data: {
		enable: boolean;
		max_length: number;
		min_length: number;
		show_strength: boolean;
		strength: PasswordStrengthType;
	};
}

const PasswordStrength: React.FC<Props> = ({ advancedSetting, data }) => {
	const { register, control } = useFormContext();

	const minLength = useWatch({
		name: 'advance.password_strength.min_length',
		control,
		defaultValue: data?.min_length,
	});

	const maxLength = useWatch({
		name: 'advance.password_strength.max_length',
		control,
		defaultValue: data?.max_length,
	});

	const strength = useWatch({
		name: 'advance.password_strength.strength',
		control,
		defaultValue: data?.strength,
	});

	const strengthOptions = [
		{
			label: __('Very Low', 'learning-management-system'),
			value: PasswordStrengthValue.VeryLow,
		},
		{
			label: __('Low', 'learning-management-system'),
			value: PasswordStrengthValue.Low,
		},
		{
			label: __('Medium', 'learning-management-system'),
			value: PasswordStrengthValue.Medium,
		},
		{
			label: __('High', 'learning-management-system'),
			value: PasswordStrengthValue.High,
		},
	];

	let strengthInfo = '';
	switch (strength) {
		case PasswordStrengthValue.Low:
			strengthInfo = __(
				'Minimum one uppercase letter',
				'learning-management-system',
			);
			break;
		case PasswordStrengthValue.Medium:
			strengthInfo = __(
				'Minimum one uppercase letter and a number',
				'learning-management-system',
			);
			break;
		case PasswordStrengthValue.High:
			strengthInfo = __(
				'Minimum one uppercase letter, a number and a special character',
				'learning-management-system',
			);
			break;
		default:
			strengthInfo = '';
	}

	const showPasswordStrengthOptions = useWatch({
		name: 'advance.password_strength.enable',
		defaultValue: advancedSetting?.password_strength?.enable,
		control,
	});

	return (
		<SingleComponentsWrapper
			title={__('Password Strength', 'learning-management-system')}
		>
			{/* Enable Password Strength */}
			<FormControlTwoCol>
				<Stack direction={['column', 'row']}>
					<FormLabel minW="3xs">
						{__('Enable Password Strength Login', 'learning-management-system')}
						<ToolTip
							label={__(
								'Enable or disable the Password Strength on Login feature for your website. When enabled, users must meet the required password strength criteria to access their accounts securely.',
								'learning-management-system',
							)}
						/>
					</FormLabel>

					<Controller
						name="advance.password_strength.enable"
						render={({ field }) => (
							<Switch {...field} defaultChecked={data?.enable} />
						)}
					/>
				</Stack>
			</FormControlTwoCol>
			<Collapse
				in={showPasswordStrengthOptions}
				animateOpacity
				style={{ width: '100%' }}
			>
				<Stack direction="column" spacing="6" width={'full'}>
					{/* Min Length */}
					<FormControl>
						<Stack direction="column">
							<FormLabel minW="3xs">
								{__('Minimum Length', 'learning-management-system')}
								<ToolTip
									label={__(
										'Set Minimum Length.',
										'learning-management-system',
									)}
								/>
							</FormLabel>

							<Controller
								name="advance.password_strength.min_length"
								defaultValue={minLength || 4}
								rules={{ required: true }}
								render={({ field }) => (
									<Slider
										{...field}
										aria-label="min-length"
										min={4}
										max={maxLength}
									>
										<SliderTrack>
											<SliderFilledTrack />
										</SliderTrack>
										<SliderThumb boxSize="6" bgColor="primary.500">
											<Text fontSize="xs" fontWeight="semibold" color="white">
												{minLength}
											</Text>
										</SliderThumb>
									</Slider>
								)}
							/>
						</Stack>
					</FormControl>

					{/* Max Length */}
					<FormControl>
						<Stack direction="column">
							<FormLabel minW="3xs">
								{__('Maximum Length', 'learning-management-system')}
								<ToolTip
									label={__(
										'Set Maximum Length.',
										'learning-management-system',
									)}
								/>
							</FormLabel>

							<Controller
								name="advance.password_strength.max_length"
								defaultValue={maxLength || 16}
								rules={{ required: true }}
								render={({ field }) => (
									<Slider
										{...field}
										aria-label="max-length"
										min={minLength}
										max={24}
									>
										<SliderTrack>
											<SliderFilledTrack />
										</SliderTrack>
										<SliderThumb boxSize="6" bgColor="primary.500">
											<Text fontSize="xs" fontWeight="semibold" color="white">
												{maxLength}
											</Text>
										</SliderThumb>
									</Slider>
								)}
							/>
						</Stack>
					</FormControl>

					{/* Strength */}
					<FormControl>
						<Stack direction="row">
							<FormLabel minW="3xs">
								{__('Strength', 'learning-management-system')}
								<ToolTip
									label={__(
										'Set Password Strength',
										'learning-management-system',
									)}
								/>
							</FormLabel>

							<Stack direction="column" w="100%">
								<Select
									{...register('advance.password_strength.strength')}
									defaultValue={data?.strength}
								>
									{strengthOptions.map((opt) => (
										<option key={opt.value} value={opt.value}>
											{opt.label}
										</option>
									))}
								</Select>

								<Text>{strengthInfo}</Text>
							</Stack>
						</Stack>
					</FormControl>

					{/* Show Strength */}
					<FormControl>
						<Stack direction="row">
							<FormLabel minW="3xs">
								{__('Show Strength', 'learning-management-system')}
								<ToolTip
									label={__(
										'Display Password Strength',
										'learning-management-system',
									)}
								/>
							</FormLabel>

							<Controller
								name="advance.password_strength.show_strength"
								render={({ field }) => (
									<Switch {...field} defaultChecked={data?.show_strength} />
								)}
							/>
						</Stack>
					</FormControl>
				</Stack>
			</Collapse>
		</SingleComponentsWrapper>
	);
};

export default PasswordStrength;
