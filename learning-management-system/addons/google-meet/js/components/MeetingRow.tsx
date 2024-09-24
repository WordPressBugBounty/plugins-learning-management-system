import {
	AlertDialog,
	AlertDialogBody,
	AlertDialogContent,
	AlertDialogFooter,
	AlertDialogHeader,
	AlertDialogOverlay,
	Badge,
	Button,
	ButtonGroup,
	Checkbox,
	Icon,
	IconButton,
	Link,
	Stack,
	Text,
	useDisclosure,
	useToast,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import humanizeDuration from 'humanize-duration';
import React from 'react';
import { BiCalendar, BiEdit, BiTrash } from 'react-icons/bi';
import { RiCalendar2Line, RiLiveLine } from 'react-icons/ri';
import { useMutation, useQueryClient } from 'react-query';
import { Link as RouterLink, useNavigate } from 'react-router-dom';
import { Td, Tr } from 'react-super-responsive-table';
import { AuthorMap } from '../../../../assets/js/back-end/types/course';
import API from '../../../../assets/js/back-end/utils/api';
import googleMeetRoutes from '../../constants/routes';
import GoogleMeetUrls from '../../constants/urls';
import { GoogleMeetStatus } from '../Enums/Enum';

interface Props {
	meeting: {
		id: number;
		course_name: string;
		start_url: string;
		name: string;
		password: string;
		duration: any;
		starts_at: any;
		ends_at: any;
		course_id: number;
		additional_authors: AuthorMap[];
		author: AuthorMap;
		calender_url: string;
		description: string;
		meet_url: string;
		meeting_id: any;
		course_permalink: string;
	};
	// onDeletePress: (id: number) => void;
	isLoading?: boolean;
	bulkIds: string[];
	setBulkIds: (value: string[]) => void;
	deleteCourseId: number | undefined;
}

const MeetingRow: React.FC<Props> = (props) => {
	const {
		meeting: {
			course_name,
			name,
			duration,
			starts_at,
			ends_at,
			id,
			course_id,
			start_url,
			additional_authors,
			author,
			calender_url,
			meet_url,
			description,
			meeting_id,
			course_permalink,
		},
		// onDeletePress,
		isLoading,
		bulkIds,
		setBulkIds,
		// deleteCourseId,
	} = props;

	const [status, setStatus] = React.useState<string>('');
	const start_at: Date = new Date(starts_at);
	const end_at: Date = new Date(ends_at);

	const durationInMilliseconds: number = end_at.getTime() - start_at.getTime();

	const humanReadableDuration: string = humanizeDuration(
		durationInMilliseconds,
	);
	const { onClose, onOpen, isOpen } = useDisclosure();
	const [deleteCourseId, setDeleteCourseId] = React.useState<number>();
	const cancelRef = React.useRef<any>();
	const navigate = useNavigate();
	const googleMeetMeetingsAPI = new API(GoogleMeetUrls.googleMeets);
	const queryClient = useQueryClient();
	const toast = useToast();
	const onEditPress = () => {
		navigate(
			googleMeetRoutes.googleMeet.edit.replace(':googleMeetId', id.toString()) +
				'?referrer=meeting-list',
		);
	};

	React.useEffect(() => {
		googleMeetStatus();
	}, [start_at, end_at]);

	const deleteGoogleMeet = useMutation(
		(id: number) => googleMeetMeetingsAPI.delete(id, { force: true }),
		{
			onSuccess: () => {
				queryClient.invalidateQueries('googleMeetList');
				toast({
					title: __('Meeting Deleted', 'learning-management-system'),
					isClosable: true,
					status: 'success',
				});
				onClose();
				setBulkIds([]);
			},
			onError: (error: any) => {
				const message: any = error?.message
					? error?.message
					: error?.data?.message;

				toast({
					title: __(
						'Could not delete the google meeting.',
						'learning-management-system',
					),
					description: message ? `${message}` : undefined,
					status: 'error',
					isClosable: true,
				});
			},
		},
	);

	const onDeletePress = (googleMeetId: number) => {
		onOpen();
		setDeleteCourseId(googleMeetId);
	};

	const onDeleteConfirm = () => {
		deleteCourseId ? deleteGoogleMeet.mutate(deleteCourseId) : null;
	};

	const googleMeetStatus = () => {
		if (start_at >= new Date()) {
			setStatus(GoogleMeetStatus.UpComing);
		} else if (start_at < new Date() && end_at > new Date()) {
			setStatus(GoogleMeetStatus.Active);
		} else if (end_at < new Date()) {
			setStatus(GoogleMeetStatus.Expired);
		} else {
			setStatus(GoogleMeetStatus.All);
		}
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
				<Link
					as={RouterLink}
					to={
						googleMeetRoutes.googleMeet.edit.replace(
							':googleMeetId',
							id.toString(),
						) + '?referrer=meeting-list'
					}
					fontWeight="semibold"
					_hover={{ color: 'primary.500' }}
				>
					{name}
				</Link>
			</Td>

			<Td>
				<Badge
					textTransform="uppercase"
					colorScheme={
						status === GoogleMeetStatus.UpComing
							? 'yellow'
							: status === GoogleMeetStatus.Expired
								? 'orange'
								: 'green'
					}
				>
					{status}
				</Badge>
			</Td>

			<Td>
				<Link
					_hover={{ textDecoration: 'none' }}
					href={calender_url}
					isExternal
				>
					<Button colorScheme="blue" size="xs" gap="2" fontWeight="semibold">
						<RiCalendar2Line />
						{__('Google Calender', 'learning-management-system')}
					</Button>
				</Link>
			</Td>

			<Td>
				<Link
					href={course_permalink}
					isExternal
					_hover={{ textDecoration: 'none' }}
				>
					<Text
						as="span"
						fontSize="xs"
						fontWeight="medium"
						color="gray.600"
						textDecoration="none"
						_hover={{ color: 'primary.500' }}
					>
						{course_name}
					</Text>
				</Link>
			</Td>

			<Td>
				<Stack direction="row" spacing="2" alignItems="center" color="gray.600">
					<Icon as={BiCalendar} />
					<Text as="span" fontSize="xs" fontWeight="medium" color="gray.600">
						{new Date(starts_at).toLocaleString()}
					</Text>
				</Stack>
			</Td>
			<Td>
				<Stack direction="row" spacing="2" alignItems="center" color="gray.600">
					<Icon as={BiCalendar} />
					<Text as="span" fontSize="xs" fontWeight="medium" color="gray.600">
						{/* {humanizeDuration((duration || 0) * 60 * 1000)} */}
						{new Date(ends_at).toLocaleString()}
					</Text>
				</Stack>
			</Td>
			<Td>
				<ButtonGroup alignItems="center">
					{status === GoogleMeetStatus.UpComing ||
					status === GoogleMeetStatus.Active ? (
						<Link
							_hover={{ textDecoration: 'none' }}
							href={meet_url}
							isExternal
						>
							<Button
								colorScheme="blue"
								size="xs"
								gap="2"
								fontWeight="semibold"
							>
								<RiLiveLine />
								{__('Start Meeting', 'learning-management-system')}
							</Button>
						</Link>
					) : null}

					<Button
						size="xs"
						gap="2"
						variant="solid"
						colorScheme="blue"
						onClick={onEditPress}
					>
						<BiEdit />
						{__('Edit', 'learning-management-system')}
					</Button>

					<IconButton
						size="xs"
						variant="outline"
						rounded="sm"
						fontSize="sm"
						_hover={{ color: 'red.500' }}
						onClick={() => onDeletePress(id)}
						icon={<BiTrash />}
						aria-label={__('Delete', 'learning-management-system')}
					/>
				</ButtonGroup>
			</Td>

			<AlertDialog
				isOpen={isOpen}
				onClose={onClose}
				isCentered
				leastDestructiveRef={cancelRef}
			>
				<AlertDialogOverlay>
					<AlertDialogContent>
						<AlertDialogHeader>
							{__('Delete Google Meeting', 'learning-management-system')}
						</AlertDialogHeader>
						<AlertDialogBody>
							{__(
								'Are you sure? You can’t restore it after deleting.',
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
									isLoading={deleteGoogleMeet.isLoading}
									onClick={onDeleteConfirm}
								>
									{__('Delete', 'learning-management-system')}
								</Button>
							</ButtonGroup>
						</AlertDialogFooter>
					</AlertDialogContent>
				</AlertDialogOverlay>
			</AlertDialog>
		</Tr>
	);
};

export default MeetingRow;
