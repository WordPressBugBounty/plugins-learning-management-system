import http from '../../../../../assets/js/back-end/utils/http';
import { formatParams } from '../../../../../assets/js/back-end/utils/utils';
import { certificateAddonUrls } from './urls';

export const getAllCertificates = (query?: any) => {
	return http<PaginatedApiResponse<Certificate>>({
		path: query
			? `${certificateAddonUrls.certificates}?${formatParams(query)}`
			: certificateAddonUrls.certificates,
		method: 'get',
	}).then((res) => res);
};

export const cloneCertificateTemplate = (id: string) => {
	return http<Certificate>({
		path:
			`${certificateAddonUrls.certificates}/clone-template?template_id=` + id,
		method: 'post',
	}).then((res) => res);
};

export const cloneCertificate = (id: number) => {
	return http<Certificate>({
		path: `${certificateAddonUrls.certificates}/${id}/clone`,
		method: 'post',
	}).then((res) => res);
};

export const getAllCertificateTemplates = () => {
	return http<CertificateSample[]>({
		path: certificateAddonUrls.certificateSamples,
		method: 'get',
	}).then((res) => res);
};

export type CertificateSettingsSchema = {
	use_absolute_img_path: boolean;
	use_ssl_verified: boolean;
};
