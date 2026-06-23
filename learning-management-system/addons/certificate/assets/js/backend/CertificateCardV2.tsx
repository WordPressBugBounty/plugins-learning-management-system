import {
	AlertDialog,
	AlertDialogBody,
	AlertDialogContent,
	AlertDialogFooter,
	AlertDialogHeader,
	AlertDialogOverlay,
	Box,
	Button,
	Flex,
	IconButton,
	Menu,
	MenuButton,
	MenuItem,
	MenuList,
	Text,
	useDisclosure,
	useToast,
} from '@chakra-ui/react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React, { useEffect, useRef, useState } from 'react';
import { BiDotsHorizontalRounded, BiEdit, BiReset, BiTrash } from 'react-icons/bi';
import { HiDocumentDuplicate } from 'react-icons/hi';
import { LuCheck, LuPencil } from 'react-icons/lu';
import { useNavigate } from 'react-router-dom';
import API from '../../../../../assets/js/back-end/utils/api';
import { certificateBackendRoutes } from '../utils/routes';
import { certificateAddonUrls } from '../utils/urls';
import { PDFDRAFT_CSS, PX_PER_UNIT } from './pdfdraft-thumb-utils';

interface Certificate {
	id: number;
	name: string;
	status: string;
	html_content?: string;
	rendered_html?: string;
	content_format?: string;
	date_modified?: string;
	date_created?: string;
}

interface Props {
	certificate: Certificate;
	bulkSelectMode?: boolean;
	selected?: boolean;
	onToggleSelect?: (id: string) => void;
}

function getLayoutFromCert(certificate: Certificate) {
	try {
		const json = JSON.parse(certificate.html_content ?? '{}');
		return json?.settings?.layout ?? null;
	} catch {
		return null;
	}
}

function timeAgo(dateStr?: string): string {
	if (!dateStr) return '';
	const diff = Date.now() - new Date(dateStr).getTime();
	const mins = Math.floor(diff / 60000);
	const hours = Math.floor(mins / 60);
	const days = Math.floor(hours / 24);
	const weeks = Math.floor(days / 7);
	const months = Math.floor(days / 30);
	if (months > 0) return `${months}mo ago`;
	if (weeks > 0) return `${weeks}w ago`;
	if (days > 0) return `${days}d ago`;
	if (hours > 0) return `${hours}h ago`;
	if (mins > 0) return `${mins}m ago`;
	return 'just now';
}

