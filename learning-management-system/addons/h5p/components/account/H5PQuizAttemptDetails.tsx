import H5PAttemptInfo from '@addons/h5p/components/H5PAttemptInfo';
import H5PQuestionScores from '@addons/h5p/components/H5PQuestionScores';
import h5pUrls from '@addons/h5p/constants/urls';
import { H5PQuizAttempt } from '@addons/h5p/types/h5pQuizAttempt';
import { Box, Stack, Tooltip } from '@chakra-ui/react';
import { useQuery } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React, { useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { Table, Tbody, Td, Th, Thead, Tr } from 'react-super-responsive-table';
import BackToListButton from '../../../../assets/js/account/common/BackToListButton';
import PageSecondaryHeading from '../../../../assets/js/account/common/PageSecondaryHeading';
import PageTitle from '../../../../assets/js/account/common/PageTitle';
import routes from '../../../../assets/js/back-end/constants/routes';
import QuizInfoSkeleton from '../../../../assets/js/back-end/skeleton/QuizAttemptSkeleton/QuizInfoSkeleton';
import API from '../../../../assets/js/back-end/utils/api';

const H5PQuizAttemptDetails = () => {
	const { attemptId }: any = useParams();
	const navigate = useNavigate();
	const h5pAttemptsAPI = new API(h5pUrls.h5pQuizAttempts);

	const attemptQuery = useQuery<H5PQuizAttempt>({
		queryKey: [`h5pQuizAttempt${attemptId}`, attemptId],
		queryFn: () => h5pAttemptsAPI.get(attemptId),
	});

	useEffect(() => {
		if (attemptQuery?.isError) {
			navigate(routes.notFound);
		}
	}, [attemptQuery?.isError, navigate]);

	const questionScores = attemptQuery?.data?.question_scores ?? [];

	return (
		<Stack direction="column" spacing="10">
			<Stack gap={8}>
				<PageTitle
					title={__('Quiz Attempts Details', 'learning-management-system')}
					beforeTitle={
						<Tooltip label={__('Back', 'learning-management-system')}>
							<BackToListButton onClick={() => navigate(-1)} />
						</Tooltip>
					}
				/>
				<Table className="account_page_table">
					<Thead className="account_page_table_head">
						<Tr>
							<Th>{__('Quiz Info', 'learning-management-system')}</Th>
							<Th>{__('Quiz Summary', 'learning-management-system')}</Th>
							<Th>{__('Result', 'learning-management-system')}</Th>
						</Tr>
					</Thead>
					<Tbody className="account_page_table_body">
						{attemptQuery.isSuccess ? (
							<H5PAttemptInfo
								attempt={attemptQuery?.data}
								isStudentView={true}
							/>
						) : (
							<QuizInfoSkeleton />
						)}
					</Tbody>
				</Table>
			</Stack>
			<Stack gap={6}>
				<PageSecondaryHeading
					title={__('Quiz Overview', 'learning-management-system')}
				/>
				<Table className="account_page_table">
					<Thead className="account_page_table_head">
						<Tr>
							<Th>{__('Question', 'learning-management-system')}</Th>
							<Th>{__('Result', 'learning-management-system')}</Th>
							<Th>{__('Points', 'learning-management-system')}</Th>
						</Tr>
					</Thead>
					<Tbody className="account_page_table_body">
						{attemptQuery.isSuccess ? (
							<H5PQuestionScores questionScores={questionScores} />
						) : (
							<Tr>
								<Td colSpan={3}>
									<Box py={2} />
								</Td>
							</Tr>
						)}
					</Tbody>
				</Table>
			</Stack>
		</Stack>
	);
};

export default H5PQuizAttemptDetails;
