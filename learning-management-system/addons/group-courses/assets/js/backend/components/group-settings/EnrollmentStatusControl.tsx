import {
	Box,
	FormErrorMessage,
	FormLabel,
	Icon,
	Switch,
	Tooltip,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { useFormContext } from 'react-hook-form';
import { BiInfoCircle } from 'react-icons/bi';
import FormControlTwoCol from '../../../../../../../assets/js/back-end/components/common/FormControlTwoCol';
import { infoIconStyles } from '../../../../../../../assets/js/back-end/config/styles';
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
					<Tooltip
						label={__(
							'Automatically deactivate the enrollment status of group members when the group status changes (e.g., trashed, deleted, drafted) and reactivate upon restoration.',
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
					<Tooltip
						label={__(
							'Deactivate the enrollment status of members when they are removed from groups. Reactivate if they are added back and were previously enrolled in any course.',
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
