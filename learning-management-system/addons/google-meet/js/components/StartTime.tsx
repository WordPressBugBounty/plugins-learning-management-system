import {
	Box,
	Flex,
	FormControl,
	FormErrorMessage,
	FormLabel,
	HStack,
	IconButton,
	Input,
	InputGroup,
	InputLeftElement,
	Select,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import ReactDatePicker from 'react-datepicker';
import { Controller, useFormContext } from 'react-hook-form';
import { BiLeftArrow, BiRightArrow, BiTimeFive } from 'react-icons/bi';
import { GoogleMeetSchema } from '../schemas';
import { numberRange } from '../utils/number';

interface Props {
	defaultValue?: string;
}

const years = numberRange(
	new Date().getFullYear(),
	new Date().getFullYear() + 15,
	1,
);
const months = [
	'January',
	'February',
	'March',
	'April',
	'May',
	'June',
	'July',
	'August',
	'September',
	'October',
	'November',
	'December',
];

const StartTime: React.FC<Props> = (props) => {
	const { defaultValue } = props;
	const {
		formState: { errors },
		control,
		getValues,
	} = useFormContext<GoogleMeetSchema>();

	const customDatePickerStyles = `
    .react-datepicker-popper {
        z-index: 1000;
    }`;

	return (
		<FormControl isInvalid={!!errors.starts_at}>
			<FormLabel>{__('Start Time', 'learning-management-system')}</FormLabel>
			<Flex gap="4" direction="column">
				<InputGroup isolation={'auto'}>
					<Box zIndex="auto" position="relative" width={'100%'}>
						<style>{customDatePickerStyles}</style>
						<InputLeftElement>
							<BiTimeFive />
						</InputLeftElement>
						<Controller
							control={control}
							name="starts_at"
							rules={{
								required: __(
									'Please set meeting start time.',
									'learning-management-system',
								),
							}}
							defaultValue={defaultValue ? new Date(defaultValue) : undefined}
							render={({ field: { onChange: onDateChange, value } }) => (
								<Box zIndex={'auto'}>
									<ReactDatePicker
										renderCustomHeader={({
											date,
											changeYear,
											changeMonth,
											decreaseMonth,
											increaseMonth,
											prevMonthButtonDisabled,
											nextMonthButtonDisabled,
										}) => (
											<HStack justifyContent="center" px={3}>
												<IconButton
													icon={<BiLeftArrow />}
													size="sm"
													aria-label={__(
														'Select previous month',
														'learning-management-system',
													)}
													onClick={decreaseMonth}
													isDisabled={prevMonthButtonDisabled}
													variant="ghost"
													colorScheme="primary"
												/>
												<Select
													value={date.getFullYear()}
													onChange={({ target: { value } }: any) =>
														changeYear(value)
													}
												>
													{years.map((option) => (
														<option key={option} value={option}>
															{option}
														</option>
													))}
												</Select>

												<Select
													value={months[date.getMonth()]}
													onChange={({ target: { value } }) =>
														changeMonth(months.indexOf(value))
													}
												>
													{months.map((option) => (
														<option key={option} value={option}>
															{option}
														</option>
													))}
												</Select>

												<IconButton
													icon={<BiRightArrow />}
													size="sm"
													aria-label={__(
														'Select next month',
														'learning-management-system',
													)}
													onClick={increaseMonth}
													isDisabled={nextMonthButtonDisabled}
													variant="ghost"
													colorScheme="primary"
												/>
											</HStack>
										)}
										dateFormat="yyyy-MM-dd, h:mm aa"
										onChange={onDateChange}
										showTimeInput
										selected={value as unknown as Date}
										customInput={<Input pl={9} />}
										autoComplete="off"
										minDate={new Date()}
										shouldCloseOnSelect={false}
									/>
								</Box>
							)}
						/>
					</Box>
				</InputGroup>
			</Flex>
			<FormErrorMessage>
				{errors?.starts_at && errors?.starts_at?.message}
			</FormErrorMessage>
		</FormControl>
	);
};

export default StartTime;
