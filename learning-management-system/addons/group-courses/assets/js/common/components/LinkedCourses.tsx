import {
	Box,
	Divider,
	Flex,
	FormControl,
	FormLabel,
	Icon,
	Link,
	Stack,
	Text,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { BiBook, BiLinkExternal } from 'react-icons/bi';

interface Course {
	id: number;
	title: string;
	permalink?: string;
}

interface LinkedCoursesProps {
	courses: Course[];
}

const LinkedCourses: React.FC<LinkedCoursesProps> = ({ courses }) => {
	// Use singular or plural based on course count
	const courseLabel =
		courses && courses.length === 1
			? __('Enrolled Course', 'learning-management-system')
			: __('Enrolled Courses', 'learning-management-system');

	if (!courses || courses.length === 0) {
		return (
			<FormControl>
				<FormLabel>{courseLabel}</FormLabel>
				<Text color="gray.500" fontSize="sm">
					{__(
						'No courses are enrolled for this group.',
						'learning-management-system',
					)}
				</Text>
			</FormControl>
		);
	}

	return (
		<FormControl>
			<FormLabel>{courseLabel}</FormLabel>
			<Stack spacing="3">
				{courses.map((course, index) => (
					<Box key={course.id}>
						<Flex align="flex-start" justify="space-between">
							<Flex align="flex-start" flex="1">
								<Icon as={BiBook} color="gray.500" mt="1" mr="3" />
								<Box flex="1">
									{course.permalink ? (
										<Link
											href={course.permalink}
											target="_blank"
											rel="noopener noreferrer"
											color="blue.600"
											fontWeight="medium"
											fontSize="sm"
											_hover={{ textDecoration: 'underline' }}
										>
											{course.title}
											<Icon as={BiLinkExternal} ml="1" fontSize="xs" />
										</Link>
									) : (
										<Text fontWeight="medium" fontSize="sm">
											{course.title}
										</Text>
									)}
								</Box>
							</Flex>
						</Flex>
						{index < courses.length - 1 && <Divider mt="3" />}
					</Box>
				))}
			</Stack>
		</FormControl>
	);
};

export default LinkedCourses;
