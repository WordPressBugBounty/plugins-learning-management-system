import {
	Box,
	Button,
	ButtonGroup,
	FormLabel,
	Icon,
	Stack,
	Tooltip,
	useToast,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { BiInfoCircle } from 'react-icons/bi';
import { useMutation, useQueryClient } from 'react-query';
import FormControlTwoCol from '../../../../assets/js/back-end/components/common/FormControlTwoCol';
import { infoIconStyles } from '../../../../assets/js/back-end/config/styles';
import { WCIntegrationSchema } from '../../../../assets/js/back-end/types/course';
import API from '../../../../assets/js/back-end/utils/api';
import { deepClean } from '../../../../assets/js/back-end/utils/utils';
import { urls } from './constants/urls';

interface Props {
	WCIntegrationData?: WCIntegrationSchema;
}

const WCIntegrationCourseSetting: React.FC<Props> = (props) => {
	const { WCIntegrationData } = props;

	const toast = useToast();
	const queryClient = useQueryClient();
	const createProductAPI = new API(urls.create_wc_product);

	const courseId = WCIntegrationData?.course_id || 0;
	const hasExistingProduct = WCIntegrationData?.product_create || false;

	const createProductMutation = useMutation<WCIntegrationSchema>((data) =>
		createProductAPI.store(data),
	);

	const handleCreateProduct = (data: WCIntegrationSchema) => {
		createProductMutation.mutate(deepClean(data), {
			onSuccess: (data: any) => {
				toast({
					title:
						data.success && data.message
							? data.message
							: __(
									'Product has been created and linked successfully.',
									'learning-management-system',
								),
					status: 'success',
					isClosable: true,
				});
				queryClient.invalidateQueries(`course${courseId}`);
			},

			onError: (error: any) => {
				const message: any = error?.message
					? error?.message
					: error?.data?.message;

				toast({
					title: __('Failed to create product.', 'learning-management-system'),
					description: message ? `${message}` : undefined,
					status: 'error',
					isClosable: true,
				});
			},
		});
	};

	return (
		<Stack direction="column" spacing="8">
			<FormControlTwoCol>
				<FormLabel minW="160px">
					{__('Create as a Product', 'learning-management-system')}
					<Tooltip
						label={__(
							'Create a new product in WooCommerce for this course. Ensure this course is set to be paid.',
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
				<ButtonGroup>
					<Button
						size="sm"
						onClick={() =>
							handleCreateProduct({
								course_id: courseId,
								product_create: true,
							})
						}
						colorScheme={'primary'}
						isLoading={createProductMutation.isLoading}
						isDisabled={hasExistingProduct}
					>
						{createProductMutation.isLoading
							? __('Creating Product...', 'learning-management-system')
							: hasExistingProduct
								? __('Product Created', 'learning-management-system')
								: __('Create Product', 'learning-management-system')}
					</Button>
				</ButtonGroup>
			</FormControlTwoCol>
		</Stack>
	);
};

export default WCIntegrationCourseSetting;
