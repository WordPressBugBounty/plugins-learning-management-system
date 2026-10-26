import {
	Box,
	Button,
	ButtonGroup,
	Collapse,
	Icon,
	Link,
	Stack,
	Text,
	useDisclosure,
} from '@chakra-ui/react';
import { __, _n, sprintf } from '@wordpress/i18n';
import React from 'react';
import { Eye, Restart } from '../../../../assets/js/back-end/constants/images';
import Breakdown, {
	BreakdownCounts,
	BreakdownRow,
} from '../../../../assets/js/interactive/pages/quiz/components/Breakdown';
import ResultTable from '../../../../assets/js/interactive/pages/quiz/components/ResultTable';
import localized from '../../../../assets/js/interactive/utils/global';

export interface QuestionScore {
	h5p_content_id: number;
	question_index: number;
	title?: string;
	score: number;
	max_score: number;
	success: boolean | null;
	completed: boolean;
}

interface Props {
	totalQuestions: number;
	totalAttempts: number;
	maxScore: number;
	earnedScore: number;
	percentage: number;
	passed: string;
	totalAnsweredQuestions?: number;
	totalCorrectAnswers?: number;
	questionScores?: QuestionScore[];
	quizName?: string;
	duration?: number;
	startedAt?: string | null;
	finishedAt?: string | null;
	onStartPress: () => void;
	isButtonLoading?: boolean;
	limitReached: boolean;
	/** Attempts the learner has left, or null when unlimited. */
	attemptsLeft?: number | null;
}

/** One row per H5P activity: its result and points. */
const activityRowsOf = (
	scores: QuestionScore[],
): { rows: BreakdownRow[]; counts: BreakdownCounts } => {
	const counts: BreakdownCounts = {
		correct: 0,
		incorrect: 0,
		pending: 0,
		unanswered: 0,
	};

	const rows = scores.map((q) => {
		const status = !q.completed
			? 'blank'
			: q.success === true
				? 'correct'
				: q.success === false
					? 'incorrect'
					: 'answered';
		if (status === 'blank') counts.unanswered++;
		if (status === 'correct') counts.correct++;
		if (status === 'incorrect') counts.incorrect++;

		return {
			key: q.h5p_content_id,
			status,
			prompt: q.title || __('Activity', 'learning-management-system'),
			points:
				q.completed && q.max_score > 0 ? `${q.score} / ${q.max_score}` : '—',
		} as BreakdownRow;
	});

	return { rows, counts };
};

/** The activity's result, laid out like the quiz scoreboard. */
const H5PScoreBoard: React.FC<Props> = ({
	totalQuestions,
	totalAttempts,
	maxScore,
	earnedScore,
	percentage,
	passed,
	totalAnsweredQuestions = 0,
	questionScores = [],
	onStartPress,
	isButtonLoading,
	limitReached,
	attemptsLeft = null,
}) => {
	const isScored = maxScore > 0;
	const { isOpen: isDetailOpen, onToggle: onDetailToggle } = useDisclosure();
	const breakdown = activityRowsOf(questionScores);
	const isPass = passed === 'yes';

	const rows = [
		{
			label: __('Total Activities', 'learning-management-system'),
			value: totalQuestions,
		},
		{
			label: __('Completed Activities', 'learning-management-system'),
			value: totalAnsweredQuestions,
		},
		{
			label: __('Total Attempts', 'learning-management-system'),
			value: totalAttempts,
		},
		{
			label: __('Total Points', 'learning-management-system'),
			value: maxScore,
		},
		{
			label: __('Earned Points', 'learning-management-system'),
			value: earnedScore,
		},
		{
			label: __('Result', 'learning-management-system'),
			value: (
				<Text
					as="span"
					fontWeight="700"
					color={isPass ? 'player.primaryText' : 'player.amber'}
				>
					{isPass
						? __('Pass', 'learning-management-system')
						: __('Fail', 'learning-management-system')}
					{isScored ? (
						<Text as="span" ms="6px">
							{percentage}%
						</Text>
					) : null}
				</Text>
			),
		},
	];

	return (
		<Stack direction="column" spacing="18px">
			<ResultTable rows={rows} />

			<ButtonGroup display="flex" flexWrap="wrap" gap="10px" spacing={0}>
				<Button
					onClick={onStartPress}
					isLoading={isButtonLoading}
					isDisabled={limitReached}
					variant="unstyled"
					display="flex"
					alignItems="center"
					h="40px"
					px="18px"
					borderRadius="8px"
					fontSize="14px"
					fontWeight="600"
					color="player.onPrimary"
					bg="player.primaryFill"
					_hover={{ bg: 'player.primaryHover' }}
					_disabled={{ opacity: 0.55, cursor: 'not-allowed' }}
					leftIcon={
						<Icon as={Restart} color="player.onPrimary" fontSize="16px" />
					}
				>
					{__('Start activity again', 'learning-management-system')}
				</Button>
				<Button
					onClick={onDetailToggle}
					variant="player-secondary"
					leftIcon={<Icon as={Eye} fontSize="16px" />}
					aria-expanded={isDetailOpen}
				>
					{isDetailOpen
						? __('Hide Details', 'learning-management-system')
						: __('View Details', 'learning-management-system')}
				</Button>
			</ButtonGroup>

			<Collapse in={isDetailOpen} animateOpacity>
				<Box pt="12px">
					<Breakdown
						title={__('Activity Details', 'learning-management-system')}
						rows={breakdown.rows}
						counts={breakdown.counts}
						emptyText={__(
							'No activity-level data was recorded for this attempt.',
							'learning-management-system',
						)}
					/>
					<Text fontSize="13px" color="player.muted" pt="14px">
						{__(
							'View your complete quiz history from your',
							'learning-management-system',
						)}{' '}
						<Link
							href={localized?.urls?.account + '#/quiz-attempts'}
							color="player.primaryText"
							fontWeight="600"
							isExternal
						>
							{__('account page', 'learning-management-system')}
						</Link>
						.
					</Text>
				</Box>
			</Collapse>

			{attemptsLeft !== null && attemptsLeft > 0 ? (
				<Text fontSize="13px" color="player.muted">
					{sprintf(
						/* translators: %d: quiz attempts the learner has left */
						_n(
							'%d attempt left.',
							'%d attempts left.',
							attemptsLeft,
							'learning-management-system',
						),
						attemptsLeft,
					)}
				</Text>
			) : null}
		</Stack>
	);
};

export default H5PScoreBoard;
