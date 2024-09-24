import {
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
	Switch,
	Text,
	useToast,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
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
import API from '../../../../../assets/js/back-end/utils/api';
import localized from '../../../../../assets/js/back-end/utils/global';
import { getWordpressLocalTime } from '../../../../../assets/js/back-end/utils/utils';
import { urls } from '../constants/urls';
import { multipleCurrencyBackendRoutes } from '../routes/routes';
import { PriceZoneSchema } from '../types/multiCurrency';
import CountryList from './components/CountryList';

interface Props {
	data: PriceZoneSchema;
	onDeletePress: (id: number) => void;
	onTrashPress: (id: number) => void;
	onRestorePress: (id: number) => void;
	setBulkIds: (value: string[]) => void;
	bulkIds: string[];
	isLoading?: boolean;
}

const PriceZoneList: React.FC<Props> = (props) => {
	const {
		data,
		onDeletePress,
		onTrashPress,
		onRestorePress,
		setBulkIds,
		bulkIds,
		isLoading,
	} = props;

	const queryClient = useQueryClient();
	const toast = useToast();

	const pricingZonePI = new API(urls.pricingZones);

	const updateStatus = useMutation<PriceZoneSchema>(
		(newStatus) => pricingZonePI.update(data?.id, { status: newStatus }),
		{
			onSuccess: () => {
				queryClient.invalidateQueries(`pricingZone${data?.id}`);
				queryClient.invalidateQueries(`pricingZonesList`);
				toast({
					title: __(
						'Pricing zone status updated successfully.',
						'learning-management-system',
					),
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
						'Failed to update the pricing zone status.',
						'learning-management-system',
					),
					description: message ? `${message}` : undefined,
					status: 'error',
					isClosable: true,
				});
			},
		},
	);

	const handleStatusChange = (newStatus: any) => {
		updateStatus.mutate(newStatus);
	};

	return (
		<Tr>
			<Td>
				<Checkbox
					isDisabled={isLoading}
					isChecked={bulkIds.includes(data?.id.toString())}
					onChange={(e) =>
						setBulkIds(
							e.target.checked
								? [...bulkIds, data?.id.toString()]
								: bulkIds.filter((item) => item !== data?.id.toString()),
						)
					}
				/>
			</Td>
			<Td>
				{data?.status === 'trash' ? (
					<Text fontWeight="semibold">{data?.title}</Text>
				) : (
					<Link
						as={RouterLink}
						to={multipleCurrencyBackendRoutes.edit.replace(
							':pricingZoneID',
							data?.id.toString(),
						)}
						fontWeight="semibold"
						_hover={{ color: 'primary.500' }}
					>
						{data?.title}
					</Link>
				)}
			</Td>
			<Td>
				<Stack direction="row" spacing="2" alignItems="center">
					<Text fontSize="xs" fontWeight="medium" color="gray.600">
						{data?.currency?.label}
					</Text>
				</Stack>
			</Td>
			<Td>
				<Stack direction="row" spacing="2" alignItems="center">
					<Text fontSize="xs" fontWeight="medium">
						{`1 ${localized.currency.code} = `}
						{data?.exchange_rate} {` ${data?.currency?.value} `}
					</Text>
				</Stack>
			</Td>
			<Td>
				<Stack direction="row" spacing="2" alignItems="center">
					<>
						<CountryList countries={data?.countries} />
					</>
				</Stack>
			</Td>
			<Td>
				<Stack direction="row" spacing="2" alignItems="center" color="gray.600">
					<Icon as={BiCalendar} />
					<Text fontSize="xs" fontWeight="medium">
						{getWordpressLocalTime(data?.date_created, 'Y-m-d, h:i A')}
					</Text>
				</Stack>
			</Td>
			<Td>
				<Stack spacing="3" w="fit-content" align="center">
					<Switch
						isChecked={'active' === data?.status}
						title={
							'active' === data?.status
								? __('Deactivate', 'learning-management-system')
								: __('Activate', 'learning-management-system')
						}
						onChange={(e) =>
							handleStatusChange(e.target.checked ? 'active' : 'inactive')
						}
						isDisabled={updateStatus.isLoading}
					/>
					{'active' === data?.status ? (
						<Badge colorScheme="green" fontSize="xs">
							{__('Active', 'learning-management-system')}
						</Badge>
					) : (
						<Badge variant="outline" fontSize="xs" colorScheme="red">
							{__('Inactive', 'learning-management-system')}
						</Badge>
					)}
				</Stack>
			</Td>
			<Td>
				{data?.status === 'trash' ? (
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
								onClick={() => onRestorePress(data?.id)}
								icon={<BiShow />}
								_hover={{ color: 'primary.500' }}
							>
								{__('Restore', 'learning-management-system')}
							</MenuItem>
							<MenuItem
								onClick={() => onDeletePress(data?.id)}
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
							to={multipleCurrencyBackendRoutes.edit.replace(
								':pricingZoneID',
								data?.id.toString(),
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
									onClick={() => onTrashPress(data?.id)}
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
	);
};

export default PriceZoneList;