const CertificateCardV2: React.FC<Props> = ({
	certificate,
	bulkSelectMode = false,
	selected = false,
	onToggleSelect,
}) => {
	const navigate = useNavigate();
	const toast = useToast();
	const queryClient = useQueryClient();
	const {
		isOpen: isDeleteOpen,
		onOpen: onDeleteOpen,
		onClose: onDeleteClose,
	} = useDisclosure();
	const cancelRef = useRef<HTMLButtonElement>(null);
	const certificateAPI = new API(certificateAddonUrls.certificates);

	const thumbRef = useRef<HTMLDivElement>(null);
	const [containerWidth, setContainerWidth] = useState(320);

	useEffect(() => {
		if (thumbRef.current) {
			setContainerWidth(thumbRef.current.offsetWidth);
		}
	}, []);

	const layout = getLayoutFromCert(certificate);
	const orientation = layout?.orientation ?? 'landscape';
	const isPublished = certificate.status === 'publish';
	const isTrashed = certificate.status === 'trash';

	const mult = PX_PER_UNIT[layout?.unit ?? 'in'] ?? 96;
	const certW = Math.round((layout?.width ?? 11) * mult);
	const certH = Math.round((layout?.height ?? 8.5) * mult);
	const innerW = containerWidth - 16;
	const scale = innerW / certW;
	const THUMB_HEIGHT = Math.round(containerWidth * (210 / 297));

	const thumbSrcdoc = certificate.rendered_html
		? `<!DOCTYPE html><html><head><meta charset="utf-8"><style>html,body{width:${certW}px;height:${certH}px}${PDFDRAFT_CSS}</style></head><body>${certificate.rendered_html}</body></html>`
		: '';
	const ago = timeAgo(certificate.date_modified ?? certificate.date_created);
	const onActionError = (err: any) => {
		toast({
			title:
				err?.message ||
				__('Something went wrong', 'learning-management-system'),
			status: 'error',
			isClosable: true,
		});
	};

	const deleteCertificate = useMutation({
		mutationFn: () => certificateAPI.delete(certificate.id, { force: true }),
		onSuccess: () => {
			queryClient.invalidateQueries({ queryKey: ['certificatesV2List'] });
			onDeleteClose();
			toast({
				title: __('Certificate deleted', 'learning-management-system'),
				status: 'success',
				isClosable: true,
			});
		},
		onError: onActionError,
	});

	const trashCertificate = useMutation({
		mutationFn: () => certificateAPI.delete(certificate.id),
		onSuccess: () => {
			queryClient.invalidateQueries({ queryKey: ['certificatesV2List'] });
			toast({
				title: __('Certificate moved to trash', 'learning-management-system'),
				status: 'success',
				isClosable: true,
			});
		},
		onError: onActionError,
	});

	const restoreCertificate = useMutation({
		mutationFn: () => certificateAPI.restore(certificate.id),
		onSuccess: () => {
			queryClient.invalidateQueries({ queryKey: ['certificatesV2List'] });
			toast({
				title: __('Certificate restored', 'learning-management-system'),
				status: 'success',
				isClosable: true,
			});
		},
		onError: onActionError,
	});

	const duplicateCertificate = useMutation({
		// Use the server-side clone endpoint, which copies post_content via a
		// direct $wpdb write (bypassing wp_kses_post). Re-POSTing html_content
		// through store() runs it through kses and corrupts the PDFDraft JSON,
		// producing a blank duplicate (MAS-3657).
		mutationFn: () => certificateAPI.cloneData(certificate.id),
		onSuccess: () => {
			queryClient.invalidateQueries({ queryKey: ['certificatesV2List'] });
			toast({
				title: __('Certificate duplicated', 'learning-management-system'),
				status: 'success',
				isClosable: true,
			});
		},
		onError: (err: any) => {
			toast({
				title:
					err?.message ||
					__('Something went wrong', 'learning-management-system'),
				status: 'error',
				isClosable: true,
			});
		},
	});

	const handleEdit = () => {
		if (isTrashed) {
			return;
		}
		navigate(
			certificateBackendRoutes.certificate.edit.replace(
				':certificateId',
				String(certificate.id),
			),
			{ state: { certificate } },
		);
	};

	return (
		<Box
			role="group"
			borderRadius="xl"
			overflow="hidden"
			bg="white"
			boxShadow={
				selected && bulkSelectMode
					? '0 0 0 2px #3A75FD'
					: '0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.04)'
			}
			border="1px solid"
			borderColor={selected && bulkSelectMode ? '#3A75FD' : 'gray.100'}
			transition="all 0.2s ease"
			_hover={{
				boxShadow:
					selected && bulkSelectMode
						? '0 0 0 2px #3A75FD'
						: '0 8px 24px rgba(0,0,0,0.10)',
				borderColor: selected && bulkSelectMode ? '#3A75FD' : 'primary.200',
				transform: 'translateY(-2px)',
			}}
		>
			{/* Thumbnail with hover overlay */}
			<Box
				ref={thumbRef}
				position="relative"
				overflow="hidden"
				width="100%"
				height={`${THUMB_HEIGHT}px`}
				bg="white"
				p={2}
				cursor="pointer"
				onClick={
					bulkSelectMode
						? () => onToggleSelect?.(String(certificate.id))
						: handleEdit
				}
			>
				{thumbSrcdoc ? (
					<iframe
						srcDoc={thumbSrcdoc}
						style={{
							width: `${certW}px`,
							height: `${certH}px`,
							transform: `scale(${scale})`,
							transformOrigin: '0 0',
							border: 'none',
							borderRadius: '4px',
							pointerEvents: 'none',
							display: 'block',
							flexShrink: 0,
						}}
						title=""
						scrolling="no"
					/>
				) : (
					<Flex
						height="100%"
						align="center"
						justify="center"
						bg="gray.50"
						borderRadius="sm"
					>
						<Text fontSize="sm" color="gray.400">
							{__('No preview', 'learning-management-system')}
						</Text>
					</Flex>
				)}

				{/* Hover overlay with Edit CTA — hidden in bulk select mode */}
				<Flex
					position="absolute"
					inset={0}
					bg="blackAlpha.500"
					align="center"
					justify="center"
					opacity={0}
					transition="opacity 0.2s ease"
					_groupHover={{ opacity: bulkSelectMode ? 0 : 1 }}
				>
					<Button
						size="sm"
						colorScheme="whiteAlpha"
						bg="white"
						color="gray.700"
						leftIcon={<LuPencil />}
						borderRadius="full"
						fontWeight="semibold"
						_hover={{ bg: 'gray.100' }}
					>
						{__('Edit', 'learning-management-system')}
					</Button>
				</Flex>

				{/* Orientation pill — top left (hidden in bulk mode) */}
				{!bulkSelectMode && (
					<Box
						position="absolute"
						top={2.5}
						left={2.5}
						bg="blackAlpha.600"
						color="white"
						fontSize="9px"
						fontWeight="700"
						letterSpacing="0.08em"
						textTransform="uppercase"
						px={2}
						py={0.5}
						borderRadius="full"
					>
						{orientation}
					</Box>
				)}

				{/* Bulk select checkbox — top left */}
				{bulkSelectMode && (
					<Box
						position="absolute"
						top={2.5}
						left={2.5}
						zIndex={20}
						onClick={(e) => {
							e.stopPropagation();
							onToggleSelect?.(String(certificate.id));
						}}
					>
						<Box
							w="20px"
							h="20px"
							borderRadius="sm"
							border="2px solid"
							borderColor={selected ? '#3A75FD' : '#D0D0D0'}
							bg={selected ? '#3A75FD' : 'white'}
							boxShadow="sm"
							display="flex"
							alignItems="center"
							justifyContent="center"
						>
							{selected && <LuCheck size={11} color="white" strokeWidth={3} />}
						</Box>
					</Box>
				)}
			</Box>

			{/* Card footer */}
			<Box px={4} pt={3} pb={3}>
				{/* Name */}
				<Text
					fontWeight="600"
					fontSize="sm"
					color="gray.800"
					noOfLines={1}
					title={certificate.name}
					mb={2}
				>
					{certificate.name}
				</Text>

				{/* Status row */}
				<Flex align="center" justify="space-between">
					<Flex align="center" gap={2}>
						{/* draft badge (draft only) */}
						{!isPublished && (
							<Box
								display="inline-flex"
								alignItems="center"
								borderRadius="full"
								border="1px solid"
								borderColor="orange.200"
								bg="orange.50"
								px={1.5}
								fontSize="10px"
								fontWeight="500"
								color="orange.700"
								flexShrink={0}
							>
								{__('Draft', 'learning-management-system')}
							</Box>
						)}

						{/* Time */}
						{ago && (
							<Text fontSize="11px" color="gray.400">
								{ago}
							</Text>
						)}
					</Flex>

					{/* Menu */}
					<Menu>
						<MenuButton
							as={IconButton}
							icon={<BiDotsHorizontalRounded size={16} />}
							variant="ghost"
							size="xs"
							color="gray.400"
							borderRadius="full"
							aria-label="More actions"
							_hover={{ bg: 'gray.100', color: 'gray.600' }}
							onClick={(e) => e.stopPropagation()}
						/>
						<MenuList
							fontSize="sm"
							minW="160px"
							shadow="lg"
							borderColor="gray.100"
						>
							{isTrashed ? (
								<>
									<MenuItem
										icon={<BiReset />}
										onClick={() => restoreCertificate.mutate()}
									>
										{__('Restore', 'learning-management-system')}
									</MenuItem>
									<MenuItem
										icon={<BiTrash />}
										color="red.500"
										onClick={onDeleteOpen}
									>
										{__('Delete Permanently', 'learning-management-system')}
									</MenuItem>
								</>
							) : (
								<>
									<MenuItem icon={<BiEdit />} onClick={handleEdit}>
										{__('Edit', 'learning-management-system')}
									</MenuItem>
									<MenuItem
										icon={<HiDocumentDuplicate />}
										onClick={() => duplicateCertificate.mutate()}
									>
										{__('Duplicate', 'learning-management-system')}
									</MenuItem>
									<MenuItem
										icon={<BiTrash />}
										color="red.500"
										onClick={() => trashCertificate.mutate()}
									>
										{__('Move to Trash', 'learning-management-system')}
									</MenuItem>
								</>
							)}
						</MenuList>
					</Menu>
				</Flex>
			</Box>

			{/* Delete dialog */}
			<AlertDialog
				isOpen={isDeleteOpen}
				leastDestructiveRef={cancelRef}
				onClose={onDeleteClose}
				isCentered
			>
				<AlertDialogOverlay>
					<AlertDialogContent borderRadius="xl">
						<AlertDialogHeader fontSize="lg" fontWeight="bold">
							{__('Delete Certificate', 'learning-management-system')}
						</AlertDialogHeader>
						<AlertDialogBody color="gray.600">
							{__(
								'Are you sure? This certificate will be permanently deleted.',
								'learning-management-system',
							)}
						</AlertDialogBody>
						<AlertDialogFooter>
							<Button ref={cancelRef} onClick={onDeleteClose} borderRadius="lg">
								{__('Cancel', 'learning-management-system')}
							</Button>
							<Button
								colorScheme="red"
								ml={3}
								onClick={() => deleteCertificate.mutate()}
								isLoading={deleteCertificate.isPending}
								borderRadius="lg"
							>
								{__('Delete', 'learning-management-system')}
							</Button>
						</AlertDialogFooter>
					</AlertDialogContent>
				</AlertDialogOverlay>
			</AlertDialog>
		</Box>
	);
};

export default CertificateCardV2;
