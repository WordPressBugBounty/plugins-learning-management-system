export const ALL_MIGRATION_STEPS: string[] = ['Courses', 'Orders', 'Reviews'];

export const MIGRATION_STEPS_RELATIVE_TO_LMS: { [key: string]: string[] } = {
	'sfwd-lms': ['Courses', 'Orders'],
	tutor: [...ALL_MIGRATION_STEPS, 'Announcement', 'Questions and Answers'],
};

export const TUTOR_MIGRATION_STEPS: string[] = [
	'Courses',
	'Orders',
	'Reviews',
	'Announcement',
	'Questions and Answers',
	'Users',
	'Quiz Attempts',
];

export const COMPLETED: string = 'completed';
export const MIGRATING: string = 'migrating';
export const COURSES: string = 'courses';
export const ORDERS: string = 'orders';
export const REVIEWS: string = 'reviews';
export const ANNOUNCEMENTS: string = 'announcement';
export const QUESTIONSANDANSWERS: string = 'questions_n_answers';
export const USERS: string = 'users';
export const QUIZATTEMPTS: string = 'quiz_attempts';
