const googleMeetRoutes = {
	googleMeet: {
		list: '/google-meet/meetings',
		listRegex: /\/google-meet[\/]*$/,
		add: '/courses/:courseId/google-meet/:sectionId/add-new-google-meet',
		edit: '/courses/:courseId/google-meet/edit/:googleMeetId',
		setAPI: '/google-meet/setAPI',
	},
};

export default googleMeetRoutes;
