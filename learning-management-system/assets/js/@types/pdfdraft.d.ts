/**
 * Type declarations for @pdfdraft/* packages.
 * Webpack aliases resolve these to prebuilt dist bundles at build time.
 * TypeScript uses these declarations so it doesn't attempt to walk the
 * designer source tree (which has its own tsconfig and ES2015+ requirements).
 */

declare module '@pdfdraft/designer' {
	import type React from 'react';

	// ── Field / group types ────────────────────────────────────────────────────

	export interface WpDataField {
		key: string;
		label: string;
		outputType: 'text' | 'image';
		hasDateFormat?: boolean;
	}

	export interface WpDataFieldGroup {
		source: string;
		label: string;
		fields: WpDataField[];
	}

	// ── Element types ──────────────────────────────────────────────────────────

	export interface ElementCategory {
		namespace: string;
		label: string;
		order?: number;
	}

	export interface ElementType {
		namespace: string;
		label: string;
		category: string;
		icon?: React.ReactElement;
		render?: React.FC<any>;
		toolbar?: React.FC<any>;
		toHTML?: (data: any) => string;
		keywords?: string[];
		tier?: string;
		getInitialDimensions?: () => { width: number; height: number };
		getInitialContent?: () => string;
		getInitialProps?: () => Record<string, unknown>;
	}

	// ── Editor state / config ──────────────────────────────────────────────────

	export interface EditorState {
		pages: unknown;
		settings: unknown;
		fonts?: unknown;
		status?: string;
		[key: string]: unknown;
	}

	export interface UIOptions {
		topBar?: boolean;
		header?: boolean;
		rightPanel?: boolean;
		scaleControls?: boolean;
		elementToolbar?: boolean;
		pageToolbar?: boolean;
		pixelGuide?: boolean;
		multiPage?: boolean;
		showSearch?: boolean;
		flattenElements?: boolean;
	}

	export interface EditorConfig {
		logo?: React.ReactElement;
		headerActions?: React.ReactNode;
		ui?: UIOptions;
		panels?: string[];
		wpDataFields?: {
			additionalGroups?: WpDataFieldGroup[];
		};
		elements?: {
			exclude?: string[];
			custom?: ElementType[];
			categories?: ElementCategory[];
		};
		api?: {
			uploadImage?: (args: {
				basename: string;
				content: string;
			}) => Promise<{ url: string }>;
		};
		backdrops?: Array<{
			id: string;
			src: string;
			label: string;
		}>;
		isPremium?: boolean;
		onProElementClick?: (namespace?: string) => void;
		onPreview?: (editorState: EditorState) => Promise<void> | void;
		onExportPDF?: (editorState: EditorState) => Promise<void> | void;
		onDelete?: () => void;
		backHandler?: () => void;
		exitHandler?: () => void;
	}

	export interface EditorProps {
		initialData?: unknown;
		onSave?: (data: EditorState) => Promise<void> | void;
		config?: EditorConfig;
		children?: React.ReactNode;
	}

	// ── Editor component ───────────────────────────────────────────────────────

	type EditorComponent = React.FC<EditorProps> & {
		Header: React.FC;
		LeftPanel: React.FC;
		RightPanel: React.FC;
	};

	export const Editor: EditorComponent;

	// ── PDFExporter ────────────────────────────────────────────────────────────

	export class PDFExporter {
		getPreviewUrl(args: {
			pages: unknown;
			settings: unknown;
			fonts?: unknown;
		}): Promise<string>;
	}

	// ── Utility exports ────────────────────────────────────────────────────────

	export function generateStyleString(style: unknown): string;
	export const WpDataFieldElement: React.FC<any>;
	export const WpDataFieldToolbarContent: React.FC<any>;
	export const ElementToolbar: React.FC<{ children?: React.ReactNode }>;
	export const ElementToolbarItems: React.FC;
	export function useEditorActions(keys: string[]): Record<string, any>;
	export const useEditorStore: {
		getState(): {
			pages: Record<string, any>;
			fonts: Map<string, any>;
			actions: {
				updateFonts(fonts: Record<string, any>): void;
				[key: string]: any;
			};
		};
		subscribe<T>(
			selector: (state: any) => T,
			listener: (value: T) => void,
		): () => void;
	};
	export const useElementsStore: {
		getState(): {
			actions: {
				get(namespace: string): (ElementType & { [key: string]: any }) | undefined;
				register(element: ElementType & { [key: string]: any }): void;
				deregister(namespace: string): void;
			};
		};
	};
	export const AdvancedSelectorRender: React.FC<any>;
	export const designerQueryClient: import('@tanstack/react-query').QueryClient;

	// ── Advanced selector (slash command for smart tags) ───────────────────────

	export interface MergeTagOption {
		tag: string;
		label: string;
		groupLabel: string;
	}

	export interface AdvancedSelectorConfig {
		getMergeTagOptions: () => MergeTagOption[];
		getMergeTagLabel?: (tag: string) => string | undefined;
		placeholder?: string;
		emptyLabel?: string;
		minWidthPx?: number;
		minHeightPx?: number;
	}

	export interface CreateAdvancedSelectorOptions {
		namespace: string;
		label: string;
		category: string;
		icon?: React.ReactNode;
		keywords?: string[];
		tier?: 'free' | 'pro';
		config: AdvancedSelectorConfig;
		getInitialDimensions?: () => { width: number; height: number };
		getInitialContent?: () => string;
	}

	export function createAdvancedSelectorElement(
		options: CreateAdvancedSelectorOptions,
	): import('@pdfdraft/designer').ElementType;

	export function registerMergeTagOptionsGetter(
		getter: () => MergeTagOption[],
	): void;
	export function unregisterMergeTagOptionsGetter(
		getter: () => MergeTagOption[],
	): void;
}

// Aliased to lucide-react by webpack (config.base.js).
// Declare the icons used by masteriyo-fields.ts directly (lucide-react not installed in free).
declare module '@pdfdraft/ui/icons' {
	import type React from 'react';
	type Icon = React.FC<
		React.SVGProps<SVGSVGElement> & { size?: number | string }
	>;
	export const BookOpen: Icon;
	export const Calendar: Icon;
	export const CalendarCheck: Icon;
	export const CalendarDays: Icon;
	export const Clock: Icon;
	export const Globe: Icon;
	export const GraduationCap: Icon;
	export const QrCode: Icon;
	export const Shield: Icon;
	export const Star: Icon;
	export const Timer: Icon;
	export const User: Icon;
	export const Users: Icon;
	// Allow any other icon to be imported without error.
	const _: Icon;
	export default _;
}

// Remaining @pdfdraft/* packages — only default exports used, no detailed types needed.
declare module '@pdfdraft/document-tools' {
	const jsPDF: any;
	export default jsPDF;
}

declare module '@pdfdraft/snapshot-kit' {
	const snapshot: (
		element: HTMLElement,
		options?: any,
	) => Promise<HTMLCanvasElement>;
	export default snapshot;
}
