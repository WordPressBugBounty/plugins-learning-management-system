type Answer = {
	id?: number;
	name: string;
	correct?: boolean;
};

type QuestionType =
	| 'true-false'
	| 'single-choice'
	| 'multiple-choice'
	| 'short-answer'
	| 'image-matching';

type Question = {
	id: number;
	answer_required: boolean;
	name: string;
	slug: string;
	permalink?: string;
	date_created: string;
	date_created_gmt: string;
	date_modified: string;
	date_modified_gmt: string;
	status: 'publish' | 'draft' | string;
	short_description: string;
	description: string;
	type: QuestionType;
	parent_id: number;
	course_id: number;
	menu_order: number;
	answers: Answer[];
	answers_required: boolean;
	randomize: boolean;
	points: number;
	positive_feedback: string;
	negative_feedback: string;
	feedback: string;
	_links: {
		self: [
			{
				href: string;
			},
		];
		collection: [
			{
				href: string;
			},
		];
	};
};

type Category = {
	id: number;
	name: string;
	slug: string;
	parent_id: number | Option;
	description: string;
	display: string;
	term_order: number;
	count: number;
	link: string;
	featured_image: number;
};

type Difficulty = {
	id: number;
	name: string;
	slug: string;
	description: string;
	term_order: number;
	count: number;
};

type Tag = {
	id: number;
	name: string;
	slug: string;
	description: string;
	term_order: number;
	count: number;
};

type Progress = {
	id: number;
	user_id: number;
	course_id: number;
	status: 'started' | 'completed' | 'progress';
	started_at: string;
	modified_at: string;
	completed_at: string;
};

type ProgressItem = {
	page: number;
	course_id: number;
	user_id: number;
	item_id: number;
	item_type: string;
	completed: boolean;
	started_at: string;
	completed_at: string;
	modified_at: string;
};

type Review = {
	id: number;
	course: { id: number; name: string };
	course_selector: { value: number; label: string };
	course_id: number;
	name: string;
	author_id: number;
	author_name: string;
	author_email: string;
	author_url: string;
	author_avatar_url: string;
	url: string;
	ip_address: string;
	date_created: string;
	description: string;
	title: string;
	rating: number;
	status: ReviewType;
	agent: string;
	type: string;
	parent: number;
	replies_count: number;
};

type Course = {
	id: number;
	name: string;
	slug: string;
	permalink: string;
	preview_permalink: string;
	date_created: string;
	date_created_gmt: string;
	date_modified: string;
	date_modified_gmt: string;
	status: CourseType;
	featured: boolean;
	catalog_visibility: string;
	description: string;
	short_description: string;
	price: string;
	regular_price: string;
	sale_price: string;
	reviews_allowed: boolean;
	duration_hour: number | undefined;
	duration_minute: number | undefined;
	average_rating: string;
	rating_count: number;
	parent_id: number;
	featured_image: number;
	categories: Category[];
	tags: Tag[];
	difficulty: Difficulty;
	menu_order: number;
	enrollment_limit: number;
	duration: number;
	access_mode: string;
	billing_cycle: string;
	show_curriculum: boolean;
	edit_post_link: string;
	author: {
		id: number;
		display_name: string;
		avatar_url: string;
	};
	_links: {
		self: [
			{
				href: string;
			},
		];
		collection: [
			{
				href: string;
			},
		];
		first: [
			{
				href: string;
			},
		];
	};
	highlights: string;
	price_type: CoursePriceType;
};

type CoursePriceType = 'free' | 'paid';

type LessonType = 'draft' | 'publish';

type VideoSource =
	| 'self-hosted'
	| 'youtube'
	| 'vimeo'
	| 'embed-video'
	| 'bunny-net';

type Lesson = {
	id: number;
	name: string;
	slug: string;
	permalink: string;
	date_created: string;
	date_created_gmt: string;
	date_modified: string;
	date_modified_gmt: string;
	status: LessonType;
	catalog_visibility: string;
	description: string;
	short_description: string;
	reviews_allowed: boolean;
	average_rating: string;
	rating_count: number;
	parent_id: number;
	course_id: number;
	menu_order: number;
	featured_image: number;
	video_url: string;
	video_source: VideoSource;
	video_source_url: string;
	video_source_id: number;
	video_playback_time: number;
	parent_menu_order: number;
	attachments: [
		{
			id: number;
			url: string;
		},
	];
	course_name: string;
	navigation: any;
};

