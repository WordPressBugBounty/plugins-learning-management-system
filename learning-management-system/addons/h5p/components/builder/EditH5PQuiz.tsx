import {
	Badge,
	Box,
	Button,
	ButtonGroup,
	Center,
	Container,
	Divider,
	Heading,
	HStack,
	Icon,
	IconButton,
	Skeleton,
	Stack,
	Text,
	Tooltip,
	useBreakpointValue,
	useDisclosure,
	useToast,
	VStack,
} from '@chakra-ui/react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import queryString from 'query-string';
import React, { useEffect, useState } from 'react';
import {
	DragDropContext,
	Draggable,
	Droppable,
	DropResult,
} from 'react-beautiful-dnd';
import { FormProvider, useForm, useWatch } from 'react-hook-form';
import { useLocation, useNavigate, useParams } from 'react-router-dom';
import NoQuestion from '../../../../assets/img/svgs/no-question.svg';
import { Sortable } from '../../../../assets/js/back-end/assets/icons';
import AddNewButton from '../../../../assets/js/back-end/components/common/AddNewButton';
import BackToBuilder from '../../../../assets/js/back-end/components/common/BackToBuilder';
import BuilderHeader from '../../../../assets/js/back-end/components/common/BuilderHeader';
import QuizBuilderTabs, {
	defaultPageIndex,
} from '../../../../assets/js/back-end/components/common/QuizBuilderTabs';
import { Trash } from '../../../../assets/js/back-end/constants/images';
import routes from '../../../../assets/js/back-end/constants/routes';
import urls from '../../../../assets/js/back-end/constants/urls';

import { BiLinkExternal } from 'react-icons/bi';
import Description from '../../../../assets/js/back-end/screens/quiz/components/Description';
import Name from '../../../../assets/js/back-end/screens/quiz/components/Name';
import QuizSKeleton from '../../../../assets/js/back-end/skeleton/QuizSkeleton';
import { CourseDataMap } from '../../../../assets/js/back-end/types/course';
import API from '../../../../assets/js/back-end/utils/api';
import { convertMinutesToHours } from '../../../../assets/js/back-end/utils/math';
import {
	deepClean,
	editContentInBuilderCache,
} from '../../../../assets/js/back-end/utils/utils';
import H5P_BADGE_COLOR from '../../constants/badgeColors';
import h5pRoutes from '../../constants/routes';
import h5pUrls from '../../constants/urls';
import TruncatedText from '../TruncatedText';
import H5PContentPicker, { H5PContent } from './H5PContentPicker';
import H5PQuizSettings from './H5PQuizSettings';

