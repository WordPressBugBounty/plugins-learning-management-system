export const certificateAccountPageRoutes = {
	certificates: '/certificates',
};

export const certificateBackendRoutes = {
	certificate: {
		list: '/certificates',
		add: '/certificates/new',
		edit: '/certificates/:certificateId/edit',
		settings: '/certificates-settings',
	},
};
