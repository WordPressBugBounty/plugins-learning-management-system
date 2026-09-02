import {
	Box,
	Button,
	Center,
	Container,
	Flex,
	Heading,
	Image,
	SimpleGrid,
	Spinner,
	Stack,
	Text,
	useToast,
} from '@chakra-ui/react';
import { useMutation } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { NavLink, useNavigate } from 'react-router-dom';
import { triggerLicenseCheck } from '../../../../../assets/js/back-end/components/LicenseCheck';
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
import localized from '../../../../../assets/js/back-end/utils/global';
import {
	isArray,
	isLicensePlanActive,
} from '../../../../../assets/js/back-end/utils/utils';
import { cloneCertificateTemplate } from '../utils/certificates';
import { certificateBackendRoutes } from '../utils/routes';
let certificateSamples = localized?.certificate_samples || [];

// Without an active plan only the first sample is offered. Free reaches this the
// same way — `isLicensePlanActive()` is false when there is no license at all —
// which is the restriction free 2.3.2 applied server-side in `helper.php`.
if (
	isArray(certificateSamples) &&
	certificateSamples.length &&
	!isLicensePlanActive()
) {
	certificateSamples = [certificateSamples?.[0]];
}

const AddNewCertificate: React.FC = () => {
	const toast = useToast();
	const navigate = useNavigate();
	const cloneTemplateMutation = useMutation({
		mutationFn: (id: string) => cloneCertificateTemplate(id),
	});

	const onTemplatePress = (id: string) => {
		cloneTemplateMutation.mutate(id, {
			onSuccess: (certificate) => {
				navigate(
					certificateBackendRoutes.certificate.edit.replace(
						':certificateId',
						certificate.id.toString(),
					),
				);
			},
			onError: (error: any) => {
				const message =
					error?.message ||
					error?.data?.message ||
					__('An unknown error occurred.', 'learning-management-system');

				toast({
					title: __(
						'Could not create a certificate',
						'learning-management-system',
					),
					description: message,
					isClosable: true,
					status: 'error',
				});
			},
		});
	};

	// Handle refresh button click
	const handleRefreshTemplates = () => {
		const url = new URL(window.location.href);
		url.searchParams.set('refresh', Date.now().toString());
		window.location.href = url.toString();
	};

	return (
		<Stack direction="column" spacing="8" alignItems="center">
			<Header>
				<HeaderTop>
					<HeaderLeftSection>
						<Flex alignItems="center" gap="4">
							<HeaderLogo />
							<NavMenu>
								<NavMenuItem>
									<NavMenuLink
										sx={navLinkStyles}
										onClick={() => {
											if (!triggerLicenseCheck()) return;
										}}
										to={
											!triggerLicenseCheck()
												? '#'
												: certificateBackendRoutes.certificate.add
										}
										_activeLink={navActiveStyles}
										as={NavLink}
									>
										<Text>
											{__('Add New Certificate', 'learning-management-system')}
										</Text>
									</NavMenuLink>
								</NavMenuItem>
							</NavMenu>
						</Flex>
					</HeaderLeftSection>
					<HeaderRightSection>
						<Button
							ml="auto"
							colorScheme={'primary'}
							variant="outline"
							onClick={handleRefreshTemplates}
						>
							{__('Refresh Template', 'learning-management-system')}
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
						<Box
							shadow="box"
							bg="white"
							cursor="pointer"
							onClick={() => onTemplatePress('blank')}
							tabIndex={0}
						>
							<figure>
								<Image src="https://raw.githubusercontent.com/wpeverest/learning-management-system-pro-certificate-samples/master/samples/blank-template-preview.png" />
							</figure>
							<Heading fontSize="md" p={2} textAlign="center">
								{__('Blank Slate', 'learning-management-system')}
							</Heading>
						</Box>
						{certificateSamples?.map((s) => (
							<Box
								key={s.id}
								shadow="box"
								bg="white"
								cursor="pointer"
								onClick={() => onTemplatePress(s.id)}
								tabIndex={0}
							>
								<figure>
									<Image src={s.preview_image} />
								</figure>
								<Heading fontSize="md" p={2} textAlign="center">
									{s.title}
								</Heading>
							</Box>
						))}
					</SimpleGrid>
					{cloneTemplateMutation.isPending ? (
						<Center
							pos="fixed"
							top="0"
							bottom="0"
							left="0"
							right="0"
							bg="rgba(255,255,255, 0.5)"
						>
							<Spinner />
						</Center>
					) : null}
				</Stack>
			</Container>
		</Stack>
	);
};

export default AddNewCertificate;
