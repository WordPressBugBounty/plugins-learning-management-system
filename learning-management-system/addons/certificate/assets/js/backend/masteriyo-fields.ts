import type {
	ElementCategory,
	ElementType,
	WpDataFieldGroup,
} from '@pdfdraft/designer';
import {
	AdvancedSelectorRender,
	ElementToolbarItems,
	designerQueryClient,
	generateStyleString,
	useEditorActions,
} from '@pdfdraft/designer';
import {
	BookOpen,
	Calendar,
	CalendarCheck,
	CalendarDays,
	Clock,
	Globe,
	GraduationCap,
	QrCode,
	Shield,
	Star,
	Timer,
	User,
	Users,
} from '@pdfdraft/ui/icons';
import { __, sprintf } from '@wordpress/i18n';
import React from 'react';
import localized from '../../../../../assets/js/back-end/utils/global';
import {
	getPluginName,
	isLicensePlanActive,
} from '../../../../../assets/js/back-end/utils/utils';

/**
 * Whether an addon is active, read synchronously from the localized data so it
 * resolves correctly at module-load time (before the wp.data `addOns` store is
 * guaranteed registered).
 */
function isAddonActive(slug: string): boolean {
	return ((localized?.addons as any[]) ?? []).some(
		(addon) => addon?.slug === slug && addon?.active,
	);
}

export const MASTERIYO_FIELD_GROUP: WpDataFieldGroup = {
	source: 'masteriyo',
	// translators: %s: the product's name.
	label: sprintf(__('%s LMS', 'learning-management-system'), getPluginName()),
	fields: [
		{
			key: 'masteriyo:course_title',
			label: 'Course Title',
			outputType: 'text',
		},
		{
			key: 'masteriyo:student_name_full',
			label: 'Student Name',
			outputType: 'text',
		},
		{ key: 'masteriyo:qr_code', label: 'QR Code', outputType: 'image' },
		{
			key: 'masteriyo:completion_date',
			label: 'Completion Date',
			outputType: 'text',
			hasDateFormat: true,
		},
		{
			key: 'masteriyo:verification_code',
			label: 'Verify Code',
			outputType: 'text',
		},
		{
			key: 'masteriyo:start_date',
			label: 'Start Date',
			outputType: 'text',
			hasDateFormat: true,
		},
		...(isAddonActive('gradebook')
			? [
					{
						key: 'masteriyo:grade',
						label: 'Grade Result',
						outputType: 'text' as const,
					},
				]
			: []),
		{
			key: 'masteriyo:instructor_name',
			label: 'Instructor Name',
			outputType: 'text',
		},
		...(isAddonActive('multiple-instructors')
			? [
					{
						key: 'masteriyo:co_instructors',
						label: 'Co-Instructors',
						outputType: 'text' as const,
					},
				]
			: []),
		{
			key: 'masteriyo:course_duration',
			label: 'Course Duration',
			outputType: 'text',
		},
		{
			key: 'masteriyo:current_date',
			label: 'Current Date',
			outputType: 'text',
			hasDateFormat: true,
		},
		{
			key: 'masteriyo:current_time',
			label: 'Current Time',
			outputType: 'text',
		},
		{
			key: 'masteriyo:current_timestamp',
			label: 'Timestamp',
			outputType: 'text',
		},
		{
			key: 'masteriyo:student_name_first',
			label: 'First Name',
			outputType: 'text',
		},
		{
			key: 'masteriyo:student_name_last',
			label: 'Last Name',
			outputType: 'text',
		},
		{ key: 'masteriyo:site_name', label: 'Site Name', outputType: 'text' },
	],
};

export const MASTERIYO_ELEMENT_CATEGORY: ElementCategory = {
	namespace: 'masteriyo',
	label: '',
	order: 3,
};

export const MASTERIYO_ELEMENT_CATEGORIES: ElementCategory[] = [
	MASTERIYO_ELEMENT_CATEGORY,
];

