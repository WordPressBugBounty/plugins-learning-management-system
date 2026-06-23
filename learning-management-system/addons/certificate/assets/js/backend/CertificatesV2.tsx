import {
	Box,
	Button,
	ButtonGroup,
	Container,
	Flex,
	IconButton,
	Input,
	InputGroup,
	InputLeftElement,
	SimpleGrid,
	Skeleton,
	Text,
	useDisclosure,
	useToast,
} from '@chakra-ui/react';
import '@pdfdraft/designer/style.css';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import { Add } from 'iconsax-react';
import React, { useEffect, useMemo, useState } from 'react';
import { LuCheck, LuSearch, LuX } from 'react-icons/lu';
import API from '../../../../../assets/js/back-end/utils/api';
import { certificateAddonUrls } from '../utils/urls';
import EmptyInfo from '../../../../../assets/js/back-end/components/common/EmptyInfo';
import FilterTabs from '../../../../../assets/js/back-end/components/common/FilterTabs';
import {
	Header,
	HeaderLeftSection,
	HeaderLogo,
	HeaderPrimaryButton,
	HeaderRightSection,
	HeaderTop,
} from '../../../../../assets/js/back-end/components/common/Header';
import { getAllCertificates } from '../utils/certificates';
import CertificateCardV2 from './CertificateCardV2';
import CertificateTemplatePicker from './CertificateTemplatePicker';
import NewCertificateDialog from './NewCertificateDialog';

type StatusFilter = 'any' | 'publish' | 'draft' | 'trash';
type OrientationFilter = 'all' | 'portrait' | 'landscape';

const STATUS_TABS = [
	{ status: 'any', name: __('All Certificates', 'learning-management-system') },
	{ status: 'publish', name: __('Published', 'learning-management-system') },
	{ status: 'draft', name: __('Draft', 'learning-management-system') },
	{ status: 'trash', name: __('Trash', 'learning-management-system') },
];

const ORIENTATION_LABELS: Record<OrientationFilter, string> = {
	all: __('All', 'learning-management-system'),
	portrait: __('Portrait', 'learning-management-system'),
	landscape: __('Landscape', 'learning-management-system'),
};

function getOrientation(cert: any): 'portrait' | 'landscape' {
	try {
		const json = JSON.parse(cert.html_content ?? '{}');
		return json?.settings?.layout?.orientation ?? 'landscape';
	} catch {
		return 'landscape';
	}
}

