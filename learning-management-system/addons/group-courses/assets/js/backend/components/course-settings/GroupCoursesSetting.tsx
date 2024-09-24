import {
	Box,
	FormLabel,
	Icon,
	NumberDecrementStepper,
	NumberIncrementStepper,
	NumberInput,
	NumberInputField,
	NumberInputStepper,
	Tooltip,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Controller } from 'react-hook-form';
import { BiInfoCircle } from 'react-icons/bi';
import FormControlTwoCol from '../../../../../../../assets/js/back-end/components/common/FormControlTwoCol';
import { infoIconStyles } from '../../../../../../../assets/js/back-end/config/styles';
import { CourseDataMap } from '../../../../../../../assets/js/back-end/types/course';
import localized from '../../../../../../../assets/js/back-end/utils/global';

interface Props {
	courseData?: CourseDataMap;
}
const currencyCode = localized.currency.code;
const currencySymbol = localized.currency.symbol;

const GroupCoursesSetting: React.FC<Props> = (props) => {
	const { courseData } = props;

	return (
		<>
			<FormControlTwoCol>
				<FormLabel>
					{__('Group Price', 'learning-management-system')}
					<Tooltip
						label={__(
							'Set the price for enrolling as a group. This allows multiple students to enroll together at a discounted rate compared to individual enrollments.',
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
				<Controller
					name="group_courses.group_price"
					defaultValue={
						courseData?.group_courses?.group_price
							? courseData?.group_courses?.group_price
							: ''
					}
					render={({ field }) => (
						<NumberInput {...field} w="full" min={0}>
							<NumberInputField borderRadius="sm" shadow="input" />
							<NumberInputStepper>
								<NumberIncrementStepper />
								<NumberDecrementStepper />
							</NumberInputStepper>
						</NumberInput>
					)}
				/>
			</FormControlTwoCol>
			<FormControlTwoCol>
				<FormLabel>
					{__('Maximum Group Size', 'learning-management-system')}
					<Tooltip
						label={__(
							'Specify the maximum number of students that can enroll as part of a single group. Helps in managing class size and interaction. Leave zero for unlimited.',
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

				<Controller
					name="group_courses.max_group_size"
					defaultValue={
						courseData?.group_courses?.max_group_size
							? courseData?.group_courses?.max_group_size
							: ''
					}
					render={({ field }) => (
						<NumberInput {...field} w="full" min={0}>
							<NumberInputField borderRadius="sm" shadow="input" />
							<NumberInputStepper>
								<NumberIncrementStepper />
								<NumberDecrementStepper />
							</NumberInputStepper>
						</NumberInput>
					)}
				/>
			</FormControlTwoCol>
		</>
	);
};

export default GroupCoursesSetting;
