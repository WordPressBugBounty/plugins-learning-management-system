import {
	AlertDialog,
	AlertDialogBody,
	AlertDialogContent,
	AlertDialogFooter,
	AlertDialogHeader,
	AlertDialogOverlay,
	Box,
	Button,
	Center,
	Flex,
	HStack,
	Heading,
	Icon,
	Modal,
	ModalCloseButton,
	ModalContent,
	ModalOverlay,
	Spinner,
	Stack,
	Text,
	useDisclosure,
	useToast,
} from '@chakra-ui/react';
import { BiLock } from 'react-icons/bi';
import { UpgradeToProBtn } from '../../../../../assets/js/back-end/components/common/pro/ProShowcaseComponent';
import {
	Editor,
	PDFExporter,
	useEditorStore,
	useElementsStore,
} from '@pdfdraft/designer';
import '@pdfdraft/designer/style.css';
import apiFetch from '@wordpress/api-fetch';
import { __, sprintf } from '@wordpress/i18n';
import React, { useEffect, useRef, useState } from 'react';
import MasteriyoLogo from '../../../../../assets/img/logo.png';
import localized from '../../../../../assets/js/back-end/utils/global';
import { certificateBackendRoutes } from '../utils/routes';
import { certificateAddonUrls } from '../utils/urls';
import {
	MASTERIYO_CUSTOM_ELEMENTS,
	MASTERIYO_ELEMENT_CATEGORIES,
	MASTERIYO_FIELD_GROUP,
	WP_DATA_FIELD_TOHTML_OVERRIDE,
} from './masteriyo-fields';
import { PX_PER_UNIT } from './pdfdraft-thumb-utils';

// Longest renderable side in px (~52in @96dpi). Blocks the 9600px (100in) canvas
// that freezes the browser during preview/export while allowing A0 (~4493px).
const MAX_RENDER_PX = 5000;

function getRenderPx(settings: any): { w: number; h: number } {
	const layout = settings?.layout ?? {};
	const mult = PX_PER_UNIT[layout.unit ?? 'in'] ?? 96;
	return {
		w: Math.round((layout.width ?? 0) * mult),
		h: Math.round((layout.height ?? 0) * mult),
	};
}

interface Props {
	certificate: {
		id: number;
		name: string;
		html_content: string;
		status: string;
		preview_link?: string;
	};
	onSave: (
		json: string,
		renderedHtml: string,
		status: 'draft' | 'publish',
	) => Promise<void>;
	isSaving: boolean;
	onBack: () => void;
}

function captureCanvasHtml(): string {
	const canvas = document.querySelector(
		'[data-pdfdraft-canvas], .PDFDraft-Container',
	);
	if (!canvas) return '';
	const clone = canvas.cloneNode(true) as Element;
	clone
		.querySelectorAll('[style*="z-index: 9999"]')
		.forEach((el) => el.remove());
	return clone.outerHTML;
}

/**
 * Ask the server to inline remote (CDN) image URLs in the design as base64 data
 * URIs before the client-side PDFExporter runs, so cross-origin images don't
 * taint the export canvas and silently drop out of Preview/PDF. Falls back to
 * the raw design on any failure.
 */
async function resolveInlinedDesign(
	editorState: any,
): Promise<{ pages: any; settings: any }> {
	try {
		const res: any = await apiFetch({
			path: certificateAddonUrls.pdfdraftInlineImages,
			method: 'POST',
			data: { pages: editorState.pages, settings: editorState.settings },
		});
		return {
			pages: res?.pages ?? editorState.pages,
			settings: res?.settings ?? editorState.settings,
		};
	} catch {
		return { pages: editorState.pages, settings: editorState.settings };
	}
}

