import {
	Button,
	Flex,
	FormLabel,
	Icon,
	Stack,
	Switch,
	useToast,
} from '@chakra-ui/react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React, { useEffect } from 'react';
import { useForm, useFormContext } from 'react-hook-form';
import { BiImport } from 'react-icons/bi';
import IndividualSectionSettingsWrapper from '../../../../../assets/js/back-end/components/IndividualSectionSettingsWrapper';
import FormControlTwoCol from '../../../../../assets/js/back-end/components/common/FormControlTwoCol';
import { ProText } from '../../../../../assets/js/back-end/components/common/pro/ProShowcaseComponent';
import ToolTip from '../../../../../assets/js/back-end/screens/settings/components/ToolTip';
import API from '../../../../../assets/js/back-end/utils/api';
import http from '../../../../../assets/js/back-end/utils/http';
import { CertificateSettingsSchema } from '../utils/certificates';
import { certificateAddonUrls } from '../utils/urls';

interface FilterParams {
	per_page?: number;
	page?: number;
	status?: string;
	orderby: string;
	order: 'asc' | 'desc';
}

interface Props {
	filterParams: FilterParams;
	updateCertificatesPending?: boolean;
}

const CertificateSetting: React.FC<Props> = (props) => {
	const { register } = useFormContext();
	const methods = useForm<CertificateSettingsSchema>();

	const queryClient = useQueryClient();
	const toast = useToast();

	const certificatesSettingAPI = new API(
		certificateAddonUrls.certificatesSetting,
	);

	const {
		data: certificateSetting,
		isLoading,
		isSuccess,
	} = useQuery({
		queryKey: ['certificatesSetting', { ...props.filterParams, status: 'all' }],
		queryFn: () => certificatesSettingAPI.get(),
	});

	const certificatesFontAPI = new API(
		certificateAddonUrls.importCertificateFonts,
	);

	const additionalCertificateFontsSettingQuery = useQuery({
		queryKey: ['additionalCertificateFontsSetting'],
		queryFn: () => certificatesFontAPI.get(),
	});

	const importAllCertificateFonts = useMutation({
		mutationFn: () =>
			http({
				path: certificateAddonUrls.importCertificateFonts,
				method: 'POST',
			}),
		...{
			onSuccess(data: any) {
				queryClient.invalidateQueries({
					queryKey: ['additionalCertificateFontsSetting'],
				});
				toast({
					title: __(
						'Certificate fonts installed',
						'learning-management-system',
					),
					description: data?.message,
					status: 'success',
					duration: 3000,
					isClosable: true,
				});
			},
			onError(data: any) {
				toast({
					title: __(
						'Certificate fonts installation failed',
						'learning-management-system',
					),
					description: data?.message,
					status: 'error',
					duration: 3000,
					isClosable: true,
				});
			},
		},
	});

	useEffect(() => {
		if (certificateSetting && isSuccess) {
			methods.reset(methods.getValues());
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [certificateSetting]);

	return (
		<IndividualSectionSettingsWrapper
			isSaveActionPending={props?.updateCertificatesPending}
			isLoading={isLoading}
		>
			<Stack spacing="6">
				<FormControlTwoCol>
					<FormLabel>
						{__('Use Image Absolute Path', 'learning-management-system')}
						<ToolTip
							label={__(
								'Enable this option if images are not showing in the certificate. This will use the absolute path for images in the certificate instead of relative path.',
								'learning-management-system',
							)}
						/>
					</FormLabel>
					<Switch
						{...register('use_absolute_img_path')}
						defaultChecked={certificateSetting?.use_absolute_img_path}
					/>
				</FormControlTwoCol>

				<FormControlTwoCol>
					<FormLabel>
						{__('Use SSL Verify Host', 'learning-management-system')}
						<ToolTip
							label={__(
								'Enable this option only if images are not showing in the certificate. This will use HTTPS for images in the certificate instead of HTTP.',
								'learning-management-system',
							)}
						/>
					</FormLabel>
					<Switch
						{...register('use_ssl_verified')}
						defaultChecked={certificateSetting?.use_ssl_verified}
					/>
				</FormControlTwoCol>

				<FormControlTwoCol>
					<FormLabel display="flex" alignItems="center">
						{__('Install Certificate Fonts', 'learning-management-system')}
						<ToolTip
							label={__(
								'Install additional fonts required for certificates.',
								'learning-management-system',
							)}
						/>
					</FormLabel>
					<Flex>
						<Button
							colorScheme="primary"
							isLoading={importAllCertificateFonts.isPending}
							variant="outline"
							type="button"
							leftIcon={<Icon as={BiImport} fontSize="md" />}
							onClick={() => importAllCertificateFonts.mutate()}
						>
							{additionalCertificateFontsSettingQuery?.data
								? __('Reinstall', 'learning-management-system')
								: __('Install', 'learning-management-system')}
						</Button>
					</Flex>
				</FormControlTwoCol>

				<FormControlTwoCol>
					<FormLabel display="flex" alignItems="center" color="gray.400">
						{__('Install Custom Fonts', 'learning-management-system')}
						<ProText ml={2} />
					</FormLabel>
					<Flex>
						<Button
							colorScheme="primary"
							variant="outline"
							type="button"
							isDisabled
							leftIcon={<Icon as={BiImport} fontSize="md" />}
						>
							{__('Upload', 'learning-management-system')}
						</Button>
					</Flex>
				</FormControlTwoCol>
			</Stack>
		</IndividualSectionSettingsWrapper>
	);
};

export default CertificateSetting;
