const h5pRoutes = {
	h5pQuiz: {
		builder: {
			add: '/courses/:courseId/h5p-quiz/:sectionId/add-new-h5p-quiz',
			edit: '/courses/:courseId/h5p-quiz/edit/:h5pQuizId',
		},
	},
	h5pQuizAttempt: {
		view: '/quiz-attempts/h5p/:attemptId',
	},
};

export default h5pRoutes;
