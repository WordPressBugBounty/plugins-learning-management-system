export type GroupSchema = {
	id: number;
	title: string;
	description: string;
	status: string;
	display_status: 'active' | 'pending' | 'inactive';
	status_reason?: string;
	repurchase_url?: string;
	author: {
		id: number;
		display_name: string;
		avatar_url: string;
	};
	emails: string[];
	courses_count: number;
	courses?: {
		id: number;
		title: string;
		permalink?: string;
	}[];
	date_created: string;
	date_modified: string;
	max_group_size: number;
	order?: {
		id: number;
		status: string;
	} | null;
};

export type MemberCourseProgress = {
	course_id: number;
	enrollment_status: string | null;
	progress_status: string | null;
	percentage: number;
	lessons: { completed: number; total: number };
	quizzes: { completed: number; total: number };
	started_at: string | null;
	modified_at: string | null;
	completed_at: string | null;
};

export type GroupProgressSchema = {
	courses: { id: number; name: string; permalink: string }[];
	members: {
		email: string;
		user: { id: number; display_name: string; avatar_url: string } | null;
		courses: MemberCourseProgress[];
	}[];
};

export type GroupSettingsSchema = {
	deactivate_enrollment_on_status_change: boolean;
	deactivate_enrollment_on_member_change: boolean;
	group_buy_button_text: string;
	group_buy_helper_text: string;
};