type Section = {
	id: number;
	name: string;
	date_created: string;
	date_created_gmt: string;
	date_modified: string;
	date_modified_gmt: string;
	description: string;
	parent_id: number;
	course_id: number;
	menu_order: number;
	course_name: string;
};

type Order = {
	id: number;
	permalink: string;
	date_created: Date;
	date_created_gmt: string;
	date_modified: string;
	date_modified_gmt: string;
	status: string;
	total: number;
	currency: string;
	currency_symbol: string;
	expiry_date: string;
	customer_id: string;
	payment_method: string;
	payment_method_title: string;
	transaction_id: string;
	date_paid: string;
	date_completed: string;
	created_via: string;
	customer_ip_address: string;
	customer_user_agent: string;
	version: string;
	order_key: string;
	customer_note: string;
	cart_hash: string;
	billing: {
		first_name: string;
		last_name: string;
		company: string;
		address_1: string;
		address_2: string;
		city: string;
		postcode: string;
		country: string;
		state: string;
		email: string;
		phone: string;
	};
	set_paid: boolean;
	course_lines: CourseLine[];
	formatted_total: string;
};

type CourseLine = {
	id: number;
	course_id: number;
	name: string;
	quantity: number;
	subtotal: string;
	total: string;
	price: number;
};

type OrderItem = {
	id: number;
	order_id: string;
	course_id: string;
	name: string;
	type: string;
	quantity: number;
	total: number | string;
};

type User = {
	id: number;
	username: string;
	password: string;
	confirm_password: string;
	nicename: string;
	email: string;
	url: string;
	date_created: string;
	activation_key: string;
	status: 0 | 1 | 1000;
	display_name: string;
	nickname: string;
	first_name: string;
	last_name: string;
	description: string;
	rich_editing: boolean;
	syntax_highlighting: boolean;
	comment_shortcuts: boolean;
	spam: boolean;
	use_ssl: boolean;
	show_admin_bar_front: boolean;
	locale: string;
	roles: string[];
	profile_image: { id: number; url: string };
	billing: {
		first_name: string;
		last_name: string;
		company_name: string;
		company_id: string;
		address_1: string;
		address_2: string;
		city: string;
		postcode: string;
		country: string;
		state: string;
		email: string;
		phone: string;
	};
	approved: boolean;
	avatar_url: string;
	instructor_apply_status: 'not_applied' | 'applied' | 'rejected' | 'approved';
};

type Content = {
	id: number;
	name: string;
	permalink: string;
	type: string;
	menu_order: number;
	parent_id: number;
};

type ScormPackage = {
	path: string;
	url: string;
	scorm_version: string;
	file_name: string;
};

type CourseBuilder = {
	contents: Content[];
	sections: Section[];
	section_order: number[];
	scorm_package?: ScormPackage;
};

type QuizBuilder = {
	contents: Content[];
};

type Countries = {
	code: string;
	name: string;
};

type Country = {
	countryCode: string;
	countryName: string;
	currencyCode: string;
	population: string;
	capital: string;
	continentName: string;
};

type Currencies = {
	code: string;
	name: string;
	symbol: string;
};

type States = {
	country: string;
	states: {
		code: string;
		name: string;
	};
};

