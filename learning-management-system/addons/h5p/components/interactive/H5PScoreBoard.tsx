import {
	Badge,
	Box,
	Button,
	ButtonGroup,
	Divider,
	HStack,
	Icon,
	Link,
	Modal,
	ModalBody,
	ModalCloseButton,
	ModalContent,
	ModalFooter,
	ModalHeader,
	ModalOverlay,
	Stack,
	Text,
	useColorMode,
	useColorModeValue,
	useDisclosure,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { BiCheckCircle, BiInfoCircle, BiXCircle } from 'react-icons/bi';
import { Table, Tbody, Td, Th, Thead, Tr } from 'react-super-responsive-table';
import CustomAlert from '../../../../assets/js/back-end/components/common/CustomAlert';
import {
	Attempts,
	EarnedPoints,
	Eye,
	Restart,
	ResultIcon,
	TotalPoints,
} from '../../../../assets/js/back-end/constants/images';
import { getWordpressLocalTime } from '../../../../assets/js/back-end/utils/utils';
import { COLORS_BASED_ON_SCREEN_COLOR_MODE } from '../../../../assets/js/interactive/constants/general';
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
}

const tdStyles = {
	display: 'flex',
	alignItems: 'center',
	gap: '8px',
};

const H5PScoreBoard: React.FC<Props> = ({
	totalQuestions,
	totalAttempts,
	maxScore,
	earnedScore,
	percentage,
	passed,
	totalAnsweredQuestions = 0,
	totalCorrectAnswers = 0,
	questionScores = [],
	quizName,
	duration = 0,
	startedAt = null,
	finishedAt = null,
	onStartPress,
	isButtonLoading,
	limitReached,
}) => {
	const { colorMode } = useColorMode();
	const isScored = maxScore > 0;

	const {
		isOpen: isQuizDetailOpen,
		onOpen: onQuizDetailOpen,
		onClose: onQuizDetailClose,
	} = useDisclosure();

	const contentColor =
		COLORS_BASED_ON_SCREEN_COLOR_MODE[colorMode]?.quizAttemptsContentColor;
	const resultTextColor =
		COLORS_BASED_ON_SCREEN_COLOR_MODE[colorMode]
			?.quizResultPercentageInScoreboard ?? 'gray.700';
	const detailsTableBg =
		COLORS_BASED_ON_SCREEN_COLOR_MODE[colorMode]?.quizDetailsTableBg;

	const modalCloseButtonBG = useColorModeValue('oxford-night', 'white');
	const modalTitleColor = useColorModeValue('oxford-night', 'white');
	const modalCloseButtonIconColor = useColorModeValue('white', 'black');

	const isQuizAnswered =
		totalAnsweredQuestions === 0 ? BiXCircle : BiCheckCircle;

	/**
	 * Convert minutes to "x hrs y mins".
	 */
	const getHrsAndMins = (minutes: number) => {
		const hrs = Math.floor(minutes / 60);
		const mins = minutes % 60;

		return hrs + ' hrs ' + mins + ' mins';
	};

	/**
	 * Elapsed time between two timestamps as "h hrs m min s sec".
	 */
	const getTotalAttemptTime = (start: any, end: any) => {
		if (!start || !end) {
			return '—';
		}

		let seconds = Math.floor(
			(new Date(end).getTime() - new Date(start).getTime()) / 1000,
		);

		if (isNaN(seconds) || seconds < 0) {
			return '—';
		}

		const hours = Math.floor(seconds / (60 * 60));
		seconds -= hours * 60 * 60;
		const minutes = Math.floor(seconds / 60);
		seconds -= minutes * 60;

		return hours + ' hrs ' + minutes + ' min ' + seconds + ' sec';
	};

	const ResultBadge = () =>
		passed === 'yes' ? (
			<Badge
				colorScheme="green"
				variant={'link'}
				color={'green.500'}
				p={0}
				textTransform={'none'}
				fontSize={'sm'}
			>
				{__('Pass', 'learning-management-system')}
			</Badge>
		) : (
			<Badge
				colorScheme="red"
				variant={'link'}
				color={'coral-red'}
				p={0}
				textTransform={'none'}
				fontSize={'sm'}
			>
				{__('Fail', 'learning-management-system')}
			</Badge>
		);

	const ResultSummary = () => (
		<HStack align="center" spacing="2">
			<ResultBadge />
			{isScored && (
				<Text color={resultTextColor} fontSize="sm" fontWeight="semibold">
					{percentage}%
				</Text>
			)}
		</HStack>
	);

	return (
		<Stack direction="column" spacing="8">
			<Table className={'interactive_table'}>
				<Thead className="interactive_table_head_with_2_cols">
					<Tr>
						<Th>{__('Metric', 'learning-management-system')}</Th>
						<Th>{__('Points', 'learning-management-system')}</Th>
					</Tr>
				</Thead>
				<Tbody className={'interactive_table_body'}>
					<Tr>
						<Td style={tdStyles}>
							<Icon as={BiInfoCircle} fontSize={'md'} />
							{__('Total Questions', 'learning-management-system')}
						</Td>
						<Td>{totalQuestions}</Td>
					</Tr>

					<Tr>
						<Td style={tdStyles}>
							<Icon as={isQuizAnswered} fontSize={'md'} />
							{__('Answered Questions', 'learning-management-system')}
						</Td>
						<Td>{totalAnsweredQuestions}</Td>
					</Tr>

					<Tr>
						<Td style={tdStyles}>
							<Icon as={Attempts} fill={'currentColor'} fontSize={'md'} />
							{__('Total Attempts', 'learning-management-system')}
						</Td>
						<Td>{totalAttempts}</Td>
					</Tr>

					<Tr>
						<Td style={tdStyles}>
							<Icon as={TotalPoints} fill={'currentColor'} fontSize={'md'} />
							{__('Total Points', 'learning-management-system')}
						</Td>
						<Td>{maxScore}</Td>
					</Tr>

					<Tr>
						<Td style={tdStyles}>
							<Icon as={EarnedPoints} fill={'currentColor'} fontSize={'md'} />
							{__('Earned Points', 'learning-management-system')}
						</Td>
						<Td>{earnedScore}</Td>
					</Tr>

					<Tr>
						<Td style={tdStyles}>
							<Icon as={ResultIcon} fill={'currentColor'} fontSize={'md'} />
							{__('Result:', 'learning-management-system')}
						</Td>
						<Td>
							<Stack
								direction={['column', 'row', 'row']}
								spacing="2"
								justify={'flex-end'}
							>
								<ResultSummary />
							</Stack>
						</Td>
					</Tr>
				</Tbody>
			</Table>

			<Stack
				direction={{ base: 'column', md: 'row' }}
				align={{ base: 'flex-start', md: 'center' }}
				gap={{ base: 4, md: 0 }}
			>
				<ButtonGroup display="flex" gap="2" flex={1}>
					<Button
						onClick={onStartPress}
						isLoading={isButtonLoading}
						isDisabled={limitReached}
						rounded="base"
						fontWeight="semibold"
						colorScheme="button"
						leftIcon={<Icon as={Restart} color={'white'} />}
						fontSize={'15px'}
					>
						{__('Start Quiz Again', 'learning-management-system')}
					</Button>
					<Button
						onClick={onQuizDetailOpen}
						rounded="base"
						fontWeight="semibold"
						variant={'outline'}
						colorScheme="button"
						leftIcon={<Icon as={Eye} />}
						fontSize={'15px'}
					>
						{__('View Details', 'learning-management-system')}
					</Button>
				</ButtonGroup>

				<Modal
					id="masteriyo-interactive-page-portal"
					isOpen={isQuizDetailOpen}
					onClose={onQuizDetailClose}
					isCentered
					size="4xl"
					scrollBehavior="inside"
				>
					<ModalOverlay />
					<ModalContent p={0}>
						<ModalHeader
							fontWeight={'bold'}
							fontSize={'3xl'}
							px={10}
							py={'26px'}
							color={modalTitleColor}
						>
							{__('Result Details', 'learning-management-system')}
						</ModalHeader>
						<Divider />
						<ModalCloseButton
							position="absolute"
							top="-35px"
							right="-35px"
							zIndex={2}
							bg={modalCloseButtonBG}
							color={modalCloseButtonIconColor}
							borderRadius="6px"
							boxShadow="sm"
							aria-label="Close dialog"
						/>
						<ModalBody p={0}>
							<Stack direction="column" gap={0}>
								<Box p={10}>
									<Table className={'interactive_table'}>
										<Thead className="interactive_table_head">
											<Tr>
												<Th>{__('Quiz Info', 'learning-management-system')}</Th>
												<Th>
													{__('Quiz Summary', 'learning-management-system')}
												</Th>
												<Th>{__('Result', 'learning-management-system')}</Th>
											</Tr>
										</Thead>
										<Tbody className={'interactive_table_body'}>
											<Tr>
												<Td>
													<Text fontWeight="normal" fontSize="sm">
														{quizName}
													</Text>
												</Td>
												<Td>
													<Stack direction="column" spacing="2">
														<Stack direction="row">
															<Text
																color={contentColor}
																fontSize="sm"
																fontWeight={'semibold'}
															>
																{__('Date:', 'learning-management-system')}
															</Text>
															<Text
																color={contentColor}
																fontSize="sm"
																fontWeight="normal"
															>
																{startedAt
																	? getWordpressLocalTime(
																			startedAt,
																			'm/d/Y, h:i A',
																		)
																	: '—'}
															</Text>
														</Stack>
														<Stack direction="row">
															<Text
																color={contentColor}
																fontSize="sm"
																fontWeight={'semibold'}
															>
																{__(
																	'Total Attempts:',
																	'learning-management-system',
																)}
															</Text>
															<Text
																color={contentColor}
																fontSize="sm"
																fontWeight="normal"
															>
																{totalAttempts}
															</Text>
														</Stack>
														<Stack direction="row">
															<Text
																color={contentColor}
																fontSize="sm"
																fontWeight={'semibold'}
															>
																{__('Quiz Time:', 'learning-management-system')}
															</Text>
															<Text
																color={contentColor}
																fontSize="sm"
																fontWeight="normal"
															>
																{getHrsAndMins(duration)}
															</Text>
														</Stack>
														<Stack direction="row">
															<Text
																color={contentColor}
																fontSize="sm"
																fontWeight={'semibold'}
															>
																{__(
																	'Attempt Time:',
																	'learning-management-system',
																)}
															</Text>
															<Text
																color={contentColor}
																fontSize="sm"
																fontWeight="normal"
															>
																{getTotalAttemptTime(startedAt, finishedAt)}
															</Text>
														</Stack>
													</Stack>
												</Td>
												<Td style={{ backgroundColor: detailsTableBg }}>
													<Stack direction="column" spacing="2">
														<ResultSummary />
														<Stack direction="row">
															<Text
																color={contentColor}
																fontSize="sm"
																fontWeight={'semibold'}
															>
																{__(
																	'Earned Points:',
																	'learning-management-system',
																)}
															</Text>
															<Text
																color={contentColor}
																fontSize="sm"
																fontWeight="normal"
															>
																{earnedScore} / {maxScore}
															</Text>
														</Stack>
														<Stack direction="row">
															<Text
																color={contentColor}
																fontSize="sm"
																fontWeight={'semibold'}
															>
																{__(
																	'Correct Answers:',
																	'learning-management-system',
																)}
															</Text>
															<Text
																color={contentColor}
																fontSize="sm"
																fontWeight="normal"
															>
																{totalCorrectAnswers} / {totalQuestions}
															</Text>
														</Stack>
														<Stack direction="row">
															<Text
																color={contentColor}
																fontSize="sm"
																fontWeight={'semibold'}
															>
																{__(
																	'Attempt Questions:',
																	'learning-management-system',
																)}
															</Text>
															<Text
																color={contentColor}
																fontSize="sm"
																fontWeight="normal"
															>
																{totalAnsweredQuestions} / {totalQuestions}
															</Text>
														</Stack>
													</Stack>
												</Td>
											</Tr>
										</Tbody>
									</Table>
								</Box>
								<Divider my={0} />
								<Stack p={10} gap={'30px'}>
									<Text
										fontWeight={'bold'}
										fontSize={'xl'}
										color={modalTitleColor}
									>
										{__('Question Details', 'learning-management-system')}
									</Text>
									<Table className="interactive_table">
										<Thead className="interactive_table_head">
											<Tr>
												<Th>{__('Question', 'learning-management-system')}</Th>
												<Th>{__('Result', 'learning-management-system')}</Th>
												<Th>{__('Points', 'learning-management-system')}</Th>
											</Tr>
										</Thead>
										<Tbody className="interactive_table_body">
											{questionScores.length === 0 ? (
												<Tr>
													<Td colSpan={3}>
														<Text color="gray.500" fontSize="sm">
															{__(
																'No question-level data was recorded for this attempt.',
																'learning-management-system',
															)}
														</Text>
													</Td>
												</Tr>
											) : null}
											{questionScores.map((q) => (
												<Tr key={q.h5p_content_id}>
													<Td style={tdStyles}>
														<Icon as={BiInfoCircle} fontSize={'md'} />
														{q.title ||
															__('Question', 'learning-management-system')}
													</Td>
													<Td>
														<HStack align="center" spacing="2">
															{!q.completed ? (
																<Text fontSize="sm" color="gray.400">
																	{__(
																		'Not attempted',
																		'learning-management-system',
																	)}
																</Text>
															) : q.success === true ? (
																<Badge
																	colorScheme="green"
																	variant={'link'}
																	color={'green.500'}
																	p={0}
																	textTransform={'none'}
																	fontSize={'13px'}
																>
																	{__('Correct', 'learning-management-system')}
																</Badge>
															) : q.success === false ? (
																<Badge
																	colorScheme="red"
																	variant={'link'}
																	color={'coral-red'}
																	p={0}
																	textTransform={'none'}
																	fontSize={'13px'}
																>
																	{__(
																		'Incorrect',
																		'learning-management-system',
																	)}
																</Badge>
															) : (
																<Badge
																	colorScheme="blue"
																	variant={'link'}
																	p={0}
																	textTransform={'none'}
																	fontSize={'13px'}
																>
																	{__('Answered', 'learning-management-system')}
																</Badge>
															)}
														</HStack>
													</Td>
													<Td style={{ textAlign: 'left' }}>
														{q.completed && q.max_score > 0
															? `${q.score}/${q.max_score} ${__(
																	'pts',
																	'learning-management-system',
																)}`
															: '—'}
													</Td>
												</Tr>
											))}
										</Tbody>
									</Table>
								</Stack>
							</Stack>
						</ModalBody>

						<ModalFooter justifyContent="flex-start" px={10}>
							<CustomAlert>
								<HStack gap={1}>
									<Text
										fontSize={'sm'}
										fontWeight={'normal'}
										color={'oxford-night'}
									>
										{__(
											'View your complete quiz history from your ',
											'learning-management-system',
										)}
									</Text>
									<Link
										href={localized?.urls?.account + '#/quiz-attempts'}
										color="primary.600"
										isExternal
										textDecor={'underline'}
										textUnderlineOffset={2}
									>
										{__('account page', 'learning-management-system')}.
									</Link>
								</HStack>
							</CustomAlert>
						</ModalFooter>
					</ModalContent>
				</Modal>
			</Stack>
		</Stack>
	);
};

export default H5PScoreBoard;
