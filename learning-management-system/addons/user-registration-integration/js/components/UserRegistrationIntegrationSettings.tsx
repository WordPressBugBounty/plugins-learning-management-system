import { FormLabel, Select, Switch } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Controller, useFormContext, useWatch } from 'react-hook-form';
import FormControlTwoCol from '../../../../assets/js/back-end/components/common/FormControlTwoCol';
import SingleComponentsWrapper from '../../../../assets/js/back-end/screens/settings/components/SingleComponentsWrapper';
import localized from '../../../../assets/js/back-end/utils/global';

interface Props {
	data:
		| {
				override_student_registration: boolean;
				override_instructor_registration: boolean;
				student_registration_form: string;
				instructor_registration_form: string;
		  }
		| any;
}

const UserRegistrationIntegrationSettings: React.FC<Props> = (props) => {
	const { data } = props;
	const { register, control } = useFormContext();

	const overrideStudentRegistration = useWatch({
		name: 'integrations.user_registration_integration.override_student_registration',
		defaultValue: data?.override_student_registration,
		control,
	});

	const overrideInstructorRegistration = useWatch({
		name: 'integrations.user_registration_integration.override_instructor_registration',
		defaultValue: data?.override_instructor_registration,
		control,
	});

	const formsList = localized?.user_registration?.ur_forms;

	return (
		<SingleComponentsWrapper
			title={__('User Registration Integration', 'learning-management-system')}
		>
			{/* 1. Override student registration */}
			<FormControlTwoCol>
				<Controller
					name="integrations.user_registration_integration.override_student_registration"
					render={({ field }) => (
						<>
							<FormLabel htmlFor="override-student-registration">
								{__(
									'Enable Custom Student Registration',
									'learning-management-system',
								)}
							</FormLabel>
							<Switch
								id="override-student-registration"
								{...field}
								defaultChecked={data?.override_student_registration}
							/>
						</>
					)}
				/>
			</FormControlTwoCol>

			{overrideStudentRegistration && (
				<FormControlTwoCol>
					<FormLabel htmlFor="student-registration-form">
						{__(
							'Student Registration Form Selector',
							'learning-management-system',
						)}
					</FormLabel>
					<Select
						id="student-registration-form"
						{...register(
							'integrations.user_registration_integration.student_registration_form',
						)}
						placeholder={__(
							'Select User Registration Form',
							'learning-management-system',
						)}
						defaultValue={data?.student_registration_form}
					>
						{formsList &&
							Object.entries(formsList ?? {}).map(([value, label]) => (
								<option key={value} value={value}>
									{label}
								</option>
							))}
					</Select>
				</FormControlTwoCol>
			)}

			{/* 2. Override instructor registration */}
			<FormControlTwoCol>
				<Controller
					name="integrations.user_registration_integration.override_instructor_registration"
					render={({ field }) => (
						<>
							<FormLabel htmlFor="override-instructor-registration">
								{__(
									'Enable Custom Instructor Registration',
									'learning-management-system',
								)}
							</FormLabel>
							<Switch
								id="override-instructor-registration"
								{...field}
								defaultChecked={data?.override_instructor_registration}
							/>
						</>
					)}
				/>
			</FormControlTwoCol>

			{overrideInstructorRegistration && (
				<FormControlTwoCol>
					<FormLabel htmlFor="override-student-registration">
						{__(
							'Instructor Registration Form Selector',
							'learning-management-system',
						)}
					</FormLabel>
					<Select
						id="student-registration-form"
						{...register(
							'integrations.user_registration_integration.instructor_registration_form',
						)}
						placeholder={__(
							'Select User Registration Form',
							'learning-management-system',
						)}
						defaultValue={data?.instructor_registration_form}
					>
						{formsList &&
							Object.entries(formsList ?? {}).map(([value, label]) => (
								<option key={value} value={value}>
									{label}
								</option>
							))}
					</Select>
				</FormControlTwoCol>
			)}
		</SingleComponentsWrapper>
	);
};

export default UserRegistrationIntegrationSettings;
