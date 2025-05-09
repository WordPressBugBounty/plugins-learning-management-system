import { Icon, Stack, Text } from '@chakra-ui/react';
import { UseMutationResult } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { BiInfoCircle } from 'react-icons/bi';
import { Table, Tbody, Th, Thead, Tr } from 'react-super-responsive-table';
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
				<Stack direction="row" spacing="1" align="center">
					<Icon as={BiInfoCircle} color="primary.400" />
					<Text as="span" fontWeight="medium" color="gray.600" fontSize="sm">
						{__(
							'No courses found, Please go to settings add necessary credentials and get courses from your google classroom.',
							'learning-management-system',
						)}
					</Text>
				</Stack>
			)}
		</>
	);
}

export default GoogleClassroomCoursesList;