type Media = {
	id: number;
	date: string;
	date_gmt: string;
	guid: {
		rendered: string;
	};
	modified: string;
	modified_gmt: string;
	slug: string;
	status: string;
	type: string;
	link: string;
	title: {
		rendered: string;
	};
	author: number;
	comment_status: string;
	ping_status: string;
	template: string;
	meta: [];
	description: {
		rendered: string;
	};
	caption: {
		rendered: string;
	};
	alt_text: string;
	media_type: string;
	mime_type: string;
	media_details: {
		width: number;
		height: number;
		file: string;
		sizes: {
			full: {
				file: string;
				height: number;
				mime_type: string;
				source_url: string;
				width: number;
			};
			large: {
				file: string;
				height: number;
				mime_type: string;
				source_url: string;
				width: number;
			};
			masteriyo_medium: {
				file: string;
				height: number;
				mime_type: string;
				source_url: string;
				width: number;
			};
			masteriyo_single: {
				file: string;
				height: number;
				mime_type: string;
				source_url: string;
				width: number;
			};
			masteriyo_thumbnail: {
				file: string;
				height: number;
				mime_type: string;
				source_url: string;
				width: number;
			};
			medium: {
				file: string;
				height: number;
				mime_type: string;
				source_url: string;
				width: number;
			};
			medium_large: {
				file: string;
				height: number;
				mime_type: string;
				source_url: string;
				width: number;
			};
			thumbnail: {
				file: string;
				height: number;
				mime_type: string;
				source_url: string;
				width: number;
			};
		};
		image_meta: {
			aperture: string;
			credit: string;
			camera: string;
			caption: string;
			created_timestamp: string;
			copyright: string;
			focal_length: string;
			iso: string;
			shutter_speed: string;
			title: string;
			orientation: string;
			keywords: [];
		};
	};
	post: number;
	source_url: string;
	_links: {
		self: [
			{
				href: string;
			},
		];
		collection: [
			{
				href: string;
			},
		];
		about: [
			{
				href: string;
			},
		];
		author: [
			{
				embeddable: boolean;
				href: string;
			},
		];
		replies: [
			{
				embeddable: boolean;
				href: string;
			},
		];
	};
};

type GeneralSettings = {
	styling: {
		primary_color: string;
		theme: string;
	};
	pages: {
		courses_page_id: number;
		learn_page_id: number;
		account_page_id: number;
		checkout_page_id: number;
	};
};

type CourseArchiveSettings = {
	display: {
		view_mode: string;
		enable_search: boolean;
		per_page: number;
		per_row: number;
		thumbnail_size: string;
	};
	filters_and_sorting?: {
		enable_ajax: boolean;
		enable_filters: boolean;
		enable_category_filter: boolean;
		enable_difficulty_level_filter: boolean;
		enable_price_type_filter: boolean;
		enable_price_filter: boolean;
		enable_rating_filter: boolean;
		enable_sorting: boolean;
		enable_date_sorting: boolean;
		enable_price_sorting: boolean;
		enable_rating_sorting: boolean;
		enable_course_title_sorting: boolean;
	};
};

type SingleCourseSettings = {
	display: {
		enable_review: boolean;
	};
	related_courses: {
		enable: boolean;
	};
};

type LearningPageSettings = {
	general: {
		logo_id: number;
	};
	display: {
		enable_questions_answers: boolean;
	};
};

type PaymentsSettings = {
	store: {
		country: string;
		city: string;
		state: string;
		address_line1: string;
		address_line2: string;
	};
	currency: {
		currency: string;
		currency_position: string;
		thousand_separator: string;
		decimal_separator: string;
		number_of_decimals: number;
	};
	offline: {
		enable: boolean;
		title: string;
		description: string;
		instructions: string;
	};
	paypal: {
		enable: boolean;
		title: string;
		description: string;
		ipn_email_notifications: boolean;
		sandbox: boolean;
		email: string;
		receiver_email: string;
		identity_token: string;
		invoice_prefix: string;
		payment_action: string;
		image_url: string;
		debug: boolean;
		sandbox_api_username: string;
		sandbox_api_password: string;
		sandbox_api_signature: string;
		live_api_username: string;
		live_api_password: string;
		live_api_signature: string;
	};
	checkout_fields: CheckoutFields;
};

type QuizSettings = {
	styling: {
		questions_display_per_page: number;
	};
};

type EmailSetting = {
	enable: boolean;
	subject: string;
	heading: string;
	content: string;
};

type EmailSettingWithRecipients = EmailSetting & { recipients: [] };

type EmailSettings = {
	general: {
		enable: boolean;
		from_name: string;
		from_email: string;
		default_content: string;
		header_image: string;
		footer_text: string;
	};
	admin: {
		new_order: EmailSettingWithRecipients;
		student_registration: EmailSettingWithRecipients;
		instructor_registration: EmailSettingWithRecipients;
		start_course: EmailSettingWithRecipients;
		completed_course: EmailSettingWithRecipients;
	};
	instructor: {
		instructor_registration: EmailSettingWithRecipients;
		start_course: EmailSettingWithRecipients;
		completed_course: EmailSetting;
	};
	student: {
		student_registration: EmailSetting;
		completed_order: EmailSetting;
		onhold_order: EmailSetting;
		cancelled_order: EmailSetting;
		completed_course: EmailSetting;
	};
};

