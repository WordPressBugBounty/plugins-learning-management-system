import {
	FormErrorMessage,
	FormLabel,
	InputGroup,
	NumberDecrementStepper,
	NumberIncrementStepper,
	NumberInput,
	NumberInputField,
	NumberInputStepper,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Controller, useFormContext } from 'react-hook-form';
import FormControlTwoCol from '../../../../../../../assets/js/back-end/components/common/FormControlTwoCol';
import ToolTip from '../../../../../../../assets/js/back-end/screens/settings/components/ToolTip';
import { GroupSettingsSchema } from '../../../types/group';

interface Props {
	defaultValue?: string;
}

const MaxMembers: React.FC<Props> = (props) => {
	const { defaultValue } = props;
	const {
		formState: { errors },
	} = useFormContext<GroupSettingsSchema>();
	return (
		<>
			<FormControlTwoCol isInvalid={!!errors?.max_members}>
				<FormLabel>
					{__('Maximum Members', 'learning-management-system')}{' '}
					<ToolTip
						label={__(
							'The maximum number of members that can be in a group. Leave blank for unlimited.',
							'learning-management-system',
						)}
					/>
				</FormLabel>
				<Controller
					name="max_members"
					defaultValue={defaultValue || ''}
					rules={{
						min: 1,
					}}
					render={({ field }) => (
						<InputGroup display="flex" flexDirection="row">
							<NumberInput {...field} w="100%">
								<NumberInputField rounded="sm" />
								<NumberInputStepper>
									<NumberIncrementStepper />
									<NumberDecrementStepper />
								</NumberInputStepper>
							</NumberInput>
						</InputGroup>
					)}
				/>

				<FormErrorMessage>
					{errors?.max_members && errors?.max_members?.message?.toString()}
				</FormErrorMessage>
			</FormControlTwoCol>
		</>
	);
};

export default MaxMembers;
