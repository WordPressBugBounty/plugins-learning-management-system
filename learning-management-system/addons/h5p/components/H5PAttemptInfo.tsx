import {
	Badge,
	HStack,
	Stack,
	Text,
	useColorModeValue,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Td, Tr } from 'react-super-responsive-table';
import {
	formatAttemptDuration,
	getWordpressLocalTime,
} from '../../../assets/js/back-end/utils/utils';
import { H5PQuizAttempt } from '../types/h5pQuizAttempt';

interface Props {
	attempt: H5PQuizAttempt;
	isStudentView?: boolean;
}

const LabeledValue: React.FC<{ label: string; value: React.ReactNode }> = ({
	label,
	value,
}) => {
	const contentColor = useColorModeValue('gray.600', 'gray.300');
	const labelColor = useColorModeValue('oxford-night', 'white');

	return (
		<Stack direction="row" spacing="2">
			<Text color={labelColor} fontSize="sm" fontWeight="semibold">
				{label}
			</Text>
			<Text color={contentColor} fontSize="sm" fontWeight="normal">
				{value}
			</Text>
		</Stack>
	);
};

/**
 * Convert minutes to an "X hrs Y mins" string.
 *
 * Mirrors `QuizAttemptInfo`'s `getHrsAndMins`.
 */
const getHrsAndMins = (minutes: number) => {
	const hrs = Math.floor(minutes / 60);
	const mins = minutes % 60;

	return `${hrs} hrs ${mins} mins`;
};

/**
 * Summary info row for an H5P quiz attempt detail page.
 *
 * Mirrors the normal quiz `QuizAttemptInfo` layout:
 * Student/Quiz info | Quiz summary | Result.
 */
const H5PAttemptInfo: React.FC<Props> = ({
	attempt,
	isStudentView = false,
}) => {
	const contentColor = useColorModeValue('gray.600', 'gray.300');
	const resultBg = useColorModeValue('#F7F7F7', 'gray.700');

	const totalQuestions = attempt.question_scores?.length ?? 0;
	const isPassed = attempt.passed === 'yes';
	const percentColor = isPassed ? 'green.500' : 'coral-red';

	return (
		<Tr>
			<Td>
				<Stack direction="column" spacing="2">
					{isStudentView ? (
						<Text fontWeight="normal" fontSize="sm">
							{attempt.quiz?.name}
						</Text>
					) : (
						<>
							<Text fontWeight="normal" fontSize="sm">
								{attempt.user?.first_name} {attempt.user?.last_name}
							</Text>
							<Text color={contentColor} fontSize="xs">
								{attempt.user?.display_name} ({attempt.user?.email})
							</Text>
						</>
					)}
				</Stack>
			</Td>
			<Td>
				<Stack direction="column" spacing="2">
					<LabeledValue
						label={__('Date:', 'learning-management-system')}
						value={getWordpressLocalTime(
							attempt.started_at ?? '',
							'm/d/Y, h:i A',
						)}
					/>
					<LabeledValue
						label={__('Total Attempts:', 'learning-management-system')}
						value={attempt.attempt_number}
					/>
					<LabeledValue
						label={__('Quiz Time:', 'learning-management-system')}
						value={getHrsAndMins(attempt.quiz?.duration ?? 0)}
					/>
					<LabeledValue
						label={__('Attempt Time:', 'learning-management-system')}
						value={formatAttemptDuration(
							attempt.started_at,
							attempt.finished_at,
						)}
					/>
				</Stack>
			</Td>
			<Td style={{ backgroundColor: resultBg }}>
				<Stack direction="column" spacing="2">
					<HStack align="center">
						<Badge
							colorScheme={isPassed ? 'green' : 'red'}
							variant="link"
							color={percentColor}
							p={0}
							textTransform="none"
							fontSize="sm"
						>
							{isPassed
								? __('Pass', 'learning-management-system')
								: __('Fail', 'learning-management-system')}
						</Badge>
						{attempt.max_score > 0 && (
							<Text color={percentColor} fontSize="sm" fontWeight="semibold">
								{attempt.percentage}%
							</Text>
						)}
					</HStack>
					<LabeledValue
						label={__('Earned Points:', 'learning-management-system')}
						value={`${attempt.score} / ${attempt.max_score}`}
					/>
					<LabeledValue
						label={__('Correct Answers:', 'learning-management-system')}
						value={`${attempt.total_correct_answers ?? 0} / ${totalQuestions}`}
					/>
					<LabeledValue
						label={__('Attempt Questions:', 'learning-management-system')}
						value={`${attempt.total_answered_questions ?? 0} / ${totalQuestions}`}
					/>
				</Stack>
			</Td>
		</Tr>
	);
};

export default H5PAttemptInfo;
