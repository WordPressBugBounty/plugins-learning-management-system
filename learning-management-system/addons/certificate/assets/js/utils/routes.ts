export const certificateAccountPageRoutes = {
	certificates: '/certificates',
};

export const certificateBackendRoutes = {
	certificate: {
		list: '/certificates',
		add: '/certificates/new',
		edit: '/certificates/:certificateId/edit',
		settings: '/certificates-settings',
		certificatesV2: '/certificates-v2',
	},
};

export const accountPageRoutes = {
	certificates: '/certificates',
};

export function newCertificateAdminUrl(adminUrl: string): string {
	return `${adminUrl}admin.php?page=masteriyo#${certificateBackendRoutes.certificate.certificatesV2}`;
}