const FIELD_ICONS: Record<string, React.ComponentType<any>> = {
	'masteriyo:course_title': BookOpen,
	'masteriyo:student_name_full': User,
	'masteriyo:student_name_first': User,
	'masteriyo:student_name_last': User,
	'masteriyo:qr_code': QrCode,
	'masteriyo:completion_date': CalendarCheck,
	'masteriyo:start_date': Calendar,
	'masteriyo:verification_code': Shield,
	'masteriyo:grade': Star,
	'masteriyo:instructor_name': GraduationCap,
	'masteriyo:co_instructors': Users,
	'masteriyo:course_duration': Clock,
	'masteriyo:current_date': CalendarDays,
	'masteriyo:current_time': Clock,
	'masteriyo:current_timestamp': Timer,
	'masteriyo:site_name': Globe,
};

const HEADING_OPTIONS = [
	{ value: '', label: 'Normal' },
	{ value: 'h1', label: 'H1' },
	{ value: 'h2', label: 'H2' },
	{ value: 'h3', label: 'H3' },
] as const;

export const HEADING_STYLE: Record<string, [number, number]> = {
	h1: [32, 700],
	h2: [24, 700],
	h3: [18, 600],
};

export const SmartTagHeadingToolbar: React.FC<{ element: any }> = ({
	element,
}) => {
	const { updateElement } = useEditorActions(['updateElement']);
	const current = (element?.props?.headingLevel as string) ?? '';

	return React.createElement(
		'div',
		{
			style: {
				display: 'flex',
				alignItems: 'center',
				gap: 4,
				padding: '0 8px',
			},
		},
		React.createElement(
			'select',
			{
				value: current,
				onChange: (e: React.ChangeEvent<HTMLSelectElement>) => {
					const level = e.target.value;
					const [fontSize, fontWeight] = HEADING_STYLE[level] ?? [];
					updateElement(element.id, (el: any) => ({
						...el,
						props: { ...el.props, headingLevel: level || null },
						style: {
							...el.style,
							...(fontSize ? { fontSize: `${fontSize}px`, fontWeight } : {}),
						},
					}));
				},
				style: {
					fontSize: 12,
					padding: '2px 4px',
					border: '1px solid #e2e8f0',
					borderRadius: 4,
					background: 'white',
				},
			},
			HEADING_OPTIONS.map(({ value, label }) =>
				React.createElement('option', { key: value, value }, label),
			),
		),
	);
};

function resolveFieldForPDF(fieldKey: string, label: string): string {
	const preview: Record<string, string> =
		(window as any)._MASTERIYO_CERTIFICATE_PREVIEW_ ?? {};

	const restKeyMap: Record<string, string> = {
		'masteriyo:course_title': 'course_title',
		'masteriyo:instructor_name': 'instructor_name',
		'masteriyo:course_duration': 'course_duration',
		'masteriyo:current_date': 'current_date',
		'masteriyo:current_time': 'current_time',
		'masteriyo:current_timestamp': 'current_timestamp',
		'masteriyo:site_name': 'site_name',
	};

	const restKey = restKeyMap[fieldKey];
	if (restKey && preview[restKey]) {
		return preview[restKey];
	}

	const now = new Date();
	const dateStr = now.toLocaleDateString(undefined, {
		year: 'numeric',
		month: 'long',
		day: 'numeric',
	});
	const timeStr = now.toLocaleTimeString(undefined, {
		hour: '2-digit',
		minute: '2-digit',
	});

	const jsFallback: Record<string, string> = {
		'masteriyo:current_date': dateStr,
		'masteriyo:current_time': timeStr,
		'masteriyo:current_timestamp': `${dateStr} ${timeStr}`,
		'masteriyo:site_name':
			(window as any)._MASTERIYO_PDFDRAFT_?.siteName || label,
		'masteriyo:instructor_name':
			(window as any)._MASTERIYO_?.current_user?.display_name || label,
	};

	return jsFallback[fieldKey] ?? label;
}

