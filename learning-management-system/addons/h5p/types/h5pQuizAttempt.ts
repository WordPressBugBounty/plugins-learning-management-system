/**
 * Shared types for H5P quiz attempts.
 */

export interface H5PQuestionScore {
	h5p_content_id: number;
	question_index: number;
	title?: string;
	score: number;
	max_score: number;
	success: boolean | null;
	completed: boolean;
}

export interface H5PQuizAttempt {
	id: number;
	attempt_number: number;
	score: number;
	max_score: number;
	percentage: number;
	passed: 'yes' | 'no';
	status: 'started' | 'finished';
	total_answered_questions?: number;
	total_correct_answers?: number;
	total_incorrect_answers?: number;
	started_at?: string | null;
	finished_at?: string | null;
	question_scores?: H5PQuestionScore[];
	user: {
		id: number;
		display_name: string;
		first_name: string;
		last_name: string;
		email: string;
	} | null;
	quiz: { id: number; name: string; duration?: number } | null;
	course: { id: number; name: string } | null;
}