type AdvancedSettings = {
	permalinks: {
		category_base: string;
		difficulty_base: string;
		tag_base: string;
		single_course_permalink: string;
		single_lesson_permalink: string;
		single_quiz_permalink: string;
		single_section_permalink: string;
	};
	account: {
		orders: string;
		view_order: string;
		my_courses: string;
		edit_account: string;
		payment_methods: string;
		lost_password: string;
		logout: string;
	};
	checkout: {
		pay: string;
		order_received: string;
		add_payment_method: string;
		delete_payment_method: string;
		set_default_payment_method: string;
	};
	debug: {
		template_debug: boolean;
		debug: boolean;
	};
	uninstall: {
		remove_data: boolean;
	};
	gdpr: {
		enable: boolean;
		message: string;
	};
	tracking: {
		allow_usage: boolean;
	};
	email_verification: {
		enable: boolean;
	};
};

type CheckoutFields = {
	address_1: boolean;
	address_2: boolean;
	country: boolean;
	company: boolean;
	customer_note: boolean;
	phone: boolean;
	postcode: boolean;
	state: boolean;
	city: boolean;
};

type Settings = {
	general: GeneralSettings;
	course_archive: CourseArchiveSettings;
	single_course: SingleCourseSettings;
	learn_page: LearningPageSettings;
	payments: PaymentsSettings;
	quiz: QuizSettings;
	emails: EmailSettings;
	advance: AdvancedSettings;
};

type Quiz = {
	id: number;
	name: string;
	slug: string;
	permalink: string;
	parent_id: number;
	course_id: number;
	course_name: string;
	menu_order: number;
	parent_menu_order: number;
	description: string;
	short_description: string;
	date_created: string;
	date_created_gmt: string;
	date_modified: string;
	date_modified_gmt: string;
	status: 'draft' | 'pending' | 'private' | 'publish' | 'future';
	pass_mark: number;
	full_mark: number;
	duration: number;
	duration_hour: number | undefined;
	duration_minute: number | undefined;
	attempts_allowed: number;
	questions_display_per_page: number | string;
	questions_display_per_page_custom: number | string;
	questions_display_per_page_global: number;
	questions_count: number;
	attempts: 'limit' | 'no-limit' | undefined;
	question_per_page: 'global' | 'custom' | undefined;
	navigation: {
		previous: {
			id: number;
			name: string;
			type: string;
			video: boolean;
			parent: {
				id: number;
				name: string;
			};
		};
		next: {
			id: number;
			name: string;
			type: string;
			video: boolean;
			parent: {
				id: number;
				name: string;
			};
		};
	};
	_links: {
		self: [
			{
				href: string;
			},
		];
		collection: [
			{
				href: string;
			},
		];
		previous: [
			{
				href: string;
			},
		];
		next: [
			{
				href: string;
			},
		];
	};
};

type QuizAttempt = {
	id: number;
	total_questions: number;
	total_answered_questions: number;
	total_marks: string;
	total_attempts: number;
	total_correct_answers: number;
	total_incorrect_answers: number;
	earned_marks: string;
	answers: any;
	attempt_status: 'attempt_started' | 'attempt_ended';
	attempt_started_at: string;
	attempt_ended_at: string;
	course: {
		id: number;
		name: string;
	};
	quiz: {
		id: number;
		name: string;
		pass_mark: number;
		duration: number;
	};
	user: {
		id: number;
		display_name: string;
		first_name: string;
		last_name: string;
		email: string;
	};
};

type Author = {
	id: number;
	display_name: string;
	avatar_url: string;
};

type CourseLinks = {
	self: [{ href: string }];
	collection: [{ href: string }];
};

type CourseType =
	| 'publish'
	| 'future'
	| 'draft'
	| 'pending'
	| 'private'
	| 'trash'
	| 'autoDraft'
	| 'inherit'
	| 'any';

type CourseTypeCount = {
	publish: number;
	future: number;
	draft: number;
	pending: number;
	private: number;
	trash: number;
	autoDraft: number;
	inherit: number;
	any: number;
};

type CourseCategoryHierarchy = Category & {
	depth: number;
};

type ContentNavigationButton = {
	id: number;
	name: string;
	type: string;
	video: boolean;
	parent: {
		id: number;
		name: string;
	};
};

type ContentNavigation = {
	previous: ContentNavigationButton;
	next: ContentNavigationButton;
};

