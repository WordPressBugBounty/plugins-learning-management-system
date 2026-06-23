import {
	Box,
	ButtonGroup,
	Center,
	Container,
	IconButton,
	Link,
	Menu,
	MenuButton,
	MenuItem,
	MenuList,
	Spinner,
	Stack,
	useToast,
} from '@chakra-ui/react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React, { useEffect, useState } from 'react';
import { FormProvider, useForm } from 'react-hook-form';
import { BiBook, BiDotsHorizontalRounded } from 'react-icons/bi';
import {
	NavLink,
	Link as RouterLink,
	useLocation,
	useParams,
} from 'react-router-dom';
import BackButton from '../../../../../assets/js/back-end/components/common/BackButton';
import {
	Header,
	HeaderAccentButton,
	HeaderLeftSection,
	HeaderLogo,
	HeaderPrimaryButton,
	HeaderRightSection,
	HeaderSecondaryButton,
	HeaderTop,
} from '../../../../../assets/js/back-end/components/common/Header';
import {
	NavMenu,
	NavMenuLink,
} from '../../../../../assets/js/back-end/components/common/Nav';
import {
	headerResponsive,
	navActiveStyles,
} from '../../../../../assets/js/back-end/config/styles';
import { useWarnUnsavedChanges } from '../../../../../assets/js/back-end/hooks/useWarnUnSavedChanges';
import MasteriyoLogo from '../../../../../assets/img/logo.png';
import API from '../../../../../assets/js/back-end/utils/api';
import localized from '../../../../../assets/js/back-end/utils/global';
import {
	addOperationInCache,
	deepClean,
	deepMerge,
	editOperationInCache,
} from '../../../../../assets/js/back-end/utils/utils';
import BlockEditor from '../components/BlockEditor';
import CertificateSkeleton from '../components/CertificateSkeleton';
import Name from '../components/Name';
import { certificateBackendRoutes } from '../utils/routes';
import { certificateAddonUrls } from '../utils/urls';
import EditCertificatePDFDraft from './EditCertificatePDFDraft';

interface CertificateDataMap extends Certificate {
	_links: {
		collection: [
			{
				href: string;
			},
		];
		self: [
			{
				href: string;
			},
		];
	};
	html_content: string;
	rendered_html?: string;
	content_format?: 'gutenberg' | 'pdfdraft';
}

