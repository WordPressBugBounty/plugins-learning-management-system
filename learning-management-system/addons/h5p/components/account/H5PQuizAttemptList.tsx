import { H5PQuizAttempt } from '@addons/h5p/types/h5pQuizAttempt';
import { Badge, Link, Stack, Text } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Link as RouterLink } from 'react-router-dom';
import { Td, Tr } from 'react-super-responsive-table';
import routes from '../../../../assets/js/account/constants/routes';

interface Props {
	attempt: H5PQuizAttempt;
}

const H5PQuizAttemptList: React.FC<Props> = ({ attempt }) => {
	const isFinished = attempt.status === 'finished';
	const detailUrl = routes.quizAttempts.h5pView.replace(
		':attemptId',
		attempt.id.toString(),
	);

	const quizLabel = `#${attempt.id} ${attempt.quiz?.name ?? ''}`.trim();

	return (
		<Tr>
			<Td>
				<Stack direction="column">
					{isFinished ? (
						<Link
							as={RouterLink}
							to={detailUrl}
							fontWeight="semibold"
							fontSize="sm"
							color={'primary.500'}
							_hover={{ color: 'primary.600' }}
						>
							{quizLabel}
						</Link>
					) : (
						<Text fontWeight="semibold" fontSize="sm" color="oxford-night">
							{quizLabel}
						</Text>
					)}
					<Text color="saint-blue" fontSize="xs" fontWeight="normal">
						{__('Course:', 'learning-management-system')} {attempt.course?.name}
					</Text>
				</Stack>
			</Td>

			<Td>
				<Stack direction="column" spacing="2">
					<Text color="oxford-night" fontSize="sm" fontWeight="semibold">
						{__('Attempt #:', 'learning-management-system')}{' '}
						<Text as="span" color="saint-blue" fontWeight="normal">
							{attempt.attempt_number}
						</Text>
					</Text>
					<Text color="oxford-night" fontSize="sm" fontWeight="semibold">
						{__('Earned Points:', 'learning-management-system')}{' '}
						<Text as="span" color="saint-blue" fontWeight="normal">
							{attempt.max_score > 0
								? `${attempt.score} / ${attempt.max_score}`
								: __('Unscored', 'learning-management-system')}
						</Text>
					</Text>
					{attempt.max_score > 0 && (
						<Text color="oxford-night" fontSize="sm" fontWeight="semibold">
							{__('Percentage:', 'learning-management-system')}{' '}
							<Text as="span" color="saint-blue" fontWeight="normal">
								{attempt.percentage}%
							</Text>
						</Text>
					)}
				</Stack>
			</Td>

			<Td>
				<Badge
					colorScheme={attempt.passed === 'yes' ? 'green' : 'red'}
					px="2"
					py="1"
					rounded="sm"
					fontSize="xs"
					textTransform="none"
				>
					{attempt.passed === 'yes'
						? __('Pass', 'learning-management-system')
						: __('Fail', 'learning-management-system')}
				</Badge>
			</Td>
		</Tr>
	);
};

export default H5PQuizAttemptList;
