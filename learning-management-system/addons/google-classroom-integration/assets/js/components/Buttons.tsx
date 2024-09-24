import {
	Button,
	ButtonGroup,
	IconButton,
	Link,
	Menu,
	MenuButton,
	MenuItem,
	MenuList,
	useDisclosure,
	useToast,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import { BiDotsVerticalRounded, BiShow, BiTrash } from 'react-icons/bi';
import { UseMutationResult, useMutation, useQueryClient } from 'react-query';
import ActionDialog from '../../../../../assets/js/back-end/components/common/ActionDialog';
import urls from '../../../../../assets/js/back-end/constants/urls';
import API from '../../../../../assets/js/back-end/utils/api';
import { googleClassroomCourses, newData } from '../GoogleClassroom';

interface GoogleClassroomUpdateCourse {
	id: string;
	google_classroom_course_id: string;
	name: string;
	google_course_url: string;
	google_classroom_enrollment_code: string;
	description: string;
	status: string;
}

type updateCourse = googleClassroomCourses;

function Buttons(props: {
	course: updateCourse;
	onImportClick: (data: any) => void;
	addCourse: UseMutationResult<any, unknown, newData, unknown>;
	courseKey: string;
	studentImportOnClick: (data: any) => void;
}) {
	const { course, onImportClick, addCourse, courseKey, studentImportOnClick } =
		props;
	const toast = useToast();
	const courseAPI = new API(urls.courses);
	const queryClient = useQueryClient();
	let courseId = Number(course.course_id) as number;
	const { onClose, onOpen, isOpen } = useDisclosure();
	const [deleteCourseId, setDeleteCourseId] = useState<number | undefined>(
		courseId,
	);

	const updateCourse = useMutation<GoogleClassroomUpdateCourse>(
		(data: any) => courseAPI.update(courseId, data),
		{
			onSuccess: (data: any) => {
				toast({
					title: __('Course Updated', 'learning-management-system'),
					status: 'success',
					isClosable: true,
				});
				queryClient.invalidateQueries(`googleClassroomCourseList`);
			},
		},
	);
	const updateNPublishCourse = (data: googleClassroomCourses) => {
		const newData: any = {
			google_classroom_course_id: data.id,
			name: data.name,
			google_course_url: data.alternateLink,
			google_classroom_enrollment_code: data.enrollmentCode,
			description: data.descriptionHeading,
			status: 'publish',
		};
		updateCourse.mutate(newData);
	};

	const deleteCourse = useMutation(
		(id: number) => courseAPI.delete(id, { force: true, children: true }),
		{
			onSuccess: () => {
				queryClient.invalidateQueries('googleClassroomCourseList');
				toast({
					title: __('Course Deleted', 'learning-management-system'),
					isClosable: true,
					status: 'success',
				});
			},
		},
	);
	const onDeletePress = (courseId?: number) => {
		onOpen();
		setDeleteCourseId(courseId);
	};

	const onDeleteConfirm = () => {
		deleteCourseId ? deleteCourse.mutate(deleteCourseId) : null;
	};

	return (
		<>
			{course.course_id && (
				<>
					<ButtonGroup>
						{course.course_status === 'draft' && (
							<Button
								onClick={() => updateNPublishCourse(course)}
								size="xs"
								variant="solid"
								colorScheme="primary"
								marginRight="36px"
								boxShadow="none"
								isLoading={updateCourse.isLoading}
							>
								{__('Publish', 'learning-management-system')}
							</Button>
						)}
						{course.course_status === 'publish' && (
							<Button
								size="xs"
								variant="solid"
								colorScheme="primary"
								marginRight="2"
								boxShadow="none"
								onClick={() => window.open(course.alternateLink, '_blank')}
							>
								{__('View Course', 'learning-management-system')}
							</Button>
						)}

						{course.course_id && (
							<ButtonGroup>
								<Menu placement="bottom-end">
									<MenuButton
										as={IconButton}
										icon={<BiDotsVerticalRounded />}
										variant="outline"
										rounded="sm"
										fontSize="large"
										size="xs"
									/>
									<MenuList>
										<>
											{course.course_status === 'publish' && (
												<>
													<MenuItem
														icon={<BiShow />}
														onClick={() => updateNPublishCourse(course)}
													>
														{__('Update Course', 'learning-management-system')}
													</MenuItem>
													<MenuItem
														icon={<BiShow />}
														onClick={() => studentImportOnClick(course)}
													>
														{__('Student Import', 'learning-management-system')}
													</MenuItem>
												</>
											)}
											<Link href={course.permalink} isExternal>
												<MenuItem icon={<BiShow />}>
													{__('Preview', 'learning-management-system')}
												</MenuItem>
											</Link>
											<MenuItem
												onClick={() => onDeletePress(courseId)}
												icon={<BiTrash />}
												_hover={{ color: 'red.500' }}
											>
												{__('Delete Permanently', 'learning-management-system')}
											</MenuItem>
										</>
									</MenuList>
								</Menu>
							</ButtonGroup>
						)}
					</ButtonGroup>
					<ActionDialog
						isOpen={isOpen}
						onClose={onClose}
						onConfirm={() => onDeleteConfirm()}
						isLoading={deleteCourse.isLoading}
						dialogTexts={{
							default: {
								header: __('Deleting course', 'learning-management-system'),
								body: __(
									'Are you sure? You can’t restore after deleting.',
									'learning-management-system',
								),
								confirm: __('Delete', 'learning-management-system'),
							},
							delete: {
								header: __('Deleting Courses', 'learning-management-system'),
								body: __('Are you sure? You can’t restore after deleting.'),
								confirm: __('Delete', 'learning-management-system'),
							},
						}}
					/>
				</>
			)}
		</>
	);
}

export default Buttons;
