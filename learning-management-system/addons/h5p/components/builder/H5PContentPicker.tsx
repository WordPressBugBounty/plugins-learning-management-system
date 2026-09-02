import {
	Badge,
	Box,
	Button,
	ButtonGroup,
	Center,
	Checkbox,
	Flex,
	FormControl,
	IconButton,
	Input,
	InputGroup,
	InputRightElement,
	Modal,
	ModalBody,
	ModalCloseButton,
	ModalContent,
	ModalFooter,
	ModalHeader,
	ModalOverlay,
	SimpleGrid,
	Spinner,
	Stack,
	Text,
	Tooltip,
	useToast,
} from '@chakra-ui/react';
import { useInfiniteQuery, useQuery } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React, {
	useCallback,
	useEffect,
	useMemo,
	useRef,
	useState,
} from 'react';
import { Controller, FormProvider, useForm, useWatch } from 'react-hook-form';
import { BiLinkExternal, BiPlus } from 'react-icons/bi';
import { MdClear } from 'react-icons/md';
import { PiPlus } from 'react-icons/pi';
import Select from '../../../../assets/js/back-end/components/common/Select';
import { VirtualizedContainer } from '../../../../assets/js/back-end/components/common/VirtualizedContainer';
import { SkeletonQuestionsList } from '../../../../assets/js/back-end/skeleton';
import API from '../../../../assets/js/back-end/utils/api';
import { isEmpty } from '../../../../assets/js/back-end/utils/utils';
import H5P_BADGE_COLOR from '../../constants/badgeColors';
import h5pUrls from '../../constants/urls';
import TruncatedText from '../TruncatedText';

export interface H5PContent {
	id: number;
	title: string;
	content_type: string;
	library_name: string;
	created_at: string;
	view_url?: string;
}

interface H5PContentListResponse {
	data: H5PContent[];
	meta: {
		total: number;
		pages: number;
		current_page: number;
		per_page: number;
	};
}

interface FilterForm {
	search: string;
	typeFilter: string;
}

interface Props {
	isOpen: boolean;
	onClose: () => void;
	onAdd: (items: H5PContent[]) => void;
	excludeIds?: number[];
}

const h5pContentsAPI = new API(h5pUrls.h5pContents);
const h5pContentTypesAPI = new API(h5pUrls.h5pContentTypes);