const EditH5PQuiz = () => {
	const { courseId, h5pQuizId }: any = useParams();
	const { search } = useLocation();
	const { page } = queryString.parse(search);

	const navigate = useNavigate();
	const buttonSize = useBreakpointValue(['sm', 'md']);
	const toast = useToast();
	const queryClient = useQueryClient();
	const methods = useForm();
	const h5pQuizAPI = new API(h5pUrls.h5pQuizzes);
	const courseAPI = new API(urls.courses);

	const activeStep = useWatch({
		control: methods.control,
		name: 'activeQuizStep',
		defaultValue: defaultPageIndex(page as string),
	}) as number;

	const {
		isOpen: isPickerOpen,
		onOpen: openPicker,
		onClose: closePicker,
	} = useDisclosure();

	const [selectedQuestions, setSelectedQuestions] = useState<H5PContent[]>([]);

	const courseQuery = useQuery<CourseDataMap>({
		queryKey: [`course${courseId}`, courseId],
		queryFn: () => courseAPI.get(courseId, 'edit'),
	});

	const h5pQuizQuery = useQuery<any>({
		queryKey: [`h5pQuiz${h5pQuizId}`, h5pQuizId],
		queryFn: () => h5pQuizAPI.get(h5pQuizId, 'edit'),
	});

	const updateH5PQuiz = useMutation({
		mutationFn: (data: any) => h5pQuizAPI.update(h5pQuizId, data),
	});

	useEffect(() => {
		if (h5pQuizQuery?.isSuccess && h5pQuizQuery?.data) {
			// Rebuild the split fields the form binds to, else they revert on save.
			const [durationHour, durationMinute] = convertMinutesToHours(
				h5pQuizQuery.data.duration || 0,
			);
			const perPage = h5pQuizQuery.data.questions_display_per_page;

			methods.reset({
				...h5pQuizQuery.data,
				duration_hour: durationHour,
				duration_minute: durationMinute,
				questions_display_per_page_mode: perPage ? '1' : '0',
				questions_display_per_page_custom: perPage || 5,
				activeQuizStep:
					methods.getValues('activeQuizStep') ??
					defaultPageIndex(page as string),
			});
			const ids: number[] = Array.isArray(h5pQuizQuery.data.h5p_content_ids)
				? h5pQuizQuery.data.h5p_content_ids
				: h5pQuizQuery.data.h5p_content_id
					? [h5pQuizQuery.data.h5p_content_id]
					: [];
			setSelectedQuestions(
				ids.map((id: number, i: number) => ({
					id,
					title:
						h5pQuizQuery.data.h5p_content_titles?.[i] || `Content ID ${id}`,
					content_type:
						h5pQuizQuery.data.h5p_content_library_names?.[i] ||
						h5pQuizQuery.data.h5p_library_name ||
						'',
					library_name:
						h5pQuizQuery.data.h5p_content_machine_names?.[i] ||
						h5pQuizQuery.data.h5p_library_name ||
						'',
					created_at: '',
					view_url: h5pQuizQuery.data.h5p_content_view_urls?.[i] || '',
				})),
			);
		}
	}, [h5pQuizQuery?.data, h5pQuizQuery?.isSuccess]);

	const onSubmit = (data: any, status?: 'draft' | 'publish') => {
		const payload = {
			...deepClean(data),
			h5p_content_ids: selectedQuestions.map((q) => q.id),
			duration:
				(Number(data.duration_hour) || 0) * 60 +
				(Number(data.duration_minute) || 0),
			questions_display_per_page:
				data.questions_display_per_page_mode === '0'
					? 0
					: Number(data.questions_display_per_page_custom) || 5,
			status: status || data.status,
		};

		updateH5PQuiz.mutate(payload, {
			onSuccess: (data: any) => {
				editContentInBuilderCache(
					queryClient,
					[`builder${courseId}`, courseId],
					data,
				);
				queryClient.invalidateQueries({ queryKey: [`h5pQuiz${h5pQuizId}`] });
				toast({
					title: __('H5P Quiz Updated', 'learning-management-system'),
					status: 'success',
					isClosable: true,
				});
				courseQuery.refetch();
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
		});
	};

	const addQuestions = (items: H5PContent[]) => {
		setSelectedQuestions((prev) => {
			const existingIds = new Set(prev.map((q) => q.id));
			return [...prev, ...items.filter((i) => !existingIds.has(i.id))];
		});
	};

	const onDragEnd = (result: DropResult) => {
		if (!result.destination) return;
		if (result.destination.index === result.source.index) return;
		const items = Array.from(selectedQuestions);
		const [moved] = items.splice(result.source.index, 1);
		items.splice(result.destination.index, 0, moved);
		setSelectedQuestions(items);
	};

	if (!h5pQuizQuery.isSuccess || !courseQuery.isSuccess) {
		return <QuizSKeleton />;
	}

	return (
		<FormProvider {...methods}>
			<Stack direction="column" spacing="8" alignItems="center">
				<BuilderHeader
					onSaveAction={(status) =>
						methods.handleSubmit((data) => onSubmit({ ...data, status }))
					}
					previewLink={h5pQuizQuery?.data?.preview_link || ''}
					isLoading={updateH5PQuiz.isPending}
					status={h5pQuizQuery?.data?.status}
				/>

				<Container maxW="container.xl">
					<Stack direction="column" spacing="6">
						<BackToBuilder />

						<Box bg="white" p="10" shadow="box">
							<QuizBuilderTabs
								quizId={h5pQuizId}
								onStepChange={(idx) =>
									navigate(
										h5pRoutes.h5pQuiz.builder.edit
											.replace(':courseId', courseId)
											.replace(':h5pQuizId', h5pQuizId) +
											(idx === 1 ? '?page=settings' : ''),
									)
								}
							/>

							<form onSubmit={methods.handleSubmit((data) => onSubmit(data))}>
								<Stack direction="column" spacing="6">
									{/* Content tab — info fields and the question list. */}
									<Box hidden={activeStep !== 0}>
										<Stack direction="column" spacing="6">
											<Name defaultValue={h5pQuizQuery?.data?.name} />
											<Description
												defaultValue={h5pQuizQuery?.data?.description}
												QuizName={h5pQuizQuery?.data?.name}
											/>
										</Stack>
									</Box>

									<Box hidden={activeStep !== 0}>
										<Stack direction="column" spacing="4">
											<Heading
												as="h3"
												fontSize="sm"
												fontWeight="semibold"
												color="gray.600"
											>
												{__('Quiz Questions', 'learning-management-system')}
											</Heading>
											{h5pQuizQuery.isLoading && (
												<VStack px={4} spacing={2}>
													{[1, 2, 3].map((key) => (
														<Box
															key={key}
															borderWidth="1px"
															borderRadius="base"
															borderColor="gray.200"
															px={4}
															py={6}
															w="100%"
														>
															<HStack spacing={4} align="center">
																<Skeleton boxSize="6" />
																<Skeleton height="20px" flex="1" />
																<Skeleton height="16px" width="24" />
															</HStack>
														</Box>
													))}
												</VStack>
											)}

											{!h5pQuizQuery.isLoading &&
												selectedQuestions.length === 0 && (
													<Stack direction="column" alignItems="center" gap="4">
														<NoQuestion />
														<Stack direction="column" gap="14px">
															<Heading
																fontSize="2xl"
																color="charcoal-gray"
																fontWeight="semibold"
															>
																{__(
																	'No Questions Found',
																	'learning-management-system',
																)}
															</Heading>
															<Text
																color="charcoal-gray"
																fontSize="md"
																fontWeight="normal"
															>
																{__(
																	'Add new question to add your content',
																	'learning-management-system',
																)}
															</Text>
														</Stack>
													</Stack>
												)}

											<DragDropContext onDragEnd={onDragEnd}>
												<Droppable droppableId="h5p-questions">
													{(provided) => (
														<Box
															ref={provided.innerRef}
															{...provided.droppableProps}
														>
															{selectedQuestions.map((q, index) => (
																<Draggable
																	key={q.id}
																	draggableId={String(q.id)}
																	index={index}
																>
																	{(dragProvided) => (
																		<Box
																			ref={dragProvided.innerRef}
																			{...dragProvided.draggableProps}
																			role="group"
																			borderWidth="1px"
																			borderColor="gray.100"
																			rounded="sm"
																			bg="white"
																			p="0"
																			mb="1"
																			_hover={{ borderColor: 'primary.500' }}
																		>
																			<Stack
																				direction="row"
																				px="2"
																				py="1.5"
																				align="center"
																			>
																				<Stack
																					direction="row"
																					spacing="2"
																					align="center"
																					flex="1"
																					minW={0}
																				>
																					<Center
																						{...dragProvided.dragHandleProps}
																					>
																						<Icon
																							fontSize="lg"
																							color="gray.500"
																							as={Sortable}
																						/>
																					</Center>
																					<Text
																						color="gray.400"
																						fontSize="xs"
																						fontWeight="bold"
																					>
																						{index + 1}
																					</Text>
																					{q.content_type && (
																						<Badge
																							colorScheme={
																								H5P_BADGE_COLOR[
																									q.library_name
																								] ?? 'gray'
																							}
																							variant="subtle"
																							fontSize="2xs"
																							flexShrink={0}
																							textTransform="uppercase"
																						>
																							{q.content_type}
																						</Badge>
																					)}
																					<TruncatedText
																						label={q.title}
																						fontSize="sm"
																						px="0"
																						py="1"
																						flex="1"
																						minW={0}
																					/>
																				</Stack>
																				<Stack
																					direction="row"
																					spacing="2"
																					opacity="0"
																					_groupHover={{ opacity: 1 }}
																					flexShrink={0}
																				>
																					{q.view_url && (
																						<Tooltip
																							label={__(
																								'Open in H5P',
																								'learning-management-system',
																							)}
																						>
																							<IconButton
																								as="a"
																								href={q.view_url}
																								target="_blank"
																								rel="noopener noreferrer"
																								variant="icon"
																								_hover={{ color: 'blue.500' }}
																								aria-label={__(
																									'Open in H5P (opens in new tab)',
																									'learning-management-system',
																								)}
																								icon={
																									<BiLinkExternal fontSize="14px" />
																								}
																								minW="auto"
																							/>
																						</Tooltip>
																					)}
																					<Tooltip
																						label={__(
																							'Delete',
																							'learning-management-system',
																						)}
																					>
																						<IconButton
																							variant="icon"
																							colorScheme="red"
																							_hover={{ color: 'red.500' }}
																							aria-label={__(
																								'Delete',
																								'learning-management-system',
																							)}
																							icon={
																								<Trash
																									width="12px"
																									height="12px"
																									fill="currentColor"
																								/>
																							}
																							minW="auto"
																							onClick={() =>
																								setSelectedQuestions((prev) =>
																									prev.filter(
																										(item) => item.id !== q.id,
																									),
																								)
																							}
																						/>
																					</Tooltip>
																				</Stack>
																			</Stack>
																		</Box>
																	)}
																</Draggable>
															))}
															{provided.placeholder}
														</Box>
													)}
												</Droppable>
											</DragDropContext>

											<Center px="5" mt={2}>
												<HStack spacing={2}>
													<Button
														as={AddNewButton}
														bg="frosted-sky"
														border="none"
														borderRadius="base"
														colorScheme="primary"
														variant="outline"
														fontSize="xs"
														onClick={openPicker}
														_hover={{
															textDecoration: 'none',
															bg: 'frosted-sky-lighter',
														}}
													>
														{__(
															'Add H5P Question',
															'learning-management-system',
														)}
													</Button>
												</HStack>
											</Center>

											<H5PContentPicker
												isOpen={isPickerOpen}
												onClose={closePicker}
												onAdd={addQuestions}
												excludeIds={selectedQuestions.map((q) => q.id)}
											/>
										</Stack>
									</Box>

									{/* Quiz Settings tab */}
									<Box hidden={activeStep !== 1}>
										<H5PQuizSettings defaultValues={h5pQuizQuery?.data} />
									</Box>

									<Box py="3">
										<Divider />
									</Box>

									<ButtonGroup>
										<Button
											variant="outline"
											colorScheme="primary"
											isLoading={updateH5PQuiz.isPending}
											onClick={methods.handleSubmit((data) =>
												onSubmit(data, 'draft'),
											)}
										>
											{h5pQuizQuery?.data?.status === 'publish'
												? __('Switch To Draft', 'learning-management-system')
												: __('Save To Draft', 'learning-management-system')}
										</Button>
										<Button
											size={buttonSize}
											colorScheme="primary"
											isLoading={updateH5PQuiz.isPending}
											onClick={methods.handleSubmit((data) =>
												onSubmit(data, 'publish'),
											)}
										>
											{h5pQuizQuery?.data?.status === 'publish'
												? __('Update Quiz', 'learning-management-system')
												: __('Publish Quiz', 'learning-management-system')}
										</Button>
										<Button
											variant="outline"
											onClick={() =>
												navigate(
													routes.courses.edit.replace(':courseId', courseId),
												)
											}
										>
											{__('Cancel', 'learning-management-system')}
										</Button>
									</ButtonGroup>
								</Stack>
							</form>
						</Box>
					</Stack>
				</Container>
			</Stack>
		</FormProvider>
	);
};

export default EditH5PQuiz;