type CourseProgressSummary = {
	total: {
		completed: number;
		pending: number;
	};
	lesson: {
		completed: number;
		pending: number;
		total: number;
	};
	quiz: {
		completed: number;
		pending: number;
		total: number;
	};
};

type CourseProgressContent = {
	item_id: number;
	item_title: string;
	item_type: 'quiz' | 'lesson';
	completed: boolean;
	video: boolean;
};

type CourseProgress = {
	id: number;
	name: string;
	user_id: number;
	course_id: 9;
	status: 'started' | 'progress' | 'completed';
	started_at: string;
	modified_at: string;
	completed_at: string | any;
	items: CourseProgressItem[];
	summary: CourseProgressSummary;
	course_permalink: string;
};

type CourseProgressItem = {
	id?: number;
	progress_id?: number;
	course_id?: number;
	user_id?: number;
	item_id?: number;
	item_type?: 'lesson' | 'quiz';
	completed?: boolean;
	started_at?: string;
	modified_at?: string;
	completed_at?: string | any;
	contents: [CourseProgressContent];
};

type QuestionAnswer = {
	id: number;
	course_id: number;
	user_id: number;
	user_name: string;
	user_email: string;
	user_avatar: string;
	user_url: string;
	created_at: string;
	content: string;
	parent: number;
	sender: 'student' | 'instructor';
	by_current_user: boolean;
	answers_count: number;
	ip_address: string;
};

type MyCourse = {
	id: number;
	user_id: number;
	course: Course & { featured_image_url: string; start_course_url: string };
	type: string;
	status: string;
	started_at: string;
	modified_at: string;
};

type PageMeta = {
	total: number;
	pages: number;
	current_page: number;
	per_page: number;
};

type OrderStatus =
	| 'any'
	| 'completed'
	| 'on-hold'
	| 'failed'
	| 'refunded'
	| 'cancelled'
	| 'pending'
	| 'trash';

type Sorting = 'asc' | 'desc';

type QuestionTypeMap = {
	value: QuestionType;
	label: string;
	icon: any;
};

type PaymentMethod = 'paypal' | 'offline';

type ReviewType = 'all' | 'approve' | 'hold' | 'spam' | 'trash';

type Option = {
	value: any;
	label: string;
	colorScheme?: string;
};

type SetupWizard = {
	course_archive: CourseArchiveSettings;
	quiz: QuizSettings;
};

type ActionButtonProps = {
	onEditPress?: () => void;
	onTrashPress?: () => void;
	onDuplicatePress?: () => void;
	onPreviewPress?: () => void;
	onRestorePress?: () => void;
	onWordPressEditPress?: () => void;
};

type NavType =
	| 'courses'
	| 'categories'
	| 'quizzes'
	| 'lessons'
	| 'assignments'
	| 'orders'
	| 'quiz-attempts'
	| 'users'
	| 'reviews'
	| 'settings';

type FilterTab = {
	name: string;
	status: string;
	icon?: React.ReactElement;
};

type FilterTabs = FilterTab[];

type QACount = {
	all: number | undefined;
	spam: number | undefined;
	trash: number | undefined;
};

type QA = {
	id: number;
	course_id: number;
	user_name: string;
	user_email: string;
	user_url: string;
	user_avatar: string;
	ip_address: string;
	created_at: string;
	content: string;
	status: ReviewStatus;
	agent: string;
	parent: number;
	user_id: number;
	by_current_user: boolean;
	sender: string;
	answers_count: number;
	course_name: string;
	_links: {
		self: [
			{
				href: string;
			},
		];
		collection: [
			{
				href: string;
			},
		];
	};
};

type SystemStatus = {
	wp_info: {
		masteriyo_ver: string;
		version: string;
		site_url: string;
		home_url: string;
		multisite: boolean;
		external_object_cache: string;
		memory_limit: string;
		debug_mode: boolean;
		cron: boolean;
		language: string;
	};
	server_info: {
		php_version: string;
		php_post_max_size: string;
		php_max_execution_time: string;
		php_max_input_vars: string;
		server_info: string;
		curl_version: string;
		max_upload_size: number;
		mysql_version: string;
		default_timezone: string;
		enable_fsockopen_or_curl: boolean;
		enable_soapclient: boolean;
		enable_domdocument: boolean;
		enable_gzip: boolean;
		enable_mbstring: boolean;
	};
};
