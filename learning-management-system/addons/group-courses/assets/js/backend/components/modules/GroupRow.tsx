import {
	AlertDialog,
	AlertDialogBody,
	AlertDialogContent,
	AlertDialogFooter,
	AlertDialogHeader,
	AlertDialogOverlay,
	Button,
	ButtonGroup,
	Icon,
	IconButton,
	Link,
	Menu,
	MenuButton,
	MenuItem,
	MenuList,
	Stack,
	Text,
	useDisclosure,
	useToast,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useRef } from 'react';
import {
	BiCalendar,
	BiDotsVerticalRounded,
	BiEdit,
	BiShow,
	BiTrash,
} from 'react-icons/bi';
import { useMutation, useQueryClient } from 'react-query';
import { Link as RouterLink } from 'react-router-dom';
import { Td, Tr } from 'react-super-responsive-table';
import API from '../../../../../../../assets/js/back-end/utils/api';
import { getWordpressLocalTime } from '../../../../../../../assets/js/back-end/utils/utils';
import { urls } from '../../../constants/urls';
import { GroupStatus } from '../../../enums/Enum';
import { groupsBackendRoutes } from '../../../routes/routes';
import { GroupSchema } from '../../../types/group';

interface Props {
	data: GroupSchema;
	isLoading?: boolean;
	bulkIds: string[];
	setBulkIds: (value: string[]) => void;
}

const GroupRow: React.FC<Props> = (props) => {
	const { data, isLoading, bulkIds, setBulkIds } = props;
	const { status } = data;
	const createdOnDate = data.date_created?.split(' ')[0];
	const toast = useToast();
	const cancelRef = useRef<any>();
	const queryClient = useQueryClient();
	const groupsAPI = new API(urls.groups);
	const { onClose, onOpen, isOpen } = useDisclosure();

	const restoreGroup = useMutation((id: number) => groupsAPI.restore(id), {
		onSuccess: () => {
			queryClient.invalidateQueries('groupsList');
			toast({
				title: __('Group Restored', 'learning-management-system'),
				isClosable: true,
				status: 'success',
			});
		},

		onError: (error: any) => {
			const message: any = error?.message
				? error?.message
				: error?.data?.message;

			toast({
				title: __('Failed to restore group.', 'learning-management-system'),
				description: message ? `${message}` : undefined,
				status: 'error',
				isClosable: true,
			});
		},
	});

	const trashGroup = useMutation((id: number) => groupsAPI.delete(id), {
		onSuccess: () => {
			queryClient.invalidateQueries('groupsList');
			toast({
				title: __('Group moved to trash', 'learning-management-system'),
				isClosable: true,
				status: 'success',
			});
		},

		onError: (error: any) => {
			const message: any = error?.message
				? error?.message
				: error?.data?.message;

			toast({
				title: __(
					'Failed to move a group to trash.',
					'learning-management-system',
				),
				description: message ? `${message}` : undefined,
				status: 'error',
				isClosable: true,
			});
		},
	});

	const deleteGroup = useMutation(
		(id: number) => groupsAPI.delete(id, { force: true }),
		{
			onSuccess: () => {
				queryClient.invalidateQueries('groupsList');
				onClose();
			},

			onError: (error: any) => {
				const message: any = error?.message
					? error?.message
					: error?.data?.message;

				toast({
					title: __('Failed to delete group.', 'learning-management-system'),
					description: message ? `${message}` : undefined,
					status: 'error',
					isClosable: true,
				});
			},
		},
	);

	const onTrashPress = () => {
		trashGroup.mutate(data.id);
	};

	const onDeletePress = () => {
		onOpen();
	};

	const onDeleteConfirm = () => {
		deleteGroup.mutate(data.id);
	};

	const onRestorePress = () => {
		restoreGroup.mutate(data.id);
	};

	return (
		<>
			<Tr>
				<Td>
					{status === 'trash' ? (
						<Text fontWeight="semibold">{data.title}</Text>
					) : (
						<Link
							as={RouterLink}
							to={groupsBackendRoutes.edit.replace(
								':groupId',
								data.id.toString(),
							)}
							fontWeight="semibold"
							_hover={{ color: 'primary.500' }}
						>
							{data.title}
						</Link>
					)}
				</Td>
				<Td>
					<Stack
						direction="row"
						spacing="2"
						alignItems="center"
						color="gray.600"
					>
						<Icon as={BiCalendar} />
						<Text fontSize="xs" fontWeight="medium">
							{getWordpressLocalTime(createdOnDate, 'Y-m-d, h:i A')}
						</Text>
					</Stack>
				</Td>
				<Td>
					{status === GroupStatus.Trash ? (
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
									onClick={() => onRestorePress()}
									icon={<BiShow />}
									_hover={{ color: 'primary.500' }}
								>
									{__('Restore', 'learning-management-system')}
								</MenuItem>
								<MenuItem
									onClick={() => onDeletePress()}
									icon={<BiTrash />}
									_hover={{ color: 'red.500' }}
								>
									{__('Delete Permanently', 'learning-management-system')}
								</MenuItem>
							</MenuList>
						</Menu>
					) : (
						<ButtonGroup>
							<RouterLink
								to={groupsBackendRoutes.edit.replace(
									':groupId',
									data.id.toString(),
								)}
							>
								<Button colorScheme="primary" leftIcon={<BiEdit />} size="xs">
									{__('Edit', 'learning-management-system')}
								</Button>
							</RouterLink>
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
										onClick={() => onTrashPress()}
										icon={<BiTrash />}
										_hover={{ color: 'red.500' }}
									>
										{__('Trash', 'learning-management-system')}
									</MenuItem>
								</MenuList>
							</Menu>
						</ButtonGroup>
					)}
				</Td>
			</Tr>
			<AlertDialog
				isOpen={isOpen}
				onClose={onClose}
				isCentered
				leastDestructiveRef={cancelRef}
			>
				<AlertDialogOverlay>
					<AlertDialogContent>
						<AlertDialogHeader>
							{__('Deleting Group', 'learning-management-system')} {data.title}
						</AlertDialogHeader>
						<AlertDialogBody>
							{__(
								'Are you sure? You can’t restore after deleting.',
								'learning-management-system',
							)}
						</AlertDialogBody>
						<AlertDialogFooter>
							<ButtonGroup>
								<Button onClick={onClose} variant="outline" ref={cancelRef}>
									{__('Cancel', 'learning-management-system')}
								</Button>
								<Button
									colorScheme="red"
									isLoading={deleteGroup.isLoading}
									onClick={onDeleteConfirm}
								>
									{__('Delete', 'learning-management-system')}
								</Button>
							</ButtonGroup>
						</AlertDialogFooter>
					</AlertDialogContent>
				</AlertDialogOverlay>
			</AlertDialog>
		</>
	);
};

export default GroupRow;
