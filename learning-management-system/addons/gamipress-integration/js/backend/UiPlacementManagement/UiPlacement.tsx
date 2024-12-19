import {
	Box,
	Select as ChakraSelect,
	Collapse,
	FormControl,
	FormLabel,
	HStack,
	Icon,
	IconButton,
	Image,
	Input,
	Stack,
	Text,
	useRadioGroup,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useMemo, useState } from 'react';
import { Draggable } from 'react-beautiful-dnd';
import { BiX } from 'react-icons/bi';
import Select from 'react-select';
import { Sortable } from '../../../../../assets/js/back-end/assets/icons';
import FormControlTwoCol from '../../../../../assets/js/back-end/components/common/FormControlTwoCol';
import {
	labelStyles,
	reactSelectStyles,
} from '../../../../../assets/js/back-end/config/styles';
import {
	BelowAuthorSectionLocation,
	BelowUsernameLocation,
	DashboardCardLocation,
	DashboardNewSectionLocation,
	LearnPageAccountPopoverBottomLocation,
	LearnPageAccountPopoverTopLocation,
	LearnPageInfoBoxPopoverBottomLocation,
	LearnPageInfoBoxPopoverTopLocation,
	LearnPageLeftToProfilePicLocation,
	NewTabLocation,
} from '../../../../../assets/js/back-end/constants/images';
import ToolTip from '../../../../../assets/js/back-end/screens/settings/components/ToolTip';
import { isEmpty } from '../../../../../assets/js/back-end/utils/utils';
import {
	AccountPageLocation,
	LearnPageLocation,
	PlacementPage,
} from '../../enums/enums';
import LocationRadioItem from './LocationRadioItem';

interface LocationType {
	location: string;
	label: string;
	imageSrc: string;
	hasTitle?: boolean;
}

const accountPageLocations: LocationType[] = [
	{
		location: AccountPageLocation.BELOW_USERNAME,
		label: __('Below Username', 'learning-management-system'),
		imageSrc: BelowUsernameLocation,
	},
	{
		location: AccountPageLocation.BELOW_AUTHOR_SECTION,
		label: __('Below Author Section', 'learning-management-system'),
		imageSrc: BelowAuthorSectionLocation,
	},
	{
		location: AccountPageLocation.DASHBOARD_NEW_SECTION,
		label: __('Dashboard New Section', 'learning-management-system'),
		imageSrc: DashboardNewSectionLocation,
		hasTitle: true,
	},
	{
		location: AccountPageLocation.NEW_TAB,
		label: __('New Tab', 'learning-management-system'),
		imageSrc: NewTabLocation,
		hasTitle: true,
	},
	{
		location: AccountPageLocation.DASHBOARD_CARD,
		label: __('Dashboard Card', 'learning-management-system'),
		imageSrc: DashboardCardLocation,
	},
];

const learnPageLocations: LocationType[] = [
	{
		location: LearnPageLocation.INFO_BOX_POPOVER_TOP,
		label: __('Info Box Popover Top', 'learning-management-system'),
		imageSrc: LearnPageInfoBoxPopoverTopLocation,
	},
	{
		location: LearnPageLocation.INFO_BOX_POPOVER_BOTTOM,
		label: __('Info Box Popover Bottom', 'learning-management-system'),
		imageSrc: LearnPageInfoBoxPopoverBottomLocation,
	},
	{
		location: LearnPageLocation.LEFT_TO_PROFILE_PIC,
		label: __('Left to Profile Pic', 'learning-management-system'),
		imageSrc: LearnPageLeftToProfilePicLocation,
	},
	{
		location: LearnPageLocation.PROFILE_PIC_POPOVER_TOP,
		label: __('Profile Pic Popover Top', 'learning-management-system'),
		imageSrc: LearnPageAccountPopoverTopLocation,
	},
	{
		location: LearnPageLocation.PROFILE_PIC_POPOVER_BOTTOM,
		label: __('Profile Pic Popover Bottom', 'learning-management-system'),
		imageSrc: LearnPageAccountPopoverBottomLocation,
	},
];

const locationsIndex: {
	[page: string]: LocationType[];
} = {
	[PlacementPage.ACCOUNT_PAGE]: accountPageLocations,
	[PlacementPage.LEARN_PAGE]: learnPageLocations,
};

interface Props {
	index: number;
	data: UiPlacementData;
	onChange: (newData: { [key: string]: any }) => void;
	onDeletePress?: () => void;
	rewardTypes: GamiPressRewardTypes;
}

