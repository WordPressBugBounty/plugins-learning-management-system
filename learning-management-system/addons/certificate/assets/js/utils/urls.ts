export const basePro = '/masteriyo/pro/v1/';

export const certificateAddonUrls = {
	certificates: basePro + 'certificates',
	myCertificates: basePro + 'certificates/mine',
	certificateSamples: basePro + 'certificates/samples',
	certificatesSetting: basePro + 'certificates/settings',
	importCertificateFonts: basePro + 'certificates/import-certificate-fonts',
	pdfdraftInlineImages: basePro + 'certificates/pdfdraft-inline-images',
	certificate: (id: number) => `${basePro}certificates/${id}`,
	certificatePreviewData: (id: number) =>
		`${basePro}certificates/${id}/pdfdraft-preview-data`,
	certificatePdfdraftPreviewData: (id: number) =>
		`${basePro}certificates/${id}/pdfdraft-preview-data`,
	certificatePdfdraftPreview: (id: number) =>
		`${basePro}certificates/${id}/pdfdraft-preview`,
	certificatePdfData: basePro + 'certificate-pdf-data',
};
