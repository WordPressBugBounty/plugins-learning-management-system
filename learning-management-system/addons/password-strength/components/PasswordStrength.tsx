import {
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
import { PasswordStrengthValue } from '../../../assets/js/back-end/enums/Enum';
import SingleComponentsWrapper from '../../../assets/js/back-end/screens/settings/components/SingleComponentsWrapper';
import ToolTip from '../../../assets/js/back-end/screens/settings/components/ToolTip';
import { PasswordStrengthType } from '../../../assets/js/back-end/types';

interface Props {
	data:
		| {
				max_length: number;
				min_length: number;
				show_strength: boolean;
				strength: PasswordStrengthType;
		  }
		| any;
}

const PasswordStrength: React.FC<Props> = (props) => {
	const { data } = props;
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

	const strengthOptions: { label: string; value: PasswordStrengthType }[] = [
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

	const strengthOptionsRender = () =>
		strengthOptions.map((strengthOption) => (
			<option key={strengthOption.value} value={strengthOption.value}>
				{strengthOption.label}
			</option>
		));

	let strengthInfo: string = '';

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

	return (
		<SingleComponentsWrapper
			title={__('Password Strength', 'learning-management-system')}
		>
			{/* Minimum Length */}
			<FormControl>
				<Stack direction="column">
					<FormLabel minW="3xs">
						{__('Minimum Length', 'learning-management-system')}
						<ToolTip
							label={__('Set Minimum Length.', 'learning-management-system')}
						/>
					</FormLabel>
					<Controller
						name="advance.password_strength.min_length"
						defaultValue={minLength || 4}
						rules={{ required: true }}
						render={({ field }) => (
							<Slider
								{...field}
								aria-label="password-strength-min"
								w="100%"
								max={maxLength}
								min={4}
							>
								<SliderTrack>
									<SliderFilledTrack />
								</SliderTrack>
								<SliderThumb boxSize="6" bgColor="blue.500">
									<Text fontSize="xs" fontWeight="semibold" color="white">
										{minLength || 4}
									</Text>
								</SliderThumb>
							</Slider>
						)}
					/>
				</Stack>
			</FormControl>
			{/* Maximum Length */}
			<FormControl>
				<Stack direction="column">
					<FormLabel minW="3xs">
						{__('Maximum Length', 'learning-management-system')}
						<ToolTip
							label={__('Set Maximum Length.', 'learning-management-system')}
						/>
					</FormLabel>
					<Controller
						name="advance.password_strength.max_length"
						defaultValue={maxLength || 16}
						rules={{ required: true }}
						render={({ field }) => (
							<Slider
								{...field}
								aria-label="password-strength-max"
								max={24}
								w="100%"
								min={minLength}
							>
								<SliderTrack>
									<SliderFilledTrack />
								</SliderTrack>
								<SliderThumb boxSize="6" bgColor="blue.500">
									<Text fontSize="xs" fontWeight="semibold" color="white">
										{maxLength || 16}
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
							label={__('Set Password Strength', 'learning-management-system')}
						/>
					</FormLabel>
					<Stack direction="column" spacing="2" w="100%">
						<Select
							{...register('advance.password_strength.strength')}
							defaultValue={data?.strength}
						>
							{strengthOptionsRender()}
						</Select>
						<Text>{strengthInfo}</Text>
					</Stack>
				</Stack>
			</FormControl>
			{/* Strength Show */}
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
		</SingleComponentsWrapper>
	);
};

export default PasswordStrength;