const EditCertificateInner: React.FC = () => {
	const { certificateId }: any = useParams();
	const location = useLocation();
	const methods = useForm();
	const certificateAPI = new API(certificateAddonUrls.certificates);
	const queryClient = useQueryClient();
	const toast = useToast();
	const [fullscreenMode, setFullscreenMode] = useState(false);
	const [guessedFormat] = useState<string | null>(() =>
		localStorage.getItem(`mto_cert_fmt_${certificateId}`),
	);

	const stateData = (location.state as any)?.certificate ?? undefined;

	const certificateQuery = useQuery({
		queryKey: [`certificate${certificateId}`, certificateId],
		queryFn: () => certificateAPI.get(`${certificateId}?context=edit`),
		initialData: stateData,
		staleTime: stateData ? Infinity : 0,
	});

	const updateCertificate = useMutation({
		mutationFn: (data: CertificateDataMap) =>
			certificateAPI.update(certificateId, data),
		...{
			onSuccess(data: CertificateDataMap) {
				methods.reset(methods.getValues());
				const certificatesList = queryClient?.getQueryData([
					'certificatesList',
					{
						order: 'desc',
						orderby: 'date',
						status: 'any',
					},
				]);

				const doesCertificateAlreadyExists = (
					certificatesList as any
				)?.data?.some((certificate: any) => certificate?.id === data?.id);

				if (doesCertificateAlreadyExists) {
					editOperationInCache(
						queryClient,
						[
							'certificatesList',
							{
								order: 'desc',
								orderby: 'date',
								status: 'any',
							},
						],
						data,
					);
				} else {
					addOperationInCache(
						queryClient,
						[
							'certificatesList',
							{
								order: 'desc',
								orderby: 'date',
								status: 'any',
							},
						],
						data,
					);
				}
				toast({
					title: __(
						'Certificate updated successfully.',
						'learning-management-system',
					),
					status: 'success',
					isClosable: true,
				});
				queryClient.invalidateQueries({ queryKey: [`certificate${data.id}`] });
				queryClient.invalidateQueries({ queryKey: ['certificatesV2List'] });
			},
			onError: (error: any) => {
				const isLimit =
					String(error?.code ?? '').endsWith('upgrade_required') ||
					/more than two published/i.test(error?.message ?? '');
				const isPdfdraft =
					(certificateQuery.data as CertificateDataMap)?.content_format ===
					'pdfdraft';
				if (isLimit && isPdfdraft) {
					return;
				}

				const message =
					error?.message ||
					error?.data?.message ||
					__('An unknown error occurred.', 'learning-management-system');

				toast({
					title: __(
						'Failed to update certificate',
						'learning-management-system',
					),
					description: message,
					status: 'error',
					isClosable: true,
				});
			},
		},
	});

	const draftCertificate = useMutation({
		mutationFn: (data: any) => certificateAPI.update(certificateId, data),
		...{
			onSuccess(data: CertificateDataMap) {
				methods.reset(methods.getValues());
				editOperationInCache(
					queryClient,
					[
						'certificatesList',
						{
							order: 'desc',
							orderby: 'date',
							status: 'any',
						},
					],
					data,
				);
				// Draft/auto-saves are silent — the in-editor save-status badge
				// communicates state instead. Only explicit Publish toasts.
				queryClient.invalidateQueries({ queryKey: [`certificate${data.id}`] });
				queryClient.invalidateQueries({ queryKey: ['certificatesV2List'] });
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
		},
	});

	const isPublished = () => certificateQuery?.data?.status === 'publish';

	const isDrafted = () => certificateQuery?.data?.status === 'draft';

	const isPDFDraftFormat = () =>
		(certificateQuery.data as CertificateDataMap)?.content_format ===
		'pdfdraft';

	const onPDFDraftSave = async (
		json: string,
		renderedHtml: string,
		status: string,
	): Promise<void> => {
		let designName: string | undefined;
		try {
			const parsed = JSON.parse(json);
			if (parsed?.settings?.name) designName = parsed.settings.name;
		} catch {}

		const payload: Partial<CertificateDataMap> = {
			status: status as any,
			html_content: json,
			content_format: 'pdfdraft',
			...(designName ? { name: designName } : {}),
		};
		if (renderedHtml) {
			payload.rendered_html = renderedHtml;
		}
		let saved: CertificateDataMap;
		if (status === 'publish') {
			saved = await updateCertificate.mutateAsync(
				payload as CertificateDataMap,
			);
		} else {
			saved = (await draftCertificate.mutateAsync(
				payload,
			)) as CertificateDataMap;
		}
		if (saved) {
			queryClient.setQueryData(
				[`certificate${certificateId}`, certificateId],
				saved,
			);
		}
	};

	const onSubmit = (data: any, status: string = 'publish') => {
		const newData = {
			status,
			html_content: data?.html_content
				?.replaceAll(/\\u002d/g, '\\\\u002d')
				?.replaceAll(/\\n/g, '\\\\n'),
		};

		if (status === 'publish') {
			updateCertificate.mutate(deepClean(deepMerge(data, newData)));
			return;
		}
		draftCertificate.mutate(deepClean(deepMerge(data, newData)));
	};

	const actions = [
		{
			label: __('Preview', 'learning-management-system'),
			action: () => window.open(certificateQuery?.data?.preview_link, '_blank'),
			variant: 'tertiary',
		},
		{
			label: isDrafted()
				? __('Save to Draft', 'learning-management-system')
				: __('Switch To Draft', 'learning-management-system'),
			action: methods.handleSubmit((data) => onSubmit(data, 'draft')),
			isLoading: draftCertificate.isPending,
			variant: 'secondary',
		},
		{
			label: isPublished()
				? __('Update', 'learning-management-system')
				: __('Publish', 'learning-management-system'),
			action: methods.handleSubmit((data) => onSubmit(data)),
			isLoading: updateCertificate.isPending,
			variant: 'primary',
		},
	];

	useWarnUnsavedChanges(methods.formState.isDirty);

	useEffect(() => {
		if (certificateQuery?.data && certificateQuery?.isSuccess) {
			methods.reset(methods.getValues());
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [certificateQuery?.data]);

	useEffect(() => {
		if (certificateQuery.isSuccess) {
			const fmt =
				(certificateQuery.data as CertificateDataMap)?.content_format ??
				'gutenberg';
			localStorage.setItem(`mto_cert_fmt_${certificateId}`, fmt);
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [certificateQuery.isSuccess]);

	if (!certificateQuery.isSuccess) {
		if (guessedFormat === 'pdfdraft') {
			return (
				<Center
					position="fixed"
					inset={0}
					bg="white"
					zIndex={99999}
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
			);
		}
		if (guessedFormat === 'gutenberg') {
			return (
				<Stack direction="column" spacing="8" align="center" mt={8}>
					<CertificateSkeleton />
				</Stack>
			);
		}
		return (
			<Center position="fixed" inset={0} bg="white" zIndex={99999}>
				<Spinner size="xl" color="blue.500" thickness="3px" />
			</Center>
		);
	}

	if (isPDFDraftFormat()) {
		return (
			<EditCertificatePDFDraft
				certificate={certificateQuery.data as CertificateDataMap}
				onSave={onPDFDraftSave}
				isSaving={updateCertificate.isPending || draftCertificate.isPending}
				onBack={() => {
					window.location.hash = `#${certificateBackendRoutes.certificate.certificatesV2}`;
				}}
			/>
		);
	}

	return (
		<Stack direction="column" spacing="8" align="center">
			<Header isSticky={false} display={fullscreenMode ? 'none' : 'block'}>
				<HeaderTop>
					<HeaderLeftSection>
						<Stack direction={['column', 'column', 'column', 'row']}>
							<HeaderLogo />
						</Stack>
						<NavMenu sx={headerResponsive.larger}>
							<>
								<NavMenuLink
									key={'Course Categories'}
									as={NavLink}
									_activeLink={navActiveStyles}
									to={certificateBackendRoutes.certificate.list}
								>
									{__('Certificate', 'learning-management-system')}
								</NavMenuLink>
							</>
						</NavMenu>
						<NavMenu sx={headerResponsive.smaller}>
							<Menu>
								<MenuButton
									as={IconButton}
									icon={<BiDotsHorizontalRounded style={{ fontSize: 25 }} />}
									style={{
										background: '#FFFFFF',
										boxShadow: 'none',
									}}
									py={'45px'}
									color={'primary.500'}
								/>
								<MenuList>
									<MenuItem>
										<NavMenuLink
											as={NavLink}
											sx={{ color: 'black', height: '20px' }}
											_activeLink={{ color: 'primary.500' }}
											to={certificateBackendRoutes.certificate.list}
											leftIcon={<BiBook />}
										>
											{__('Certificate', 'learning-management-system')}
										</NavMenuLink>
									</MenuItem>
								</MenuList>
							</Menu>
						</NavMenu>
					</HeaderLeftSection>
					<HeaderRightSection>
						<Link href={certificateQuery?.data?.preview_link} isExternal>
							<HeaderAccentButton
								width={['50px', '60px', '70px']}
								variant="tertiary"
							>
								{__('Preview', 'learning-management-system')}
							</HeaderAccentButton>
						</Link>
						<HeaderSecondaryButton
							onClick={methods.handleSubmit((data) => onSubmit(data, 'draft'))}
							isLoading={draftCertificate.isPending}
							width={['90px', '110px', '120px']}
						>
							{isDrafted()
								? __('Save to Draft', 'learning-management-system')
								: __('Switch To Draft', 'learning-management-system')}
						</HeaderSecondaryButton>
						<HeaderPrimaryButton
							onClick={methods.handleSubmit((data) => onSubmit(data))}
							isLoading={updateCertificate.isPending}
							width={
								isPublished()
									? ['45px', '50px', '55px', '60px']
									: ['50px', '60px', '70px']
							}
						>
							{isPublished()
								? __('Update', 'learning-management-system')
								: __('Publish', 'learning-management-system')}
						</HeaderPrimaryButton>
					</HeaderRightSection>
				</HeaderTop>
			</Header>
			<Container maxW="container.xl">
				<Stack direction="column" spacing="6">
					<ButtonGroup>
						<RouterLink to={certificateBackendRoutes.certificate.list}>
							<BackButton />
						</RouterLink>
					</ButtonGroup>
					<Box
						flex="1"
						bg="white"
						p={['4', null, '10']}
						shadow="box"
						display="flex"
						flexDirection="column"
						justifyContent="space-between"
					>
						<Stack direction="column" spacing="2">
							<FormProvider {...methods}>
								<form method="post" onSubmit={(e) => e.preventDefault()}>
									<Stack direction="column" spacing="6">
										<Name defaultValue={certificateQuery?.data?.name} />
										<BlockEditor
											defaultValue={certificateQuery?.data?.html_content}
											actions={actions as any}
											fullscreenMode={fullscreenMode}
											setFullscreenMode={setFullscreenMode}
										/>
									</Stack>
								</form>
							</FormProvider>
						</Stack>
					</Box>
				</Stack>
			</Container>
		</Stack>
	);
};

/**
 * Remount the editor whenever the certificate id changes so the previous
 * editor (Gutenberg block editor or the PDFDraft designer) fully unmounts
 * before the next one mounts. This prevents two react-dnd HTML5 backends
 * from being active at once ("Cannot have two HTML5 backends at the same
 * time") when navigating between certificates of different formats.
 */
const EditCertificate: React.FC = () => {
	const { certificateId }: any = useParams();

	return <EditCertificateInner key={certificateId} />;
};

export default EditCertificate;