const H5PContentPicker: React.FC<Props> = ({
	isOpen,
	onClose,
	onAdd,
	excludeIds = [],
}) => {
	const [bulkIds, setBulkIds] = useState<string[]>([]);
	const containerRef = useRef<HTMLDivElement>(null);
	const toast = useToast();

	const methods = useForm<FilterForm>({
		defaultValues: { search: '', typeFilter: 'all' },
	});
	const { control, register, setValue } = methods;

	const search = useWatch({ control, name: 'search' });
	const typeFilter = useWatch({ control, name: 'typeFilter' });

	// Fetch installed H5P content types from the H5P libraries table — the single source of truth, no hardcoded list.
	const typesQuery = useQuery<{ name: string; title: string }[]>({
		queryKey: ['h5pContentTypes'],
		queryFn: () => h5pContentTypesAPI.list({}),
		staleTime: 5 * 60 * 1000,
	});

	const contentTypeOptions = useMemo(() => {
		const types = typesQuery.data ?? [];
		return types.map((t) => ({ label: t.title, value: t.name }));
	}, [typesQuery.data]);

	useEffect(() => {
		if (!isOpen) {
			setBulkIds([]);
			setValue('search', '');
			setValue('typeFilter', 'all');
		}
	}, [isOpen, setValue]);

	// Stable key for the exclude list so the query refetches when the quiz's questions change.
	const excludeKey = useMemo(
		() => [...excludeIds].sort((a, b) => a - b).join(','),
		[excludeIds],
	);

	const contentsQuery = useInfiniteQuery({
		queryKey: ['h5pContents', search?.trim(), typeFilter, excludeKey],
		queryFn: ({ pageParam = 1 }) => {
			const params: Record<string, any> = {
				per_page: 20,
				page: pageParam,
			};
			if (search) params.search = search;
			if (typeFilter !== 'all') params.type = typeFilter;
			if (excludeIds.length) params.exclude = excludeIds;
			return h5pContentsAPI.list(params);
		},
		enabled: isOpen,
		initialPageParam: 1,
		staleTime: 60 * 1000,
		getNextPageParam: (lastResponse: H5PContentListResponse) => {
			if (!lastResponse?.meta) return undefined;
			return lastResponse.meta.current_page >= lastResponse.meta.pages
				? undefined
				: lastResponse.meta.current_page + 1;
		},
	});

	useEffect(() => {
		if (contentsQuery.isError) {
			toast({
				title: __('Failed to fetch H5P content.', 'learning-management-system'),
				status: 'error',
				isClosable: true,
			});
		}
	}, [contentsQuery.isError, toast]);

	const allItems: H5PContent[] = useMemo(() => {
		const pages = contentsQuery.data?.pages;
		if (!pages) return [];
		return pages.flatMap((page: H5PContentListResponse) => page.data ?? []);
	}, [contentsQuery.data]);

	const hasNextPage = contentsQuery.hasNextPage;
	const isFetchingNextPage = contentsQuery.isFetchingNextPage;

	const handleAddImmediate = useCallback(
		(item: H5PContent) => {
			onAdd([item]);
			onClose();
		},
		[onAdd, onClose],
	);

	const handleAddSelected = () => {
		const selectedItems = allItems.filter((i) =>
			bulkIds.includes(String(i.id)),
		);
		if (selectedItems.length > 0) {
			onAdd(selectedItems);
			onClose();
		}
	};

	const getItemHeight = useCallback(() => 52, []);

	const renderItem = useCallback(
		(index: number) => {
			const item = allItems[index];
			if (!item) return null;
			const isChecked = bulkIds.includes(String(item.id));
			return (
				<Box
					key={item.id}
					borderTopWidth={index === 0 ? 0 : 1}
					borderTopColor="gray.100"
					pb={1}
				>
					<Stack
						direction="row"
						px="2"
						pb="1.5"
						pt="1"
						align="center"
						_hover={{ bg: 'gray.50' }}
					>
						<Stack direction="row" spacing="2" align="center" flex="1" minW={0}>
							<Center mx={1}>
								<Checkbox
									isChecked={isChecked}
									onChange={(e) => {
										const id = String(item.id);
										setBulkIds((prev) => {
											const set = new Set(prev);
											if (e.target.checked) set.add(id);
											else set.delete(id);
											return Array.from(set);
										});
									}}
								/>
							</Center>
							<Badge
								colorScheme={H5P_BADGE_COLOR[item.library_name] ?? 'gray'}
								fontSize="xs"
								px={2}
								py={0.5}
								borderRadius="sm"
								flexShrink={0}
								textTransform="uppercase"
							>
								{item.content_type || item.library_name}
							</Badge>
							<TruncatedText
								label={item.title}
								px="0"
								py="1"
								fontSize="sm"
								flex="1"
								minW={0}
							/>
						</Stack>
						<Stack direction="row" spacing="4" flexShrink={0} align="center">
							{item.view_url && (
								<Tooltip
									label={__('Open in H5P', 'learning-management-system')}
								>
									<IconButton
										as="a"
										href={item.view_url}
										target="_blank"
										rel="noopener noreferrer"
										_hover={{ color: 'blue.500' }}
										variant="icon"
										aria-label={__(
											'Open in H5P (opens in new tab)',
											'learning-management-system',
										)}
										icon={<BiLinkExternal fontSize="16px" />}
										minW="auto"
									/>
								</Tooltip>
							)}
							<Tooltip label={__('Add to Quiz', 'learning-management-system')}>
								<IconButton
									_hover={{ color: 'blue.500' }}
									variant="icon"
									aria-label={__('Add', 'learning-management-system')}
									icon={<BiPlus fontSize="20px" />}
									minW="auto"
									onClick={() => handleAddImmediate(item)}
								/>
							</Tooltip>
						</Stack>
					</Stack>
				</Box>
			);
		},
		[allItems, bulkIds, handleAddImmediate],
	);

	return (
		<Modal
			isOpen={isOpen}
			onClose={onClose}
			size="4xl"
			isCentered
			closeOnOverlayClick={false}
			scrollBehavior="outside"
		>
			<ModalOverlay />
			<ModalContent maxH="100vh">
				<ModalHeader borderBottom="1px" borderColor="gray.200">
					{__('H5P Question Bank', 'learning-management-system')}
				</ModalHeader>
				<ModalCloseButton />

				<ModalBody px={6} overflow="hidden">
					<FormProvider {...methods}>
						{/* Filter bar — identical structure to QuestionBank */}
						<SimpleGrid
							templateColumns={{ base: '1fr', md: '2fr 1fr' }}
							spacing={4}
							py={4}
							position="sticky"
							top={0}
							zIndex={10}
							bg="white"
							boxShadow="sm"
							transition="box-shadow 0.2s ease-in-out"
						>
							<FormControl w="100%">
								<InputGroup>
									<Tooltip
										label={__('Select All', 'learning-management-system')}
									>
										<Flex p={2} mr={2} align="center" justify="center">
											<Checkbox
												isDisabled={contentsQuery.isLoading || !allItems.length}
												isIndeterminate={
													allItems.length > 0 &&
													bulkIds.length > 0 &&
													bulkIds.length < allItems.length
												}
												isChecked={
													allItems.length > 0 &&
													bulkIds.length === allItems.length
												}
												onChange={(e) => {
													setBulkIds(
														e.target.checked
															? allItems.map((i) => String(i.id))
															: [],
													);
												}}
											/>
										</Flex>
									</Tooltip>
									{search && (
										<InputRightElement>
											<MdClear
												cursor="pointer"
												onClick={() => setValue('search', '')}
											/>
										</InputRightElement>
									)}
									<Controller
										name="search"
										control={control}
										render={() => (
											<Input
												{...register('search')}
												placeholder={__(
													'Search Questions…',
													'learning-management-system',
												)}
												bg="white"
												autoFocus
											/>
										)}
									/>
								</InputGroup>
							</FormControl>

							<FormControl w="100%">
								<Controller
									name="typeFilter"
									control={control}
									render={({ field: { onChange, value } }) => (
										<Select
											placeholder={__(
												'Question Types',
												'learning-management-system',
											)}
											onChange={(option: any) =>
												onChange(option?.value ?? 'all')
											}
											value={
												contentTypeOptions.find((t) => t.value === value) ??
												null
											}
											isClearable
											isLoading={typesQuery.isLoading}
											options={contentTypeOptions}
											menuPortalTarget={
												typeof document !== 'undefined'
													? document.body
													: undefined
											}
											menuPosition="fixed"
											styles={{
												menuPortal: (base: any) => ({
													...base,
													zIndex: 9999,
												}),
											}}
										/>
									)}
								/>
							</FormControl>
						</SimpleGrid>

						{/* List */}
						{contentsQuery.isLoading ? (
							<SkeletonQuestionsList no_of_items={5} />
						) : isEmpty(allItems) ? (
							<Center py={10}>
								<Text fontSize="sm" color="gray.500">
									{search
										? __(
												'No questions found for this search.',
												'learning-management-system',
											)
										: __(
												'No H5P question content found.',
												'learning-management-system',
											)}
								</Text>
							</Center>
						) : (
							<VirtualizedContainer
								itemCount={allItems.length}
								getItemHeight={getItemHeight}
								renderItem={renderItem}
								containerRef={containerRef}
								useParentScroll={false}
								height="60vh"
								overflowY="auto"
								isLoading={contentsQuery.isInitialLoading}
								customLoader={<SkeletonQuestionsList />}
								onLoadMore={() => {
									if (hasNextPage && !isFetchingNextPage) {
										contentsQuery.fetchNextPage();
									}
								}}
								border="gray.100"
								borderWidth={1}
								borderRadius="md"
								position="relative"
							/>
						)}

						{isFetchingNextPage && (
							<Flex
								position="absolute"
								bottom="60px"
								left="50%"
								transform="translateX(-50%)"
								zIndex={2}
								p={2}
								bg="whiteAlpha.800"
								borderRadius="md"
								boxShadow="md"
							>
								<Spinner />
							</Flex>
						)}

						<ModalFooter padding={4} justifyContent="space-between">
							<Text fontSize="sm" color="gray.500">
								{bulkIds.length}
								{__(' items selected', 'learning-management-system')}
							</Text>
							<ButtonGroup gap={2}>
								<Button variant="outline" onClick={onClose}>
									{__('Cancel', 'learning-management-system')}
								</Button>
								<Button
									leftIcon={<PiPlus size={15} />}
									colorScheme="primary"
									isDisabled={isEmpty(allItems) || isEmpty(bulkIds)}
									onClick={handleAddSelected}
								>
									{__('Add Selected', 'learning-management-system')}
								</Button>
							</ButtonGroup>
						</ModalFooter>
					</FormProvider>
				</ModalBody>
			</ModalContent>
		</Modal>
	);
};

export default H5PContentPicker;