function makeMasteriyoElement(
	fieldKey: string,
	label: string,
	outputType: 'text' | 'image',
	tier: 'free' | 'pro' = 'free',
): ElementType {
	const isImage = outputType === 'image';
	const namespace = `masteriyo__${fieldKey.replace('masteriyo:', '')}`;

	const advancedConfig = {
		getMergeTagOptions: () => [] as any[],
		placeholder: `{ ${label} }`,
		emptyLabel: `{ ${label} }`,
		minWidthPx: 0,
		minHeightPx: 0,
	};

	const TextRender = (props: any) =>
		React.createElement(AdvancedSelectorRender, {
			...props,
			'data-merge-tag': `{{${fieldKey}}}`,
			config: advancedConfig,
			queryClient: designerQueryClient,
			extraToolbar: React.createElement(
				React.Fragment,
				null,
				React.createElement(SmartTagHeadingToolbar, { element: props.data }),
				React.createElement(ElementToolbarItems),
			),
		});

	TextRender.displayName = `MasteriyoField_${fieldKey}`;

	const ImageRender: React.FC<any> = ({ data, style, ...rest }) =>
		React.createElement(
			'div',
			{
				...rest,
				'data-id': data?.id,
				'data-merge-tag': `{{${fieldKey}}}`,
				style: {
					position: 'absolute',
					boxSizing: 'border-box',
					width: '100%',
					height: '100%',
					background: '#e5e7eb',
					border: '2px dashed #9ca3af',
					borderRadius: 4,
					display: 'flex',
					flexDirection: 'column',
					alignItems: 'center',
					justifyContent: 'center',
					gap: 4,
					overflow: 'hidden',
					...(style ?? {}),
				},
			},
			React.createElement(
				'span',
				{
					style: {
						fontSize: 11,
						color: '#6b7280',
						fontFamily: 'Inter, sans-serif',
					},
				},
				`[ ${label} ]`,
			),
		);

	ImageRender.displayName = `MasteriyoImageField_${fieldKey}`;

	const IconComponent = FIELD_ICONS[fieldKey] ?? BookOpen;
	const icon = React.createElement(IconComponent, { size: 16 });

	return {
		namespace,
		label,
		category: 'masteriyo',
		icon,
		render: isImage ? ImageRender : TextRender,
		toHTML: (elementData: any) => {
			const headingLevel = elementData?.props?.headingLevel as
				| string
				| undefined;
			const [hSize, hWeight] = HEADING_STYLE[headingLevel ?? ''] ?? [];
			const styleObj = { ...(elementData?.style ?? {}) };
			delete (styleObj as any).backgroundColor;
			if (headingLevel && hSize && !(styleObj as any).fontSize)
				(styleObj as any).fontSize = hSize;
			if (headingLevel && hWeight && !(styleObj as any).fontWeight)
				(styleObj as any).fontWeight = hWeight;
			const style = generateStyleString(styleObj);
			const textAlign = (elementData?.style as any)?.textAlign ?? 'start';
			const rawText: string = resolveFieldForPDF(
				fieldKey,
				(elementData?.props?.content as string | undefined) ||
					(elementData?.content as string | undefined) ||
					elementData?.props?.fallback ||
					label,
			);
			const escaped = rawText
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/\n/g, '<br>');
			const innerStyle =
				'overflow-wrap:break-word;word-break:normal;-webkit-font-variant-ligatures:none;font-variant-ligatures:none;position:relative;text-align:inherit;padding:0;margin:0';
			const inner = headingLevel
				? `<${headingLevel} style="margin:0;padding:0;font-size:inherit;font-weight:inherit;">${escaped}</${headingLevel}>`
				: escaped;
			return `<div id="${elementData?.id}" data-type="text" data-merge-tag="{{${fieldKey}}}" style="${style};"><div style="line-height:1.4;letter-spacing:0px;text-align:${textAlign};padding:0;margin:0;"><div style="${innerStyle}">${inner}</div></div></div>`;
		},
		keywords: ['masteriyo', 'lms', 'certificate', label.toLowerCase()],
		tier,
		getInitialDimensions: () =>
			isImage ? { width: 100, height: 100 } : { width: 200, height: 30 },
		getInitialContent: () => `{ ${label} }`,
		getInitialProps: () => ({ field: fieldKey, content: `{ ${label} }` }),
	};
}

