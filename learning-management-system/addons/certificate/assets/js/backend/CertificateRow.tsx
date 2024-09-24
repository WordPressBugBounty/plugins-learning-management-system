import {
	AlertDialog,
	AlertDialogBody,
	AlertDialogContent,
	AlertDialogFooter,
	AlertDialogHeader,
	AlertDialogOverlay,
	Avatar,
	Badge,
	Button,
	ButtonGroup,
	Checkbox,
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
	BiCopy,
	BiDotsVerticalRounded,
	BiEdit,
	BiShow,
	BiTrash,
} from 'react-icons/bi';
import { useMutation, useQueryClient } from 'react-query';
import { Link as RouterLink } from 'react-router-dom';
import { Td, Tr } from 'react-super-responsive-table';
import API from '../../../../../assets/js/back-end/utils/api';
import { getWordpressLocalTime } from '../../../../../assets/js/back-end/utils/utils';
import { certificateBackendRoutes } from '../utils/routes';
import { certificateAddonUrls } from '../utils/urls';

interface Props {
	data: Certificate;
	bulkIds: string[];
	setBulkIds: (value: string[]) => void;
	isLoading?: boolean;
}

const CertificateRow: React.FC<Props> = (props) => {
	const {
		data: {
			id,
			name,
			preview_link,
			author,
			status,
			date_created,
			edit_post_link,
		},
		bulkIds,
		setBulkIds,
		isLoading,
	} = props;

	const certificatesAPI = new API(certificateAddonUrls.certificates);
	const cancelRef = useRef<any>();
	const queryClient = useQueryClient();
	const toast = useToast();
	const { onClose, onOpen, isOpen } = useDisclosure();

	const deleteCertificate = useMutation(
		(id: number) => certificatesAPI.delete(id, { force: true }),
		{
			onSuccess: () => {
				queryClient.invalidateQueries('certificatesList');
				onClose();
				toast({
					title: __(
						'Certificate Permanently Deleted',
						'learning-management-system',
					),
					isClosable: true,
					status: 'success',
				});
			},
		},
	);

	const restoreCertificate = useMutation(
		(id: number) => certificatesAPI.restore(id),
		{
			onSuccess: () => {
				queryClient.invalidateQueries('certificatesList');
				toast({
					title: __('Certificate Restored', 'learning-management-system'),
					isClosable: true,
					status: 'success',
				});
			},
		},
	);

	const trashCertificate = useMutation(
		(id: number) => certificatesAPI.delete(id, { force: false }),
		{
			onSuccess: () => {
				queryClient.invalidateQueries('certificatesList');
				toast({
					title: __('Certificate Trashed', 'learning-management-system'),
					isClosable: true,
					status: 'success',
				});
			},
		},
	);

	const cloneCertificate = useMutation(
		(id: number) => certificatesAPI.cloneData(id),
		{
			onSuccess: () => {
				queryClient.invalidateQueries('certificatesList');
				toast({
					title: __(
						'Certificate duplicated successfully.',
						'learning-management-system',
					),
					isClosable: true,
					status: 'success',
				});
			},
			onError: (error: any) => {
				onClose();
				toast({
					title: __('Failed to duplicate.', 'learning-management-system'),
					description: `${error.response?.data?.message}`,
					isClosable: true,
					status: 'error',
				});
			},
		},
	);

	const onTrashPress = () => {
		trashCertificate.mutate(id);
	};

	const onDeletePress = () => {
		onOpen();
	};

	const onDeleteConfirm = () => {
		deleteCertificate.mutate(id);
	};

	const onRestorePress = () => {
		restoreCertificate.mutate(id);
	};

	const onClonePress = () => {
		cloneCertificate.mutate(id);
	};

	return (
		<Tr>
			<Td>
				<Checkbox
					isDisabled={isLoading}
					isChecked={bulkIds.includes(id.toString())}
					onChange={(e) =>
						setBulkIds(
							e.target.checked
								? [...bulkIds, id.toString()]
								: bulkIds.filter((item) => item !== id.toString()),
						)
					}
				/>
			</Td>
			<Td>
				{status === 'trash' ? (
					<Text fontWeight="semibold">{name}</Text>
				) : (
					<Link
						as={RouterLink}
						to={certificateBackendRoutes.certificate.edit.replace(
							':certificateId',
							id.toString(),
						)}
						fontWeight="semibold"
						_hover={{ color: 'blue.500' }}
					>
						{name}
						{status === 'draft' ? (
							<Badge bg="blue.200" fontSize="10px" ml="2" mt="-2">
								{__('Draft', 'learning-management-system')}
							</Badge>
						) : null}
					</Link>
				)}
			</Td>
			<Td>
				<Stack direction="row" spacing="2" alignItems="center">
					<Avatar src={author?.avatar_url} size="xs" />
					<Text fontSize="xs" fontWeight="medium" color="gray.600">
						{author?.display_name}
					</Text>
				</Stack>
			</Td>
			<Td>
				<Stack direction="row" spacing="2" alignItems="center" color="gray.600">
					<Icon as={BiCalendar} />
					<Text fontSize="xs" fontWeight="medium">
						{getWordpressLocalTime(date_created, 'Y-m-d h:i A')}
					</Text>
				</Stack>
			</Td>
			<Td>
				{status === 'trash' ? (
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
								_hover={{ color: 'blue.500' }}
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
							to={certificateBackendRoutes.certificate.edit.replace(
								':certificateId',
								id.toString(),
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
								<MenuItem icon={<BiCopy />} onClick={onClonePress}>
									{__('Duplicate', 'learning-management-system')}
								</MenuItem>
								<Link href={preview_link} isExternal>
									<MenuItem icon={<BiShow />}>
										{__('Preview', 'learning-management-system')}
									</MenuItem>
								</Link>
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
				<AlertDialog
					isOpen={isOpen}
					onClose={onClose}
					isCentered
					leastDestructiveRef={cancelRef}
				>
					<AlertDialogOverlay>
						<AlertDialogContent>
							<AlertDialogHeader>
								{__('Deleting Certificate', 'learning-management-system')}{' '}
								{name}
							</AlertDialogHeader>
							<AlertDialogBody>
								{__(
									"Are you sure? You can't restore after deleting.",
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
										isLoading={deleteCertificate.isLoading}
										onClick={onDeleteConfirm}
									>
										{__('Delete', 'learning-management-system')}
									</Button>
								</ButtonGroup>
							</AlertDialogFooter>
						</AlertDialogContent>
					</AlertDialogOverlay>
				</AlertDialog>
			</Td>
		</Tr>
	);
};

export default CertificateRow;
