import { Badge, HStack, Icon, Stack, Text } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { BiInfoCircle } from 'react-icons/bi';
import { Td, Tr } from 'react-super-responsive-table';
import { H5PQuestionScore } from '../types/h5pQuizAttempt';

interface Props {
	questionScores: H5PQuestionScore[];
}

/**
 * Per-question result rows (Question / Result / Points) for an H5P attempt.
 * No "Answer" column — H5P reports only score/success, not answer text.
 */
const H5PQuestionScores: React.FC<Props> = ({ questionScores }) => {
	// Like the normal quiz overview, list only attempted questions (empty notice when none, not a list of "Not attempted" rows).
	const attemptedScores = questionScores.filter((q) => q.completed);

	if (attemptedScores.length === 0) {
		return (
			<Tr>
				<Td>
					<Stack direction="row" spacing="1" align="center">
						<Icon as={BiInfoCircle} color="primary.400" />
						<Text as="span" fontWeight="medium" color="gray.600" fontSize="sm">
							{__('No questions attempt found.', 'learning-management-system')}
						</Text>
					</Stack>
				</Td>
				<Td></Td>
				<Td></Td>
			</Tr>
		);
	}

	return (
		<>
			{attemptedScores.map((q) => (
				<Tr key={q.h5p_content_id}>
					<Td>
						<HStack spacing="2" align="center">
							<Icon as={BiInfoCircle} fontSize="xl" />
							<Text fontSize="sm">
								{q.title || __('Question', 'learning-management-system')}
							</Text>
						</HStack>
					</Td>
					<Td>
						<HStack>
							{q.success === true ? (
								<Badge w="fit-content" variant="link">
									<Text
										fontSize="13px"
										fontWeight="semibold"
										color="green.400"
										textTransform="none"
									>
										{__('Correct', 'learning-management-system')}
									</Text>
								</Badge>
							) : q.success === false ? (
								<Badge w="fit-content" variant="link">
									<Text
										fontSize="13px"
										fontWeight="semibold"
										color="red.400"
										textTransform="none"
									>
										{__('Incorrect', 'learning-management-system')}
									</Text>
								</Badge>
							) : (
								<Badge w="fit-content" variant="link">
									<Text
										fontSize="13px"
										fontWeight="semibold"
										color="blue.400"
										textTransform="none"
									>
										{__('Answered', 'learning-management-system')}
									</Text>
								</Badge>
							)}
						</HStack>
					</Td>
					<Td>
						<Text ml="3" fontSize="xs" fontWeight="bold" textAlign="left">
							{q.completed && q.max_score > 0
								? `${q.score}/${q.max_score} ${__('pts', 'learning-management-system')}`
								: '—'}
						</Text>
					</Td>
				</Tr>
			))}
		</>
	);
};

export default H5PQuestionScores;
