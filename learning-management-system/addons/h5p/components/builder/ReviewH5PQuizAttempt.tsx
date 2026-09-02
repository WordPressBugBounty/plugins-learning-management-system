import {
	Box,
	ButtonGroup,
	Container,
	Skeleton,
	SkeletonText,
	Stack,
	Text,
} from '@chakra-ui/react';
import { useQuery } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React, { useEffect } from 'react';
import { useParams } from 'react-router';
import { Link as RouterLink, useNavigate } from 'react-router-dom';
import { Table, Tbody, Td, Th, Thead, Tr } from 'react-super-responsive-table';
import BackButton from '../../../../assets/js/back-end/components/common/BackButton';
import {
	Header,
	HeaderLeftSection,
	HeaderLogo,
	HeaderTop,
} from '../../../../assets/js/back-end/components/common/Header';
import {
	NavMenu,
	NavMenuItem,
	NavMenuLink,
} from '../../../../assets/js/back-end/components/common/Nav';
import { navActiveStyles } from '../../../../assets/js/back-end/config/styles';
import backendRoutes from '../../../../assets/js/back-end/constants/routes';
import API from '../../../../assets/js/back-end/utils/api';
import h5pUrls from '../../constants/urls';
import { H5PQuizAttempt } from '../../types/h5pQuizAttempt';
import H5PAttemptInfo from '../H5PAttemptInfo';
import H5PQuestionScores from '../H5PQuestionScores';

const H5P_ATTEMPTS_LIST = `${backendRoutes.quiz_attempts.list}?type=h5p`;

const ReviewH5PQuizAttempt = () => {
	const { attemptId }: any = useParams();
	const navigate = useNavigate();
	const h5pAttemptsAPI = new API(h5pUrls.h5pQuizAttempts);

	const attemptQuery = useQuery<H5PQuizAttempt>({
		queryKey: [`h5pQuizAttempt${attemptId}`, attemptId],
		queryFn: () => h5pAttemptsAPI.get(attemptId),
	});

	useEffect(() => {
		if (attemptQuery?.isError) {
			navigate(backendRoutes.notFound);
		}
	}, [attemptQuery?.isError, navigate]);

	const questionScores = attemptQuery?.data?.question_scores ?? [];

	return (
		<Stack direction="column" spacing="8" alignItems="center">
			<Header>
				<HeaderTop>
					<HeaderLeftSection>
						<HeaderLogo />
						<NavMenu>
							<NavMenuItem>
								<NavMenuLink
									to={H5P_ATTEMPTS_LIST}
									_activeLink={navActiveStyles}
								>
									<Text>
										{__('H5P Quiz Details', 'learning-management-system')}
									</Text>
								</NavMenuLink>
							</NavMenuItem>
						</NavMenu>
					</HeaderLeftSection>
				</HeaderTop>
			</Header>
			<Container maxW="container.xl">
				<Stack direction="column" spacing="8">
					<Stack direction="column" spacing="6">
						<ButtonGroup>
							<RouterLink to={H5P_ATTEMPTS_LIST}>
								<BackButton />
							</RouterLink>
						</ButtonGroup>
					</Stack>

					{attemptQuery.isSuccess ? (
						<Text fontSize="lg" fontWeight="bold">
							{attemptQuery?.data?.quiz?.name ??
								__('H5P Quiz Attempt', 'learning-management-system')}
						</Text>
					) : (
						<Stack direction="column" spacing="5">
							<Skeleton height="22px" width="160px" />
							<SkeletonText noOfLines={1} width="65px" />
						</Stack>
					)}

					<Box bg="white" py={{ base: 6, md: 12 }} shadow="box">
						<Stack direction="column" spacing="8">
							<Table>
								<Thead>
									<Tr>
										<Th>{__('Student Info', 'learning-management-system')}</Th>
										<Th>{__('Quiz Summary', 'learning-management-system')}</Th>
										<Th>{__('Result', 'learning-management-system')}</Th>
										<Th></Th>
									</Tr>
								</Thead>
								<Tbody>
									{attemptQuery.isSuccess ? (
										<H5PAttemptInfo attempt={attemptQuery?.data} />
									) : (
										<Tr>
											<Td colSpan={3}>
												<SkeletonText noOfLines={3} spacing="3" />
											</Td>
										</Tr>
									)}
								</Tbody>
							</Table>
						</Stack>
					</Box>
					<Stack>
						<Text fontSize="md" fontWeight="bold">
							{__('Quiz Overview', 'learning-management-system')}
						</Text>
					</Stack>
					<Box bg="white" py={{ base: 6, md: 12 }} shadow="box">
						<Stack direction="column" spacing="8">
							<Table>
								<Thead>
									<Tr>
										<Th>{__('Question', 'learning-management-system')}</Th>
										<Th>{__('Result', 'learning-management-system')}</Th>
										<Th>{__('Points', 'learning-management-system')}</Th>
										<Th></Th>
									</Tr>
								</Thead>
								<Tbody>
									{attemptQuery.isSuccess ? (
										<H5PQuestionScores questionScores={questionScores} />
									) : (
										<Tr>
											<Td colSpan={3}>
												<SkeletonText noOfLines={4} spacing="3" />
											</Td>
										</Tr>
									)}
								</Tbody>
							</Table>
						</Stack>
					</Box>
				</Stack>
			</Container>
		</Stack>
	);
};

export default ReviewH5PQuizAttempt;
