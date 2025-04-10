import {
	Box,
	Button,
	Collapse,
	FormLabel,
	HStack,
	List,
	ListIcon,
	ListItem,
	Select,
	Stack,
	UnorderedList,
	VStack,
	useSteps,
	useToast,
} from '@chakra-ui/react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React, { useCallback, useEffect, useMemo, useState } from 'react';
import { useForm } from 'react-hook-form';
import { BiRightArrowAlt } from 'react-icons/bi';
import { IoIosMove } from 'react-icons/io';
import CustomAlert from '../../../../assets/js/back-end/components/common/CustomAlert';
import FormControlTwoCol from '../../../../assets/js/back-end/components/common/FormControlTwoCol';
import ToolTip from '../../../../assets/js/back-end/screens/settings/components/ToolTip';
import API from '../../../../assets/js/back-end/utils/api';
import { deepClean, isEmpty } from '../../../../assets/js/back-end/utils/utils';
import {
	ALL_MIGRATION_STEPS,
	ANNOUNCEMENTS,
	COMPLETED,
	COURSES,
	MIGRATING,
	MIGRATION_STEPS_RELATIVE_TO_LMS,
	ORDERS,
	QUESTIONSANDANSWERS,
	REVIEWS,
} from '../constants/general';
import { urls } from '../constants/urls';
import MigrationStatusDisplay from './MigrationStatusDisplay';
interface LMS {
	name: string;
	label: string;
}

interface MigrationData {
	type?:
		| 'courses'
		| 'orders'
		| 'reviews'
		| 'questions_n_answers'
		| 'announcement'
		| 'users';
	lms_name: string;
}

function useLMSsQuery() {
	const LMSsAPI = new API(urls.migrationLMSs);
	return useQuery({
		queryKey: ['migrationLMSsList'],
		queryFn: () => LMSsAPI.list(),
	});
}

function useMigrateMutation() {
	const migrationAPI = new API(urls.migrations);
	const toast = useToast();

	return useMutation({
		mutationFn: (data: MigrationData) => migrationAPI.store(data),
		onError: (err: any) => {
			toast({
				title:
					err?.message ||
					__('Something went wrong', 'learning-management-system'),
				status: 'error',
				isClosable: true,
			});
		},
	});
}

