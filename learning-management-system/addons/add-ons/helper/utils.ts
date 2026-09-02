export const getAnswerByType = (
	quizAttemptAnswers: any,
	answerType: string,
) => {
	return Object.values(quizAttemptAnswers).filter(
		(answer: any) => answerType === answer?.type,
	);
};