/**
 * Live save-status badge shown in the header (next to the logo). Replaces the
 * intrusive auto-save banner with a quiet, always-visible indicator:
 * "Saving…" while a save is in flight, "Unsaved changes" once the canvas is
 * edited, and "Saved" when persisted. State is read from the designer's editor
 * store (`isDirty`) plus a `__mtoSaving` flag we set around `handleSave`.
 */
const SaveStatusBadge: React.FC = () => {
	const read = () => {
		const s = useEditorStore.getState() as any;
		return { isDirty: !!s.isDirty, isSaving: !!s.__mtoSaving };
	};
	const [snap, setSnap] = useState(read);

	useEffect(() => {
		setSnap(read());
		const unsub = useEditorStore.subscribe(
			(s: any) => `${!!s.isDirty}|${!!s.__mtoSaving}`,
			(v: string) => {
				const [dirty, saving] = v.split('|');
				setSnap({ isDirty: dirty === 'true', isSaving: saving === 'true' });
			},
		);
		return () => unsub();
	}, []);

	let label = __('Saved', 'learning-management-system');
	let color = 'gray.500';
	let indicator: React.ReactNode = (
		<Box as="span" w="6px" h="6px" borderRadius="full" bg="green.400" />
	);

	if (snap.isSaving) {
		label = __('Saving…', 'learning-management-system');
		color = 'gray.500';
		indicator = <Spinner size="xs" speed="0.7s" color="gray.400" />;
	} else if (snap.isDirty) {
		label = __('Unsaved changes', 'learning-management-system');
		color = 'orange.500';
		indicator = (
			<Box as="span" w="6px" h="6px" borderRadius="full" bg="orange.400" />
		);
	}

	return (
		<Flex align="center" gap={1.5} aria-live="polite" userSelect="none">
			{indicator}
			<Text fontSize="xs" fontWeight="500" color={color} whiteSpace="nowrap">
				{label}
			</Text>
		</Flex>
	);
};

function base64ToBlob(base64: string, mime = 'image/png'): Blob {
	const byteString = atob(base64);
	const ab = new ArrayBuffer(byteString.length);
	const ia = new Uint8Array(ab);
	for (let i = 0; i < byteString.length; i++) {
		ia[i] = byteString.charCodeAt(i);
	}
	return new Blob([ab], { type: mime });
}

function getDefaultInitialData(certificate: Props['certificate']) {
	return {
		id: certificate.id,
		slug: '',
		pages: {
			'page-1': {
				name: '',
				children: [],
			},
		},
		pagesOrder: ['page-1'],
		settings: {
			name: certificate.name,
			layout: {
				height: 8.5,
				width: 11,
				unit: 'in',
				orientation: 'landscape',
			},
		},
		status: certificate.status === 'publish' ? 'publish' : 'draft',
		versionHistory: [],
	};
}

const MASTERIYO_BACKDROP_PATTERNS = [
	{
		id: 'pattern-1',
		src: 'https://img.masteriyo.com/w:auto/h:auto/q:auto/id:f8c17996ac796f9436e266d581a8c833/directUpload/certificate-background-pattern-1.png',
		label: 'Pattern 1',
	},
	{
		id: 'pattern-2',
		src: 'https://img.masteriyo.com/w:auto/h:auto/q:auto/id:0e892cf8bed164c90e43a74b2a1d37f4/directUpload/certificate-background-pattern-2.png',
		label: 'Pattern 2',
	},
	{
		id: 'pattern-3',
		src: 'https://img.masteriyo.com/w:auto/h:auto/q:auto/id:35719770de4f6dabd513f5d325ee98b8/directUpload/certificate-background-pattern-3.png',
		label: 'Pattern 3',
	},
	{
		id: 'pattern-4',
		src: 'https://img.masteriyo.com/w:auto/h:auto/q:auto/id:9e68ad421fc5fa34b4043807d6b6faff/directUpload/certificate-background-pattern-4.png',
		label: 'Pattern 4',
	},
	{
		id: 'pattern-5',
		src: 'https://img.masteriyo.com/w:auto/h:auto/q:auto/id:72da242f7a6d477b1af17d33a48534e3/directUpload/certificate-background-pattern-5.png',
		label: 'Pattern 5',
	},
	{
		id: 'pattern-6',
		src: 'https://img.masteriyo.com/w:auto/h:auto/q:auto/id:54e67bb4a3e60382e45344f0bd700227/directUpload/certificate-background-pattern-6.png',
		label: 'Pattern 6',
	},
];

