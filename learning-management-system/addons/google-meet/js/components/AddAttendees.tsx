import {
	Flex,
	FormControl,
	FormErrorMessage,
	FormLabel,
	Switch,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { useFormContext } from 'react-hook-form';
import ToolTip from '../../../../assets/js/back-end/screens/settings/components/ToolTip';
import { GoogleMeetSchema } from '../schemas';

interface Props {
	defaultValue?: boolean;
}

const AddAttendees: React.FC<Props> = (props) => {
	const { defaultValue } = props;

	const {
		register,
		formState: { errors },
	} = useFormContext<GoogleMeetSchema>();

	return (
		<FormControl isInvalid={!!errors.add_all_students_as_attendee}>
			<Flex justifyContent="space-between">
				<FormLabel>
					{__('Add all students as attendees', 'learning-management-system')}{' '}
					<ToolTip
						label={__(
							'By activating this feature, you are allowing all the enrolled student as attendees for the meeting.',
							'learning-management-system',
						)}
					/>
				</FormLabel>
				<Switch
					id="add_all_students_as_attendee"
					defaultChecked={defaultValue}
					{...register('add_all_students_as_attendee')}
				/>
				<FormErrorMessage>
					{errors.add_all_students_as_attendee &&
						errors.add_all_students_as_attendee.message}
				</FormErrorMessage>
			</Flex>
		</FormControl>
	);
};

export default AddAttendees;