const UiPlacement: React.FC<Props> = (props) => {
	const { index, data, onChange, onDeletePress, rewardTypes } = props;
	const page = data.page || '';
	const [isCollapsed, setIsCollapsed] = useState(false);

	const {
		getRootProps,
		getRadioProps,
		value: selectedLocationSlug,
	} = useRadioGroup({
		name: 'location',
		defaultValue: data.location,
		onChange: (newLocation) => onChange({ location: newLocation }),
	});

	const group = getRootProps();

	const currentLocation = useMemo(() => {
		const locations = locationsIndex[page];

		if (!locations) {
			return null;
		}

		return locations.find((item) => item.location === selectedLocationSlug);
	}, [page, selectedLocationSlug]);

	const rewardTypeOptions = useMemo(() => {
		return rewardTypes
			? Object.entries(rewardTypes).map(([slug, typeData]) => ({
					label: typeData.plural_name,
					value: slug,
				}))
			: [];
	}, [rewardTypes]);

	return (
		<Draggable draggableId={data.id.toString()} index={index}>
			{(draggableProvided) => (
				<Box
					bg="white"
					mb="8"
					p="2"
					borderWidth="1px"
					borderRadius="3px"
					borderStyle="solid"
					borderColor="gray.100"
					ref={draggableProvided.innerRef}
					{...draggableProvided.draggableProps}
				>
					<Stack direction="column" spacing="8" p="5">
						<Stack
							cursor="pointer"
							onClick={() => setIsCollapsed(!isCollapsed)}
							direction="row"
							spacing="3"
							alignItems="center"
						>
							<span {...draggableProvided.dragHandleProps}>
								<Icon as={Sortable} fontSize="md" color="gray.500" />
							</span>

							<Text fontWeight="semibold" fontSize="lg">
								{currentLocation?.hasTitle && data.title
									? data.title
									: isEmpty(data.types)
										? __('All Types', 'learning-management-system')
										: rewardTypeOptions
												.filter(
													(rewardType) =>
														data.types?.includes(rewardType.value),
												)
												.map((type) => type.label)
												.join(', ')}
							</Text>

							<HStack flex={1} justifyContent="flex-end">
								<IconButton
									aria-label="delete item"
									variant="outline"
									colorScheme="red"
									boxShadow="none"
									size="xs"
									icon={<BiX />}
									onClick={onDeletePress}
								/>
							</HStack>
						</Stack>

						<Collapse in={!isCollapsed}>
							<Stack direction="column" spacing="8">
								<FormControlTwoCol alignItems="center">
									<FormLabel>
										{__('Show Types', 'learning-management-system')}
										<ToolTip
											label={__(
												'Select reward types to show in this location.',
												'learning-management-system',
											)}
										/>
									</FormLabel>

									<Select
										styles={reactSelectStyles}
										isMulti
										closeMenuOnSelect={false}
										isClearable={true}
										placeholder="All"
										defaultValue={rewardTypeOptions.filter(
											(rewardType) => data.types?.includes(rewardType.value),
										)}
										options={rewardTypeOptions}
										noOptionsMessage={({ inputValue }) => {
											if (inputValue.length > 0) {
												return __(
													'No Types found.',
													'learning-management-system',
												);
											}
											return __('No Types.', 'learning-management-system');
										}}
										onChange={(value) =>
											onChange({
												types: value?.map((item) => item.value),
											})
										}
									/>
								</FormControlTwoCol>

								<FormControlTwoCol>
									<FormLabel>
										{__('Page', 'learning-management-system')}
										<ToolTip
											label={__(
												'Select a page to show these reward types.',
												'learning-management-system',
											)}
										/>
									</FormLabel>
									<ChakraSelect
										placeholder={__(
											'Select a Page',
											'learning-management-system',
										)}
										value={data.page}
										onChange={(e) => onChange({ page: e.target.value })}
									>
										<option value="account-page">
											{__('Account Page', 'learning-management-system')}
										</option>
										<option value="learn-page">
											{__('Learn Page', 'learning-management-system')}
										</option>
									</ChakraSelect>
								</FormControlTwoCol>

								{locationsIndex[page]?.length ? (
									<FormControl
										sx={{
											'.chakra-form__label': labelStyles,
										}}
									>
										<FormLabel>
											{__('Location', 'learning-management-system')}
											<ToolTip
												label={__(
													'Select a location show these reward types.',
													'learning-management-system',
												)}
											/>
										</FormLabel>

										<Box
											{...group}
											display="flex"
											flexDirection="row"
											alignItems="flex-start"
											flexWrap="wrap"
											m="-6px"
										>
											{locationsIndex[page]?.map((locationDef: any) => (
												<Box
													key={locationDef.location}
													flex="0 0 25%"
													maxWidth="25%"
													width="25%"
													p="6px"
												>
													<LocationRadioItem
														{...getRadioProps({ value: locationDef.location })}
													>
														<Image
															src={locationDef.imageSrc}
															alt={locationDef.label}
															width="100%"
															borderRadius="3px"
														/>
														<Text
															textAlign="center"
															fontSize="sm"
															mt={2}
															fontWeight="semibold"
														>
															{locationDef.label}
														</Text>
													</LocationRadioItem>
												</Box>
											))}
										</Box>
									</FormControl>
								) : null}

								{currentLocation?.hasTitle ? (
									<FormControlTwoCol alignItems="center">
										<FormLabel>
											{__('Title', 'learning-management-system')}
											<ToolTip
												label={__(
													'Enter title for this location.',
													'learning-management-system',
												)}
											/>
										</FormLabel>
										<Input
											type="text"
											value={data.title || ''}
											onChange={(e) => onChange({ title: e.target.value })}
										/>
									</FormControlTwoCol>
								) : null}
							</Stack>
						</Collapse>
					</Stack>
				</Box>
			)}
		</Draggable>
	);
};

export default UiPlacement;
