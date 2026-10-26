import { useInfiniteQuery } from '@tanstack/react-query';
import API from '../../../../../../../assets/js/back-end/utils/api';
import { urls } from '../../backend/constants/urls';
import { AnnouncementSchema } from '../../backend/types/announcement';

export const ANNOUNCEMENTS_PER_PAGE = 5;

export const announcementsQueryKey = (courseId: number) => [
	`announcement${courseId}`,
	courseId,
];

/**
 * A course's published announcements, paged, plus the unread count the
 * megaphone and the More sheet both show.
 */
export const useAnnouncementsQuery = (courseId: number, enabled = true) => {
	const api = new API(urls.courseAnnouncement);

	const query = useInfiniteQuery({
		queryKey: announcementsQueryKey(courseId),
		queryFn: ({ pageParam = 1 }) =>
			api.list({
				course_id: courseId,
				per_page: ANNOUNCEMENTS_PER_PAGE,
				page: pageParam,
				status: 'publish',
				request_from: 'learn',
			}),
		initialPageParam: 1,
		getNextPageParam: (lastPage, allPages) => {
			if (!lastPage || !lastPage.data) return undefined;
			if (lastPage.data.length < ANNOUNCEMENTS_PER_PAGE) return undefined;
			return allPages.length + 1;
		},
		enabled,
	});

	const announcements: AnnouncementSchema[] = query.isSuccess
		? query.data.pages.flatMap((page) => page.data ?? [])
		: [];

	const unreadCount = announcements.filter(
		(announcement) =>
			announcement[`has_user_read_${announcement?.id}`] === false,
	).length;

	return { api, query, announcements, unreadCount };
};
