import {
	Box,
	Button,
	ButtonGroup,
	Checkbox,
	Flex,
	FormLabel,
	Icon,
	Link,
	Modal,
	ModalBody,
	ModalCloseButton,
	ModalContent,
	ModalFooter,
	ModalHeader,
	ModalOverlay,
	Stack,
	Text,
	Tooltip,
	useToast,
} from '@chakra-ui/react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React, { useCallback, useState } from 'react';
import { BiLinkExternal } from 'react-icons/bi';
import AsyncSelect from '../../../../assets/js/back-end/components/common/AsyncSelect';
import FormControlTwoCol from '../../../../assets/js/back-end/components/common/FormControlTwoCol';
import ToolTip from '../../../../assets/js/back-end/screens/settings/components/ToolTip';
import { WCIntegrationSchema } from '../../../../assets/js/back-end/types/course';
import API from '../../../../assets/js/back-end/utils/api';
import { deepClean } from '../../../../assets/js/back-end/utils/utils';
import { WC_COURSE_PRODUCT_TYPES } from './constants/productTypes';
import { urls } from './constants/urls';

interface MasteriyoWcIntegration {
	adminUrl: string;
}

interface WcProduct {
	id: number;
	name: string;
	price: string;
	type?: string;
	type_label?: string;
}

interface WcProductOption {
	value: number;
	label: string;
	price: string;
	type?: string;
	type_label?: string;
}

interface Props {
	WCIntegrationData?: WCIntegrationSchema;
	regularPrice?: string;
}

