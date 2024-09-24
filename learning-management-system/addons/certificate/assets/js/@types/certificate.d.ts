type UserCertificate = {
	id: number;
	download_url: string;
	course: import('../../../../../assets/js/back-end/schemas').CourseSchema & {
		featured_image_url: string;
		price_label: string;
		started_at: string;
	};
};

interface Certificate {
	id: number;
	name: string;
	slug: string;
	permalink: string;
	preview_link: string;
	edit_post_link: string;
	status: string;
	description: string;
	short_description: string;
	parent_id: number;
	date_created: string;
	date_modified: string;
	author: {
		id: number;
		display_name: string;
		avatar_url: string;
	};
}

interface CertificateSample {
	html: string;
	image: string;
	title: string;
}
