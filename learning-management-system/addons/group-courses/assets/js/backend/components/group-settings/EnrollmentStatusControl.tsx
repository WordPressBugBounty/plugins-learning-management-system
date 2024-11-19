import { FormErrorMessage, FormLabel, Switch } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { useFormContext } from 'react-hook-form';
import FormControlTwoCol from '../../../../../../../assets/js/back-end/components/common/FormControlTwoCol';
import ToolTip from '../../../../../../../assets/js/back-end/screens/settings/components/ToolTip';
import { GroupSettingsSchema } from '../../../types/group';

interface Props {
	onStatusChange?: boolean;
	onMemberChange?: boolean;
}

const EnrollmentStatusControl: React.FC<Props> = (props) => {
	const { onStatusChange, onMemberChange } = props;
	const {
		register,
		formState: { errors },
	} = useFormContext<GroupSettingsSchema>();

	return (
		<>
			<FormControlTwoCol
				isInvalid={!!errors?.deactivate_enrollment_on_status_change}
			>
				<FormLabel>
					{__(
						'Deactivate Enrollment on Status Change',
						'learning-management-system',
					)}
					<ToolTip
						label={__(
							'Automatically deactivate the enrollment status of group members when the group status changes (e.g., trashed, deleted, drafted) and reactivate upon restoration.',
							'learning-management-system',
						)}
					/>
				</FormLabel>
				<Switch
					defaultChecked={onMemberChange}
					{...register('deactivate_enrollment_on_status_change')}
				/>
				<FormErrorMessage>
					{errors?.deactivate_enrollment_on_status_change &&
						errors.deactivate_enrollment_on_status_change.message?.toString()}
				</FormErrorMessage>
			</FormControlTwoCol>

			<FormControlTwoCol
				isInvalid={!!errors?.deactivate_enrollment_on_member_change}
			>
				<FormLabel>
					{__(
						'Deactivate Enrollment on Member Change',
						'learning-management-system',
					)}
					<ToolTip
						label={__(
							'Deactivate the enrollment status of members when they are removed from groups. Reactivate if they are added back and were previously enrolled in any course.',
							'learning-management-system',
						)}
					/>
				</FormLabel>
				<Switch
					defaultChecked={onStatusChange}
					{...register('deactivate_enrollment_on_member_change')}
				/>
				<FormErrorMessage>
					{errors?.deactivate_enrollment_on_member_change &&
						errors.deactivate_enrollment_on_member_change.message?.toString()}
				</FormErrorMessage>
			</FormControlTwoCol>
		</>
	);
};

export default EnrollmentStatusControl;
