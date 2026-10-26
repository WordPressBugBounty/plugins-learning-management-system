import {
	AlertDialog,
	AlertDialogBody,
	AlertDialogContent,
	AlertDialogFooter,
	AlertDialogHeader,
	AlertDialogOverlay,
	Box,
	Button,
	Container,
	Flex,
	Heading,
	HStack,
	Icon,
	Stack,
	Text,
	useColorMode,
	useDisclosure,
	useToast,
	VStack,
} from '@chakra-ui/react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { __, _x, sprintf } from '@wordpress/i18n';
import React, {
	useCallback,
	useEffect,
	useMemo,
	useRef,
	useState,
} from 'react';
import {
	BiCheckCircle,
	BiCheckDouble,
	BiChevronRight,
	BiInfoCircle,
	BiRepeat,
	BiTime,
} from 'react-icons/bi';
import { useNavigate, useParams } from 'react-router-dom';
import MasteriyoPagination from '../../../../assets/js/back-end/components/common/MasteriyoPagination';
import { paginationSx } from '../../../../assets/js/interactive/pages/quiz/components/paginationSx';
import { Check } from '../../../../assets/js/back-end/constants/images';
import urls from '../../../../assets/js/back-end/constants/urls';
import API from '../../../../assets/js/back-end/utils/api';
import { masteriyoHumanizer } from '../../../../assets/js/back-end/utils/utils';
import ContentErrorDisplay from '../../../../assets/js/interactive/components/ContentErrorDisplay';
import ContentNav from '../../../../assets/js/interactive/components/ContentNav';
import QuizTimer from '../../../../assets/js/interactive/components/QuizTimer';
import {
	attemptsLeftOf,
	passMarkOf,
	pointsHint,
} from '../../../../assets/js/interactive/pages/quiz/components/QuizStart';
import StatCells from '../../../../assets/js/interactive/pages/quiz/components/StatCells';
import { COLORS_BASED_ON_SCREEN_COLOR_MODE } from '../../../../assets/js/interactive/constants/general';
import { useCourseContext } from '@interactive/context/CourseContext';
import { ContentQueryError } from '../../../../assets/js/back-end/schemas';
import LessonSkeleton from '../../../../assets/js/interactive/skeleton/LessonSkeleton';
import { previewFlag } from '../../../../assets/js/interactive/utils/helper';
import localized from '../../../../assets/js/interactive/utils/global';
import RedirectNavigation from '../../../../assets/js/interactive/utils/RedirectNavigation';
import h5pUrls from '../../constants/urls';
import H5PScoreBoard, { QuestionScore } from './H5PScoreBoard';
import {
	containerFor,
	useContentWidth,
} from '../../../../assets/js/interactive/hooks/useContentWidth';
import ContentWidthButton from '../../../../assets/js/interactive/components/card/ContentWidthButton';

interface H5PQuizSchema {
	id: number;
	name: string;
	description: string;
	h5p_content_id: number;
	h5p_content_ids?: number[];
	h5p_iframe_url: string;
	h5p_iframe_urls?: string[];
	h5p_library_name: string;
	full_marks: number;
	pass_mark: number;
	pass_mark_type: 'point' | 'percentage';
	attempts_allowed: number;
	duration: number;
	show_result: boolean;
	questions_display_per_page: number;
	questions_display_per_page_global: number;
	randomize_questions: boolean;
	parent_menu_order?: number;
	navigation?: any;
}

interface H5PQuizAttemptSchema {
	id: number;
	h5p_quiz_id: number;
	h5p_content_id: number;
	course_id: number;
	user_id: number;
	attempt_number: number;
	score: number;
	max_score: number;
	percentage: number;
	passed: string;
	total_answered_questions: number;
	total_correct_answers: number;
	total_incorrect_answers: number;
	question_scores: QuestionScore[];
	status: 'started' | 'finished';
	started_at: string | null;
	finished_at: string | null;
}

/**
 * Deterministic Fisher-Yates shuffle (mulberry32). Seeding by attempt id keeps
 * the randomized order stable across reloads/resume for the whole attempt.
 */