const Migration: React.FC = () => {
	const { activeStep, setActiveStep } = useSteps({
		index: 0,
		count: ALL_MIGRATION_STEPS.length,
	});

	const [showMigrationStatus, setShowMigrationStatus] =
		useState<boolean>(false);
	const toast = useToast();
	const queryClient = useQueryClient();
	const {
		register,
		handleSubmit,
		getValues,
		formState: { errors },
		watch,
	} = useForm();

	const lmsWatchedValue = watch('lms_name');
	const migrationLMSsQuery = useLMSsQuery();
	const migrate = useMigrateMutation();

	const [migrationStatus, setMigrationStatus] = useState({
		courses: 'not_started',
		orders: 'not_started',
		reviews: 'not_started',
		announcement: 'not_started',
		questions_n_answers: 'not_started',
	});

	const updateMigrationStatus = useCallback(
		(response: any) => {
			setMigrationStatus((prevStatus) => {
				let newStatus = { ...prevStatus };

				if (response.remainingCourses && !isEmpty(response.remainingCourses)) {
					newStatus.courses = MIGRATING;
				} else if (
					response.remainingOrders &&
					!isEmpty(response.remainingOrders)
				) {
					newStatus = {
						...newStatus,
						courses: COMPLETED,
						orders: MIGRATING,
					};
				} else if (
					response.remainingReviews &&
					!isEmpty(response.remainingReviews)
				) {
					newStatus = {
						...newStatus,
						courses: COMPLETED,
						orders: COMPLETED,
						reviews: MIGRATING,
					};
				} else if (
					response.remainingAnnouncement &&
					!isEmpty(response.remainingAnnouncement)
				) {
					newStatus = {
						...newStatus,
						courses: COMPLETED,
						orders: COMPLETED,
						reviews: COMPLETED,
						announcement: MIGRATING,
					};
				} else if (
					response.remainingQuestionsAnswers &&
					!isEmpty(response.remainingQuestionsAnswers)
				) {
					newStatus = {
						...newStatus,
						courses: COMPLETED,
						orders: COMPLETED,
						reviews: COMPLETED,
						announcement: COMPLETED,
						questions_n_answers: MIGRATING,
					};
				} else if (
					response.remainingUsers &&
					!isEmpty(response.remainingUsers)
				) {
					newStatus = {
						...newStatus,
						courses: COMPLETED,
						orders: COMPLETED,
						reviews: COMPLETED,
						announcement: COMPLETED,
						questions_n_answers: COMPLETED,
					};
				} else if (
					response.remainingQuizAttempts &&
					!isEmpty(response.remainingQuizAttempts)
				) {
					newStatus = {
						...newStatus,
						courses: COMPLETED,
						orders: COMPLETED,
						reviews: COMPLETED,
						announcement: COMPLETED,
						questions_n_answers: COMPLETED,
					};
				} else {
					newStatus = {
						courses: COMPLETED,
						orders: COMPLETED,
						reviews: COMPLETED,
						announcement: COMPLETED,
						questions_n_answers: COMPLETED,
					};
				}

				const allCompleted =
					newStatus.courses === COMPLETED &&
					newStatus.orders === COMPLETED &&
					newStatus.reviews === COMPLETED &&
					newStatus.announcement === COMPLETED &&
					newStatus.questions_n_answers === COMPLETED;

				if (allCompleted) {
					queryClient.invalidateQueries({ queryKey: ['courseList'] });
				}

				return newStatus;
			});
		},
		[setMigrationStatus, queryClient],
	);

	const onSubmit = (data: any) => {
		setActiveStep(0);
		setMigrationStatus({
			...migrationStatus,
			courses: MIGRATING,
			orders: 'not_started',
			reviews: 'not_started',
			announcement: 'not_started',
			questions_n_answers: 'not_started',
		});
		migrate.mutate(deepClean(data), {
			onSuccess: (response) => {
				updateMigrationStatus(response);
			},
			onError: (error: any) => {
				const message =
					error?.message ||
					error?.data?.message ||
					__('Failed to migrate.', 'learning-management-system');
				toast({
					title: __('Failed to migrate.', 'learning-management-system'),
					description: message,
					status: 'error',
					isClosable: true,
				});
			},
		});
	};

	const renderLMSsOption = () => {
		if (migrationLMSsQuery.isLoading) {
			return (
				<option disabled>
					{__('Loading...', 'learning-management-system')}
				</option>
			);
		}

		if (migrationLMSsQuery.isError) {
			return (
				<option disabled>
					{__('Error loading options', 'learning-management-system')}
				</option>
			);
		}

		const lmsOptions = migrationLMSsQuery.data?.data || [];

		return lmsOptions.map((lms: LMS) => (
			<option value={lms.name} key={lms.name}>
				{lms.label}
			</option>
		));
	};

	const onMigrationalStatusClose = useCallback(() => {
		return setShowMigrationStatus(false);
	}, []);

	const currentlyActiveLms = migrationLMSsQuery.data?.data.find(
		(d: any) => d?.name === getValues('lms_name'),
	);

	const migrationProcessInProgress = useMemo(() => {
		return Object.keys(migrationStatus).find(
			(key) => migrationStatus[key] === MIGRATING,
		);
	}, [migrationStatus]); // This will return the step that is migrating

	const isMigrationProcessCompleted = useMemo(() => {
		return Object.keys(migrationStatus).every(
			(key) => migrationStatus[key] === COMPLETED,
		);
	}, [migrationStatus]); // This will return true if the process is completed

	useEffect(() => {
		if (migrationStatus[COURSES] === COMPLETED) {
			setActiveStep(1);
			if (migrationStatus[ORDERS] === COMPLETED) {
				setActiveStep(2);

				if (migrationStatus[REVIEWS] === COMPLETED) {
					setActiveStep(3);
				}
			}
		}
	}, [migrationStatus, setActiveStep]);

	useEffect(() => {
		let statusTimeOut: NodeJS.Timeout;
		if (
			!showMigrationStatus &&
			Object.keys(migrationStatus).some(
				(key) => migrationStatus[key] === MIGRATING,
			)
		) {
			setShowMigrationStatus(true);
		}
		if (
			showMigrationStatus &&
			Object.keys(migrationStatus).every(
				(key) => migrationStatus[key] === COMPLETED,
			)
		) {
			statusTimeOut = setTimeout(() => {
				setShowMigrationStatus(false);
			}, 2000);
		}

		return () => {
			clearTimeout(statusTimeOut);
		};
	}, [migrationStatus, showMigrationStatus]);

	useEffect(() => {
		if (errors.lms_name) {
			toast({
				title: __(
					String(errors.lms_name.message),
					'learning-management-system',
				),
				status: 'error',
				isClosable: true,
			});
		}
	}, [errors.lms_name, toast]);

	useEffect(() => {
		const allCompleted =
			migrationStatus.courses === COMPLETED &&
			migrationStatus.orders === COMPLETED &&
			migrationStatus.reviews === COMPLETED &&
			migrationStatus.announcement === COMPLETED &&
			migrationStatus.questions_n_answers === COMPLETED;

		if (allCompleted) {
			return;
		}
		if (!migrate.isPending) {
			const lsmName = getValues('lms_name');
			(
				[COURSES, ORDERS, REVIEWS, ANNOUNCEMENTS, QUESTIONSANDANSWERS] as Array<
					| 'courses'
					| 'orders'
					| 'reviews'
					| 'announcement'
					| 'questions_n_answers'
				>
			).forEach((type) => {
				if (migrationStatus[type] === MIGRATING) {
					migrate.mutate(
						{ lms_name: lsmName, type },
						{
							onSuccess: (response) => {
								updateMigrationStatus(response);
							},
							onError: (error: any) => {
								const message =
									error?.message ||
									error?.data?.message ||
									__('Failed to migrate.', 'learning-management-system');
								toast({
									title: __('Failed to migrate.', 'learning-management-system'),
									description: message,
									status: 'error',
									isClosable: true,
								});
							},
						},
					);
				}
			});
		}
	}, [migrationStatus, updateMigrationStatus, getValues, migrate, toast]);

	return (
		<Stack direction="column" spacing="6">
			<form onSubmit={handleSubmit(onSubmit)}>
				<FormControlTwoCol isInvalid={!!errors?.lms_name}>
					<FormLabel htmlFor="lms_name">
						{__('Migration From', 'learning-management-system')}
						<ToolTip
							label={__(
								'Choose an LMS from the list to migrate.',
								'learning-management-system',
							)}
						/>
					</FormLabel>
					<VStack>
						<HStack width={'full'}>
							<Select
								id="lms_name"
								isDisabled={migrate.isPending}
								placeholder={__('Select an LMS', 'learning-management-system')}
								{...register('lms_name', {
									required: __('Select an LMS.', 'learning-management-system'),
								})}
							>
								{renderLMSsOption()}
							</Select>
							<Button
								colorScheme="blue"
								type="submit"
								isLoading={migrate.isPending}
								isDisabled={migrate.isPending}
								loadingText={__('Migrating...', 'learning-management-system')}
								size="md"
								rightIcon={<IoIosMove size={15} />}
							>
								{__('Migrate', 'learning-management-system')}
							</Button>
						</HStack>
						{/* LMS migration info */}

						<Collapse in={currentlyActiveLms}>
							<VStack gap={3} my={5} alignItems={'flex-start'}>
								{/* Migration Alert */}
								<CustomAlert status="warning" mb="6">
									{__(
										'Before proceeding with the migration, please test the process on a staging site. The following data will be migrated, and once the migration is complete, the data cannot be restored to the previous LMS system.',
										'learning-management-system',
									)}
								</CustomAlert>

								{/* List of data that will be migrated */}

								<List spacing={3}>
									{[
										...(MIGRATION_STEPS_RELATIVE_TO_LMS[
											currentlyActiveLms?.name
										] || ALL_MIGRATION_STEPS),
									]?.map((step: string) => {
										return (
											<ListItem
												fontSize={'small'}
												color={'gray.600'}
												key={step}
												fontWeight={'semibold'}
											>
												<ListIcon
													as={BiRightArrowAlt}
													color="green.500"
													fontSize={'sm'}
												/>
												{step}
												{step === 'Courses' && (
													<Box ml={8} mt={2}>
														<UnorderedList spacing={1}>
															<ListItem fontSize="small" color="gray.600">
																{__(
																	'All Enrolled Users',
																	'learning-management-system',
																)}
															</ListItem>

															<ListItem fontSize="small" color="gray.600">
																{__(
																	'Instructors who created the course',
																	'learning-management-system',
																)}
															</ListItem>
														</UnorderedList>
													</Box>
												)}
											</ListItem>
										);
									})}
								</List>
							</VStack>
						</Collapse>
					</VStack>
				</FormControlTwoCol>

				{/* Migration status modal */}
				<MigrationStatusDisplay
					activeStep={activeStep}
					currentlyActiveLms={currentlyActiveLms}
					lmsWatchedValue={lmsWatchedValue}
					isMigrationProcessCompleted={isMigrationProcessCompleted}
					migrationProcessInProgress={migrationProcessInProgress}
					onMigrationalStatusClose={onMigrationalStatusClose}
					showMigrationStatus={showMigrationStatus}
				/>
			</form>
		</Stack>
	);
};

export default Migration;
