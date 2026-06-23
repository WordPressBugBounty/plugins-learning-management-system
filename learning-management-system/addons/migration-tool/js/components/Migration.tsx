import {
	Badge,
	Box,
	Button,
	CloseButton,
	FormControl,
	FormLabel,
	HStack,
	Input,
	InputGroup,
	InputRightElement,
	Modal,
	ModalBody,
	ModalContent,
	ModalFooter,
	ModalHeader,
	ModalOverlay,
	SimpleGrid,
	Skeleton,
	Stack,
	Tag,
	Text,
	Wrap,
	WrapItem,
	useDisclosure,
	useToast,
} from '@chakra-ui/react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import React, { useCallback, useEffect, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import CustomAlert from '../../../../assets/js/back-end/components/common/CustomAlert';
import routes from '../../../../assets/js/back-end/constants/routes';
import backendUrls from '../../../../assets/js/back-end/constants/urls';
import API from '../../../../assets/js/back-end/utils/api';
import { urls } from '../constants/urls';
import MigrationStatusDisplay from './MigrationStatusDisplay';

export interface StepProgress {
	total: number;
	completed: number;
	failed: number;
	offset: number;
	pct: number;
}

export interface MigrationProgress {
	session_id: string;
	status: 'running' | 'completed' | 'failed' | 'cancelled';
	lms_slug: string;
	current_step: string;
	steps: Record<string, StepProgress>;
	overall_pct: number;
	failed_total: number;
}

interface LastCompleted {
	lms_slug: string;
	lms_label: string;
	completed_at: number | null;
}

interface ActiveSessionResponse {
	data: MigrationProgress | null;
	last_completed: LastCompleted | null;
}

interface LMS {
	name: string;
	label: string;
	steps: string[];
	active?: boolean;
}

const TERMINAL_STATUSES = ['completed', 'failed', 'cancelled'];
const NOTICE_DISMISSED_KEY = 'masteriyo_migration_notice_dismissed';

// Untranslated on purpose so the confirmation gate matches regardless of site locale.
const CONFIRM_KEYWORD = 'confirm';

/** Inline "Logs" link used inside migration result notices. */
const LogsLink: React.FC<{ onClick: () => void }> = ({ onClick }) => (
	<Text
		as="span"
		color="primary.500"
		fontWeight="600"
		cursor="pointer"
		textDecoration="underline"
		onClick={onClick}
	>
		{__('Logs', 'learning-management-system')}
	</Text>
);

const LMS_DESCRIPTIONS: Record<string, string[]> = {
	'sfwd-lms': [
		'Courses',
		'Users',
		'Enrollments',
		'Orders',
		'Course Progress',
		'Quiz Attempts',
	],
	learnpress: [
		'Courses',
		'Users',
		'Enrollments',
		'Orders',
		'Reviews',
		'Quiz Attempts',
		'Course Progress',
	],
	lifterlms: [
		'Courses',
		'Users',
		'Enrollments',
		'Orders',
		'Reviews',
		'Course Progress',
		'Quiz Attempts',
	],
	masterstudy: [
		'Courses',
		'Users',
		'Enrollments',
		'Orders',
		'Reviews',
		'Course Progress',
		'Quiz Attempts',
		'Wishlists',
	],
	tutor: [
		'Courses',
		'Users',
		'Enrollments',
		'Orders',
		'Reviews',
		'Questions & Answers',
		'Announcements',
		'Course Progress',
		'Quiz Attempts',
		'Google Meet',
		'Wishlists',
	],
};

function useLMSsQuery() {
	const LMSsAPI = new API(urls.migrationLMSs);
	return useQuery({
		queryKey: ['migrationLMSsList'],
		queryFn: () => LMSsAPI.list(),
	});
}

interface MigrationProps {
	onLogsClick?: () => void;
}

