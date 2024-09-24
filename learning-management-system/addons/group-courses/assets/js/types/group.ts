export type GroupSchema = {
	id: number;
	title: string;
	description: string;
	status: string;
	author: {
		id: number;
		display_name: string;
		avatar_url: string;
	};
	emails: string[];
	courses_count: number;
	date_created: string;
	date_modified: string;
};

export type GroupSettingsSchema = {
	max_members: string;
	deactivate_enrollment_on_status_change: boolean;
	deactivate_enrollment_on_member_change: boolean;
};