const EditCertificatePDFDraft: React.FC<Props> = ({
	certificate,
	onSave,
	onBack,
}) => {
	const [isEditorLoading, setIsEditorLoading] = useState(true);
	useEffect(() => {
		if (process.env.NODE_ENV !== 'production') {
			return;
		}
		const buildUrl: string | undefined = (localized as any)?.buildUrl;
		if (!buildUrl) {
			return;
		}
		const STYLE_ID = 'masteriyo-pdfdraft-designer-style';
		if (!document.getElementById(STYLE_ID)) {
			const link = document.createElement('link');
			link.id = STYLE_ID;
			link.rel = 'stylesheet';
			link.href = `${buildUrl}masteriyo-backend.css`;
			document.head.appendChild(link);
		}
		return () => {
			document.getElementById(STYLE_ID)?.remove();
		};
	}, []);

	useEffect(() => {
		const observer = new MutationObserver(() => {
			if (
				document.querySelector('[data-pdfdraft-canvas], .PDFDraft-Container')
			) {
				setIsEditorLoading(false);
				observer.disconnect();
			}
		});
		observer.observe(document.body, { childList: true, subtree: true });
		return () => observer.disconnect();
	}, []);

	useEffect(() => {
		function injectGoogleFont(family: string) {
			const clean = (family || '').replace(/['"]/g, '').trim();
			const GENERIC = [
				'inter',
				'serif',
				'sans-serif',
				'monospace',
				'cursive',
				'fantasy',
				'system-ui',
				'ui-monospace',
				'inherit',
				'initial',
				'unset',
			];
			if (!clean || GENERIC.includes(clean.toLowerCase())) return;
			const encoded = encodeURIComponent(clean);
			if (
				!document.querySelector(
					`link[href*="fonts.googleapis.com"][href*="${encoded}"]`,
				)
			) {
				const link = document.createElement('link');
				link.rel = 'stylesheet';
				link.href = `https://fonts.googleapis.com/css2?family=${encoded}:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap`;
				document.head.appendChild(link);
			}
			useEditorStore.getState().actions.updateFonts({
				[clean]: {
					id: clean,
					family: clean,
					variants: [100, 200, 300, 400, 500, 600, 700, 800, 900],
					subsets: ['latin'],
					category: 'sans-serif',
					version: '',
					lastModified: '',
					popularity: 0,
					defSubset: 'latin',
					defVariant: '400',
					url: `https://fonts.googleapis.com/css2?family=${encoded}:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap`,
				},
			});
		}

		function loadFontsFromPages(pages: Record<string, any>) {
			for (const page of Object.values(pages)) {
				for (const el of (page?.children as any[]) || []) {
					const f1: string | undefined = el?.style?.fontFamily;
					const f2: string | undefined = el?.props?.globalStyle?.fontFamily;
					if (f1) injectGoogleFont(f1);
					if (f2) injectGoogleFont(f2);
					const content: string | undefined = el?.props?.content;
					if (
						content &&
						typeof content === 'string' &&
						content.includes('font-family')
					) {
						const re = /font-family:\s*['"]?([^;'"]+)/gi;
						let m: RegExpExecArray | null;
						// eslint-disable-next-line no-cond-assign
						while ((m = re.exec(content)) !== null) {
							injectGoogleFont(m[1].trim());
						}
					}
				}
			}
		}

		loadFontsFromPages(useEditorStore.getState().pages);

		return (useEditorStore as any).subscribe(
			(state: any) => state.pages,
			(pages: any) => loadFontsFromPages(pages),
		);
	}, []);

	useEffect(() => {
		apiFetch<Record<string, string>>({
			path: certificateAddonUrls.certificatePreviewData(certificate.id),
		})
			.then((data) => {
				(window as any)._MASTERIYO_CERTIFICATE_PREVIEW_ = data;
			})
			.catch(() => {});
	}, [certificate.id]);

	useEffect(() => {
		const timer = setTimeout(() => {
			const { actions } = useElementsStore.getState();
			const existing = actions.get('wp-data-field');
			if (!existing) return;
			actions.deregister('wp-data-field');
			actions.register({
				...existing,
				toHTML: WP_DATA_FIELD_TOHTML_OVERRIDE.toHTML,
			});
		}, 0);
		return () => clearTimeout(timer);
	}, []);

	const handleSave = async (data: any): Promise<void> => {
		const renderedHtml = captureCanvasHtml();
		(useEditorStore as any).setState({ __mtoSaving: true });
		try {
			await onSave(
				JSON.stringify(data),
				renderedHtml,
				data?.status === 'draft' ? 'draft' : 'publish',
			);
		} catch (err: any) {
			const isLimit =
				String(err?.code ?? '').endsWith('upgrade_required') ||
				/more than two published/i.test(err?.message ?? '');
			if (isLimit) {
				setProPopup({
					title: __('Publishing limit reached', 'learning-management-system'),
					message: __(
						'You can publish a maximum of 2 certificates in the free version. Upgrade to Pro to publish unlimited certificates.',
						'learning-management-system',
					),
				});
				onProOpen();
			}
		} finally {
			(useEditorStore as any).setState({ __mtoSaving: false });
		}
	};

	const {
		isOpen: isDeleteOpen,
		onOpen: onDeleteOpen,
		onClose: onDeleteClose,
	} = useDisclosure();
	const {
		isOpen: isProOpen,
		onOpen: onProOpen,
		onClose: onProClose,
	} = useDisclosure();
	const toast = useToast();

	// Reject oversized designs before the client-side rasterizer freezes the browser.
	const assertRenderable = (settings: any): boolean => {
		const { w, h } = getRenderPx(settings);
		if (w > MAX_RENDER_PX || h > MAX_RENDER_PX) {
			toast({
				title: __(
					'Certificate too large to render',
					'learning-management-system',
				),
				description: sprintf(
					/* translators: 1: width in pixels, 2: height in pixels, 3: maximum allowed pixels */
					__(
						'This certificate is %1$d×%2$d px, which exceeds the %3$d px render limit. Please reduce its width or height.',
						'learning-management-system',
					),
					w,
					h,
					MAX_RENDER_PX,
				),
				status: 'error',
				isClosable: true,
			});
			return false;
		}
		return true;
	};
	const cancelDeleteRef = useRef<HTMLButtonElement>(null);
	const [isDeleting, setIsDeleting] = useState(false);
	const [proPopup, setProPopup] = useState<{ title: string; message: string }>({
		title: __('Premium feature', 'learning-management-system'),
		message: __(
			'This certificate field is a Premium feature. Upgrade to Pro to unlock it and other advanced certificate fields.',
			'learning-management-system',
		),
	});

	const handleConfirmDelete = async () => {
		setIsDeleting(true);
		try {
			await apiFetch({
				path: `${certificateAddonUrls.certificate(certificate.id)}?force=true`,
				method: 'DELETE',
			});
			onDeleteClose();
			window.location.hash = `#${certificateBackendRoutes.certificate.certificatesV2}`;
		} finally {
			setIsDeleting(false);
		}
	};

	useEffect(() => {
		const prevScrollY = window.scrollY;
		window.scrollTo(0, 0);
		document.body.classList.add('pdfdraft-fullscreen-editor');
		return () => {
			document.body.classList.remove('pdfdraft-fullscreen-editor');
			window.scrollTo(0, prevScrollY);
		};
	}, []);

	let initialData: unknown;
	try {
		initialData = certificate.html_content
			? JSON.parse(certificate.html_content)
			: getDefaultInitialData(certificate);
	} catch {
		initialData = getDefaultInitialData(certificate);
	}

	return (
		<>
			<style>
				{`
					/* Full-screen mode: hide WP admin bar, admin menu, Masteriyo nav */
					body.pdfdraft-fullscreen-editor #wpadminbar,
					body.pdfdraft-fullscreen-editor #adminmenuwrap,
					body.pdfdraft-fullscreen-editor #adminmenuback,
					body.pdfdraft-fullscreen-editor .masteriyo-layout__sidebar,
					body.pdfdraft-fullscreen-editor .masteriyo-layout__header,
					body.pdfdraft-fullscreen-editor .masteriyo-header {
						display: none !important;
					}

					body.pdfdraft-fullscreen-editor {
						margin-top: 0 !important;
						overflow: hidden !important;
					}

					/* Shell takes full viewport */
					.masteriyo-pdfdraft-editor-shell {
						position: fixed;
						inset: 0;
						z-index: 99999;
						width: 100vw;
						height: 100vh;
						overflow: hidden;
						background: #f3f4f6;
					}

					.masteriyo-pdfdraft-editor-shell > div {
						height: 100%;
						min-height: 100%;
					}

					/* Radix UI / Floating UI portals render at document.body level.
					   Their default z-index is lower than the shell, so boost them above it
					   so dropdowns, tooltips and modals render correctly in full-screen mode. */
					body.pdfdraft-fullscreen-editor [data-radix-popper-content-wrapper],
					body.pdfdraft-fullscreen-editor [data-radix-portal],
					body.pdfdraft-fullscreen-editor [data-floating-ui-portal] {
						z-index: 100000 !important;
					}

					/* Chakra UI modal portal must appear above the full-screen shell (z-index 99999) */
					body.pdfdraft-fullscreen-editor .chakra-modal__overlay,
					body.pdfdraft-fullscreen-editor .chakra-modal__content-container,
					body.pdfdraft-fullscreen-editor [class*="chakra-modal"],
					body.pdfdraft-fullscreen-editor [data-chakra-portal] {
						z-index: 100001 !important;
					}

					/* Chakra UI toast portals (id: chakra-toast-manager-{position}) must
					   render above the full-screen shell so save/publish feedback is visible */
					body.pdfdraft-fullscreen-editor [id^="chakra-toast-manager"] {
						z-index: 100002 !important;
					}

					/* WP admin paragraph/list styles break text scrollHeight + page thumbnails */
					.masteriyo-pdfdraft-editor-shell .PDFDraft-Element[data-type="text"] .pdfdraft-text-content p,
					.masteriyo-pdfdraft-editor-shell .PDFDraft-Element[data-type="text"] .ProseMirror,
					.masteriyo-pdfdraft-editor-shell .PDFDraft-Element[data-type="text"] .ProseMirror p,
					.masteriyo-pdfdraft-editor-shell .pdfdraft-page-thumbnail-preview p {
						margin: 0 !important;
						padding: 0 !important;
						line-height: inherit;
					}

					/* Canvas border: PDFDraft-Canvas-Inner has the exact scaled dimensions,
					   so this outline always matches the white canvas without spilling into
					   the gray overflow area from the transform:scale droppable container. */
					.masteriyo-pdfdraft-editor-shell .PDFDraft-Canvas-Inner {
						outline: 1px solid #3a75fd;
					}

					/* Hide the container Moveable control box entirely.
					   The className prop is added to the moveable-control-box element itself,
					   so the selector must NOT use a descendant combinator. */
					.masteriyo-pdfdraft-editor-shell .PDFDraft-Moveable-For-Container.moveable-control-box {
						display: none !important;
					}

					/* Remove trash icon from "Delete design" dropdown item for visual consistency
					   with the other icon-less items (Save to Draft, Export as PDF). */
					.masteriyo-pdfdraft-editor-shell [role="menuitem"] svg,
					body.pdfdraft-fullscreen-editor [data-radix-dropdown-menu-item] svg,
					body.pdfdraft-fullscreen-editor [role="menuitem"] svg {
						display: none !important;
					}

					/* Reduce element panel label size */
					.masteriyo-pdfdraft-editor-shell [class*="cursor-grab"] span,
					.masteriyo-pdfdraft-editor-shell label[class*="uppercase"] {
						font-size: 12px !important;
					}

					/* Hide the redundant top-bar "Save" button. "Save to Draft" already
					   exists in the ⋮ menu and Publish/Update commits the design. Save is
					   the first direct-child BUTTON of the header actions group (headerActions
					   holds the status badge — a div, not a button — so it's unaffected). */
					.masteriyo-pdfdraft-editor-shell .flex.h-full.flex-nowrap.items-center.space-x-4 > button:first-of-type {
						display: none !important;
					}

					/* Hide the localStorage auto-save recovery banner ("Unsaved changes
					   detected — auto-saved …"). The header save-status badge communicates
					   state instead. */
					.masteriyo-pdfdraft-editor-shell .bg-amber-50.border-b.border-amber-200 {
						display: none !important;
					}
				`}
			</style>
			<div
				className="masteriyo-pdfdraft-editor-shell"
				data-masteriyo-certificate-format="pdfdraft"
			>
				<Editor
					initialData={initialData as any}
					onSave={(data: any) => handleSave(data)}
					config={{
						logo: React.createElement('img', {
							src: localized.logo || MasteriyoLogo,
							alt: 'Masteriyo LMS',
							style: { width: 36, height: 36, objectFit: 'contain' },
						}),
						headerActions: React.createElement(SaveStatusBadge),
						ui: {
							topBar: true,
							showSearch: false,
							flattenElements: true,
							multiPage: false,
						} as any,
						panels: ['elements', 'library', 'backdrops', 'settings'],
						isPremium: false,
						onProElementClick: () => {
							setProPopup({
								title: __('Premium feature', 'learning-management-system'),
								message: __(
									'This certificate field is a Premium feature. Upgrade to Pro to unlock it and other advanced certificate fields.',
									'learning-management-system',
								),
							});
							onProOpen();
						},
						backdrops: MASTERIYO_BACKDROP_PATTERNS,
						wpDataFields: {
							additionalGroups: [MASTERIYO_FIELD_GROUP],
						},
						elements: {
							exclude: ['chart', 'table', 'date', 'wp-data-field'],
							custom: [...MASTERIYO_CUSTOM_ELEMENTS],
							categories: MASTERIYO_ELEMENT_CATEGORIES,
						},
						api: {
							uploadImage: async ({
								basename,
								content,
							}: {
								basename: string;
								content: string;
							}) => {
								const blob = base64ToBlob(content);
								const formData = new FormData();
								formData.append('file', blob, `${basename}.png`);
								const media: any = await apiFetch({
									path: '/wp/v2/media',
									method: 'POST',
									body: formData,
								});
								return { url: media.source_url };
							},
						},
						onPreview: async (editorState: any) => {
							try {
								const { pages, settings } =
									await resolveInlinedDesign(editorState);
								if (!assertRenderable(settings)) {
									return;
								}
								const exporter = new PDFExporter();
								const url = await exporter.getPreviewUrl({
									pages,
									settings,
									fonts: editorState.fonts,
								});
								window.open(url, '_blank');
							} catch (err: any) {
								toast({
									title: __('Preview failed', 'learning-management-system'),
									description:
										err?.message ??
										__('Please try again.', 'learning-management-system'),
									status: 'error',
									isClosable: true,
								});
							}
						},
						onExportPDF: async (editorState: any) => {
							try {
								const { pages, settings } =
									await resolveInlinedDesign(editorState);
								if (!assertRenderable(settings)) {
									return;
								}
								const exporter = new PDFExporter();
								const url = await exporter.getPreviewUrl({
									pages,
									settings,
									fonts: editorState.fonts,
								});
								window.open(url, '_blank');
							} catch (err: any) {
								toast({
									title: __('Export failed', 'learning-management-system'),
									description:
										err?.message ??
										__('Please try again.', 'learning-management-system'),
									status: 'error',
									isClosable: true,
								});
							}
						},
						onDelete: () => {
							onDeleteOpen();
						},
						exitHandler: () => {
							window.location.hash = `#${certificateBackendRoutes.certificate.certificatesV2}`;
						},
					}}
				>
					<Editor.Header />
					<Editor.LeftPanel />
					<Editor.RightPanel />
				</Editor>
			</div>

			{/* Loading overlay — covers the PDFDraft skeleton until the canvas is ready */}
			{isEditorLoading && (
				<Center
					position="fixed"
					inset={0}
					zIndex={999999}
					bg="white"
					flexDirection="column"
					gap={4}
				>
					<img
						src={localized.logo || MasteriyoLogo}
						alt="Masteriyo"
						style={{ width: 56, height: 56, objectFit: 'contain' }}
					/>
					<Spinner size="lg" color="blue.500" thickness="3px" />
				</Center>
			)}

			{/* Delete confirmation — rendered above the full-screen shell via CSS z-index override */}
			<AlertDialog
				isOpen={isDeleteOpen}
				leastDestructiveRef={cancelDeleteRef}
				onClose={onDeleteClose}
				isCentered
			>
				<AlertDialogOverlay>
					<AlertDialogContent>
						<AlertDialogHeader fontSize="lg" fontWeight="bold">
							{__('Delete Certificate', 'learning-management-system')}
						</AlertDialogHeader>
						<AlertDialogBody>
							{__(
								'Are you sure? This certificate will be permanently deleted and cannot be recovered.',
								'learning-management-system',
							)}
						</AlertDialogBody>
						<AlertDialogFooter>
							<Button ref={cancelDeleteRef} onClick={onDeleteClose}>
								{__('Cancel', 'learning-management-system')}
							</Button>
							<Button
								colorScheme="red"
								ml={3}
								onClick={handleConfirmDelete}
								isLoading={isDeleting}
							>
								{__('Delete', 'learning-management-system')}
							</Button>
						</AlertDialogFooter>
					</AlertDialogContent>
				</AlertDialogOverlay>
			</AlertDialog>

			<Modal isOpen={isProOpen} onClose={onProClose} isCentered>
				<ModalOverlay bg="blackAlpha.600" />
				<ModalContent
					w={{ base: '90%', md: '500px' }}
					p={6}
					bg="white"
					mx="auto"
					shadow="2xl"
					borderRadius="lg"
				>
					<ModalCloseButton />
					<Stack spacing={5}>
						<HStack spacing={3} align="center">
							<Flex
								bg="primary.200"
								color="primary.500"
								p={2.5}
								borderRadius="lg"
								align="center"
								justify="center"
							>
								<Icon as={BiLock} boxSize={5} />
							</Flex>
							<Heading size="md" fontWeight="semibold" m={0}>
								{proPopup.title}
							</Heading>
						</HStack>
						<Text color="gray.600" fontSize="md">
							{proPopup.message}
						</Text>
						<UpgradeToProBtn url="https://masteriyo.com/upgrade/?utm_source=wp-admin&utm_medium=certificateeditor&utm_campaign=proupsell&utm_content=fields" />
					</Stack>
				</ModalContent>
			</Modal>
		</>
	);
};

export default EditCertificatePDFDraft;
