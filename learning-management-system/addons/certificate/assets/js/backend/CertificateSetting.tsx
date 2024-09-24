import {
	Box,
	Button,
	FormLabel,
	Icon,
	Stack,
	Switch,
	Tooltip,
	useToast,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { useFormContext } from 'react-hook-form';
import { BiImport, BiInfoCircle } from 'react-icons/bi';
import { useMutation, useQuery, useQueryClient } from 'react-query';
import FormControlTwoCol from '../../../../../assets/js/back-end/components/common/FormControlTwoCol';
import { infoIconStyles } from '../../../../../assets/js/back-end/config/styles';
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

	const additionalCertificateFontsSettingQuery = useQuery(
		['additionalCertificateFontsSetting'],
		() => certificatesFontAPI.get(),
	);

	const importAllCertificateFonts = useMutation(
		() =>
			http({
				path: certificateAddonUrls.importCertificateFonts,
				method: 'POST',
			}),
		{
			onSuccess(data: any) {
				queryClient.invalidateQueries('additionalCertificateFontsSetting');
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
	);
	return (
		<Stack spacing="4">
			<FormControlTwoCol>
				<FormLabel>
					{__('Use Image Absolute Path', 'learning-management-system')}
					<Tooltip
						label={__(
							'Enable this option if images are not showing in the certificate. This will use the absolute path for images in the certificate instead of relative path.',
							'learning-management-system',
						)}
						hasArrow
						fontSize="xs"
					>
						<Box as="span" sx={infoIconStyles}>
							<Icon as={BiInfoCircle} />
						</Box>
					</Tooltip>
				</FormLabel>
				<Box display="flex" justifyContent="flex-end">
					<Switch
						{...register('use_absolute_img_path')}
						defaultChecked={certificateSetting?.use_absolute_img_path}
					/>
				</Box>
			</FormControlTwoCol>

			<FormControlTwoCol>
				<FormLabel>
					{__('Use SSL Verify Host', 'learning-management-system')}
					<Tooltip
						label={__(
							'Enable this option only if images are not showing in the certificate. This will use HTTPS for images in the certificate instead of HTTP.',
							'learning-management-system',
						)}
						hasArrow
						fontSize="xs"
					>
						<Box as="span" sx={infoIconStyles}>
							<Icon as={BiInfoCircle} />
						</Box>
					</Tooltip>
				</FormLabel>
				<Box display="flex" justifyContent="flex-end">
					<Switch
						{...register('use_ssl_verified')}
						defaultChecked={certificateSetting?.use_ssl_verified}
					/>
				</Box>
			</FormControlTwoCol>

			<FormControlTwoCol>
				<FormLabel>
					{__('Install Certificate Fonts', 'learning-management-system')}
					<Tooltip
						label={__(
							'Install additional fonts required for certificates.',
							'learning-management-system',
						)}
						hasArrow
						fontSize="xs"
					>
						<Box as="span" sx={infoIconStyles}>
							<Icon as={BiInfoCircle} />
						</Box>
					</Tooltip>
				</FormLabel>
				<Box display="flex" justifyContent="flex-end">
					<Button
						colorScheme="primary"
						isLoading={importAllCertificateFonts.isLoading}
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
		</Stack>
	);
};

export default CertificateSetting;
