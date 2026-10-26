import {
	Box,
	FormControl,
	FormLabel,
	Progress,
	Skeleton,
	Stack,
	Table,
	TableContainer,
	Tbody,
	Td,
	Text,
	Th,
	Thead,
	Tr,
} from '@chakra-ui/react';
import { useQuery } from '@tanstack/react-query';
import { __, sprintf } from '@wordpress/i18n';
import React from 'react';
import StatusBadge from '../../../../../../assets/js/account/common/StatusBadge';
import { accountPageFormLabelStyles } from '../../../../../../assets/js/account/utils/general';
import API from '../../../../../../assets/js/back-end/utils/api';
import { getSiteDate } from '../../../../../../assets/js/back-end/utils/utils';
import { urls } from '../../constants/urls';
import { GroupProgressSchema, MemberCourseProgress } from '../../types/group';

interface Props {
	groupId: number;
}

type Standing = {
	status: 'draft' | 'cancelled' | 'completed' | 'pending';
	label: string;
};

/** The badge key (its colour) and label for a member's standing in a course. */
export const standing = (row: MemberCourseProgress): Standing => {
	if (!row.enrollment_status) {
		return {
			status: 'draft',
			label: __('Not enrolled', 'learning-management-system'),
		};
	}
	if (row.enrollment_status !== 'active') {
		return {
			status: 'cancelled',
			label: __('Inactive', 'learning-management-system'),
		};
	}
	if (row.progress_status === 'completed') {
		return {
			status: 'completed',
			label: __('Completed', 'learning-management-system'),
		};
	}
	if (!row.progress_status) {
		return {
			status: 'draft',
			label: __('Not started', 'learning-management-system'),
		};
	}
	return {
		status: 'pending',
		label: __('In progress', 'learning-management-system'),
	};
};

const countOf = (counts: { completed: number; total: number }) =>
	sprintf(
		/* translators: 1: completed count, 2: total count */
		__('%1$d of %2$d', 'learning-management-system'),
		counts.completed,
		counts.total,
	);

const lastActivity = (row: MemberCourseProgress) => {
	const date = row.completed_at ?? row.modified_at;
	return date ? getSiteDate(date) : '—';
};

const GroupProgress: React.FC<Props> = ({ groupId }) => {
	const progressQuery = useQuery<GroupProgressSchema>({
		queryKey: ['groupProgress', groupId],
		queryFn: () => new API(urls.groups).get(`${groupId}/progress`),
	});

	return (
		<FormControl>
			<FormLabel sx={accountPageFormLabelStyles}>
				{__('Member Progress', 'learning-management-system')}
			</FormLabel>
			{progressQuery.isLoading ? (
				<Skeleton height="6rem" rounded="md" />
			) : progressQuery.isError || !progressQuery.data ? (
				<Text color="coral-red" fontSize="sm">
					{__('Could not load member progress.', 'learning-management-system')}
				</Text>
			) : (
				<ProgressTables {...progressQuery.data} />
			)}
		</FormControl>
	);
};

const ProgressTables: React.FC<GroupProgressSchema> = ({
	courses,
	members,
}) => {
	if (courses.length === 0 || members.length === 0) {
		return (
			<Text color="saint-blue" fontSize="sm">
				{courses.length === 0
					? __(
							'Progress appears once the group has a course.',
							'learning-management-system',
						)
					: __(
							'Progress appears once the group has members.',
							'learning-management-system',
						)}
			</Text>
		);
	}

	return (
		<Stack spacing="6">
			{courses.map((course, courseIndex) => (
				<Box key={course.id} data-testid={`group-progress-${course.id}`}>
					{courses.length > 1 && (
						<Text fontWeight="medium" fontSize="sm" mb="2">
							{course.name}
						</Text>
					)}
					<TableContainer
						borderWidth="1px"
						borderColor="icy-blue-gray"
						rounded="md"
					>
						<Table size="sm" variant="simple">
							<Thead>
								<Tr>
									<Th>{__('Member', 'learning-management-system')}</Th>
									<Th>{__('Status', 'learning-management-system')}</Th>
									<Th minW="10rem">
										{__('Progress', 'learning-management-system')}
									</Th>
									<Th>{__('Lessons', 'learning-management-system')}</Th>
									<Th>{__('Quizzes', 'learning-management-system')}</Th>
									<Th>{__('Last activity', 'learning-management-system')}</Th>
								</Tr>
							</Thead>
							<Tbody>
								{members.map((member) => {
									const row = member.courses[courseIndex];
									const badge = standing(row);
									return (
										<Tr key={member.email}>
											<Td>
												<Text fontSize="sm" fontWeight="medium">
													{member.user?.display_name ?? member.email}
												</Text>
												<Text fontSize="xs" color="gray.500">
													{member.user
														? member.email
														: __(
																'No account yet',
																'learning-management-system',
															)}
												</Text>
											</Td>
											<Td>
												<StatusBadge status={badge.status}>
													{badge.label}
												</StatusBadge>
											</Td>
											<Td>
												<Stack spacing="1">
													<Text fontSize="xs">{row.percentage}%</Text>
													<Progress
														rounded="full"
														size="xs"
														value={row.percentage}
														max={100}
														colorScheme="primary"
														aria-label={__(
															'Progress',
															'learning-management-system',
														)}
													/>
												</Stack>
											</Td>
											<Td fontSize="sm">{countOf(row.lessons)}</Td>
											<Td fontSize="sm">{countOf(row.quizzes)}</Td>
											<Td fontSize="sm">{lastActivity(row)}</Td>
										</Tr>
									);
								})}
							</Tbody>
						</Table>
					</TableContainer>
				</Box>
			))}
		</Stack>
	);
};

export default GroupProgress;
