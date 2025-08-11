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

export type GroupSettingsSchema = {
	deactivate_enrollment_on_status_change: boolean;
	deactivate_enrollment_on_member_change: boolean;
	group_buy_button_text: string;
	group_buy_helper_text: string;
};