/**
 * Certificate fields that are a paid feature.
 *
 * They stay in the palette in both products: the editor renders a `pro`-tier
 * element locked and routes a click to the upsell, and unlocks it when its
 * `isPremium` config is true. Tier is a static property of the field; the
 * licence check lives at the `isPremium` call site in EditCertificatePDFDraft.
 *
 * Grade and Co-Instructors are deliberately absent — they are gated on their
 * own addons in MASTERIYO_FIELD_GROUP above, and an addon check is the better
 * gate for them because it also hides a field a licensed site cannot fill.
 */
const MASTERIYO_PRO_FIELD_KEYS = new Set([
	'masteriyo:verification_code',
	'masteriyo:qr_code',
]);

export const MASTERIYO_CUSTOM_ELEMENTS: ElementType[] =
	MASTERIYO_FIELD_GROUP.fields.map((f) =>
		makeMasteriyoElement(
			f.key,
			f.label,
			f.outputType,
			MASTERIYO_PRO_FIELD_KEYS.has(f.key) ? 'pro' : 'free',
		),
	);

function resolveWpDataFieldForHTML(fieldKey: string, fallback: string): string {
	const now = new Date();
	const dateStr = now.toLocaleDateString(undefined, {
		year: 'numeric',
		month: 'long',
		day: 'numeric',
	});
	const timeStr = now.toLocaleTimeString(undefined, {
		hour: '2-digit',
		minute: '2-digit',
	});
	const preview = (window as any)._MASTERIYO_CERTIFICATE_PREVIEW_ ?? {};
	const pdfdraft = (window as any)._MASTERIYO_PDFDRAFT_ ?? {};
	const currentUser = (window as any)._MASTERIYO_?.current_user ?? {};

	const resolved: Record<string, string> = {
		'site.name': preview.site_name || pdfdraft.siteName || '',
		'site.tagline': '',
		'site.url': window.location.origin,
		'site.home_url': window.location.origin,
		'site.current_date': preview.current_date || dateStr,
		'site.current_time': preview.current_time || timeStr,
		'site.admin_email': '',
		'site.wp_version': '',
		'site.language': '',
		'user.display_name': currentUser.display_name || '',
		'user.first_name': currentUser.first_name || '',
		'user.last_name': currentUser.last_name || '',
		'user.email': currentUser.email || currentUser.user_email || '',
		'user.login': currentUser.user_login || '',
		'user.id': currentUser.id ? String(currentUser.id) : '',
		'user.url': currentUser.user_url || '',
		'user.description': '',
		'user.registered': '',
		'user.roles': '',
		'post.title': preview.course_title || '',
		'post.author_name': preview.instructor_name || '',
		'post.date': preview.current_date || dateStr,
		'post.url': '',
		'post.id': '',
	};

	return resolved[fieldKey] || fallback || `{{${fieldKey}}}`;
}

