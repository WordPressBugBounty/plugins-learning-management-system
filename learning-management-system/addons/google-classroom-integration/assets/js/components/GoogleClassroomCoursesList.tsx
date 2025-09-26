import { UseMutationResult } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Table, Tbody, Th, Thead, Tr } from 'react-super-responsive-table';
import EmptyInfo from '../../../../../assets/js/back-end/components/common/EmptyInfo';
import { googleClassroomCoursesList, newData } from '../GoogleClassroom';
import GoogleClassroomCoursesRow from './GoogleClassroomCoursesRow';

interface Props {
	googleClassroomQueryData?: googleClassroomCoursesList;
	onImportClick: (data: any) => void;
	studentImportOnClick: (data: any) => void;
	addCourse: UseMutationResult<any, unknown, newData, unknown>;
	isLoading?: boolean;
}

function GoogleClassroomCoursesList(props: Props) {
	const {
		googleClassroomQueryData,
		onImportClick,
		addCourse,
		studentImportOnClick,
	} = props;

	return (
		<>
			{googleClassroomQueryData?.length ? (
				googleClassroomQueryData?.length > 0 && (
					<Table>
						<Thead>
							<Tr>
								<Th>{__('Class Name', 'learning-management-system')}</Th>
								<Th>{__('Class Code', 'learning-management-system')}</Th>
								<Th>{__('Status', 'learning-management-system')}</Th>
								<Th>{__('Action', 'learning-management-system')}</Th>
							</Tr>
						</Thead>
						<Tbody>
							{googleClassroomQueryData?.map((course) => (
								<GoogleClassroomCoursesRow
									key={course.id}
									courseKey={course.id}
									course={course}
									onImportClick={onImportClick}
									studentImportOnClick={studentImportOnClick}
									addCourse={addCourse}
								/>
							))}
						</Tbody>
					</Table>
				)
			) : (
				<EmptyInfo
					title={__(
						'No courses found. Please add your Google Classroom credentials in Settings.',
						'learning-management-system',
					)}
					docs={
						'https://docs.masteriyo.com/free-addons/google-classroom-integration'
					}
				/>
			)}
		</>
	);
}

export default GoogleClassroomCoursesList;
