import {
	Box,
	Button,
	Collapse,
	FormControl,
	FormErrorMessage,
	FormLabel,
	HStack,
	Icon,
	InputGroup,
	InputRightAddon,
	NumberDecrementStepper,
	NumberIncrementStepper,
	NumberInput,
	NumberInputField,
	NumberInputStepper,
	Radio,
	RadioGroup,
	Stack,
	Switch,
	Tab,
	TabList,
	TabPanel,
	TabPanels,
	Tabs,
	Tooltip,
} from '@chakra-ui/react';
import { useQuery } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import { Controller, useFormContext, useWatch } from 'react-hook-form';
import { BiInfoCircle } from 'react-icons/bi';
import FormControlTwoCol from '../../../../assets/js/back-end/components/common/FormControlTwoCol';
import {
	infoIconStyles,
	tabListStyles,
	tabStyles,
} from '../../../../assets/js/back-end/config/styles';
import http from '../../../../assets/js/back-end/utils/http';
import { convertMinutesToHours } from '../../../../assets/js/back-end/utils/math';
import { CustomChakraRadio } from '../../../../assets/js/getting-started/components/CustomChakraRadio';
import h5pUrls from '../../constants/urls';

interface Props {
	defaultValues?: any;
}

const H5PQuizSettings: React.FC<Props> = ({ defaultValues = {} }) => {
	const {
		control,
		setValue,
		formState: { errors },
	} = useFormContext();

	const quizId = defaultValues?.id;

	const fullPointsQuery = useQuery<{ full_points: number }>({
		queryKey: [`${quizId}h5pFullPoints`, quizId],
		queryFn: () =>
			http({
				path: h5pUrls.h5pQuizzes + '/' + quizId + '/full-points',
				method: 'post',
			}),
		enabled: false,
	});

	const [hours, minutes] = convertMinutesToHours(defaultValues?.duration || 0);

	const [attemptsDisplayValue, setAttemptsDisplayValue] = useState(
		defaultValues?.attempts_allowed && defaultValues.attempts_allowed !== 0
			? '1'
			: '0',
	);

	// questions_display_per_page: 0 = "From Global Settings", any other value = "Set Individually"
	const defaultDisplayPerPage =
		!defaultValues?.questions_display_per_page ||
		defaultValues.questions_display_per_page === 0
			? '0'
			: '1';

	const watchDisplayPerPage = useWatch({
		name: 'questions_display_per_page_mode',
		defaultValue: defaultDisplayPerPage,
		control,
	});

	const watchPassType = useWatch({
		name: 'pass_mark_type',
		defaultValue: defaultValues?.pass_mark_type || 'point',
		control,
	});

	const isPassPercent = 'percentage' === watchPassType;

	return (
		<Tabs orientation="vertical">
			<Stack direction="row" flex="1">
				<TabList sx={tabListStyles}>
					<Tab sx={tabStyles}>
						{__('General', 'learning-management-system')}
					</Tab>
					<Tab sx={tabStyles}>
						{__('Display', 'learning-management-system')}
					</Tab>
				</TabList>

				<TabPanels flex="1">
					{/* General tab */}
					<TabPanel>
						<Stack direction="column" spacing="6">
							{/* Full Points */}
							<FormControlTwoCol>
								<FormLabel>
									{__('Full Points', 'learning-management-system')}
								</FormLabel>
								<Controller
									name="full_marks"
									control={control}
									defaultValue={defaultValues?.full_marks ?? 100}
									render={({ field }) => (
										<HStack>
											<NumberInput {...field} w="full" min={0}>
												<NumberInputField borderRadius="sm" shadow="input" />
												<NumberInputStepper>
													<NumberIncrementStepper />
													<NumberDecrementStepper />
												</NumberInputStepper>
											</NumberInput>
											<Button
												isDisabled={!quizId}
												isLoading={fullPointsQuery.isFetching}
												colorScheme="primary"
												outline="none"
												variant="outline"
												onClick={async () => {
													const result = await fullPointsQuery.refetch();
													if (result.data?.full_points !== undefined) {
														setValue('full_marks', result.data.full_points);
													}
												}}
												boxShadow="none"
												borderWidth={1.5}
												fontSize="sm"
												fontWeight="normal"
												px={6}
											>
												{__('Auto Calculate', 'learning-management-system')}
											</Button>
										</HStack>
									)}
								/>
							</FormControlTwoCol>

							{/* Pass Points */}
							<FormControlTwoCol>
								<FormLabel>
									{__('Pass', 'learning-management-system')}{' '}
									{isPassPercent
										? __('Percent', 'learning-management-system')
										: __('Points', 'learning-management-system')}
								</FormLabel>
								<Stack>
									<Controller
										name="pass_mark"
										control={control}
										defaultValue={defaultValues?.pass_mark || 40}
										render={({ field }) => (
											<HStack>
												<NumberInput {...field} w="full" min={1}>
													<NumberInputField borderRadius="sm" shadow="input" />
													<NumberInputStepper>
														<NumberIncrementStepper />
														<NumberDecrementStepper />
													</NumberInputStepper>
												</NumberInput>
												<Box>
													<CustomChakraRadio
														name="pass_mark_type"
														options={[
															{ value: 'point', label: 'Point' },
															{ value: 'percentage', label: 'Percent' },
														]}
														defaultValue={
															defaultValues?.pass_mark_type || 'point'
														}
													/>
												</Box>
											</HStack>
										)}
									/>
								</Stack>
							</FormControlTwoCol>

							{/* Time limit */}
							<FormControlTwoCol>
								<FormLabel>
									{__('Time limit', 'learning-management-system')}
								</FormLabel>
								<Stack direction={['column', 'column', 'column', 'row']}>
									<FormControlTwoCol
										isInvalid={!!errors?.duration_hour}
										applyLabelStyles={false}
									>
										<Controller
											name="duration_hour"
											control={control}
											defaultValue={hours || 0}
											rules={{
												required: __(
													'Hours is required.',
													'learning-management-system',
												),
												min: 0,
											}}
											render={({ field }) => (
												<InputGroup>
													<NumberInput {...field} flex="1" min={0}>
														<NumberInputField rounded="sm" />
														<NumberInputStepper>
															<NumberIncrementStepper />
															<NumberDecrementStepper />
														</NumberInputStepper>
													</NumberInput>
													<InputRightAddon>
														{__('Hours', 'learning-management-system')}
													</InputRightAddon>
												</InputGroup>
											)}
										/>
										<FormErrorMessage>
											{errors?.duration_hour &&
												(errors.duration_hour.message as string)}
										</FormErrorMessage>
									</FormControlTwoCol>

									<FormControlTwoCol
										isInvalid={!!errors?.duration_minute}
										applyLabelStyles={false}
									>
										<Controller
											name="duration_minute"
											control={control}
											defaultValue={minutes || 0}
											rules={{
												required: __(
													'Minutes is required.',
													'learning-management-system',
												),
												min: 0,
												max: 59,
											}}
											render={({ field }) => (
												<InputGroup>
													<NumberInput {...field} flex="1" min={0} max={59}>
														<NumberInputField rounded="sm" />
														<NumberInputStepper>
															<NumberIncrementStepper />
															<NumberDecrementStepper />
														</NumberInputStepper>
													</NumberInput>
													<InputRightAddon>
														{__('Minutes', 'learning-management-system')}
													</InputRightAddon>
												</InputGroup>
											)}
										/>
										<FormErrorMessage>
											{errors?.duration_minute &&
												(errors.duration_minute.message as string)}
										</FormErrorMessage>
									</FormControlTwoCol>
								</Stack>
							</FormControlTwoCol>

							{/* Attempts Allowed */}
							<FormControlTwoCol>
								<FormLabel>
									{__('Attempts Allowed', 'learning-management-system')}
								</FormLabel>
								<RadioGroup
									onChange={setAttemptsDisplayValue}
									value={attemptsDisplayValue}
								>
									<Stack direction="column" spacing="4">
										<Stack direction="row" spacing="8" align="flex-start">
											<Radio
												value="0"
												onChange={(e: any) =>
													setValue('attempts_allowed', e.target.value)
												}
											>
												{__('No limit', 'learning-management-system')}
											</Radio>
											<Radio
												value="1"
												onChange={() =>
													setValue(
														'attempts_allowed',
														defaultValues?.attempts_allowed || 5,
													)
												}
											>
												{__('Limit', 'learning-management-system')}
											</Radio>
										</Stack>
									</Stack>
								</RadioGroup>
							</FormControlTwoCol>

							{/* Number of Attempts */}
							<FormControlTwoCol isInvalid={!!errors?.attempts_allowed}>
								<FormLabel>
									{__('Number of Attempts', 'learning-management-system')}
								</FormLabel>
								<Controller
									name="attempts_allowed"
									control={control}
									defaultValue={defaultValues?.attempts_allowed || 0}
									rules={{
										required: __(
											'Attempts allowed is required.',
											'learning-management-system',
										),
									}}
									render={({ field }) => (
										<InputGroup>
											<NumberInput {...field} w="full" min={1}>
												<NumberInputField rounded="sm" />
												<NumberInputStepper>
													<NumberIncrementStepper />
													<NumberDecrementStepper />
												</NumberInputStepper>
											</NumberInput>
											<InputRightAddon>
												{__('Attempts', 'learning-management-system')}
											</InputRightAddon>
										</InputGroup>
									)}
								/>
								<FormErrorMessage>
									{errors?.attempts_allowed &&
										(errors.attempts_allowed.message as string)}
								</FormErrorMessage>
							</FormControlTwoCol>
						</Stack>
					</TabPanel>

					{/* Display tab */}
					<TabPanel>
						<Stack direction="column" spacing="6">
							{/* Questions Per Page */}
							<FormControlTwoCol>
								<FormLabel>
									{__('Questions Per Page', 'learning-management-system')}
									<Tooltip
										label={__(
											'Total number of questions to be shown per page for a quiz.',
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

								<Stack direction="column" spacing="4">
									<Controller
										name="questions_display_per_page_mode"
										control={control}
										defaultValue={defaultDisplayPerPage}
										render={({ field }) => (
											<RadioGroup {...field}>
												<Stack direction="row" spacing="6" align="flex-start">
													<Radio value="0">
														{__(
															'From Global Settings',
															'learning-management-system',
														)}
													</Radio>
													<Radio value="1">
														{__(
															'Set Individually',
															'learning-management-system',
														)}
													</Radio>
												</Stack>
											</RadioGroup>
										)}
									/>

									<Collapse in={watchDisplayPerPage !== '0'} animateOpacity>
										<FormControl
											isInvalid={!!errors?.questions_display_per_page_custom}
										>
											<Controller
												name="questions_display_per_page_custom"
												control={control}
												defaultValue={
													defaultValues?.questions_display_per_page || 5
												}
												rules={{
													required: __(
														'Questions per page is required.',
														'learning-management-system',
													),
												}}
												render={({ field }) => (
													<NumberInput
														{...field}
														defaultValue={
															defaultValues?.questions_display_per_page || 5
														}
														w="full"
														min={1}
														max={999}
													>
														<NumberInputField rounded="sm" />
														<NumberInputStepper>
															<NumberIncrementStepper />
															<NumberDecrementStepper />
														</NumberInputStepper>
													</NumberInput>
												)}
											/>
											<FormErrorMessage>
												{errors?.questions_display_per_page_custom &&
													(errors.questions_display_per_page_custom
														.message as string)}
											</FormErrorMessage>
										</FormControl>
									</Collapse>
								</Stack>
							</FormControlTwoCol>

							{/* Randomize Questions */}
							<FormControlTwoCol>
								<FormLabel>
									{__('Randomize Questions', 'learning-management-system')}
									<Tooltip
										label={__(
											'When enabled, the order of questions will be randomized for each attempt.',
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
									name="randomize_questions"
									control={control}
									render={({ field }) => (
										<Switch
											{...field}
											defaultChecked={
												defaultValues?.randomize_questions === true
											}
										/>
									)}
								/>
							</FormControlTwoCol>

							{/* Show Result */}
							<FormControlTwoCol>
								<FormLabel>
									{__('Show Result', 'learning-management-system')}
									<Tooltip
										label={__(
											'When enabled, the result screen is shown after the student finishes the quiz.',
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
									name="show_result"
									control={control}
									render={({ field }) => (
										<Switch
											{...field}
											defaultChecked={defaultValues?.show_result === true}
										/>
									)}
								/>
							</FormControlTwoCol>
						</Stack>
					</TabPanel>
				</TabPanels>
			</Stack>
		</Tabs>
	);
};

export default H5PQuizSettings;