export function getMasteriyoMergeTagOptions() {
	// The merge-tag dropdown is the other door onto the same fields: the tag
	// resolves server-side in the shared CertificatePDF, so offering a paid
	// field here would hand it over regardless of the palette's tier lock.
	const isLicensed = isLicensePlanActive();

	const masGroup = MASTERIYO_FIELD_GROUP.fields
		.filter((f) => isLicensed || !MASTERIYO_PRO_FIELD_KEYS.has(f.key))
		.map((f) => ({
			tag: `{{${f.key}}}`,
			label: f.label,
			groupLabel: sprintf(
				// translators: %s: the product's name.
				__('%s LMS', 'learning-management-system'),
				getPluginName(),
			),
		}));

	const wpFields = [
		{ tag: '{{site.name}}', label: 'Site Name', groupLabel: 'Site Info' },
		{ tag: '{{site.tagline}}', label: 'Site Tagline', groupLabel: 'Site Info' },
		{ tag: '{{site.url}}', label: 'Site URL', groupLabel: 'Site Info' },
		{
			tag: '{{site.current_date}}',
			label: 'Current Date',
			groupLabel: 'Site Info',
		},
		{
			tag: '{{site.current_time}}',
			label: 'Current Time',
			groupLabel: 'Site Info',
		},
		{ tag: '{{user.display_name}}', label: 'Display Name', groupLabel: 'User' },
		{ tag: '{{user.first_name}}', label: 'First Name', groupLabel: 'User' },
		{ tag: '{{user.last_name}}', label: 'Last Name', groupLabel: 'User' },
		{ tag: '{{user.email}}', label: 'Email', groupLabel: 'User' },
	];

	return [...masGroup, ...wpFields];
}

export function getMasteriyoMergeTagLabel(tag: string): string | undefined {
	const all = getMasteriyoMergeTagOptions();
	return all.find((o) => o.tag === tag || o.tag === `{{${tag}}}`)?.label;
}

export const WP_DATA_FIELD_TOHTML_OVERRIDE: ElementType = {
	namespace: 'wp-data-field',
	label: 'WP Data Fields',
	category: 'core',
	icon: React.createElement(BookOpen, { size: 16 }),
	render: undefined as any,
	toolbar: undefined as any,
	toHTML: (data: any) => {
		const headingLevel = data?.props?.headingLevel as string | undefined;
		const [hSize, hWeight] = HEADING_STYLE[headingLevel ?? ''] ?? [];
		const styleObj = { ...(data?.style ?? {}) };
		delete (styleObj as any).backgroundColor;
		if (headingLevel && hSize && !(styleObj as any).fontSize)
			(styleObj as any).fontSize = hSize;
		if (headingLevel && hWeight && !(styleObj as any).fontWeight)
			(styleObj as any).fontWeight = hWeight;
		const style = generateStyleString(styleObj);
		const fallback: string = data?.props?.fallback ?? '';

		let fieldKey: string = data?.props?.field ?? '';
		if (!fieldKey && data?.props?.content) {
			const match = (data.props.content as string).match(/\{\{([^}]+)\}\}/);
			if (match) fieldKey = match[1];
		}

		if (!fieldKey)
			return fallback
				? `<div id="${data?.id}" style="${style}"><div style="line-height:1.4;letter-spacing:0px;padding:0;margin:0;"><div style="word-wrap:break-word;white-space:pre-wrap;position:relative;padding:0;margin:0;">${fallback}</div></div></div>`
				: '';
		const display = resolveWpDataFieldForHTML(fieldKey, fallback);
		const textAlign = (data?.style as any)?.textAlign ?? 'start';
		const escaped = display
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/\n/g, '<br>');
		const innerStyle =
			'overflow-wrap:break-word;word-break:normal;-webkit-font-variant-ligatures:none;font-variant-ligatures:none;position:relative;text-align:inherit;padding:0;margin:0';
		const inner = headingLevel
			? `<${headingLevel} style="margin:0;padding:0;font-size:inherit;font-weight:inherit;">${escaped}</${headingLevel}>`
			: escaped;
		return `<div id="${data?.id}" data-type="text" data-merge-tag="{{${fieldKey}}}" style="${style};"><div style="line-height:1.4;letter-spacing:0px;text-align:${textAlign};padding:0;margin:0;"><div style="${innerStyle}">${inner}</div></div></div>`;
	},
	keywords: ['wp', 'data', 'field', 'dynamic', 'merge'],
	tier: 'free',
	getInitialDimensions: () => ({ width: 200, height: 30 }),
	getInitialContent: () => '',
};
