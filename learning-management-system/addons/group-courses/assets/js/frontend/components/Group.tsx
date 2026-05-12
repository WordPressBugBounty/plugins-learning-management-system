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
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React, { useRef, useState } from 'react';
import { BiGroup } from 'react-icons/bi';
import { RxDividerVertical } from 'react-icons/rx';
import {
	EditIcon,
	Trash,
} from '../../../../../../assets/js/back-end/constants/images';
import API from '../../../../../../assets/js/back-end/utils/api';
import { urls } from '../../constants/urls';
import { GroupDisplayStatus } from '../../enums/Enum';
import { GroupSchema } from '../../types/group';

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

	const deleteGroup = useMutation({
		mutationFn: () => groupAPI.delete(group.id, { force: true }),
		...{
			onSuccess: () => {
				queryClient.invalidateQueries({ queryKey: ['groupsList'] });
				toast({
					title: __(
						'Group deleted successfully.',
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
	});

	const displayStatus = group.display_status ?? GroupDisplayStatus.Inactive;
	const isPending = displayStatus === GroupDisplayStatus.Pending;
	const isInactive = displayStatus === GroupDisplayStatus.Inactive;

	const statusReasonLabel: Record<string, string> = {
		failed: __('Your last order failed.', 'learning-management-system'),
		cancelled: __(
			'Your last order was cancelled.',
			'learning-management-system',
		),
		refunded: __('Your last order was refunded.', 'learning-management-system'),
		removed: __('Your last order was removed.', 'learning-management-system'),
	};
	const inactiveTooltip =
		statusReasonLabel[group.status_reason ?? ''] ??
		__('Payment incomplete.', 'learning-management-system');

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
							<Button ref={cancelRef} onClick={onClose} variant={'outline'}>
								{__('Cancel', 'learning-management-system')}
							</Button>
							<Button
								colorScheme="red"
								onClick={() => deleteGroup.mutate()}
								ml={3}
								isLoading={deleteGroup.isPending}
							>
								{__('Delete', 'learning-management-system')}
							</Button>
						</AlertDialogFooter>
					</AlertDialogContent>
				</AlertDialogOverlay>
			</AlertDialog>
			<Box
				bgColor="muted"
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
					<Flex alignItems="center" gap={2} flexWrap="wrap">
						<Text
							cursor="pointer"
							onClick={() => onExpandedGroupsChange?.(group.id)}
							color={'oxford-night'}
							fontWeight={'semibold'}
						>
							{group.title}
						</Text>
						{isPending && (
							<Badge
								color="yellow.500"
								p={1}
								borderRadius="base"
								variant={'link'}
							>
								{__('Pending', 'learning-management-system')}
							</Badge>
						)}
						{isInactive && (
							<Tooltip label={inactiveTooltip}>
								<Badge
									color="gray.500"
									p={1}
									borderRadius="base"
									variant={'link'}
									cursor="default"
								>
									{__('Inactive', 'learning-management-system')}
								</Badge>
							</Tooltip>
						)}
					</Flex>
					<ButtonGroup
						color="gray.600"
						size="xs"
						p={{ base: 1, sm: 2 }}
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
								<Text textAlign="start" fontSize="md" color={'saint-blue'}>
									{group?.emails?.length || 0}
								</Text>
							</HStack>
						</Tooltip>

						<Icon as={RxDividerVertical} />
						<Tooltip label={__('Edit', 'learning-management-system')}>
							<IconButton
								_hover={{ color: 'primary.500', background: 'none' }}
								onClick={() => onExpandedGroupsChange?.(group.id)}
								variant="unstyled"
								cursor="pointer"
								icon={<Icon fontSize="lg" as={EditIcon} />}
								aria-label={__('Edit', 'learning-management-system')}
								mt={1}
							/>
						</Tooltip>
						<Icon as={RxDividerVertical} />
						<Tooltip label={__('Delete', 'learning-management-system')}>
							<IconButton
								_hover={{ color: 'red.500', background: 'none' }}
								cursor={'pointer'}
								isDisabled={deleteGroup.isPending}
								isLoading={deleteGroup.isPending}
								onClick={handleDeleteClick}
								variant="unstyled"
								icon={<Icon fontSize="lg" as={Trash} />}
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