const WCIntegrationCourseSetting: React.FC<Props> = (props) => {
	const { WCIntegrationData, regularPrice } = props;
	const isFree = !regularPrice || parseFloat(regularPrice) <= 0;

	const toast = useToast();
	const queryClient = useQueryClient();

	const courseId = WCIntegrationData?.course_id || 0;
	const hasExistingProduct = WCIntegrationData?.product_create || false;

	const createProductAPI = new API(urls.create_wc_product);

	const createProductMutation = useMutation<WCIntegrationSchema>({
		mutationFn: (data) => createProductAPI.store(data),
	});

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
				queryClient.invalidateQueries({ queryKey: [`course${courseId}`] });
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

	// ── Link existing product ──────────────────────────────────────────────
	const [selectedOption, setSelectedOption] = useState<WcProductOption | null>(
		null,
	);
	const [isLinkModalOpen, setIsLinkModalOpen] = useState(false);
	const [linkConfirmed, setLinkConfirmed] = useState(false);

	const wcIntegrationData = (
		window as { _MASTERIYO_WC_INTEGRATION_?: MasteriyoWcIntegration }
	)._MASTERIYO_WC_INTEGRATION_;

	const fetchProducts = (search: string): Promise<WcProductOption[]> =>
		new API(urls.list_wc_products)
			.list(search ? { search } : undefined)
			.then((data: any) =>
				(data?.data || []).map((p: WcProduct) => ({
					value: p.id,
					label: p.name,
					price: p.price,
					type: p.type,
					type_label: p.type_label,
				})),
			)
			.catch(() => []);

	const loadProductOptions = useCallback(
		(inputValue: string, callback: (options: WcProductOption[]) => void) => {
			fetchProducts(inputValue).then(callback);
		},
		[],
	);

	const linkProductMutation = useMutation({
		mutationFn: () =>
			new API(urls.link_wc_product(courseId)).store({
				product_id: selectedOption!.value,
			}),
		onSuccess: () => {
			toast({
				title: __('Product linked successfully.', 'learning-management-system'),
				status: 'success',
				isClosable: true,
			});
			setSelectedOption(null);
			setIsLinkModalOpen(false);
			setLinkConfirmed(false);
			queryClient.invalidateQueries({ queryKey: [`course${courseId}`] });
		},
		onError: (error: any) => {
			toast({
				title: __('Failed to link product.', 'learning-management-system'),
				description: error?.message || error?.data?.message,
				status: 'error',
				isClosable: true,
			});
		},
	});

	const unlinkProductMutation = useMutation({
		mutationFn: () => new API(urls.link_wc_product(courseId)).deleteResource(),
		onSuccess: () => {
			toast({
				title: __('Product unlinked.', 'learning-management-system'),
				status: 'success',
				isClosable: true,
			});
			queryClient.invalidateQueries({ queryKey: [`course${courseId}`] });
		},
		onError: (error: any) => {
			toast({
				title: __('Failed to unlink product.', 'learning-management-system'),
				description: error?.message,
				status: 'error',
				isClosable: true,
			});
		},
	});

	const linkedProductId = WCIntegrationData?.linked_product_id;
	const linkedProductName = WCIntegrationData?.linked_product_name;
	const linkedProductPrice = WCIntegrationData?.linked_product_price;
	const linkedProductEditUrl = WCIntegrationData?.linked_product_edit_url || '';

	const showLinkSection = !isFree && !hasExistingProduct && !linkedProductId;

	const defaultProductsQuery = useQuery({
		queryKey: ['wc-products-default'],
		queryFn: () => fetchProducts(''),
		enabled: showLinkSection,
		staleTime: 5 * 60 * 1000,
	});

	const targetTypeLabel = __('Masteriyo Course', 'learning-management-system');

	const handleLinkClick = () => {
		if (
			selectedOption?.type &&
			(WC_COURSE_PRODUCT_TYPES as readonly string[]).includes(
				selectedOption.type,
			)
		) {
			linkProductMutation.mutate();
			return;
		}
		setLinkConfirmed(false);
		setIsLinkModalOpen(true);
	};

	const handleLinkModalClose = () => {
		if (linkProductMutation.isPending) return;
		setIsLinkModalOpen(false);
		setLinkConfirmed(false);
	};

	// ── Render helpers ─────────────────────────────────────────────────────
	const details = (hasExistingProduct: boolean) => (
		<>
			<FormLabel minW="160px">
				{__('Create as a Product', 'learning-management-system')}
				<ToolTip
					label={__(
						'Create a new product in WooCommerce for this course. Ensure this course is set to be paid.',
						'learning-management-system',
					)}
				/>
			</FormLabel>
			<ButtonGroup>
				<Tooltip
					label={
						linkedProductId
							? __(
									'A product is already linked to this course.',
									'learning-management-system',
								)
							: __(
									'Set a paid course price before creating a WooCommerce product.',
									'learning-management-system',
								)
					}
					isDisabled={!isFree && !linkedProductId}
					hasArrow
					shouldWrapChildren
					placement="top"
				>
					<Button
						size="sm"
						onClick={() =>
							handleCreateProduct({
								course_id: courseId,
								product_create: true,
							})
						}
						colorScheme={'primary'}
						isDisabled={hasExistingProduct || isFree || !!linkedProductId}
					>
						{createProductMutation.isPending
							? __('Creating Product...', 'learning-management-system')
							: __('Create Product', 'learning-management-system')}
					</Button>
				</Tooltip>
			</ButtonGroup>
		</>
	);

	const linkSection = () => {
		if (linkedProductId && linkedProductName) {
			return (
				<>
					<FormLabel minW="160px">
						{__('Linked WC Product', 'learning-management-system')}
						<ToolTip
							label={__(
								'This WooCommerce product is linked to the course. Unlink to change or remove the association.',
								'learning-management-system',
							)}
						/>
					</FormLabel>
					<Flex align="center" gap={3} flex={1}>
						<Box flex={1}>
							<Text
								fontSize="sm"
								fontWeight="semibold"
								display="flex"
								alignItems="center"
								gap={1}
							>
								{linkedProductName}
								{linkedProductEditUrl && (
									<Link
										href={linkedProductEditUrl}
										isExternal
										color="primary.500"
										fontSize="xs"
										aria-label={__(
											'Edit in WooCommerce',
											'learning-management-system',
										)}
									>
										<Icon as={BiLinkExternal} ml="1" fontSize="xs" />
									</Link>
								)}
							</Text>
							{linkedProductPrice && (
								<Text fontSize="xs" color="gray.500">
									{__('Regular price:', 'learning-management-system')}{' '}
									<strong>{linkedProductPrice}</strong>
								</Text>
							)}
						</Box>
						<Button
							size="xs"
							colorScheme="red"
							variant="outline"
							isLoading={unlinkProductMutation.isPending}
							onClick={() => unlinkProductMutation.mutate()}
						>
							{__('Unlink', 'learning-management-system')}
						</Button>
					</Flex>
				</>
			);
		}

		const isLinkDisabled = isFree || courseId === 0;
		const linkDisabledTooltip = isFree
			? __(
					'Set a paid course price before linking a WooCommerce product.',
					'learning-management-system',
				)
			: __(
					'Save the course before linking a WooCommerce product.',
					'learning-management-system',
				);

		return (
			<>
				<FormLabel minW="160px">
					{__('Link Existing Product', 'learning-management-system')}
					<ToolTip
						label={__(
							'Search and link an existing WooCommerce product to this course.',
							'learning-management-system',
						)}
					/>
				</FormLabel>
				<Stack spacing={2} flex={1}>
					<Tooltip
						label={linkDisabledTooltip}
						isDisabled={!isLinkDisabled}
						hasArrow
						shouldWrapChildren
						placement="top"
					>
						<AsyncSelect
							placeholder={__(
								'Search WooCommerce products…',
								'learning-management-system',
							)}
							value={selectedOption}
							onChange={(option) =>
								setSelectedOption(option as WcProductOption | null)
							}
							loadOptions={loadProductOptions}
							defaultOptions={
								defaultProductsQuery.isSuccess ? defaultProductsQuery.data : []
							}
							isClearable
							isDisabled={isLinkDisabled}
							noOptionsMessage={() =>
								__('No products found', 'learning-management-system')
							}
							loadingMessage={() =>
								__('Searching…', 'learning-management-system')
							}
						/>
					</Tooltip>
					{selectedOption && !isLinkDisabled && (
						<ButtonGroup>
							<Button
								size="sm"
								colorScheme="primary"
								isLoading={linkProductMutation.isPending}
								onClick={handleLinkClick}
							>
								{__('Link Product', 'learning-management-system')}
							</Button>
						</ButtonGroup>
					)}
				</Stack>

				{/* Link & Convert confirmation modal */}
				<Modal
					isOpen={isLinkModalOpen}
					onClose={handleLinkModalClose}
					isCentered
					size="md"
				>
					<ModalOverlay />
					<ModalContent>
						<ModalHeader display="flex" alignItems="center" gap={2}>
							<Text
								as="span"
								className="dashicons dashicons-info"
								color="blue.500"
							/>
							{__('Convert & Link Product', 'learning-management-system')}
						</ModalHeader>
						<ModalCloseButton isDisabled={linkProductMutation.isPending} />
						<ModalBody>
							<Stack spacing={4}>
								<Text fontSize="sm" color="gray.600">
									{__(
										'To link this product to the course, its type will be changed to Masteriyo Course.',
										'learning-management-system',
									)}
								</Text>
								<Box
									bg="gray.50"
									border="1px solid"
									borderColor="gray.200"
									borderRadius="md"
									px={4}
									py={3}
								>
									<Text fontSize="sm" fontWeight="semibold">
										{selectedOption?.label}
									</Text>
									{selectedOption?.type_label && (
										<Text fontSize="xs" color="gray.500" mt={2}>
											{selectedOption.type_label}
											{' → '}
											<Text as="span" color="primary.600" fontWeight="medium">
												{targetTypeLabel}
											</Text>
										</Text>
									)}
								</Box>
								<Checkbox
									isChecked={linkConfirmed}
									onChange={(e) => setLinkConfirmed(e.target.checked)}
									colorScheme="primary"
									size="sm"
								>
									{__(
										'I understand the product type will change to ',
										'learning-management-system',
									)}
									<Text as="span" fontWeight="medium">
										{targetTypeLabel}
									</Text>
									.
								</Checkbox>
							</Stack>
						</ModalBody>
						<ModalFooter gap={3}>
							<Button
								size="sm"
								variant="ghost"
								onClick={handleLinkModalClose}
								isDisabled={linkProductMutation.isPending}
							>
								{__('Cancel', 'learning-management-system')}
							</Button>
							<Button
								size="sm"
								colorScheme="primary"
								isDisabled={!linkConfirmed}
								isLoading={linkProductMutation.isPending}
								onClick={() => linkProductMutation.mutate()}
							>
								{__('Convert & Link', 'learning-management-system')}
							</Button>
						</ModalFooter>
					</ModalContent>
				</Modal>
			</>
		);
	};

	return (
		<Stack direction="column" spacing="8">
			<FormControlTwoCol>{details(hasExistingProduct)}</FormControlTwoCol>

			<FormControlTwoCol>{linkSection()}</FormControlTwoCol>
		</Stack>
	);
};

export default WCIntegrationCourseSetting;
