interface WishlistItemSchema {
	id: number;
	course_title: string;
	course_id: number;
	author_id: number;
	created_at: string;
	default_featured_image_url: string;
	author?: {
		id: number;
		display_name: string;
		avatar_url: string;
	};
	course?: {
		id: number;
		name: string;
		slug: string;
		permalink: string;
		preview_permalink: string;
		date_created: string;
		date_created_gmt: string;
		date_modified: string;
		date_modified_gmt: string;
		status: string;
		featured: boolean;
		catalog_visibility: string;
		description: string;
		short_description: string;
		price_type: string;
		price_label: string;
		price: string;
		regular_price: string;
		sale_price: string;
		reviews_allowed: boolean;
		average_rating: string;
		rating_count: number;
		parent_id: number;
		featured_image: number;
		menu_order: number;
		enrollment_limit: number;
		duration: number;
		access_mode: string;
		billing_cycle: string;
		show_curriculum: boolean;
		featured_image_url: string;
		categories: [
			{
				id: number;
				name: string;
				slug: string;
			},
		];
		difficulty: {
			id: number;
			name: string;
			slug: string;
		};
		duration: number;
		average_rating: string;
		review_count: number;
		start_course_url: string;
		add_to_cart_url: string;
		buy_button: {
			text: string;
			url: string;
		};
		author?: {
			id: number;
			display_name: string;
			avatar_url: string;
		};
	};
}

interface WishlistItemsApiResponse {
	data: WishlistItemSchema[];
	meta: {
		current_page: number;
		pages: number;
		per_page: number;
		total: number;
	};
}
