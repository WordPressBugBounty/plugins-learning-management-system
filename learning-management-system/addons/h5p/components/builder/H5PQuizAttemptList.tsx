import {
	Badge,
	Button,
	ButtonGroup,
	HStack,
	IconButton,
	Link,
	Menu,
	MenuButton,
	MenuItem,
	MenuList,
	Stack,
	Text,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { BiDotsVerticalRounded } from 'react-icons/bi';
import { Link as RouterLink } from 'react-router-dom';
import { Td, Tr } from 'react-super-responsive-table';
import { Trash } from '../../../../assets/js/back-end/constants/images';
import h5pRoutes from '../../constants/routes';
import { H5PQuizAttempt } from '../../types/h5pQuizAttempt';

interface Props {
	data: H5PQuizAttempt;
	onDeletePress: (id: number) => void;
}

const H5PQuizAttemptList: React.FC<Props> = ({ data, onDeletePress }) => {
	const isFinished = 'finished' === data.status;
	const detailUrl = h5pRoutes.h5pQuizAttempt.view.replace(
		':attemptId',
		data.id.toString(),
	);

	const studentLabel = (
		<>
			#{data.id}{' '}
			{data.user
				? `${data.user.first_name} ${data.user.last_name}`.trim() ||
					data.user.display_name
				: `User #${data.id}`}
		</>
	);

	return (
		<Tr>
			<Td>
				<Stack direction="column" spacing="1">
					{isFinished ? (
						<Link
							as={RouterLink}
							to={detailUrl}
							fontWeight="semibold"
							fontSize="sm"
							_hover={{ color: 'primary.500' }}
						>
							{studentLabel}
						</Link>
					) : (
						<Text fontWeight="semibold" fontSize="sm">
							{studentLabel}
						</Text>
					)}
					{data.user && (
						<Text color="gray.600" fontSize="xs">
							{data.user.display_name} ({data.user.email})
						</Text>
					)}
				</Stack>
			</Td>

			<Td>
				<Text fontWeight="bold" color="gray.600" fontSize="sm">
					{data.quiz?.name ?? `Quiz #${data.id}`}
				</Text>
				{data.course && (
					<Text color="gray.600" fontSize="xs">
						{__('Course:', 'learning-management-system')} {data.course.name}
					</Text>
				)}
			</Td>

			<Td>
				<Stack direction="column" spacing="1">
					<Text color="gray.600" fontSize="xs">
						{__('Attempt #:', 'learning-management-system')}{' '}
						{data.attempt_number}
					</Text>
					<Text color="gray.600" fontSize="xs">
						{__('Score:', 'learning-management-system')}{' '}
						{data.max_score > 0
							? `${data.score} / ${data.max_score}`
							: __('Unscored', 'learning-management-system')}
					</Text>
				</Stack>
			</Td>

			<Td>
				{!isFinished ? (
					<HStack align="center">
						<Badge
							colorScheme="yellow"
							variant="link"
							color="yellow.500"
							p={0}
							textTransform="none"
							fontSize="sm"
						>
							{__('In Progress', 'learning-management-system')}
						</Badge>
					</HStack>
				) : (
					<HStack align="center">
						<Badge
							colorScheme={data.passed === 'yes' ? 'green' : 'red'}
							variant="link"
							color={data.passed === 'yes' ? 'green.500' : 'coral-red'}
							p={0}
							textTransform="none"
							fontSize="sm"
						>
							{data.passed === 'yes'
								? __('Pass', 'learning-management-system')
								: __('Fail', 'learning-management-system')}
						</Badge>
						{data.max_score > 0 && (
							<Text
								color={data.passed === 'yes' ? 'green.500' : 'coral-red'}
								fontSize="sm"
								fontWeight="semibold"
							>
								{data.percentage}%
							</Text>
						)}
					</HStack>
				)}
			</Td>

			<Td>
				<ButtonGroup>
					{isFinished && (
						<RouterLink to={detailUrl}>
							<Button colorScheme="primary" variant="outline" size="xs">
								{__('View', 'learning-management-system')}
							</Button>
						</RouterLink>
					)}
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
							<MenuItem
								onClick={() => onDeletePress(data.id)}
								icon={<Trash width="12px" height="12px" fill="currentColor" />}
								_hover={{ color: 'red.500' }}
							>
								{__('Delete', 'learning-management-system')}
							</MenuItem>
						</MenuList>
					</Menu>
				</ButtonGroup>
			</Td>
		</Tr>
	);
};

export default H5PQuizAttemptList;
