import {
	Box,
	Button,
	ButtonGroup,
	Container,
	Icon,
	IconButton,
	Link,
	Menu,
	MenuButton,
	MenuItem,
	MenuList,
	Stack,
	useToast,
} from '@chakra-ui/react';
import { __, sprintf } from '@wordpress/i18n';
import React, { useState } from 'react';
import { FormProvider, useForm } from 'react-hook-form';
import { BiBook, BiChevronLeft, BiDotsHorizontalRounded } from 'react-icons/bi';
import { useMutation, useQuery, useQueryClient } from 'react-query';
import { NavLink, Link as RouterLink, useParams } from 'react-router-dom';
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
import API from '../../../../../assets/js/back-end/utils/api';
import {
	deepClean,
	deepMerge,
} from '../../../../../assets/js/back-end/utils/utils';
import BlockEditor from '../components/BlockEditor';
import CertificateSkeleton from '../components/CertificateSkeleton';
import Name from '../components/Name';
import { certificateBackendRoutes } from '../utils/routes';
import { certificateAddonUrls } from '../utils/urls';

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
}

const EditCertificate: React.FC = () => {
	const { certificateId }: any = useParams();
	const methods = useForm();
	const certificateAPI = new API(certificateAddonUrls.certificates);
	const queryClient = useQueryClient();
	const toast = useToast();
	const [fullscreenMode, setFullscreenMode] = useState(false);

	const certificateQuery = useQuery(
		[`certificate${certificateId}`, certificateId],
		() => certificateAPI.get(`${certificateId}?context=edit`),
	);

	const updateCertificate = useMutation(
		(data: CertificateDataMap) => certificateAPI.update(certificateId, data),
		{
			onSuccess(data: CertificateDataMap) {
				toast({
					title: __(
						'Certificate updated successfully.',
						'learning-management-system',
					),
					status: 'success',
					isClosable: true,
				});
				queryClient.invalidateQueries(`certificate${data.id}`);
			},
			onError: (error: any) => {
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
	);

	const draftCertificate = useMutation(
		(data: any) => certificateAPI.update(certificateId, data),
		{
			onSuccess(data: CertificateDataMap) {
				toast({
					title: sprintf(
						/* translators: %s: Certificate name */
						__('%s drafted', 'learning-management-system'),
						data.name,
					),
					status: 'success',
					isClosable: true,
				});
				queryClient.invalidateQueries(`certificate${data.id}`);
			},
		},
	);

	const isPublished = () => certificateQuery.data?.status === 'publish';

	const isDrafted = () => certificateQuery.data?.status === 'draft';

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
			action: () => window.open(certificateQuery.data?.preview_link, '_blank'),
			variant: 'tertiary',
		},
		{
			label: isDrafted()
				? __('Save to Draft', 'learning-management-system')
				: __('Switch To Draft', 'learning-management-system'),
			action: methods.handleSubmit((data) => onSubmit(data, 'draft')),
			isLoading: draftCertificate.isLoading,
			variant: 'secondary',
		},
		{
			label: isPublished()
				? __('Update', 'learning-management-system')
				: __('Publish', 'learning-management-system'),
			action: methods.handleSubmit((data) => onSubmit(data)),
			isLoading: updateCertificate.isLoading,
			variant: 'primary',
		},
	];
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
									leftIcon={<BiBook />}
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
						<Link href={certificateQuery.data?.preview_link} isExternal>
							<HeaderAccentButton
								width={['50px', '60px', '70px']}
								variant="tertiary"
							>
								{__('Preview', 'learning-management-system')}
							</HeaderAccentButton>
						</Link>
						<HeaderSecondaryButton
							onClick={methods.handleSubmit((data) => onSubmit(data, 'draft'))}
							isLoading={draftCertificate.isLoading}
							width={['90px', '110px', '120px']}
						>
							{isDrafted()
								? __('Save to Draft', 'learning-management-system')
								: __('Switch To Draft', 'learning-management-system')}
						</HeaderSecondaryButton>
						<HeaderPrimaryButton
							onClick={methods.handleSubmit((data) => onSubmit(data))}
							isLoading={updateCertificate.isLoading}
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
							<Button
								variant="link"
								_hover={{ color: 'primary.500' }}
								leftIcon={<Icon fontSize="xl" as={BiChevronLeft} />}
							>
								{__('Back to certificates', 'learning-management-system')}
							</Button>
						</RouterLink>
					</ButtonGroup>
					{certificateQuery.isSuccess ? (
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
											<Name defaultValue={certificateQuery.data?.name} />
											<BlockEditor
												defaultValue={certificateQuery.data?.html_content}
												actions={actions as any}
												fullscreenMode={fullscreenMode}
												setFullscreenMode={setFullscreenMode}
											/>
										</Stack>
									</form>
								</FormProvider>
							</Stack>
						</Box>
					) : (
						<CertificateSkeleton />
					)}
				</Stack>
			</Container>
		</Stack>
	);
};

export default EditCertificate;