function seededShuffle(length: number, seed: number): number[] {
	const arr = Array.from({ length }, (_, i) => i);
	let s = seed >>> 0 || 1;
	const rand = () => {
		s |= 0;
		s = (s + 0x6d2b79f5) | 0;
		let t = Math.imul(s ^ (s >>> 15), 1 | s);
		t = (t + Math.imul(t ^ (t >>> 7), 61 | t)) ^ t;
		return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
	};
	for (let i = arr.length - 1; i > 0; i--) {
		const j = Math.floor(rand() * (i + 1));
		[arr[i], arr[j]] = [arr[j], arr[i]];
	}
	return arr;
}

const InteractiveH5PQuiz = () => {
	const { h5pQuizId, courseId }: any = useParams();
	const navigate = useNavigate();
	const toast = useToast();
	const queryClient = useQueryClient();
	const { colorMode } = useColorMode();
	const [contentWidth, cycleContentWidth] = useContentWidth();
	const { setContentData, setActiveContentId } = useCourseContext();

	const cancelRef = useRef<any>();

	const {
		isOpen: isSubmitOpen,
		onOpen: onSubmitOpen,
		onClose: onSubmitClose,
	} = useDisclosure();

	const quizAPI = useMemo(() => new API(h5pUrls.h5pQuizzes), []);
	const attemptAPI = useMemo(() => new API(h5pUrls.h5pQuizAttempts), []);
	const progressItemAPI = useMemo(() => new API(urls.courseProgressItem), []);

	const [activeAttempt, setActiveAttempt] =
		useState<H5PQuizAttemptSchema | null>(null);
	const [iframeKey, setIframeKey] = useState(0);
	const initializedRef = useRef(false);

	// Track per-question xAPI score: contentId → { score, max, success, completion }
	const [completedScores, setCompletedScores] = useState<
		Record<
			number,
			{
				score: number;
				max: number;
				success: boolean | null;
				completion: boolean;
			}
		>
	>({});
	// Per-content max score from the renderer's getMaxScore() so skipped questions still count toward the total at finish.
	const [contentMaxScores, setContentMaxScores] = useState<
		Record<number, number>
	>({});
	const finishingRef = useRef(false);

	// Pagination state.
	const [currentPage, setCurrentPage] = useState(0);

	// Iframe auto-resize: keyed by `${iframeKey}-${contentId}`.
	const [iframeHeights, setIframeHeights] = useState<Record<string, number>>(
		{},
	);
	const iframeEls = useRef<Record<string, HTMLIFrameElement | null>>({});
	const resizeObservers = useRef<Record<string, ResizeObserver>>({});

	// Cleanup any stale ResizeObservers on attempt reset.
	useEffect(() => {
		const observers = resizeObservers.current;
		return () => {
			Object.values(observers).forEach((obs) => obs.disconnect());
			resizeObservers.current = {};
		};
	}, [iframeKey]);

	// Listen for { type:'masteriyo_h5p_height' } posted by the renderer iframes (H5P's own resize message never reaches this page).
	useEffect(() => {
		const handler = (event: MessageEvent) => {
			if (event.origin !== window.location.origin) return;
			const msg = event.data;
			if (
				msg?.type !== 'masteriyo_h5p_height' ||
				typeof msg.height !== 'number'
			)
				return;
			if (!msg.height) return;
			Object.entries(iframeEls.current).forEach(([key, iframe]) => {
				if (iframe && iframe.contentWindow === event.source) {
					// Use height as-is — adding padding here creates an infinite grow feedback loop with the renderer's own measurement.
					setIframeHeights((prev) => {
						if (prev[key] === msg.height) return prev;
						return { ...prev, [key]: msg.height };
					});
				}
			});
		};
		window.addEventListener('message', handler);
		return () => window.removeEventListener('message', handler);
	}, []);

	// Cache one stable ref callback per key so React only fires null→el on real mount/unmount, not on every re-render.
	const iframeRefCache = useRef<
		Record<string, (el: HTMLIFrameElement | null) => void>
	>({});

	const getIframeRef = useCallback(
		(key: string): ((el: HTMLIFrameElement | null) => void) => {
			if (!iframeRefCache.current[key]) {
				iframeRefCache.current[key] = (el: HTMLIFrameElement | null) => {
					if (!el) {
						// Real unmount (page navigation / key change) — disconnect and prune.
						resizeObservers.current[key]?.disconnect();
						delete resizeObservers.current[key];
						delete iframeEls.current[key];
						delete iframeRefCache.current[key];
						return;
					}
					iframeEls.current[key] = el;
				};
			}
			return iframeRefCache.current[key];
		},
		[],
	);

	const handleIframeLoad = useCallback((key: string) => {
		const iframe = iframeEls.current[key];
		if (!iframe) return;

		try {
			const doc = iframe.contentDocument;
			if (!doc) return;

			const measure = () => {
				const wrap = doc.querySelector(
					'.masteriyo-h5p-wrap',
				) as HTMLElement | null;
				// Max of scrollHeight (full height even under overflow:hidden) and the rect so neither tall nor short content underreports.
				const h = wrap
					? Math.max(
							wrap.scrollHeight,
							Math.ceil(wrap.getBoundingClientRect().height),
						)
					: (doc.body?.scrollHeight ?? 0);
				if (h > 50) {
					setIframeHeights((prev) =>
						prev[key] === h ? prev : { ...prev, [key]: h },
					);
				}
			};

			// Disconnect any stale observer for this key before attaching a new one.
			if (resizeObservers.current[key]) {
				resizeObservers.current[key].disconnect();
			}

			// Observe .masteriyo-h5p-wrap (or body) from the parent so every H5P height change is caught, not just postMessage.
			const target = (doc.querySelector('.masteriyo-h5p-wrap') ??
				doc.body) as Element;
			const ro = new ResizeObserver(measure);
			ro.observe(target);
			resizeObservers.current[key] = ro;

			measure(); // immediate read; postMessage updates arrive later as well
		} catch (_) {}
	}, []);

	useEffect(() => {
		setActiveContentId(h5pQuizId);
	}, [h5pQuizId, setActiveContentId]);

	const quizQuery = useQuery<H5PQuizSchema, ContentQueryError>({
		queryKey: [`interactiveH5PQuiz${h5pQuizId}`, h5pQuizId],
		queryFn: () => quizAPI.get(h5pQuizId),
	});

	const attemptsQuery = useQuery<H5PQuizAttemptSchema[], Error>({
		queryKey: [`h5pQuizAttempts${h5pQuizId}`, h5pQuizId],
		queryFn: () =>
			attemptAPI
				.list({ h5p_quiz_id: h5pQuizId })
				.then((res: any) => res?.data ?? []),
	});

	const progressItemQuery = useQuery<any>({
		queryKey: [`h5pProgressItem${h5pQuizId}`, h5pQuizId],
		queryFn: () =>
			progressItemAPI.list({ item_id: h5pQuizId, course_id: courseId }),
	});

	useEffect(() => {
		if (quizQuery?.isSuccess) {
			setContentData(quizQuery?.data);
		}
	}, [quizQuery?.data, quizQuery?.isSuccess, setContentData]);

	// Reset attempt-tracking state when navigating to a different H5P quiz.
	useEffect(() => {
		setActiveAttempt(null);
		setCompletedScores({});
		setCurrentPage(0);
		finishingRef.current = false;
		initializedRef.current = false;
	}, [h5pQuizId]);

	// Resume an in-progress attempt on first load.
	useEffect(() => {
		if (attemptsQuery?.isSuccess && !initializedRef.current) {
			initializedRef.current = true;
			const started = (attemptsQuery?.data || []).find(
				(a) => a.status === 'started',
			);
			if (started) {
				setActiveAttempt(started);
				setIframeKey((k) => k + 1);
			}
		}
	}, [attemptsQuery?.isSuccess, attemptsQuery?.data]);

	const finishedAttempts = useMemo(
		() =>
			(attemptsQuery?.data || [])
				.filter((a) => a.status === 'finished')
				.sort((a, b) => b.attempt_number - a.attempt_number),
		[attemptsQuery?.data],
	);
	const latestFinished = finishedAttempts[0];
	const attemptsAllowed = Number(quizQuery?.data?.attempts_allowed || 0);
	const finishedCount = finishedAttempts.length;
	const maxReached = attemptsAllowed > 0 && finishedCount >= attemptsAllowed;

	const startMutation = useMutation({
		mutationFn: () => attemptAPI.store({ h5p_quiz_id: h5pQuizId }),
	});

	const finishMutation = useMutation({
		mutationFn: (data: {
			id: number;
			score: number;
			max_score: number;
			question_scores: QuestionScore[];
		}) =>
			attemptAPI.update(data.id, {
				score: data.score,
				max_score: data.max_score,
				question_scores: data.question_scores,
			}),
	});

	const completeMutation = useMutation({
		mutationFn: (data: any) =>
			progressItemAPI.store({ ...data, ...previewFlag() }),
	});

	// Derived: all content IDs for this quiz.
	const allContentIds = useMemo(() => {
		const ids = quizQuery?.data?.h5p_content_ids;
		if (ids && ids.length > 0) return ids;
		return quizQuery?.data?.h5p_content_id
			? [quizQuery.data.h5p_content_id]
			: [];
	}, [quizQuery?.data]);

	// Derived: all iframe URLs (one per content item).
	const allIframeUrls = useMemo(() => {
		const iframeUrls = quizQuery?.data?.h5p_iframe_urls;
		if (iframeUrls && iframeUrls.length > 0) return iframeUrls;
		return quizQuery?.data?.h5p_iframe_url
			? [quizQuery.data.h5p_iframe_url]
			: [];
	}, [quizQuery?.data]);

	// Randomized order is a deterministic shuffle seeded by attempt id (stable across reloads), or natural order otherwise.
	const displayIndices = useMemo(() => {
		const len = allContentIds.length;
		if (!len) return [];
		const randomize = Boolean(quizQuery?.data?.randomize_questions);
		const seed = activeAttempt?.id ?? latestFinished?.id ?? 0;
		return randomize && seed
			? seededShuffle(len, seed)
			: allContentIds.map((_, i) => i);
	}, [
		allContentIds,
		quizQuery?.data?.randomize_questions,
		activeAttempt?.id,
		latestFinished?.id,
	]);

	const orderedIds = useMemo(
		() => displayIndices.map((i) => allContentIds[i]),
		[displayIndices, allContentIds],
	);

	const orderedUrls = useMemo(
		() => displayIndices.map((i) => allIframeUrls[i]),
		[displayIndices, allIframeUrls],
	);

	// Questions per page: per-quiz 0 means "From Global Settings" → use the global value; if neither is set, show all on one page.
	const questionsPerPage = useMemo(() => {
		const individual = Number(quizQuery?.data?.questions_display_per_page || 0);
		const global = Number(
			quizQuery?.data?.questions_display_per_page_global || 0,
		);
		const qpp = individual > 0 ? individual : global;
		return qpp > 0 ? qpp : orderedIds.length || 1;
	}, [
		quizQuery?.data?.questions_display_per_page,
		quizQuery?.data?.questions_display_per_page_global,
		orderedIds.length,
	]);

	const totalPages = useMemo(
		() => Math.max(1, Math.ceil(orderedIds.length / questionsPerPage)),
		[orderedIds.length, questionsPerPage],
	);

	const pageIds = useMemo(
		() =>
			orderedIds.slice(
				currentPage * questionsPerPage,
				(currentPage + 1) * questionsPerPage,
			),
		[orderedIds, currentPage, questionsPerPage],
	);

	const pageUrls = useMemo(
		() =>
			orderedUrls.slice(
				currentPage * questionsPerPage,
				(currentPage + 1) * questionsPerPage,
			),
		[orderedUrls, currentPage, questionsPerPage],
	);

	const handleFinish = useCallback(
		(
			scores: Record<
				number,
				{
					score: number;
					max: number;
					success: boolean | null;
					completion: boolean;
				}
			>,
		) => {
			if (!activeAttempt || finishingRef.current) return;
			finishingRef.current = true;

			const totalScore = Object.values(scores).reduce(
				(sum, s) => sum + s.score,
				0,
			);

			// Prefer the renderer-reported max (known even for skipped questions), falling back to the attempted max.
			const resolveMax = (contentId: number, attempted?: { max: number }) =>
				contentMaxScores[contentId] ?? attempted?.max ?? 0;

			const totalMax = orderedIds.reduce(
				(sum, contentId) => sum + resolveMax(contentId, scores[contentId]),
				0,
			);

			// Build per-question breakdown in display order; unattempted items get zero score.
			const question_scores: QuestionScore[] = orderedIds.map(
				(contentId, idx) => {
					const q = scores[contentId];
					return {
						h5p_content_id: contentId,
						question_index: idx + 1,
						score: q ? q.score : 0,
						max_score: resolveMax(contentId, q),
						success: q ? q.success : null,
						completed: q ? q.completion : false,
					};
				},
			);

			finishMutation.mutate(
				{
					id: activeAttempt.id,
					score: totalScore,
					max_score: totalMax,
					question_scores,
				},
				{
					onSuccess: (attempt: H5PQuizAttemptSchema) => {
						onSubmitClose();
						setActiveAttempt(attempt);
						queryClient.invalidateQueries({
							queryKey: [`h5pQuizAttempts${h5pQuizId}`],
						});
						queryClient.invalidateQueries({
							queryKey: [`courseProgress${courseId}`],
						});
					},
					onError: (err: any) => {
						finishingRef.current = false;
						onSubmitClose();
						toast({
							title:
								err?.message ||
								__(
									'Failed to record your H5P result.',
									'learning-management-system',
								),
							status: 'error',
							isClosable: true,
						});
					},
				},
			);
		},
		[
			activeAttempt,
			contentMaxScores,
			courseId,
			finishMutation,
			h5pQuizId,
			onSubmitClose,
			orderedIds,
			queryClient,
			toast,
		],
	);

	const handleStart = () => {
		startMutation.mutate(undefined, {
			onSuccess: (attempt: H5PQuizAttemptSchema) => {
				// Display order derives deterministically from the attempt id in `displayIndices`, so no shuffle state is set here.
				setCurrentPage(0);
				setActiveAttempt(attempt);
				setCompletedScores({});
				finishingRef.current = false;
				setIframeKey((k) => k + 1);
				queryClient.invalidateQueries({
					queryKey: [`h5pQuizAttempts${h5pQuizId}`],
				});
				queryClient.invalidateQueries({
					queryKey: [`courseProgress${courseId}`],
				});
				queryClient.invalidateQueries({
					queryKey: [`h5pProgressItem${h5pQuizId}`],
				});
			},
			onError: (err: any) => {
				toast({
					title:
						err?.message ||
						__('Unable to start the activity.', 'learning-management-system'),
					status: 'error',
					isClosable: true,
				});
			},
		});
	};

	// Collect xAPI scores from H5P iframes — no auto-finish; user submits explicitly.
	useEffect(() => {
		const handler = (event: MessageEvent) => {
			if (event.origin !== window.location.origin) return;

			const data = event.data;

			// Record each content's max score (static per content).
			if (data?.type === 'masteriyo_h5p_max') {
				const maxId = Number(data.h5pId);
				const maxVal = Number(data.max);
				if (allContentIds.includes(maxId) && maxVal > 0) {
					setContentMaxScores((prev) =>
						prev[maxId] === maxVal ? prev : { ...prev, [maxId]: maxVal },
					);
				}
				return;
			}

			if (!data || data.type !== 'masteriyo_h5p_xapi') return;
			const hasScore = typeof data.score?.raw === 'number';
			const hasResult = typeof data.success === 'boolean';
			if (!data.completion && !hasScore && !hasResult) return;

			const h5pId = Number(data.h5pId);
			if (!allContentIds.includes(h5pId)) return;

			// Reject messages not originating from one of our tracked H5P iframes.
			const sourceIsKnownIframe = Object.values(iframeEls.current).some(
				(iframe) => iframe && iframe.contentWindow === event.source,
			);
			if (!sourceIsKnownIframe) return;

			if (
				!activeAttempt ||
				activeAttempt.status !== 'started' ||
				finishingRef.current
			)
				return;

			const scoreData = data.score || {};
			const hasMax = typeof scoreData.max === 'number' && scoreData.max > 0;
			// Infer success from score when H5P omits result.success (Drag & Drop, Summary, Image Choice, etc.).
			const inferredSuccess: boolean | null =
				typeof data.success === 'boolean'
					? data.success
					: hasScore && hasMax
						? (scoreData.raw as number) >= (scoreData.max as number)
						: null;
			const questionScore = {
				score: hasScore ? (scoreData.raw as number) : 0,
				max: hasMax ? (scoreData.max as number) : 0,
				success: inferredSuccess,
				// Mark completed when H5P signals it explicitly OR when a numeric score arrives (some types fire score without completion:true).
				completion: Boolean(data.completion) || hasScore,
			};

			setCompletedScores((prev) => ({ ...prev, [h5pId]: questionScore }));
		};

		window.addEventListener('message', handler);
		return () => window.removeEventListener('message', handler);
	}, [activeAttempt, allContentIds]);

	const onCompletePress = useCallback(() => {
		completeMutation.mutate(
			{
				course_id: courseId,
				item_id: h5pQuizId,
				item_type: 'h5p-quiz',
				completed: true,
			},
			{
				onSuccess: () => {
					queryClient.invalidateQueries({
						queryKey: [`courseProgress${courseId}`],
					});
					queryClient.invalidateQueries({
						queryKey: [`h5pProgressItem${h5pQuizId}`],
					});
				},
				onError: () => {
					// Mark-complete failed; still navigate so the student isn't blocked.
				},
			},
		);
		// Navigate immediately; the API call completes in the background.
		if (quizQuery.data?.navigation) {
			RedirectNavigation(quizQuery.data.navigation, courseId, navigate);
		}
	}, [
		completeMutation,
		courseId,
		h5pQuizId,
		navigate,
		queryClient,
		quizQuery.data?.navigation,
	]);

	if (quizQuery.isError) {
		return (
			<ContentErrorDisplay
				code={quizQuery?.error?.code}
				message={quizQuery?.error?.message}
				bg={COLORS_BASED_ON_SCREEN_COLOR_MODE[colorMode]?.lessonBG}
			/>
		);
	}

	if (!quizQuery.isSuccess || !attemptsQuery.isSuccess) {
		return <LessonSkeleton />;
	}

	const quiz = quizQuery.data;
	const isAttemptActive = Boolean(
		activeAttempt &&
		activeAttempt.status === 'started' &&
		allIframeUrls.length > 0,
	);

	const resultAttempt =
		activeAttempt && activeAttempt.status === 'finished'
			? activeAttempt
			: latestFinished;

	// Same cells as the quiz intro, with activities in place of questions.
	const cells = [
		{
			icon: BiInfoCircle,
			label: __('Activities', 'learning-management-system'),
			value: String(allContentIds.length),
			hint: pointsHint(quiz.full_marks),
		},
		{
			icon: BiCheckDouble,
			label: __('Pass mark', 'learning-management-system'),
			value: passMarkOf(quiz.pass_mark, quiz.pass_mark_type),
		},
		{
			icon: BiTime,
			label: __('Time limit', 'learning-management-system'),
			value:
				quiz.duration === 0
					? __('None', 'learning-management-system')
					: masteriyoHumanizer(quiz.duration * 60 * 1000),
		},
		{
			icon: BiRepeat,
			label: __('Attempts left', 'learning-management-system'),
			value: attemptsLeftOf(
				attemptsAllowed > 0 ? attemptsAllowed - finishedCount : null,
			),
		},
	];

	// Whether this session's attempt has just finished (status from local state).
	const isFinished = activeAttempt?.status === 'finished';

	// A finished attempt exists when this session just finished OR the query returned one (persists across refresh, mirrors normal quiz).
	const hasFinishedAttempt = isFinished || Boolean(latestFinished);

	// Honor "Show Result" setting: when disabled, hide the scoreboard after finishing (learner still completes via nav, like normal quiz).
	const showResult = quiz.show_result !== false;
	const resultVisible = !isAttemptActive && hasFinishedAttempt && showResult;

	const isCompleted =
		completeMutation.isSuccess || Boolean(progressItemQuery?.data?.completed);

	// Same as a native quiz: with Conditional Quiz Completion Button on, a failed
	// attempt does not offer completion (issue #908).
	const passGated = localized.hideCompleteQuizButtonOnFail === 'yes';
	const attemptPassed = resultAttempt?.passed === 'yes';

	const quizCompletionButton =
		hasFinishedAttempt && (!passGated || attemptPassed) ? (
			<Flex align="center" justify="center">
				<Button
					isDisabled={isCompleted}
					isLoading={completeMutation.isPending}
					onClick={onCompletePress}
					variant="unstyled"
					display="inline-flex"
					alignItems="center"
					gap="8px"
					h="40px"
					px="18px"
					borderRadius="8px"
					fontSize="14px"
					fontWeight="600"
					color={isCompleted ? 'player.success' : 'player.onPrimary'}
					bg={isCompleted ? 'transparent' : 'player.primaryFill'}
					_hover={isCompleted ? {} : { bg: 'player.primaryHover' }}
					_disabled={{ opacity: 1, cursor: 'default' }}
					leftIcon={
						isCompleted ? (
							<Icon fontSize="18px" as={Check} color="player.success" />
						) : undefined
					}
				>
					{isCompleted
						? __('Completed', 'learning-management-system')
						: __('Complete activity', 'learning-management-system')}
				</Button>
			</Flex>
		) : null;

	return (
		<VStack minH="100%" justify={'space-between'}>
			<Container
				centerContent
				position="relative"
				maxW={containerFor(contentWidth)}
				px={{ base: 0, md: '16px' }}
				py={{ base: 0, md: '40px' }}
			>
				<ContentWidthButton width={contentWidth} onCycle={cycleContentWidth} />
				<Box
					bg="player.card"
					w="full"
					border="1px solid"
					borderColor="player.border"
					borderWidth={{ base: '0 0 1px', md: '1px' }}
					borderRadius={{ base: 0, md: '12px' }}
					// clip, not hidden: hidden would stop the timer sticking.
					overflow="clip"
				>
					{isAttemptActive && quiz.duration > 0 && (
						<QuizTimer
							quizId={quiz.id}
							startedOn={activeAttempt?.started_at}
							duration={quiz.duration}
							onQuizExpire={() => handleFinish(completedScores)}
							px={{ base: '16px', md: '40px' }}
						/>
					)}
					<Box
						px={{ base: '16px', md: '40px' }}
						py={{ base: '18px', md: '32px' }}
					>
						<Stack direction="column" spacing="8">
							<HStack>
								<Heading
									as="h1"
									flex={1}
									color="player.text"
									fontSize={{ base: '22px', md: '28px' }}
									fontWeight="700"
									lineHeight="1.25"
								>
									{quiz.name}
								</Heading>
							</HStack>

							{/* Hide description on the results screen — consistent with Normal Quiz */}
							{quiz.description && !(resultAttempt && !isAttemptActive) ? (
								<Text
									className="masteriyo-h5p-quiz-description"
									dangerouslySetInnerHTML={{ __html: quiz.description }}
								/>
							) : null}

							{isAttemptActive ? (
								<Stack direction="column" spacing="8">
									{pageUrls.map((iframeUrl, idx) => {
										const contentId = pageIds[idx];
										const globalIdx = currentPage * questionsPerPage + idx;
										const frameKey = `${iframeKey}-${contentId ?? idx}`;
										return (
											<iframe
												key={frameKey}
												ref={getIframeRef(frameKey)}
												onLoad={() => handleIframeLoad(frameKey)}
												className="masteriyo-h5p-iframe-wrap"
												data-h5p-id={contentId}
												title={`${quiz.name} ${globalIdx + 1}`}
												src={iframeUrl}
												tabIndex={-1}
												style={{
													width: '100%',
													// Iframes can't use fit-content: drive height from the measured value; 'auto' until first measurement so it grows up into place.
													height: iframeHeights[frameKey]
														? `${iframeHeights[frameKey]}px`
														: 'auto',
													border: 'none',
													display: 'block',
												}}
												allow="fullscreen *; autoplay *"
											/>
										);
									})}

									<Box sx={paginationSx}>
										<MasteriyoPagination
											metaData={{
												total: orderedIds.length,
												pages: totalPages,
												current_page: currentPage + 1,
												per_page: questionsPerPage,
											}}
											setFilterParams={(params: {
												page?: number;
												per_page?: number;
											}) => {
												if (params.page !== undefined) {
													setCurrentPage(params.page - 1);
												}
											}}
											perPageText={__(
												'Activities per page:',
												'learning-management-system',
											)}
											showPerPage={false}
										/>
									</Box>
								</Stack>
							) : resultVisible ? (
								/* Finished — show ScoreBoard-style results */
								<H5PScoreBoard
									totalQuestions={allContentIds.length}
									totalAttempts={finishedCount}
									maxScore={
										// Use full_marks when set, but never below the H5P max — a too-low full_marks would let percentage exceed 100%.
										quiz.full_marks > 0
											? Math.max(quiz.full_marks, resultAttempt?.max_score ?? 0)
											: (resultAttempt?.max_score ?? 0)
									}
									earnedScore={resultAttempt?.score ?? 0}
									percentage={resultAttempt?.percentage ?? 0}
									passed={resultAttempt?.passed ?? 'no'}
									totalAnsweredQuestions={
										resultAttempt?.total_answered_questions ?? 0
									}
									totalCorrectAnswers={
										resultAttempt?.total_correct_answers ?? 0
									}
									questionScores={resultAttempt?.question_scores ?? []}
									quizName={quiz.name}
									duration={quiz.duration}
									startedAt={resultAttempt?.started_at ?? null}
									finishedAt={resultAttempt?.finished_at ?? null}
									attemptsLeft={
										attemptsAllowed > 0 ? attemptsAllowed - finishedCount : null
									}
									onStartPress={handleStart}
									isButtonLoading={startMutation.isPending}
									limitReached={maxReached}
								/>
							) : hasFinishedAttempt ? (
								/* Finished but "Show Result" is disabled — submission acknowledgement.
								   Completion/navigation happens via the nav, like normal quizzes. */
								<Stack spacing="4" align="center" textAlign="center" py="6">
									<Icon as={BiCheckCircle} color="green.400" fontSize="5xl" />
									<Heading as="h6" size="md" color="player.text">
										{__('Activity finished', 'learning-management-system')}
									</Heading>
									<Text fontSize="sm" color="player.muted">
										{__(
											'Use Next in the footer to continue the course.',
											'learning-management-system',
										)}
									</Text>
								</Stack>
							) : (
								/* Not yet started: stat cells + Start button */
								<Stack spacing="18px">
									<StatCells cells={cells} />

									{allContentIds.length > 0 ? (
										<Button
											onClick={handleStart}
											isLoading={startMutation.isPending}
											variant="unstyled"
											display="inline-flex"
											alignItems="center"
											alignSelf="flex-end"
											gap="6px"
											h="44px"
											px="20px"
											borderRadius="8px"
											fontSize="15px"
											fontWeight="600"
											color="player.onPrimary"
											bg="player.primaryFill"
											_hover={{ bg: 'player.primaryHover' }}
											rightIcon={<Icon as={BiChevronRight} fontSize="18px" />}
										>
											{__('Start activity', 'learning-management-system')}
										</Button>
									) : (
										<Text color="player.danger">
											{__(
												'No H5P content is linked to this activity yet.',
												'learning-management-system',
											)}
										</Text>
									)}
								</Stack>
							)}
						</Stack>
					</Box>
					{/* closes <Box p={['5', null, '10']}> */}
				</Box>
				{/* closes outer shadow="box" Box */}

				{/* Submit confirmation dialog */}
				<AlertDialog
					isCentered
					isOpen={isSubmitOpen}
					leastDestructiveRef={cancelRef}
					onClose={onSubmitClose}
				>
					<AlertDialogOverlay>
						<AlertDialogContent>
							<AlertDialogHeader fontSize="lg" fontWeight="bold">
								{sprintf(
									/* translators: %s: activity name */
									_x(
										'Finish activity: %s',
										'H5P finish dialog title',
										'learning-management-system',
									),
									quiz.name,
								)}
							</AlertDialogHeader>

							<AlertDialogBody>
								{__(
									'Your answers so far are recorded. You can do the activity again afterwards.',
									'learning-management-system',
								)}
							</AlertDialogBody>

							<AlertDialogFooter>
								<Button
									variant="player-secondary"
									ref={cancelRef}
									onClick={onSubmitClose}
								>
									{__('Cancel', 'learning-management-system')}
								</Button>
								<Button
									colorScheme="button"
									isLoading={finishMutation.isPending}
									onClick={() => handleFinish(completedScores)}
									ml={3}
								>
									{__('Finish activity', 'learning-management-system')}
								</Button>
							</AlertDialogFooter>
						</AlertDialogContent>
					</AlertDialogOverlay>
				</AlertDialog>
			</Container>

			<ContentNav
				type="h5p-quiz"
				navigation={quiz.navigation}
				courseId={courseId}
				onCompletePress={onSubmitOpen}
				isButtonLoading={finishMutation.isPending}
				isButtonDisabled={!isAttemptActive}
				quizStarted={isAttemptActive}
				// Mirror the normal quiz: false keeps the center empty until the attempt starts (Submit) or finishes (Complete), instead of showing disabled "Completed".
				isAssignmentSubmitted={false}
				quizCompletionButton={quizCompletionButton}
				isCompleted={isCompleted}
			/>
		</VStack>
	);
};

export default InteractiveH5PQuiz;
