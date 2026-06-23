import {
	Box,
	Button,
	Center,
	Container,
	Flex,
	Heading,
	SimpleGrid,
	Spinner,
	Stack,
	Text,
} from '@chakra-ui/react';
import { useMutation } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import { Add } from 'iconsax-react';
import React, { useEffect, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import {
	Header,
	HeaderLeftSection,
	HeaderLogo,
	HeaderRightSection,
	HeaderTop,
} from '../../../../../assets/js/back-end/components/common/Header';
import {
	NavMenu,
	NavMenuItem,
	NavMenuLink,
} from '../../../../../assets/js/back-end/components/common/Nav';
import {
	navActiveStyles,
	navLinkStyles,
} from '../../../../../assets/js/back-end/config/styles';
import { ProCrownFilledIcon } from '../../../../../assets/js/back-end/constants/images';
import API from '../../../../../assets/js/back-end/utils/api';
import templates from '../../templates.json';
import { certificateBackendRoutes } from '../utils/routes';
import { certificateAddonUrls } from '../utils/urls';
import { PDFDRAFT_CSS, PX_PER_UNIT } from './pdfdraft-thumb-utils';

interface PDFDraftTemplate {
	id: string;
	name: string;
	orientation: 'landscape' | 'portrait';
	json: Record<string, unknown>;
	locked?: boolean;
}

export interface CertificateTemplatePickerProps {
	onClose: () => void;
	onOpenBlank: () => void;
}

function TemplateThumb({ tpl }: { tpl: PDFDraftTemplate }) {
	const thumbRef = useRef<HTMLDivElement>(null);
	const [containerWidth, setContainerWidth] = useState(320);

	useEffect(() => {
		if (thumbRef.current) {
			setContainerWidth(thumbRef.current.offsetWidth);
		}
	}, []);

	const layout = (tpl.json as any)?.settings?.layout ?? {};
	const mult = PX_PER_UNIT[layout.unit ?? 'in'] ?? 96;
	const certW = Math.round((layout.width ?? 11) * mult);
	const certH = Math.round((layout.height ?? 8.5) * mult);
	const cardH = Math.round(containerWidth * 0.72);
	const scale = Math.min(containerWidth / certW, cardH / certH);
	const scaledW = certW * scale;
	const scaledH = certH * scale;
	const offsetX = Math.round((containerWidth - scaledW) / 2);
	const offsetY = Math.round((cardH - scaledH) / 2);
	const bg = (tpl.json as any)?.settings?.background ?? {};
	const bgColor = bg.color ?? '#ffffff';
	const bgImage = bg.image ? resolveAssetUrl(bg.image) : '';
	const bgStyle = bgImage
		? `background-color:${bgColor};background-image:url('${bgImage}');background-size:100% 100%;background-repeat:no-repeat`
		: `background:${bgColor}`;

	const page = Object.values((tpl.json as any)?.pages ?? {})[0] as any;
	const childHtml = buildChildrenHtml(page?.children ?? []);
	const fontLinks = buildFontLinks(collectFontFamilies(page?.children ?? []));
	const srcdoc = `<!DOCTYPE html><html><head><meta charset="utf-8">${fontLinks}<style>html,body{width:${certW}px;height:${certH}px;${bgStyle}}${PDFDRAFT_CSS}</style></head><body>${childHtml}</body></html>`;

	return (
		<Box
			ref={thumbRef}
			as="figure"
			m={0}
			position="relative"
			overflow="hidden"
			height={`${cardH}px`}
			bg="gray.50"
		>
			<iframe
				srcDoc={srcdoc}
				style={{
					position: 'absolute',
					top: 0,
					left: 0,
					width: `${certW}px`,
					height: `${certH}px`,
					transform: `translate(${offsetX}px, ${offsetY}px) scale(${scale})`,
					transformOrigin: '0 0',
					border: 'none',
					pointerEvents: 'none',
					display: 'block',
				}}
				title={tpl.name}
				scrolling="no"
			/>
		</Box>
	);
}

function buildChildrenHtml(children: any[]): string {
	return children.map(buildElementHtml).join('');
}

function styleToString(style: Record<string, unknown>): string {
	return Object.entries(style)
		.map(
			([k, v]) => `${k.replace(/([A-Z])/g, (m) => `-${m.toLowerCase()}`)}:${v}`,
		)
		.join(';');
}


function resolveAssetUrl(src: string): string {
	if (!src) return src;
	const assetsBase: string =
		(window as any)._MASTERIYO_?.pdfdraft_assets_base_url ?? '';
	return src
		.replace(/__MASTERIYO_TPL_ASSETS_BASE__/g, `${assetsBase}templates`)
		.replace(/__MASTERIYO_SEAL__/g, `${assetsBase}teal-medallion-seal.webp`);
}


function globalFontStyle(gs: Record<string, any> = {}): string {
	const parts: string[] = ['line-height:1.4', 'letter-spacing:0px'];
	if (gs.fontFamily)
		parts.push(`font-family:'${gs.fontFamily}',Inter,sans-serif`);
	if (gs.fontSize != null)
		parts.push(
			`font-size:${typeof gs.fontSize === 'number' ? `${gs.fontSize}px` : gs.fontSize}`,
		);
	if (gs.fontWeight != null) parts.push(`font-weight:${gs.fontWeight}`);
	if (gs.color) parts.push(`color:${gs.color}`);
	return parts.join(';');
}


const GENERIC_FONT_KEYWORDS = new Set([
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
]);

function collectFontFamilies(children: any[], acc: Set<string> = new Set()): Set<string> {
	for (const el of children ?? []) {
		const fams = [el?.style?.fontFamily, el?.props?.globalStyle?.fontFamily];
		for (const f of fams) {
			if (f && typeof f === 'string') {
				const clean = f.replace(/['"]/g, '').trim();
				// Skip only exact CSS generics — keep real families like "DM Sans".
				if (clean && !GENERIC_FONT_KEYWORDS.has(clean.toLowerCase())) {
					acc.add(clean);
				}
			}
		}
		if (el?.children) collectFontFamilies(el.children, acc);
	}
	return acc;
}

function buildFontLinks(families: Set<string>): string {
	return Array.from(families)
		.filter(Boolean)
		.map(
			(fam) =>
				`<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=${encodeURIComponent(
					fam,
				)}:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap">`,
		)
		.join('');
}

function buildElementHtml(el: any): string {
	if (!el) return '';
	const baseStyle = `position:absolute;box-sizing:border-box;${styleToString(el.style ?? {})}`;

	switch (el.type) {
		case 'text':
			return `<div style="${baseStyle};overflow:hidden;"><div style="width:100%;height:100%;${globalFontStyle(el.props?.globalStyle)}">${el.content ?? ''}</div></div>`;
		case 'shape': {
			const bg = el.props?.background?.color ?? 'transparent';
			const br = el.props?.borderRadius
				? `border-radius:${el.props.borderRadius}px;`
				: '';
			return `<div style="${baseStyle};background:${bg};${br}"></div>`;
		}
		case 'divider': {
			const color = el.props?.style?.color ?? '#000';
			return `<div style="${baseStyle};border-bottom:${el.props?.lineWeight ?? 1}px solid ${color};"></div>`;
		}
		case 'image': {
			const src = resolveAssetUrl(el.props?.src ?? '');
			if (!src) return '';
			return `<div style="${baseStyle};overflow:hidden;"><img src="${src}" style="width:100%;height:100%;object-fit:cover;" /></div>`;
		}
		case 'group':
			return `<div style="${baseStyle}">${buildChildrenHtml(el.children ?? [])}</div>`;
		default:
			if (el.type?.startsWith('masteriyo__')) {
				// Render the field's placeholder text styled with the element's own
				// font (baseStyle already includes el.style font props), matching the
				// canvas. If content is a raw {{tag}}, show a humanized label instead.
				const raw = (el.content ?? '').toString();
				const display =
					raw && !raw.includes('{{')
						? raw
						: (el.props?.field ?? el.type)
								.replace('masteriyo:', '')
								.replace(/_/g, ' ');
				return `<div style="${baseStyle};line-height:1.4;overflow:hidden;">${display}</div>`;
			}
			return '';
	}
}

const CertificateTemplatePicker: React.FC<CertificateTemplatePickerProps> = ({
	onClose,
	onOpenBlank,
}) => {
	const navigate = useNavigate();
	const [creatingId, setCreatingId] = useState<string | null>(null);
	const certificateAPI = new API(certificateAddonUrls.certificates);

	const createMutation = useMutation({
		mutationFn: (tpl: PDFDraftTemplate) =>
			certificateAPI.store({
				name: tpl.name,
				status: 'draft',
				content_format: 'pdfdraft',
				html_content: JSON.stringify(tpl.json),
			}),
		onSuccess: (newCert: any, tplArg: PDFDraftTemplate) => {
			const assetsBase: string =
				(window as any)._MASTERIYO_?.pdfdraft_assets_base_url ?? '';
			const finalJson = JSON.stringify({
				...tplArg.json,
				id: newCert.id,
				slug: newCert.slug ?? '',
				status: newCert.status ?? 'draft',
				versionHistory: [],
			})
				.replace(/__MASTERIYO_TPL_ASSETS_BASE__/g, `${assetsBase}templates`)
				.replace(/__MASTERIYO_SEAL__/g, `${assetsBase}teal-medallion-seal.webp`);
			navigate(
				certificateBackendRoutes.certificate.edit.replace(
					':certificateId',
					String(newCert.id),
				),
				{ state: { certificate: { ...newCert, html_content: finalJson } } },
			);
		},
		onSettled: () => setCreatingId(null),
	});

	return (
		<Stack direction="column" spacing="8" alignItems="center">
			<Header>
				<HeaderTop>
					<HeaderLeftSection>
						<HeaderLogo />
						<NavMenu>
							<NavMenuItem>
								<NavMenuLink
									sx={navLinkStyles}
									_activeLink={navActiveStyles}
									as="span"
									cursor="default"
								>
									<Text>
										{__('Add New Certificate', 'learning-management-system')}
									</Text>
								</NavMenuLink>
							</NavMenuItem>
						</NavMenu>
					</HeaderLeftSection>
					<HeaderRightSection>
						<Button
							ml="auto"
							colorScheme="primary"
							variant="outline"
							onClick={onClose}
						>
							{__('Cancel', 'learning-management-system')}
						</Button>
					</HeaderRightSection>
				</HeaderTop>
			</Header>

			<Container maxW="container.xl">
				<Stack direction="column" spacing="10">
					<Stack direction="column" spacing="4" textAlign="center">
						<Heading fontSize="2xl">
							{__('Choose a Template', 'learning-management-system')}
						</Heading>
						<Text color="gray.500">
							{__(
								'Start with a blank template or one of the starter templates.',
								'learning-management-system',
							)}
						</Text>
					</Stack>

					<SimpleGrid columns={[2, 3, 4]} spacing="4">
						{/* Create from blank */}
						<Flex
							role="group"
							direction="column"
							align="center"
							justify="center"
							cursor="pointer"
							tabIndex={0}
							gap={4}
							border="1.5px dashed"
							borderColor="gray.200"
							minH="272px"
							bgGradient="linear(135deg, #f8f9ff 0%, #eef1ff 100%)"
							transition="all 0.2s ease"
							_hover={{
								borderColor: 'primary.400',
								bgGradient: 'linear(135deg, #eef1ff 0%, #e0e6ff 100%)',
								transform: 'translateY(-3px)',
								boxShadow: '0 12px 32px rgba(99,102,241,0.12)',
							}}
							onClick={() => {
								onClose();
								onOpenBlank();
							}}
						>
							<Flex
								align="center"
								justify="center"
								w="56px"
								h="56px"
								borderRadius="full"
								bg="white"
								boxShadow="0 2px 8px rgba(99,102,241,0.15)"
								transition="all 0.2s"
								_groupHover={{
									boxShadow: '0 4px 16px rgba(99,102,241,0.25)',
									transform: 'scale(1.08)',
								}}
							>
								<Add size={24} color="#6366f1" />
							</Flex>
							<Box textAlign="center" px={4}>
								<Text
									fontWeight="700"
									color="gray.700"
									fontSize="sm"
									letterSpacing="-0.01em"
								>
									{__('Create from blank', 'learning-management-system')}
								</Text>
								<Text fontSize="xs" color="gray.400" mt={1} lineHeight="1.4">
									{__(
										'Start with an empty canvas',
										'learning-management-system',
									)}
								</Text>
							</Box>
						</Flex>

						{/* Template cards */}
						{(templates as PDFDraftTemplate[]).map((tpl) => (
							<Box
								key={tpl.id}
								position="relative"
								shadow="box"
								bg="white"
								cursor={tpl.locked ? 'not-allowed' : 'pointer'}
								tabIndex={0}
								opacity={creatingId && creatingId !== tpl.id ? 0.5 : 1}
								onClick={() => {
									if (tpl.locked || creatingId) return;
									setCreatingId(tpl.id);
									createMutation.mutate(tpl);
								}}
							>
								<TemplateThumb tpl={tpl} />
								<Heading fontSize="md" p={2} textAlign="center">
									{tpl.name}
								</Heading>
								{tpl.locked && (
									<Button
										position="absolute"
										top={2}
										right={2}
										zIndex={1}
										size="sm"
										minW="fit-content"
										borderRadius="5px"
										colorScheme="green"
										leftIcon={<ProCrownFilledIcon />}
										boxShadow="sm"
										onClick={(e) => {
											e.stopPropagation();
											window.open(
												'https://masteriyo.com/upgrade/',
												'_blank',
											);
										}}
									>
										{__('Pro', 'learning-management-system')}
									</Button>
								)}
							</Box>
						))}
					</SimpleGrid>
				</Stack>
			</Container>

			{creatingId && (
				<Center
					position="fixed"
					top={0}
					bottom={0}
					left={0}
					right={0}
					bg="whiteAlpha.700"
					zIndex={9999}
				>
					<Spinner size="xl" color="blue.500" />
				</Center>
			)}
		</Stack>
	);
};

export default CertificateTemplatePicker;
