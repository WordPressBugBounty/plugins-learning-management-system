import {
	Box,
	Button,
	FormLabel,
	Icon,
	Stack,
	Switch,
	useToast,
} from '@chakra-ui/react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { useFormContext } from 'react-hook-form';
import { BiImport } from 'react-icons/bi';
import FormControlTwoCol from '../../../../../assets/js/back-end/components/common/FormControlTwoCol';
import { ProShowCaseInlineButton } from '../../../../../assets/js/back-end/components/common/pro/ProShowcaseComponent';
import ToolTip from '../../../../../assets/js/back-end/screens/settings/components/ToolTip';
import API from '../../../../../assets/js/back-end/utils/api';
import http from '../../../../../assets/js/back-end/utils/http';
import { CertificateSettingsSchema } from '../utils/certificates';
import { certificateAddonUrls } from '../utils/urls';

interface Props {
	certificateSetting: CertificateSettingsSchema;
}

const CertificateSetting: React.FC<Props> = (props) => {
	const { certificateSetting } = props;
	const { register } = useFormContext();

	const queryClient = useQueryClient();
	const toast = useToast();

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
	return (
		<Stack spacing="6">
			<FormControlTwoCol>
				<FormLabel display="flex" alignItems="center">
					{__('Use Image Absolute Path', 'learning-management-system')}
					<ToolTip
						label={__(
							'Enable this option if images are not showing in the certificate. This will use the absolute path for images in the certificate instead of relative path.',
							'learning-management-system',
						)}
					/>
				</FormLabel>
				<Box display="flex" justifyContent="flex-end">
					<Switch
						{...register('use_absolute_img_path')}
						defaultChecked={certificateSetting?.use_absolute_img_path}
					/>
				</Box>
			</FormControlTwoCol>

			<FormControlTwoCol>
				<FormLabel display="flex" alignItems="center">
					{__('Use SSL Verify Host', 'learning-management-system')}
					<ToolTip
						label={__(
							'Enable this option only if images are not showing in the certificate. This will use HTTPS for images in the certificate instead of HTTP.',
							'learning-management-system',
						)}
					/>
				</FormLabel>
				<Box display="flex" justifyContent="flex-end">
					<Switch
						{...register('use_ssl_verified')}
						defaultChecked={certificateSetting?.use_ssl_verified}
					/>
				</Box>
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
				<Box display="flex" justifyContent="flex-end">
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
				</Box>
			</FormControlTwoCol>

			<ProShowCaseInlineButton
				mt={2}
				label={__('Install Custom Fonts', 'learning-management-system')}
				buttonText={__('Upload', 'learning-management-system')}
				leftIcon={<Icon as={BiImport} fontSize="md" />}
			/>
		</Stack>
	);
};

export default CertificateSetting;
