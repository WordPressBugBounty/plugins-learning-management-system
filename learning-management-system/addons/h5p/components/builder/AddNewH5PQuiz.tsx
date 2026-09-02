import {
	Badge,
	Box,
	Button,
	ButtonGroup,
	Center,
	Container,
	Heading,
	HStack,
	Icon,
	IconButton,
	Stack,
	Tab,
	TabList,
	TabPanel,
	TabPanels,
	Tabs,
	Text,
	Tooltip,
	useDisclosure,
	useToast,
} from '@chakra-ui/react';
import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import { FormProvider, useForm } from 'react-hook-form';
import { BiLinkExternal } from 'react-icons/bi';
import { useNavigate, useParams } from 'react-router-dom';
import NoQuestion from '../../../../assets/img/svgs/no-question.svg';
import AddNewButton from '../../../../assets/js/back-end/components/common/AddNewButton';
import BackToBuilder from '../../../../assets/js/back-end/components/common/BackToBuilder';
import BuilderHeader from '../../../../assets/js/back-end/components/common/BuilderHeader';
import CourseName from '../../../../assets/js/back-end/components/common/CourseName';
import {
	Sortable,
	Trash,
} from '../../../../assets/js/back-end/constants/images';
import routes from '../../../../assets/js/back-end/constants/routes';
import urls from '../../../../assets/js/back-end/constants/urls';
import { SectionSchema } from '../../../../assets/js/back-end/schemas';
import Description from '../../../../assets/js/back-end/screens/quiz/components/Description';
import Name from '../../../../assets/js/back-end/screens/quiz/components/Name';
import QuizSKeleton from '../../../../assets/js/back-end/skeleton/QuizSkeleton';
import { CourseDataMap } from '../../../../assets/js/back-end/types/course';
import API from '../../../../assets/js/back-end/utils/api';
import {
	addContentToBuilderCache,
	deepClean,
} from '../../../../assets/js/back-end/utils/utils';
import H5P_BADGE_COLOR from '../../constants/badgeColors';
import h5pRoutes from '../../constants/routes';
import h5pUrls from '../../constants/urls';
import TruncatedText from '../TruncatedText';
import H5PContentPicker, { H5PContent } from './H5PContentPicker';
import H5PQuizSettings from './H5PQuizSettings';

const tabStyles = {
	fontWeight: 'medium',
	py: '4',
};

