import { AuthorMap } from '../../../../assets/js/back-end/types/course';

export interface GoogleMeetSchema {
	id?: number;
	status?: string;
	calender_url?: string;
	meet_url?: string;
	course_name?: string;
	description: string;
	name?: string;
	summary?: string;
	created_at?: string;
	add_all_students_as_attendee?: boolean;
	parent_id: number;
	course_id?: number;
	sectionId?: number;
	parent_menu_order?: number;
	navigation?: any;
	starts_at?: Date | string;
	ends_at?: Date | string;
	time_zone?: string;
	duration?: number;
	meeting_id?: string;
	duration_hour?: number;
	duration_minute?: number;
	author: AuthorMap;
	course_permalink?: string;
}