const Migration: React.FC<MigrationProps> = ({ onLogsClick }) => {
	const [sessionId, setSessionId] = useState<string | null>(null);
	const [selectedLMS, setSelectedLMS] = useState<LMS | null>(null);
	const [isDone, setIsDone] = useState(false);
	const [migratedTotal, setMigratedTotal] = useState(0);
	const [noticeDismissed, setNoticeDismissed] = useState<boolean>(
		() => localStorage.getItem(NOTICE_DISMISSED_KEY) === '1',
	);
	const [lastNoticeDismissed, setLastNoticeDismissed] = useState(false);
	const [resumableSession, setResumableSession] =
		useState<MigrationProgress | null>(null);
	const [lastCompleted, setLastCompleted] = useState<LastCompleted | null>(
		null,
	);
	const [confirmText, setConfirmText] = useState('');
	const toast = useToast();
	const {
		isOpen: isConfirmOpen,
		onOpen: openConfirm,
		onClose: closeConfirm,
	} = useDisclosure();

	// Require an exact keyword match before enabling Start, guarding against accidental or duplicate runs.
	const isConfirmMatch = confirmText === CONFIRM_KEYWORD;

	// Reset the field on close so the dialog always reopens blank.
	const handleCloseConfirm = useCallback(() => {
		setConfirmText('');
		closeConfirm();
	}, [closeConfirm]);
	const queryClient = useQueryClient();
	const navigate = useNavigate();

	// Resolve the migration-tool log file ID once so "Logs" links point directly
	// to the specific file instead of the generic Logs tab. The file name is
	// stable for the life of the page, so we never need to refetch.
	const migrationLogQuery = useQuery({
		queryKey: ['migrationToolLog'],
		queryFn: () =>
			new API(backendUrls.logs).list({
				search: 'migration-tool',
				per_page: 1,
				orderby: 'date',
				order: 'desc',
			}),
		staleTime: Infinity,
	});
	const rawLogId = migrationLogQuery.data?.data?.[0]?.id ?? null;
	const migrationLogId: string | null =
		typeof rawLogId === 'string' && rawLogId.startsWith('migration-tool')
			? rawLogId
			: null;
	const hasLogsLink = migrationLogId != null;

	// Deep-link to the migration-tool log file when it exists; fall back to the
	// generic Logs tab handler supplied by the parent otherwise.
	const handleLogsClick = useCallback(() => {
		if (migrationLogId != null) {
			navigate(routes.log.replace(':id', migrationLogId.toString()));
		} else {
			onLogsClick?.();
		}
	}, [migrationLogId, navigate, onLogsClick]);

	const migrationLMSsQuery = useLMSsQuery();

	// On mount, check whether a migration was running before the page was closed.
	const activeSessionQuery = useQuery<ActiveSessionResponse>({
		queryKey: ['migrationActiveSession'],
		queryFn: () =>
			apiFetch<ActiveSessionResponse>({ path: urls.migrationActive }),
		staleTime: Infinity,
	});

	// Restore state once both the active-session check and LMS list have loaded.
	useEffect(() => {
		const resp = activeSessionQuery.data;
		const lmsOptions: LMS[] = migrationLMSsQuery.data?.data ?? [];
		if (!resp || sessionId) return;

		if (resp.last_completed) {
			setLastCompleted(resp.last_completed);
		}

		const active = resp.data;
		if (!active) return;

		const matchedLMS =
			lmsOptions.find((l) => l.name === active.lms_slug) ?? null;
		setSelectedLMS(matchedLMS);
		queryClient.setQueryData(['migrationStatus', active.session_id], active);

		if (active.status === 'completed') {
			setMigratedTotal(
				Object.values(active.steps).reduce((s, st) => s + st.total, 0),
			);
			setIsDone(true);
		} else {
			// Don't auto-open the modal — let the user choose to resume.
			setResumableSession(active);
		}
	}, [
		activeSessionQuery.data,
		migrationLMSsQuery.data,
		sessionId,
		queryClient,
	]);

	// Poll from whichever session is active: the open modal (sessionId) takes
	// priority; the background banner (resumableSession) keeps updating when the
	// modal is closed so the user sees live progress without reopening it.
	const activeSessionId = sessionId ?? resumableSession?.session_id ?? null;

	const statusQuery = useQuery<MigrationProgress>({
		queryKey: ['migrationStatus', activeSessionId],
		queryFn: () =>
			apiFetch<MigrationProgress>({
				path: urls.migrationStatus(activeSessionId!),
			}),
		enabled: !!activeSessionId,
		refetchOnMount: 'always',
		refetchInterval: (query) =>
			TERMINAL_STATUSES.includes(query.state.data?.status ?? '') ? false : 4000,
	});

	const progress = statusQuery.data;

	useEffect(() => {
		if (!progress) return;

		if (progress.status === 'completed') {
			queryClient.invalidateQueries({ queryKey: ['courseList'] });
			setMigratedTotal(
				Object.values(progress.steps).reduce((s, st) => s + st.total, 0),
			);
			setIsDone(true);
			setResumableSession(null);
			queryClient.invalidateQueries({ queryKey: ['migrationActiveSession'] });
		} else if (TERMINAL_STATUSES.includes(progress.status)) {
			setResumableSession(null);
			queryClient.invalidateQueries({ queryKey: ['migrationActiveSession'] });
		} else {
			setResumableSession(progress);
		}
	}, [progress, queryClient]);

	const startMutation = useMutation({
		mutationFn: (lmsName: string) =>
			apiFetch<{ session_id: string; status: string }>({
				path: urls.migrationStart,
				method: 'POST',
				data: { lms_name: lmsName },
			}),
		onSuccess: (data) => {
			// Purge any stale status cache from previous sessions before mounting the new one.
			queryClient.removeQueries({ queryKey: ['migrationStatus'] });
			queryClient.invalidateQueries({ queryKey: ['migrationActiveSession'] });
			setSessionId(data.session_id);
			localStorage.removeItem(NOTICE_DISMISSED_KEY);
			setNoticeDismissed(false);
		},
		onError: (err: any) => {
			toast({
				title:
					err?.message ??
					__('Failed to start migration.', 'learning-management-system'),
				status: 'error',
				isClosable: true,
			});
		},
	});

	// Start only once the keyword is confirmed and a source LMS is selected.
	const handleConfirmStart = useCallback(() => {
		if (!isConfirmMatch || !selectedLMS) return;
		handleCloseConfirm();
		startMutation.mutate(selectedLMS.name);
	}, [isConfirmMatch, selectedLMS, handleCloseConfirm, startMutation]);

	const cancelMutation = useMutation({
		mutationFn: () =>
			apiFetch({ path: urls.migrationCancel(sessionId!), method: 'DELETE' }),
		onSuccess: () => {
			queryClient.invalidateQueries({
				queryKey: ['migrationStatus', sessionId],
			});
			queryClient.invalidateQueries({ queryKey: ['migrationActiveSession'] });
			setResumableSession(null);
		},
		onError: (err: any) => {
			toast({
				title:
					err?.message ??
					__('Failed to cancel migration.', 'learning-management-system'),
				status: 'error',
				isClosable: true,
			});
		},
	});

	const dismissCompletedNotice = useCallback(() => {
		localStorage.setItem(NOTICE_DISMISSED_KEY, '1');
		setNoticeDismissed(true);
		setIsDone(false);
	}, []);

	const onClose = useCallback(() => {
		if (progress && !TERMINAL_STATUSES.includes(progress.status)) {
			// Minimize: hide the modal but let the background job keep running.
			// The "View Progress" banner will reappear so the user can reopen it.
			setResumableSession(progress);
			setSessionId(null);
		} else {
			queryClient.removeQueries({ queryKey: ['migrationStatus', sessionId] });
			setSessionId(null);
		}
	}, [sessionId, progress, queryClient]);

	const lmsOptions: LMS[] = migrationLMSsQuery.data?.data ?? [];

	return (
		<Stack direction="column" spacing="6">
			<Box>
				<Text fontSize="md" fontWeight="600" color="gray.700">
					{__('Select source LMS', 'learning-management-system')}
				</Text>
				<Text fontSize="sm" color="gray.500" mt="1">
					{__(
						'Select the LMS plugin you want to migrate data from. Only installed and active plugins are available.',
						'learning-management-system',
					)}
				</Text>
			</Box>

			{/* In-progress session detected after page reload */}
			{resumableSession && !sessionId && (
				<CustomAlert status="info" mb={0}>
					<HStack
						justify="space-between"
						align="center"
						w="full"
						flexWrap="wrap"
						gap="2"
					>
						<Text fontSize="sm" color="gray.700" lineHeight="1.5">
							{__('A migration from', 'learning-management-system')}{' '}
							<Text as="strong">
								{selectedLMS?.label ?? resumableSession.lms_slug}
							</Text>{' '}
							{__('is in progress', 'learning-management-system')} (
							{resumableSession.overall_pct}%
							{__(' complete', 'learning-management-system')}).
						</Text>
						<Button
							size="sm"
							colorScheme="primary"
							boxShadow="none"
							onClick={() => {
								queryClient.invalidateQueries({
									queryKey: ['migrationStatus', resumableSession.session_id],
								});
								setSessionId(resumableSession.session_id);
								setResumableSession(null);
							}}
						>
							{__('View Progress', 'learning-management-system')}
						</Button>
					</HStack>
				</CustomAlert>
			)}

			{/* Last completed migration info — shown when idle (no active session, not just finished) */}
			{!isDone &&
				!resumableSession &&
				!sessionId &&
				lastCompleted &&
				!lastNoticeDismissed && (
					<CustomAlert status="info" mb={0}>
						<HStack justify="space-between" align="flex-start" w="full" gap="2">
							<Text fontSize="sm" color="gray.700" lineHeight="1.5">
								{__('Data from', 'learning-management-system')}{' '}
								<Text as="strong">
									{lastCompleted.lms_label || lastCompleted.lms_slug}
								</Text>{' '}
								{__(
									'was successfully migrated into Masteriyo',
									'learning-management-system',
								)}
								{lastCompleted.completed_at
									? ` ${__('on', 'learning-management-system')} ${new Date(lastCompleted.completed_at * 1000).toLocaleString(undefined, { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })}`
									: ''}
								{'.'}
								{hasLogsLink && (
									<>
										{' '}
										{__('Open the', 'learning-management-system')}{' '}
										<LogsLink onClick={handleLogsClick} />{' '}
										{__(
											'tab to review the full report.',
											'learning-management-system',
										)}
									</>
								)}
							</Text>
							<CloseButton
								size="sm"
								onClick={() => setLastNoticeDismissed(true)}
								flexShrink={0}
								aria-label={__('Dismiss', 'learning-management-system')}
							/>
						</HStack>
					</CustomAlert>
				)}

			<SimpleGrid columns={3} spacing="3">
				{migrationLMSsQuery.isLoading
					? Array.from({ length: 5 }).map((_, i) => (
							<Skeleton key={i} height="64px" borderRadius="lg" />
						))
					: lmsOptions.map((lms) => {
							const isActive = lms.active !== false;
							const isSelected = selectedLMS?.name === lms.name;
							return (
								<Box
									key={lms.name}
									border="1px solid"
									borderColor={isSelected ? 'primary.500' : 'gray.200'}
									borderRadius="lg"
									p="3"
									cursor={isActive ? 'pointer' : 'not-allowed'}
									opacity={isActive ? 1 : 0.65}
									bg={isSelected ? 'primary.10' : 'white'}
									transition="border-color 0.15s, background 0.15s"
									_hover={
										isActive && !isSelected
											? { borderColor: 'primary.300', bg: 'primary.10' }
											: {}
									}
									onClick={() => {
										if (!isActive) return;
										if (isDone) {
											setIsDone(false);
											setSessionId(null);
										}
										setSelectedLMS(lms);
									}}
								>
									<HStack
										spacing="1.5"
										mb="2.5"
										alignItems="center"
										wrap="wrap"
									>
										<Text fontSize="sm" fontWeight="700" color="gray.700">
											{lms.label}
										</Text>
										{!isActive && (
											<Badge
												fontSize="xs"
												colorScheme="gray"
												variant="subtle"
												fontWeight="500"
												textTransform="none"
											>
												{__('Not Active', 'learning-management-system')}
											</Badge>
										)}
										{isActive && isSelected && (
											<Box
												ms="auto"
												lineHeight="0"
												flexShrink={0}
												color="primary.500"
											>
												<svg
													width="15"
													height="15"
													viewBox="0 0 24 24"
													fill="currentColor"
												>
													<path d="M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2zm-1 14.4l-3.7-3.7 1.4-1.4 2.3 2.3 4.9-4.9 1.4 1.4-6.3 6.3z" />
												</svg>
											</Box>
										)}
									</HStack>
									<Wrap spacing="1" mt="1">
										{(LMS_DESCRIPTIONS[lms.name] ?? [lms.label]).map((item) => (
											<WrapItem key={item}>
												<Tag
													size="sm"
													colorScheme={
														isSelected && isActive ? 'primary' : 'gray'
													}
													variant="subtle"
												>
													{item}
												</Tag>
											</WrapItem>
										))}
									</Wrap>
								</Box>
							);
						})}
			</SimpleGrid>

			{isDone && !noticeDismissed && (
				<CustomAlert
					status={migratedTotal === 0 ? 'warning' : 'success'}
					mb={0}
				>
					<HStack justify="space-between" align="flex-start" w="full" gap="2">
						<Text fontSize="sm" color="gray.700" lineHeight="1.5">
							{migratedTotal === 0 ? (
								<>
									{__(
										'No data was found to migrate from',
										'learning-management-system',
									)}{' '}
									<Text as="strong">{selectedLMS?.label}</Text>
									{__(
										'. The source LMS may be empty.',
										'learning-management-system',
									)}
								</>
							) : (
								<>
									{__('All data from', 'learning-management-system')}{' '}
									<Text as="strong">{selectedLMS?.label}</Text>{' '}
									{__(
										'has been successfully migrated into Masteriyo.',
										'learning-management-system',
									)}
									{hasLogsLink && (
										<>
											{' '}
											{__('Open the', 'learning-management-system')}{' '}
											<LogsLink onClick={handleLogsClick} />{' '}
											{__(
												'tab to review the full report, including any skipped or failed items.',
												'learning-management-system',
											)}
										</>
									)}
								</>
							)}
						</Text>
						<CloseButton
							size="sm"
							onClick={dismissCompletedNotice}
							flexShrink={0}
							aria-label={__('Dismiss', 'learning-management-system')}
						/>
					</HStack>
				</CustomAlert>
			)}

			<HStack pt="4" borderTop="1px solid" borderColor="gray.100">
				<Button
					onClick={() => {
						if (resumableSession) {
							queryClient.invalidateQueries({
								queryKey: ['migrationStatus', resumableSession.session_id],
							});
							setSessionId(resumableSession.session_id);
							setResumableSession(null);
						} else if (selectedLMS) {
							openConfirm();
						}
					}}
					isDisabled={
						!!sessionId ||
						(resumableSession
							? false
							: !selectedLMS ||
								startMutation.isPending ||
								activeSessionQuery.isLoading)
					}
					isLoading={startMutation.isPending && !resumableSession}
					loadingText={__('Starting...', 'learning-management-system')}
					colorScheme="primary"
					boxShadow="none"
				>
					{sessionId
						? __('Migrating...', 'learning-management-system')
						: resumableSession
							? __('View Progress', 'learning-management-system')
							: __('Start Migration', 'learning-management-system')}
				</Button>
			</HStack>

			<Modal
				isOpen={isConfirmOpen}
				onClose={handleCloseConfirm}
				isCentered
				size="md"
			>
				<ModalOverlay bg="blackAlpha.600" />
				<ModalContent borderRadius="xl" mx="4" boxShadow="xl">
					<ModalHeader fontSize="md" fontWeight="700" pt="6" px="6" pb="1">
						{__('Confirm Migration', 'learning-management-system')}
					</ModalHeader>
					<ModalBody px="6" pb="2">
						<Stack spacing="5">
							<CustomAlert status="warning" isAlertIconTop mb={0}>
								<Text fontSize="sm" color="gray.700" lineHeight="1.6">
									<Text as="strong">
										{__('Important:', 'learning-management-system')}
									</Text>{' '}
									{__(
										'This will permanently migrate all courses, enrollments, and user progress from',
										'learning-management-system',
									)}{' '}
									<Text as="strong">{selectedLMS?.label}</Text>{' '}
									{__('into Masteriyo. Always', 'learning-management-system')}{' '}
									<Text as="strong">
										{__('back up your database', 'learning-management-system')}
									</Text>{' '}
									{__(
										'and test on a staging site before running on production.',
										'learning-management-system',
									)}
								</Text>
							</CustomAlert>

							<FormControl>
								<FormLabel
									fontSize="sm"
									fontWeight="500"
									color="gray.600"
									mb="2"
									lineHeight="1.5"
								>
									{__('Type', 'learning-management-system')}{' '}
									<Box
										as="span"
										display="inline-block"
										bg="gray.100"
										color="gray.800"
										fontFamily="mono"
										fontWeight="700"
										fontSize="xs"
										px="1.5"
										py="0.5"
										borderRadius="md"
										border="1px solid"
										borderColor="gray.200"
									>
										{CONFIRM_KEYWORD}
									</Box>{' '}
									{__('to start the migration.', 'learning-management-system')}
								</FormLabel>
								<InputGroup>
									<Input
										value={confirmText}
										onChange={(e) => setConfirmText(e.target.value)}
										onKeyDown={(e) => {
											if (e.key === 'Enter') {
												e.preventDefault();
												handleConfirmStart();
											}
										}}
										placeholder={__(
											'Type "confirm" here…',
											'learning-management-system',
										)}
										autoFocus
										autoComplete="off"
										spellCheck={false}
										autoCapitalize="off"
										h="44px"
										fontSize="sm"
										borderRadius="lg"
										textTransform="none"
										borderColor={isConfirmMatch ? 'green.400' : 'gray.300'}
										focusBorderColor={
											isConfirmMatch ? 'green.400' : 'primary.500'
										}
										_placeholder={{ color: 'gray.400', textTransform: 'none' }}
										aria-label={__(
											'Type confirm to start the migration',
											'learning-management-system',
										)}
									/>
									{isConfirmMatch && (
										<InputRightElement h="44px" pe="2" pointerEvents="none">
											<Box color="green.500" lineHeight="0">
												<svg
													width="18"
													height="18"
													viewBox="0 0 24 24"
													fill="currentColor"
												>
													<path d="M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2zm-1 14.4l-3.7-3.7 1.4-1.4 2.3 2.3 4.9-4.9 1.4 1.4-6.3 6.3z" />
												</svg>
											</Box>
										</InputRightElement>
									)}
								</InputGroup>
							</FormControl>
						</Stack>
					</ModalBody>
					<ModalFooter gap="3" px="6" pt="4" pb="6">
						<Button
							variant="ghost"
							onClick={handleCloseConfirm}
							size="sm"
							color="gray.600"
							fontWeight="500"
						>
							{__('Cancel', 'learning-management-system')}
						</Button>
						<Button
							colorScheme="primary"
							boxShadow="none"
							size="sm"
							px="5"
							fontWeight="600"
							isDisabled={!isConfirmMatch}
							onClick={handleConfirmStart}
						>
							{__('Confirm & Start', 'learning-management-system')}
						</Button>
					</ModalFooter>
				</ModalContent>
			</Modal>

			{sessionId && (
				<MigrationStatusDisplay
					progress={progress ?? null}
					lmsLabel={selectedLMS?.label ?? ''}
					onClose={onClose}
					onCancel={() => cancelMutation.mutate()}
					isCancelling={cancelMutation.isPending}
				/>
			)}
		</Stack>
	);
};

export default Migration;