const AddNewH5PQuiz = () => {
	const { courseId, sectionId }: any = useParams();
	const navigate = useNavigate();
	const toast = useToast();
	const queryClient = useQueryClient();
	const methods = useForm();
	const h5pQuizAPI = new API(h5pUrls.h5pQuizzes);

	const {
		isOpen: isPickerOpen,
		onOpen: openPicker,
		onClose: closePicker,
	} = useDisclosure();

	const [selectedQuestions, setSelectedQuestions] = useState<H5PContent[]>([]);

	const sectionAPI = new API(urls.sections);
	const courseAPI = new API(urls.courses);

	const sectionQuery = useQuery<SectionSchema>({
		queryKey: [`section${sectionId}`, sectionId],
		queryFn: () => sectionAPI.get(sectionId),
	});

	const courseQuery = useQuery<CourseDataMap>({
		queryKey: [`course${courseId}`, courseId],
		queryFn: () => courseAPI.get(courseId, 'edit'),
	});

	const addH5PQuiz = useMutation({
		mutationFn: (data: any) => h5pQuizAPI.store(data),
	});

	const onSubmit = (data: any, status?: string) => {
		const payload = {
			...deepClean(data),
			course_id: Number(courseId),
			parent_id: Number(sectionId),
			h5p_content_ids: selectedQuestions.map((q) => q.id),
			duration:
				(Number(data.duration_hour) || 0) * 60 +
				(Number(data.duration_minute) || 0),
			questions_display_per_page:
				data.questions_display_per_page_mode === '0'
					? 0
					: Number(data.questions_display_per_page_custom) || 5,
			status: status || 'draft',
		};

		addH5PQuiz.mutate(payload, {
			onSuccess: (data: any) => {
				addContentToBuilderCache(
					queryClient,
					[`builder${courseId}`, courseId],
					data,
					'h5p-quiz',
				);
				toast({
					title: __('H5P Quiz added.', 'learning-management-system'),
					status: 'success',
					isClosable: true,
				});
				navigate({
					pathname: h5pRoutes.h5pQuiz.builder.edit
						.replace(':courseId', courseId)
						.replace(':h5pQuizId', String(data.id)),
				});
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

	if (
		!sectionQuery.isSuccess ||
		!courseQuery.isSuccess ||
		sectionQuery.data?.course_id != courseId
	) {
		return <QuizSKeleton />;
	}

	return (
		<FormProvider {...methods}>
			<Stack direction="column" spacing="8" alignItems="center">
				<BuilderHeader
					onSaveAction={(status) =>
						methods.handleSubmit((data) => onSubmit(data, status))
					}
					previewLink=""
					isLoading={addH5PQuiz.isPending}
				/>
				<Container maxW="container.xl">
					<Stack direction="column" spacing="6">
						<BackToBuilder />
						<CourseName
							courseName={courseQuery?.data?.name}
							courseLink={routes.courses.edit.replace(':courseId', courseId)}
						/>
						<Box bg="white" p="10" shadow="box">
							<Stack direction="column" spacing="8">
								<form onSubmit={methods.handleSubmit((data) => onSubmit(data))}>
									<Stack direction="column" spacing="6">
										<Tabs>
											<TabList
												justifyContent="center"
												borderBottom="1px"
												borderColor="gray.100"
											>
												<Tab sx={tabStyles}>
													{__('Info', 'learning-management-system')}
												</Tab>
												<Tab sx={tabStyles}>
													{__('Questions', 'learning-management-system')}
												</Tab>
												<Tab sx={tabStyles}>
													{__('Settings', 'learning-management-system')}
												</Tab>
											</TabList>
											<TabPanels>
												{/* Info */}
												<TabPanel px="0">
													<Stack direction="column" spacing="6">
														<Name />
														<Description />
													</Stack>
												</TabPanel>

												{/* Questions */}
												<TabPanel px="0">
													<Stack direction="column" spacing="4">
														{selectedQuestions.length === 0 && (
															<Stack
																direction="column"
																alignItems="center"
																gap="4"
															>
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

														{selectedQuestions.map((q, index) => (
															<Box
																key={q.id}
																role="group"
																borderWidth="1px"
																borderColor="gray.100"
																rounded="sm"
																bg="white"
																p="0"
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
																		cursor="pointer"
																	>
																		<Center>
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
																					H5P_BADGE_COLOR[q.library_name] ??
																					'gray'
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
																			flex="1"
																			fontSize="sm"
																			px="0"
																			py="1"
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
														))}

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
												</TabPanel>

												{/* Settings */}
												<TabPanel px="0">
													<H5PQuizSettings />
												</TabPanel>
											</TabPanels>
										</Tabs>

										<ButtonGroup>
											<Button
												colorScheme="primary"
												type="submit"
												isLoading={addH5PQuiz.isPending}
											>
												{__('Add H5P Quiz', 'learning-management-system')}
											</Button>
											<Button
												variant="outline"
												onClick={() =>
													navigate({
														pathname: routes.courses.edit.replace(
															':courseId',
															courseId,
														),
														search: '?page=builder',
													})
												}
											>
												{__('Cancel', 'learning-management-system')}
											</Button>
										</ButtonGroup>
									</Stack>
								</form>
							</Stack>
						</Box>
					</Stack>
				</Container>
			</Stack>
		</FormProvider>
	);
};

export default AddNewH5PQuiz;
