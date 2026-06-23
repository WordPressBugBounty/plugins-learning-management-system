import {
	Box,
	Button,
	HStack,
	Icon,
	Modal,
	ModalContent,
	ModalOverlay,
	Progress,
	Skeleton,
	Spinner,
	Text,
	VStack,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import {
	BiBook,
	BiCart,
	BiChat,
	BiGroup,
	BiHeart,
	BiTrendingUp,
	BiVideo,
} from 'react-icons/bi';
import { HiAcademicCap } from 'react-icons/hi';
import { IoMdStar } from 'react-icons/io';
import {
	MdCampaign,
	MdQuiz,
	MdVideoCall,
	MdWorkspacePremium,
} from 'react-icons/md';
import { MigrationProgress } from './Migration';

interface MigrationStatusDisplayProps {
	progress: MigrationProgress | null;
	lmsLabel: string;
	onClose: () => void;
	onCancel: () => void;
	isCancelling: boolean;
}

const STEP_ICON_MAP: Record<string, React.ElementType> = {
	users: BiGroup,
	courses: BiBook,
	lessons: BiBook,
	enrollments: HiAcademicCap,
	orders: BiCart,
	reviews: IoMdStar,
	announcement: MdCampaign,
	questions_n_answers: BiChat,
	progress: BiTrendingUp,
	lesson_progress: BiTrendingUp,
	quiz_attempts: MdQuiz,
	quiz_results: MdQuiz,
	quizzes: MdQuiz,
	google_meet: MdVideoCall,
	earned_certificates: MdWorkspacePremium,
	wishlists: BiHeart,
};

const STEP_LABELS: Record<string, string> = {
	users: 'Users',
	courses: 'Courses',
	enrollments: 'Enrollments',
	orders: 'Orders',
	reviews: 'Reviews',
	announcement: 'Announcements',
	questions_n_answers: 'Questions & Answers',
	progress: 'Course Progress',
	lesson_progress: 'Course Progress',
	quiz_attempts: 'Quiz Attempts',
	quiz_results: 'Quiz Attempts',
	google_meet: 'Google Meet',
	earned_certificates: 'Certificates',
	wishlists: 'Wishlists',
};

function stepSlugToLabel(slug: string): string {
	return (
		STEP_LABELS[slug] ??
		slug.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
	);
}

const StepIcon: React.FC<{ slug: string }> = ({ slug }) => {
	const IconComponent = STEP_ICON_MAP[slug] ?? BiVideo;
	return <Icon as={IconComponent} boxSize="14px" />;
};

const MigrationStatusDisplay: React.FC<MigrationStatusDisplayProps> = ({
	progress,
	lmsLabel,
	onClose,
	onCancel,
	isCancelling,
}) => {
	const isCompleted = progress?.status === 'completed';
	const isFailed = progress?.status === 'failed';
	const isCancelled = progress?.status === 'cancelled';
	const isRunning = progress?.status === 'running';
	const isTerminal = isCompleted || isFailed || isCancelled;

	const overallPct = progress?.overall_pct ?? 0;

	const totalItems = isCompleted
		? Object.values(progress?.steps ?? {}).reduce((s, st) => s + st.total, 0)
		: null;
	const isEmptyMigration = isCompleted && totalItems === 0;

	return (
		<Modal
			isOpen
			onClose={onClose}
			size="md"
			closeOnOverlayClick={false}
			isCentered
		>
			<ModalOverlay bg="blackAlpha.600" />
			<ModalContent borderRadius="lg" overflow="hidden" maxW="520px" my={0}>
				{/* Header */}
				<Box
					px="6"
					pt="5"
					pb="4"
					borderBottom="1px solid"
					borderColor="gray.100"
				>
					<HStack justify="space-between" mb="3" align="flex-start">
						<HStack spacing="2.5" align="flex-start" flex="1">
							{/* Status icon — square-rounded, color encodes state */}
							{isCompleted ? (
								<Box
									w="8"
									h="8"
									borderRadius="10px"
									bg={isEmptyMigration ? 'orange.50' : 'green.50'}
									display="flex"
									alignItems="center"
									justifyContent="center"
									flexShrink={0}
									mt="0.5"
									color={isEmptyMigration ? 'orange.500' : 'green.500'}
								>
									{isEmptyMigration ? (
										<svg
											width="13"
											height="13"
											viewBox="0 0 24 24"
											fill="none"
											stroke="currentColor"
											strokeWidth="2.5"
											strokeLinecap="round"
										>
											<circle cx="12" cy="12" r="10" />
											<line x1="12" y1="8" x2="12" y2="12" />
											<line x1="12" y1="16" x2="12.01" y2="16" />
										</svg>
									) : (
										<svg
											width="14"
											height="14"
											viewBox="0 0 24 24"
											fill="none"
											stroke="currentColor"
											strokeWidth="2.5"
											strokeLinecap="round"
										>
											<polyline points="20 6 9 17 4 12" />
										</svg>
									)}
								</Box>
							) : isCancelled ? (
								<Box
									w="8"
									h="8"
									borderRadius="10px"
									bg="gray.100"
									display="flex"
									alignItems="center"
									justifyContent="center"
									flexShrink={0}
									mt="0.5"
									color="gray.500"
								>
									<svg
										width="13"
										height="13"
										viewBox="0 0 24 24"
										fill="none"
										stroke="currentColor"
										strokeWidth="2.5"
										strokeLinecap="round"
									>
										<line x1="18" y1="6" x2="6" y2="18" />
										<line x1="6" y1="6" x2="18" y2="18" />
									</svg>
								</Box>
							) : isFailed ? (
								<Box
									w="8"
									h="8"
									borderRadius="10px"
									bg="red.50"
									display="flex"
									alignItems="center"
									justifyContent="center"
									flexShrink={0}
									mt="0.5"
									color="red.500"
								>
									<svg
										width="13"
										height="13"
										viewBox="0 0 24 24"
										fill="none"
										stroke="currentColor"
										strokeWidth="2.5"
										strokeLinecap="round"
									>
										<line x1="12" y1="8" x2="12" y2="12" />
										<line x1="12" y1="16" x2="12.01" y2="16" />
										<circle cx="12" cy="12" r="10" />
									</svg>
								</Box>
							) : (
								<Box
									w="8"
									h="8"
									borderRadius="10px"
									bg="primary.100"
									display="flex"
									alignItems="center"
									justifyContent="center"
									flexShrink={0}
									mt="0.5"
									color="primary.500"
								>
									<Spinner size="xs" color="primary.500" speed="0.9s" />
								</Box>
							)}
							<Box>
								<Text fontSize="sm" fontWeight="600" color="gray.700">
									{isCompleted
										? `${lmsLabel} ${__('Migration Complete', 'learning-management-system')}`
										: isFailed
											? __('Migration Failed', 'learning-management-system')
											: isCancelled
												? __(
														'Migration Cancelled',
														'learning-management-system',
													)
												: `${__('Migrating from', 'learning-management-system')} ${lmsLabel}`}
								</Text>
								<Text fontSize="xs" color="gray.500" mt="0.5">
									{isCompleted
										? isEmptyMigration
											? __(
													'No data found to migrate.',
													'learning-management-system',
												)
											: __(
													'All data migrated successfully.',
													'learning-management-system',
												)
										: isFailed
											? __(
													'Migration failed. Please try again.',
													'learning-management-system',
												)
											: isCancelled
												? __(
														'Migration was cancelled.',
														'learning-management-system',
													)
												: __(
														'Migrating your data — this may take a few minutes.',
														'learning-management-system',
													)}
								</Text>
							</Box>
						</HStack>

						{/* Right side: close button only */}
						<Box flexShrink={0} mt="0.5">
							<Box
								as="button"
								onClick={onClose}
								w="6"
								h="6"
								display="flex"
								alignItems="center"
								justifyContent="center"
								borderRadius="md"
								color="gray.400"
								_hover={{ color: 'gray.600', bg: 'gray.100' }}
								lineHeight="0"
								aria-label={
									isTerminal
										? __('Close', 'learning-management-system')
										: __('Minimize', 'learning-management-system')
								}
							>
								<svg
									width="14"
									height="14"
									viewBox="0 0 24 24"
									fill="none"
									stroke="currentColor"
									strokeWidth="2"
									strokeLinecap="round"
								>
									<line x1="18" y1="6" x2="6" y2="18" />
									<line x1="6" y1="6" x2="18" y2="18" />
								</svg>
							</Box>
						</Box>
					</HStack>

					{/* Overall progress bar with inline % label above it */}
					{!isTerminal && (
						<Text
							fontSize="xs"
							fontWeight="500"
							color="gray.400"
							textAlign="right"
							mb="1"
						>
							{overallPct}%
						</Text>
					)}
					<Progress
						value={overallPct}
						colorScheme={
							isCompleted
								? isEmptyMigration
									? 'orange'
									: 'green'
								: isFailed
									? 'red'
									: isCancelled
										? 'gray'
										: 'primary'
						}
						h="4px"
						borderRadius="full"
						isAnimated={isRunning}
						hasStripe={isRunning}
						sx={{ '& > div': { transition: 'width 0.1s linear' } }}
					/>
				</Box>

				{/* Step rows */}
				{!progress ? (
					<VStack spacing="0" align="stretch">
						{Array.from({ length: 5 }).map((_, i) => (
							<React.Fragment key={i}>
								{i > 0 && <Box h="1px" bg="gray.100" />}
								<HStack px="6" py="3" justify="space-between">
									<HStack spacing="2.5">
										<Skeleton w="7" h="7" borderRadius="lg" flexShrink={0} />
										<Skeleton height="12px" width="90px" borderRadius="base" />
									</HStack>
									<Skeleton height="12px" width="36px" borderRadius="base" />
								</HStack>
							</React.Fragment>
						))}
					</VStack>
				) : (
					<Box
						maxH="340px"
						overflowY="auto"
						css={{
							scrollbarWidth: 'thin',
							scrollbarColor: '#CBD5E0 transparent',
						}}
					>
						{Object.entries(progress.steps).map(([slug, step], index) => {
							const isSkipped = step.total === 0;
							const isActive =
								slug === progress.current_step && !isTerminal && !isSkipped;
							const isStepDone = !isSkipped && (step.pct >= 100 || isCompleted);
							const labelColor = isSkipped
								? 'gray.400'
								: isStepDone || isActive
									? 'gray.700'
									: 'gray.400';
							const barColor = isStepDone
								? 'green'
								: step.failed > 0
									? 'orange'
									: 'primary';

							const iconBoxBg = isSkipped
								? 'gray.100'
								: isStepDone
									? 'green.50'
									: isActive
										? 'primary.100'
										: isFailed && step.failed > 0
											? 'red.50'
											: 'gray.100';

							const stepIconColor = isSkipped
								? 'gray.300'
								: isStepDone
									? 'green.500'
									: isActive
										? 'primary.500'
										: isFailed && step.failed > 0
											? 'red.400'
											: 'gray.400';

							return (
								<React.Fragment key={slug}>
									{index > 0 && <Box h="1px" bg="gray.100" />}
									<Box px="6" py="3">
										<HStack justify="space-between" mb={isActive ? 1.5 : 0}>
											<HStack spacing="2.5">
												<Box
													w="7"
													h="7"
													borderRadius="lg"
													bg={iconBoxBg}
													display="flex"
													alignItems="center"
													justifyContent="center"
													flexShrink={0}
													color={stepIconColor}
												>
													{isActive && isRunning ? (
														<Spinner
															size="xs"
															color="primary.500"
															speed="0.9s"
														/>
													) : (
														<StepIcon slug={slug} />
													)}
												</Box>

												<Text
													fontSize="xs"
													fontWeight={isActive ? '600' : '400'}
													color={labelColor}
												>
													{stepSlugToLabel(slug)}
												</Text>
											</HStack>

											<HStack spacing="2" align="flex-start">
												{!isSkipped && step.failed > 0 && (
													<Text fontSize="xs" color="red.400">
														{step.failed}{' '}
														{__('failed', 'learning-management-system')}
													</Text>
												)}
												<Text fontSize="xs" color="gray.400" fontWeight="500">
													{isSkipped
														? '—'
														: `${step.completed.toLocaleString()} / ${step.total.toLocaleString()}`}
												</Text>
											</HStack>
										</HStack>

										{isActive && (
											<Box mt="1.5">
												<Progress
													value={isStepDone ? 100 : step.pct}
													colorScheme={barColor}
													size="xs"
													borderRadius="full"
													isAnimated={isRunning}
													hasStripe={isRunning}
													sx={{
														'& > div': { transition: 'width 0.08s linear' },
													}}
												/>
											</Box>
										)}
									</Box>
								</React.Fragment>
							);
						})}
					</Box>
				)}

				{/* Footer — always visible for consistent modal shape */}
				<HStack
					justify="flex-end"
					spacing="2"
					px="6"
					py="3"
					borderTop="1px solid"
					borderColor="gray.100"
				>
					{isTerminal ? (
						<Button
							size="sm"
							colorScheme="primary"
							onClick={onClose}
							boxShadow="none"
						>
							{__('Close', 'learning-management-system')}
						</Button>
					) : (
						<Button
							size="sm"
							variant="unstyled"
							color="red.500"
							onClick={onCancel}
							boxShadow="none"
							fontSize="sm"
							fontWeight="500"
							_hover={{ color: isCancelling ? 'red.500' : 'red.600' }}
							isDisabled={isCancelling}
							display="flex"
							alignItems="center"
							gap="1.5"
						>
							{isCancelling && (
								<Spinner size="xs" color="red.500" speed="0.9s" />
							)}
							{isCancelling
								? __('Cancelling...', 'learning-management-system')
								: __('Cancel Migration', 'learning-management-system')}
						</Button>
					)}
				</HStack>
			</ModalContent>
		</Modal>
	);
};

export default React.memo(MigrationStatusDisplay);