const CertificatesV2: React.FC = () => {
	const [showPicker, setShowPicker] = useState(false);
	const {
		isOpen: isBlankOpen,
		onOpen: onBlankOpen,
		onClose: onBlankClose,
	} = useDisclosure();
	const [statusFilter, setStatusFilter] = useState<StatusFilter>('any');
	const [orientationFilter, setOrientationFilter] =
		useState<OrientationFilter>('all');
	const [search, setSearch] = useState('');
	const [bulkSelectMode, setBulkSelectMode] = useState(false);
	const [selectedIds, setSelectedIds] = useState<Set<string>>(new Set());

	const toggleBulkSelect = () => {
		setBulkSelectMode((v) => !v);
		setSelectedIds(new Set());
	};
	const toggleSelect = (id: string) =>
		setSelectedIds((prev) => {
			const next = new Set(prev);
			next.has(id) ? next.delete(id) : next.add(id);
			return next;
		});

	const toast = useToast();
	const queryClient = useQueryClient();
	const certificatesAPI = new API(certificateAddonUrls.certificates);

	const onBulkSuccess = (title: string) => {
		queryClient.invalidateQueries({ queryKey: ['certificatesV2List'] });
		toast({ title, status: 'success', isClosable: true });
		setBulkSelectMode(false);
		setSelectedIds(new Set());
	};
	const onBulkError = (err: any) => {
		toast({
			title:
				err?.message ||
				__('Something went wrong', 'learning-management-system'),
			status: 'error',
			isClosable: true,
		});
	};

	const bulkTrashMutation = useMutation({
		mutationFn: () =>
			certificatesAPI.bulkDelete('delete', {
				ids: Array.from(selectedIds),
				children: true,
			}),
		onSuccess: () =>
			onBulkSuccess(
				__('Certificates moved to trash', 'learning-management-system'),
			),
		onError: onBulkError,
	});

	const bulkRestoreMutation = useMutation({
		mutationFn: () =>
			certificatesAPI.bulkRestore('restore', {
				ids: Array.from(selectedIds),
			}),
		onSuccess: () =>
			onBulkSuccess(
				__('Certificates restored', 'learning-management-system'),
			),
		onError: onBulkError,
	});

	const bulkDeleteMutation = useMutation({
		mutationFn: () =>
			certificatesAPI.bulkDelete('delete', {
				ids: Array.from(selectedIds),
				force: true,
				children: true,
			}),
		onSuccess: () =>
			onBulkSuccess(
				__('Certificates deleted', 'learning-management-system'),
			),
		onError: onBulkError,
	});

	const isTrashView = statusFilter === 'trash';

	const certificatesQuery = useQuery({
		queryKey: ['certificatesV2List', statusFilter],
		queryFn: () =>
			getAllCertificates({
				status: statusFilter,
				per_page: 100,
				order: 'desc',
				orderby: 'date',
				content_format: 'pdfdraft',
			}),
	});

	const allCerts: any[] = useMemo(
		() =>
			(certificatesQuery.data?.data ?? []).filter(
				(c: any) => c.content_format === 'pdfdraft',
			),
		[certificatesQuery.data],
	);

	// Counts come from the server (content-format aware, includes trash) and are
	// persisted so the tab badges don't flicker when switching tabs refetches.
	const [counts, setCounts] = useState({
		any: 0,
		publish: 0,
		draft: 0,
		trash: 0,
	});
	useEffect(() => {
		const serverCounts = certificatesQuery.data?.meta?.counts;
		if (serverCounts) {
			setCounts({
				any: serverCounts.any ?? 0,
				publish: serverCounts.publish ?? 0,
				draft: serverCounts.draft ?? 0,
				trash: serverCounts.trash ?? 0,
			});
		}
	}, [certificatesQuery.data?.meta?.counts]);

	const displayed = useMemo(() => {
		let certs = allCerts;
		if (orientationFilter !== 'all')
			certs = certs.filter((c) => getOrientation(c) === orientationFilter);
		if (search.trim()) {
			const q = search.toLowerCase();
			certs = certs.filter((c) => (c.name ?? '').toLowerCase().includes(q));
		}
		return [...certs].sort(
			(a, b) =>
				new Date(b.date_modified ?? b.date_created ?? 0).getTime() -
				new Date(a.date_modified ?? a.date_created ?? 0).getTime(),
		);
	}, [allCerts, orientationFilter, search]);

	if (showPicker) {
		return (
			<CertificateTemplatePicker
				onClose={() => setShowPicker(false)}
				onOpenBlank={() => {
					setShowPicker(false);
					onBlankOpen();
				}}
			/>
		);
	}

	return (
		<Box minH="100vh" bg="gray.50">
			<Header>
				<HeaderTop>
					<HeaderLeftSection gap={7}>
						<HeaderLogo />
						<FilterTabs
							tabs={STATUS_TABS}
							defaultActive="any"
							onTabChange={(s) => setStatusFilter(s as StatusFilter)}
							counts={counts}
							isCounting={certificatesQuery.isLoading}
						/>
					</HeaderLeftSection>
					<HeaderRightSection>
						<HeaderPrimaryButton
							onClick={() => setShowPicker(true)}
							leftIcon={<Add />}
						>
							{__('Add New Certificate', 'learning-management-system')}
						</HeaderPrimaryButton>
					</HeaderRightSection>
				</HeaderTop>
			</Header>

			<Container maxW="container.xl" py={8}>
				{/* Toolbar */}
				<Flex justify="space-between" align="center" mb={7} gap={3} wrap="wrap">
					<Flex align="center" gap={3}>
						<Box
							as="button"
							w="18px"
							h="18px"
							border="1.5px solid"
							borderColor={bulkSelectMode ? 'gray.800' : '#9E9E9E'}
							borderRadius="sm"
							bg={bulkSelectMode ? 'gray.800' : 'white'}
							display="flex"
							alignItems="center"
							justifyContent="center"
							cursor="pointer"
							onClick={toggleBulkSelect}
							flexShrink={0}
						>
							{bulkSelectMode && (
								<LuCheck size={10} strokeWidth={3} color="white" />
							)}
						</Box>

						<ButtonGroup size="sm" spacing={1}>
							{(Object.keys(ORIENTATION_LABELS) as OrientationFilter[]).map(
								(o) => (
									<Button
										key={o}
										bg={orientationFilter === o ? 'gray.800' : 'white'}
										color={orientationFilter === o ? 'white' : 'gray.600'}
										border="1px solid"
										borderColor={
											orientationFilter === o ? 'gray.800' : 'gray.200'
										}
										variant="unstyled"
										display="flex"
										alignItems="center"
										px={4}
										h="34px"
										onClick={() => setOrientationFilter(o)}
										borderRadius="full"
										fontWeight="500"
										fontSize="sm"
										transition="all 0.15s"
										_hover={{ borderColor: 'gray.400' }}
									>
										{ORIENTATION_LABELS[o]}
									</Button>
								),
							)}
						</ButtonGroup>
					</Flex>

					<InputGroup size="sm" w="220px">
						<InputLeftElement pointerEvents="none" h="36px">
							<LuSearch color="#A0AEC0" size={14} />
						</InputLeftElement>
						<Input
							h="36px"
							placeholder={__(
								'Search templates...',
								'learning-management-system',
							)}
							value={search}
							onChange={(e) => setSearch(e.target.value)}
							borderRadius="full"
							bg="white"
							borderColor="gray.200"
							fontSize="sm"
							_focus={{
								borderColor: 'primary.400',
								boxShadow: '0 0 0 1px var(--chakra-colors-primary-400)',
							}}
						/>
					</InputGroup>
				</Flex>

				{/* Grid */}
				{certificatesQuery.isLoading ? (
					<SimpleGrid columns={{ base: 1, sm: 2, md: 3, lg: 4 }} spacing={5}>
						{Array.from({ length: 8 }).map((_, i) => (
							<Skeleton
								key={i}
								height="260px"
								borderRadius="xl"
								startColor="gray.100"
								endColor="gray.200"
							/>
						))}
					</SimpleGrid>
				) : displayed.length === 0 ? (
					<Box bg="white" py={{ base: 6, md: 12 }} shadow="box" mx="auto">
						{statusFilter === 'any' &&
						allCerts.length === 0 &&
						!search.trim() &&
						orientationFilter === 'all' ? (
							<EmptyInfo
								onPrimaryButtonClick={() => setShowPicker(true)}
								title={__(
									'Create Your First Certificate',
									'learning-management-system',
								)}
								description={__(
									'Design certificate templates that can be awarded to students upon course completion. Customize the design, add your branding, and set completion criteria.',
									'learning-management-system',
								)}
								primaryButtonLabel={__(
									'Add New Certificate',
									'learning-management-system',
								)}
								docs={'https://docs.masteriyo.com/free-addons/certificate-builder'}
							/>
						) : (
							<EmptyInfo isResultFiltered />
						)}
					</Box>
				) : (
					<SimpleGrid columns={{ base: 1, sm: 2, md: 3, lg: 4 }} spacing={5}>
						{displayed.map((cert) => (
							<CertificateCardV2
								key={cert.id}
								certificate={cert}
								bulkSelectMode={bulkSelectMode}
								selected={selectedIds.has(String(cert.id))}
								onToggleSelect={toggleSelect}
							/>
						))}
					</SimpleGrid>
				)}
			</Container>

			<NewCertificateDialog isOpen={isBlankOpen} onClose={onBlankClose} />

			{/* Floating bulk action bar */}
			{bulkSelectMode && selectedIds.size > 0 && (
				<Box
					position="fixed"
					bottom={6}
					left="50%"
					transform="translateX(-50%)"
					zIndex={100000}
				>
					<Flex
						align="center"
						gap={1}
						borderRadius="full"
						border="1px solid"
						borderColor="gray.200"
						bg="white"
						px={2}
						py={1.5}
						boxShadow="0px 8px 32px rgba(0,0,0,0.15)"
					>
						<Text
							px={3}
							fontSize="sm"
							fontWeight="500"
							color="gray.800"
							whiteSpace="nowrap"
						>
							{selectedIds.size}{' '}
							{__('selected', 'learning-management-system')}
						</Text>
						<Box w="1px" h="20px" bg="gray.200" mx={1} />
						{isTrashView ? (
							<>
								<Button
									size="sm"
									variant="ghost"
									borderRadius="full"
									isLoading={bulkRestoreMutation.isPending}
									onClick={() => bulkRestoreMutation.mutate()}
								>
									{__('Restore', 'learning-management-system')}
								</Button>
								<Button
									size="sm"
									colorScheme="red"
									variant="ghost"
									borderRadius="full"
									isLoading={bulkDeleteMutation.isPending}
									onClick={() => bulkDeleteMutation.mutate()}
								>
									{__('Delete Permanently', 'learning-management-system')}
								</Button>
							</>
						) : (
							<Button
								size="sm"
								colorScheme="red"
								variant="ghost"
								borderRadius="full"
								isLoading={bulkTrashMutation.isPending}
								onClick={() => bulkTrashMutation.mutate()}
							>
								{__('Move to Trash', 'learning-management-system')}
							</Button>
						)}
						<IconButton
							aria-label={__('Cancel selection', 'learning-management-system')}
							size="sm"
							variant="ghost"
							icon={<LuX />}
							borderRadius="full"
							onClick={() => {
								setBulkSelectMode(false);
								setSelectedIds(new Set());
							}}
						/>
					</Flex>
				</Box>
			)}
		</Box>
	);
};

export default CertificatesV2;
