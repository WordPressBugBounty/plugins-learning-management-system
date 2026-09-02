type DownloadMaterial = {
	id: number;
	url: string;
	preview_url?: string;
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
		| 'audio/wav'
		| 'audio/ogg';
	file_size: number;
	formatted_file_size: string;
};

type DownloadMaterials = DownloadMaterial[];

declare module '@wordpress/media-utils';

// Consumed by the standalone editor shell. Both packages are externalised
// onto the site's own scripts by the build, so they are deliberately not
// installed — installing @wordpress/block-editor alone would drag most of a
// gigabyte of Gutenberg packages into node_modules for type information.
declare module '@wordpress/block-editor';
declare module '@wordpress/block-library';
declare module '@wordpress/keyboard-shortcuts';

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
	required_plugin?: {
		name: string;
		file: string;
		slug: string;
		status: 'inactive' | 'not-installed';
	};
	plan: 'Free' | 'Starter' | 'Growth' | 'Scale' | 'Elite' | 'Pro' | 'Basic';
	locked: boolean;
	isStatic?: boolean;
	video?: string;
	docs?: string;
	category?: string;
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
	data: Addons;
	menu_items?: Addon['menu_items'];
}

interface AsyncSelectOption {
	value: string | number;
	label: string;
	avatar_url?: string;
}

type PaginatedApiResponse<T = object> = { data: T[]; meta: Meta };
