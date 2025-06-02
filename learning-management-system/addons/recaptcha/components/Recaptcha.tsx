import {
	Box,
	Checkbox,
	FormLabel,
	Icon,
	Input,
	InputGroup,
	InputRightElement,
	Link,
	Radio,
	RadioGroup,
	Select,
	Slider,
	SliderFilledTrack,
	SliderThumb,
	SliderTrack,
	Stack,
	Text,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import { Controller, useFormContext, useWatch } from 'react-hook-form';
import { BsEyeFill, BsEyeSlashFill } from 'react-icons/bs';
import CustomAlert from '../../../assets/js/back-end/components/common/CustomAlert';
import FormControlTwoCol from '../../../assets/js/back-end/components/common/FormControlTwoCol';
import SingleComponentsWrapper from '../../../assets/js/back-end/screens/settings/components/SingleComponentsWrapper';
import ToolTip from '../../../assets/js/back-end/screens/settings/components/ToolTip';

interface Props {
	data:
		| {
				domain: string;
				error_message: string;
				secret_key: string;
				site_key: string;
				size: string;
				theme: string;
				version: string;
				score: number;
				pages: string;
				enable_login_form: boolean;
				enable_student_register_form: boolean;
				enable_instructor_register_form: boolean;
		  }
		| any;
}

type Option = { label: string; value: string | number };

const setOptions = (x: Option) => (
	<option key={x.value} value={x.value}>
		{x.label}
	</option>
);
const languagesList = [
	{
		label: __('Same as Client system', 'learning-management-system'),
		value: '',
	},
	{ label: 'Arabic', value: 'ar' },
	{ label: 'Afrikaans', value: 'af' },
	{ label: 'Amharic', value: 'ah' },
	{ label: 'Armenian', value: 'hy' },
	{ label: 'Azerbaijani', value: 'az' },
	{ label: 'Basque', value: 'eu' },
	{ label: 'Bengali', value: 'bn' },
	{ label: 'Bulgarian', value: 'bg' },
	{ label: 'Catalan', value: 'ca' },
	{ label: 'Chinese (Traditional)', value: 'zh-TW' },
	{ label: 'Chinese (Simplified)', value: 'zh-CN' },
	{ label: 'Chinese (Hong Kong)', value: 'zh-HK' },
	{ label: 'Croatian', value: 'hr' },
	{ label: 'Czech', value: 'cs' },
	{ label: 'Danish', value: 'da' },
	{ label: 'Dutch', value: 'nl' },
	{ label: 'English (UK)', value: 'en-GB' },
	{ label: 'English (US)', value: 'en' },
	{ label: 'Estonian', value: 'et' },
	{ label: 'Filipino', value: 'fil' },
	{ label: 'Finnish', value: 'fi' },
	{ label: 'French', value: 'fr' },
	{ label: 'French (Canadian)', value: 'fr-CA' },
	{ label: 'Galician', value: 'gl' },
	{ label: 'Georgian', value: 'ka' },
	{ label: 'German', value: 'de' },
	{ label: 'German (Austria)', value: 'de-AT' },
	{ label: 'German (Switzerland)', value: 'de-CH' },
	{ label: 'Greek', value: 'el' },
	{ label: 'Gujarati', value: 'gu' },
	{ label: 'Hebrew', value: 'iw' },
	{ label: 'Hindi', value: 'hi' },
	{ label: 'Hungarain', value: 'hu' },
	{ label: 'Icelandic', value: 'is' },
	{ label: 'Indonesian', value: 'id' },
	{ label: 'Italian', value: 'it' },
	{ label: 'Japanese', value: 'ja' },
	{ label: 'Korean', value: 'ko' },
	{ label: 'Kannada', value: 'kn' },
	{ label: 'Latvian', value: 'lv' },
	{ label: 'Laothian', value: 'lo' },
	{ label: 'Lithuanian', value: 'lt' },
	{ label: 'Malay', value: 'ml' },
	{ label: 'Malayalam', value: 'mr' },
	{ label: 'Mongolian', value: 'mn' },
	{ label: 'Norwegian', value: 'no' },
	{ label: 'Persian', value: 'fa' },
	{ label: 'Polish', value: 'po' },
	{ label: 'Portuguese', value: 'pt' },
	{ label: 'Portuguese (Brazil)', value: 'pt-BR' },
	{ label: 'Portuguese (Portugal)', value: 'pt-PT' },
	{ label: 'Romanian', value: 'ro' },
	{ label: 'Russian', value: 'ru' },
	{ label: 'Serbian', value: 'sr' },
	{ label: 'Sinhalese', value: 'si' },
	{ label: 'Slovak', value: 'sk' },
	{ label: 'Slovenian', value: 'sl' },
	{ label: 'Spanish', value: 'es' },
	{ label: 'Spanish (Latin America)', value: 'es-419' },
	{ label: 'Swahili', value: 'sw' },
	{ label: 'Swedish', value: 'sv' },
	{ label: 'Thai', value: 'th' },
	{ label: 'Tamil', value: 'ta' },
	{ label: 'Telugu', value: 'te' },
	{ label: 'Turkish', value: 'tr' },
	{ label: 'Ukrainian', value: 'uk' },
	{ label: 'Urdu', value: 'ur' },
	{ label: 'Vietnamese', value: 'vi' },
	{ label: 'Zulu', value: 'zu' },
];

const Recaptcha: React.FC<Props> = (props) => {
	const { data } = props;
	const { register, control } = useFormContext();
	const [key, setKey] = useState<{ site: boolean; secret: boolean }>({
		site: false,
		secret: false,
	});
	const [enableLoginForm, setEnableLoginForm] = useState(
		data?.enable_login_form,
	);
	const [enableStudentRegisterForm, setEnableStudentRegisterForm] = useState(
		data?.enable_student_register_form,
	);
	const [enableInstructorRegisterForm, setEnableInstructorRegisterForm] =
		useState(data?.enable_instructor_register_form);
	const version = useWatch({
		name: 'authentication.recaptcha.version',
		control,
		defaultValue: data?.version,
	});
	const score = useWatch({
		name: 'authentication.recaptcha.score',
		control,
		defaultValue: data?.score,
	});

	const themeOptions: Option[] = [
		{ label: __('Dark', 'learning-management-system'), value: 'dark' },
		{ label: __('Light', 'learning-management-system'), value: 'light' },
	];

	const sizeOptions: Option[] = [
		{ label: __('Normal', 'learning-management-system'), value: 'normal' },
		{ label: __('Compact', 'learning-management-system'), value: 'compact' },
	];

	const domainOptions: Option[] = [
		{
			label: __('google.com', 'learning-management-system'),
			value: 'google.com',
		},
		{
			label: __('recaptcha.net', 'learning-management-system'),
			value: 'recaptcha.net',
		},
	];

	const scriptsOptions: Option[] = [
		{ label: __('All Pages', 'learning-management-system'), value: 'all' },
		{ label: __('Form Pages', 'learning-management-system'), value: 'form' },
	];

	return (
		<>
			<SingleComponentsWrapper
				title={__('reCAPTCHA', 'learning-management-system')}
			>
				<Stack direction="column" spacing="5">
					<FormControlTwoCol mb={2}>
						<FormLabel>
							{__('Select reCAPTCHA Version', 'learning-management-system')}
						</FormLabel>
						<Controller
							name="authentication.recaptcha.version"
							render={({ field }) => (
								<RadioGroup defaultValue={data?.version} {...field}>
									<Stack
										direction="column"
										spacing="4"
										width="full"
										paddingLeft="6px"
									>
										<Box>
											<Radio value="v2_i_am_not_a_robot">
												{__('Version 2', 'learning-management-system')}
											</Radio>
											<Text color="gray.500">
												{__(
													'Users have to check "I am not a robot" checkbox',
													'learning-management-system',
												)}
											</Text>
										</Box>
										<Box>
											<Radio value="v2_no_interaction">
												{__('Version 2', 'learning-management-system')}
											</Radio>
											<Text color="gray.500">
												{__(
													'No user interaction needed, however, if traffic is suspicious, users are asked to solve a CAPTCHA',
													'learning-management-system',
												)}
											</Text>
										</Box>
										<Box>
											<Radio value="v3">
												{__('Version 3', 'learning-management-system')}
											</Radio>
											<Text color="gray.500">
												{__(
													'Verify request with a score without user interaction',
													'learning-management-system',
												)}
											</Text>
										</Box>
									</Stack>
								</RadioGroup>
							)}
						/>
					</FormControlTwoCol>
					<FormControlTwoCol>
						<FormLabel>
							{__('State the Site & Secret keys', 'learning-management-system')}
							<ToolTip
								label={__(
									'You can obtain these keys for free by registering for your Google reCAPTCHA.',
									'learning-management-system',
								)}
							/>
						</FormLabel>
						<Stack>
							<Stack direction="column" spacing="4">
								<Stack direction={['column', 'column', 'row']} spacing="2">
									<Text flex="0.5">
										{__('Site Key', 'learning-management-system')}
									</Text>
									<InputGroup flex="3" size="md">
										<Input
											type={key.site ? 'text' : 'password'}
											{...register('authentication.recaptcha.site_key')}
											defaultValue={data?.site_key}
										/>
										<InputRightElement>
											<Icon
												as={key.site ? BsEyeFill : BsEyeSlashFill}
												onClick={() =>
													setKey((th) => ({ ...th, site: !th.site }))
												}
											/>
										</InputRightElement>
									</InputGroup>
								</Stack>
								<Stack direction={['column', 'column', 'row']} spacing="2">
									<Text flex="0.5">
										{__('Secret Key', 'learning-management-system')}
									</Text>
									<InputGroup flex="3">
										<Input
											type={key.secret ? 'text' : 'password'}
											{...register('authentication.recaptcha.secret_key')}
											defaultValue={data?.secret_key}
										/>
										<InputRightElement>
											<Icon
												as={key.secret ? BsEyeFill : BsEyeSlashFill}
												onClick={() =>
													setKey((th) => ({ ...th, secret: !th.secret }))
												}
											/>
										</InputRightElement>
									</InputGroup>
								</Stack>
								<CustomAlert status="info" width="fit-content">
									<Text>
										{__('Refer to ', 'learning-management-system')}
										<Link
											color="primary.600"
											textDecoration="underline"
											href="https://www.google.com/recaptcha/admin"
											isExternal
										>
											{__(
												'how to get the Google reCAPTCHA keys',
												'learning-management-system',
											)}
										</Link>
										{__(
											' if you need help with the process',
											'learning-management-system',
										)}
									</Text>
								</CustomAlert>
							</Stack>
						</Stack>
					</FormControlTwoCol>
					{/* <Divider orientation="horizontal" /> */}

					<FormControlTwoCol>
						<FormLabel>
							{__('Forms', 'learning-management-system')}{' '}
							<ToolTip
								label={__(
									'Check the forms where you want to enable google reCAPTCHA',
									'learning-management-system',
								)}
							/>
						</FormLabel>
						<Stack gap="3">
							<Stack
								direction={['column', 'column', 'row']}
								justifyContent="space-between"
							>
								<Checkbox
									{...register('authentication.recaptcha.enable_login_form')}
									isChecked={enableLoginForm}
									onChange={(e) => setEnableLoginForm(e.target.checked)}
								>
									{__('Login Form', 'learning-management-system')}
								</Checkbox>
								<Checkbox
									{...register(
										'authentication.recaptcha.enable_student_register_form',
									)}
									isChecked={enableStudentRegisterForm}
									onChange={(e) =>
										setEnableStudentRegisterForm(e.target.checked)
									}
								>
									{__(
										'Student Registration Form',
										'learning-management-system',
									)}
								</Checkbox>
								<Checkbox
									{...register(
										'authentication.recaptcha.enable_instructor_register_form',
									)}
									isChecked={enableInstructorRegisterForm}
									onChange={(e) =>
										setEnableInstructorRegisterForm(e.target.checked)
									}
								>
									{__(
										'Instructor Registration Form',
										'learning-management-system',
									)}
								</Checkbox>
							</Stack>
						</Stack>
					</FormControlTwoCol>
					{/* <Divider orientation="horizontal" /> */}

					{version === 'v3' ? (
						<>
							<FormControlTwoCol>
								<FormLabel>
									{__('Captcha Score', 'learning-management-system')}
								</FormLabel>
								<Controller
									name="authentication.recaptcha.score"
									defaultValue={data?.score}
									render={({ field }) => (
										<Slider
											{...field}
											max={10}
											min={0}
											onChange={(value) => field.onChange(value)}
										>
											<SliderTrack>
												<SliderFilledTrack />
											</SliderTrack>
											<SliderThumb boxSize="6" bgColor="blue.500">
												<Text fontSize="xs" fontWeight="semibold" color="white">
													{score / 10}
												</Text>
											</SliderThumb>
										</Slider>
									)}
								/>
							</FormControlTwoCol>
							<FormControlTwoCol>
								<FormLabel>
									{__(
										'Load CAPTCHA v3 scripts on:',
										'learning-management-system',
									)}
								</FormLabel>
								<Select
									{...register('authentication.recaptcha.pages')}
									defaultValue={data?.pages}
								>
									{scriptsOptions.map(setOptions)}
								</Select>
							</FormControlTwoCol>
						</>
					) : null}

					<FormControlTwoCol>
						<FormLabel>
							{__('Captcha Language', 'learning-management-system')}
							<ToolTip
								label={__(
									'Select the language of the text used in the CAPTCHA text',
									'learning-management-system',
								)}
							/>
						</FormLabel>
						<Select
							{...register('authentication.recaptcha.language')}
							defaultValue={data?.language}
						>
							{languagesList.map(setOptions)}
						</Select>
					</FormControlTwoCol>
					<FormControlTwoCol>
						<FormLabel>
							{__('Select Domain', 'learning-management-system')}
							<ToolTip
								label={__(
									'Change the domain from this setting if Google is blocked',
									'learning-management-system',
								)}
							/>
						</FormLabel>
						<Select
							{...register('authentication.recaptcha.domain')}
							defaultValue={data?.domain}
						>
							{domainOptions.map(setOptions)}
						</Select>
					</FormControlTwoCol>
					<FormControlTwoCol>
						<FormLabel>{__('Theme', 'learning-management-system')}</FormLabel>
						<Select {...register('authentication.recaptcha.theme')}>
							{themeOptions.map(setOptions)}
						</Select>
					</FormControlTwoCol>
					<FormControlTwoCol>
						<FormLabel>{__('Size', 'learning-management-system')}</FormLabel>
						<Select {...register('authentication.recaptcha.size')}>
							{sizeOptions.map(setOptions)}
						</Select>
					</FormControlTwoCol>
					{/* <Divider orientation="horizontal" /> */}

					<FormControlTwoCol>
						<FormLabel>
							{__('Error Message', 'learning-management-system')}
							<ToolTip
								label={__(
									'State the message to the users who do not complete the CAPTCHA',
									'learning-management-system',
								)}
							/>
						</FormLabel>
						<Input
							bg="white"
							{...register('authentication.recaptcha.error_message')}
							defaultValue={data?.error_message}
						/>
					</FormControlTwoCol>
				</Stack>
			</SingleComponentsWrapper>
		</>
	);
};

export default Recaptcha;
