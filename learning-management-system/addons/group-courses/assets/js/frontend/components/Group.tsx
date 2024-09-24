import {
	AlertDialog,
	AlertDialogBody,
	AlertDialogContent,
	AlertDialogFooter,
	AlertDialogHeader,
	AlertDialogOverlay,
	Badge,
	Box,
	Button,
	ButtonGroup,
	Flex,
	HStack,
	Icon,
	IconButton,
	Text,
	Tooltip,
	useToast,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useRef, useState } from 'react';
import { BiBook, BiEdit, BiGroup, BiTrash } from 'react-icons/bi';
import { useMutation, useQueryClient } from 'react-query';
import API from '../../../../../../assets/js/back-end/utils/api';
import { urls } from '../../constants/urls';
import { GroupStatus } from '../../enums/Enum';
import { GroupSchema } from '../../types/group';
// import { GroupStatus } from '../../enums/Enum';
import { RxDividerVertical } from 'react-icons/rx';

interface GroupProps {
	group: GroupSchema;
	onExpandedGroupsChange?: (id: number | null) => void;
}

const Group: React.FC<GroupProps> = ({ group, onExpandedGroupsChange }) => {
	const toast = useToast();
	const queryClient = useQueryClient();
	const groupAPI = new API(urls.groups);
	const [isOpen, setIsOpen] = useState(false);
	const onClose = () => setIsOpen(false);
	const onOpen = () => setIsOpen(true);
	const cancelRef = useRef<HTMLButtonElement>(null);

	const handleDeleteClick = () => {
		onOpen();
	};

	const deleteGroup = useMutation(
		() => groupAPI.delete(group.id, { force: true }),
		{
			onSuccess: () => {
				queryClient.invalidateQueries(['groupsList']);
				toast({
					title: __(
						'Group created successfully.',
						'learning-management-system',
					),
					status: 'success',
					isClosable: true,
				});
				onExpandedGroupsChange?.(null);
			},
			onError: (error: any) => {
				toast({
					title: __('An error occurred.', 'learning-management-system'),
					description: error.response?.data?.message || error.message,
					status: 'error',
					isClosable: true,
				});
			},
		},
	);

	const isPublished = group.status === GroupStatus.Publish;

	return (
		<>
			<AlertDialog
				isOpen={isOpen}
				onClose={onClose}
				leastDestructiveRef={cancelRef}
				isCentered
			>
				<AlertDialogOverlay>
					<AlertDialogContent>
						<AlertDialogHeader fontSize="lg" fontWeight="bold">
							{__('Deleting Group', 'learning-management-system')}
						</AlertDialogHeader>
						<AlertDialogBody>
							{__(
								'Are you sure? You can’t undo this action afterwards.',
								'learning-management-system',
							)}
						</AlertDialogBody>
						<AlertDialogFooter>
							<Button ref={cancelRef} onClick={onClose}>
								{__('Cancel', 'learning-management-system')}
							</Button>
							<Button
								colorScheme="red"
								onClick={() => deleteGroup.mutate()}
								ml={3}
								isLoading={deleteGroup.isLoading}
							>
								{__('Delete', 'learning-management-system')}
							</Button>
						</AlertDialogFooter>
					</AlertDialogContent>
				</AlertDialogOverlay>
			</AlertDialog>
			<Box
				bgColor="muted"
				my={3}
				border="1px"
				borderColor="gray.200"
				rounded={'md'}
				position={'relative'}
			>
				<Flex
					justifyContent={'space-between'}
					alignItems={'center'}
					py={1}
					px={3}
					flexWrap={'wrap'}
				>
					<Text
						cursor="pointer"
						onClick={() => onExpandedGroupsChange?.(group.id)}
						color={'gray.600'}
						fontWeight={'semibold'}
					>
						{group.title}
						{!isPublished && (
							<Badge ml={3} colorScheme="yellow" p={1} borderRadius="md">
								{__('Pending', 'learning-management-system')}
							</Badge>
						)}
					</Text>
					<ButtonGroup
						color="gray.600"
						size="xs"
						p="2"
						alignItems={'center'}
						flexWrap={'wrap'}
					>
						<Tooltip
							label={`${group?.emails?.length || 0} ${__(
								'members',
								'learning-management-system',
							)}`}
						>
							<HStack>
								<Icon as={BiGroup} />
								<Text textAlign="start" fontSize="md">
									{group?.emails?.length || 0}
								</Text>
							</HStack>
						</Tooltip>
						<Icon as={RxDividerVertical} />
						<Tooltip
							label={`${group?.courses_count || 0} ${__(
								'Enrolled Courses',
								'learning-management-system',
							)}`}
						>
							<HStack>
								<Icon as={BiBook} />
								<Text textAlign="start" fontSize="md">
									{group?.courses_count || 0}
								</Text>
							</HStack>
						</Tooltip>
						<Icon as={RxDividerVertical} />
						<Tooltip label={__('Edit', 'learning-management-system')}>
							<IconButton
								_hover={{ color: 'primary.500', background: 'none' }}
								onClick={() => onExpandedGroupsChange?.(group.id)}
								variant="unstyled"
								cursor={'pointer'}
								icon={<Icon fontSize="lg" as={BiEdit} />}
								aria-label={__('Edit', 'learning-management-system')}
								mt={1}
							/>
						</Tooltip>
						<Icon as={RxDividerVertical} />
						<Tooltip label={__('Delete', 'learning-management-system')}>
							<IconButton
								_hover={{ color: 'red.500', background: 'none' }}
								cursor={'pointer'}
								isDisabled={deleteGroup.isLoading}
								isLoading={deleteGroup.isLoading}
								onClick={handleDeleteClick}
								variant="unstyled"
								icon={<Icon fontSize="lg" as={BiTrash} />}
								aria-label={__('Delete', 'learning-management-system')}
								mt={1}
							/>
						</Tooltip>
					</ButtonGroup>
				</Flex>
			</Box>
		</>
	);
};

export default Group;
