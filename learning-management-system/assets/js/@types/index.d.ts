type DownloadMaterial = {
	id: number;
	url: string;
	title: string;
	mime_type:
		| 'application/pdf'
		| 'application/zip'
		| 'application/msword'
		| 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
		| 'application/vnd.ms-powerpoint'
		| 'application/vnd.openxmlformats-officedocument.presentationml.presentation'
		| 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
		| 'application/vnd.ms-excel'
		| 'application/vnd.ms-excel'
		| 'image/jpeg'
		| 'image/png'
		| 'image/jpg'
		| 'image/gif'
		| 'video/mp4'
		| 'video/mkv'
		| 'video/avi'
		| 'video/flv'
		| 'video/mov'
		| 'audio/mpeg'
		| 'audio/wav';

	file_size: number;
	formatted_file_size: '100 KB';
};

type DownloadMaterials = DownloadMaterial[];

declare module '@wordpress/media-utils';

type Addon = {
	slug: string;
	active: boolean;
	addon_name: string;
	addon_type: string;
	addon_uri: string;
	description: string;
	author: string;
	author_uri: string;
	thumbnail: string;
	requires: string;
	requirement_fulfilled: string;
	plan: 'Starter' | 'Growth' | 'Scale';
	locked: boolean;
};

type Addons = Addon[];

type AddonResponse = Addon & {
	menu_items?: {
		menu_title: string;
		menu_slug: string;
		slug: string;
	}[];
};

interface AddonsResponse {
	addons: Addons;
	menu_items?: Addon['menu_items'];
}

interface AsyncSelectOption {
	value: string | number;
	label: string;
	avatar_url?: string;
}

type PaginatedApiResponse<T = object> = { data: T[]; meta: Meta };
