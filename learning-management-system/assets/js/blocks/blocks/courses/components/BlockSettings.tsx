import { Box, HStack, Icon, useRadio, useRadioGroup } from '@chakra-ui/react';
import { InspectorControls } from '@wordpress/block-editor';
import {
	RangeControl,
	SelectControl,
	ToggleControl,
	__experimentalToolsPanel as ToolsPanel,
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import React, { useMemo } from 'react';
import Select from 'react-select';

import { reactSelectStyles } from './../../../../../../assets/js/back-end/config/styles';
import {
	CourseDefaultLayout,
	CourseLayout1tLayout,
	CourseLayout2tLayout,
	ThreeCoursesGridView,
	ThreeCoursesListView,
} from './../../../../back-end/constants/images';

import { Tab, TabPanel } from './../../../components';

const TabComponent = Tab as any;

// Declare global types
declare global {
	const _MASTERIYO_BLOCKS_DATA_: {
		categories?: Array<{ name: string; slug: string }>;
	};
}

interface CategoryOption {
	label: string;
	value: string;
}

const BlockSettings = ({ attributes, setAttributes }: any) => {
	const {
		count = 12,
		columns = 3,
		categoryIds = [],
		clientId,
		viewType = 'grid',
		enableCourseFilters = false,
		enableCategoryFilter = true,
		enableDifficultyLevelFilter = true,
		enablePriceTypeFilter = true,
		enablePriceFilter = true,
		enableRatingFilter = true,
		enableSorting = false,
		enableSortByDate = true,
		enableSortByPrice = true,
		enableSortByRating = true,
		enableSortByTitle = true,
		sortBy = 'date',
		sortOrder = 'desc',
		template = 'simple',
		showSearch = true,
		showThumbnail = true,
		showDifficultyBadge = true,
		showCategories = true,
		showCourseTitle = true,
		showAuthor = true,
		showAuthorAvatar = true,
		showAuthorName = true,
		showRating = true,
		showCourseDescription = true,
		showMetadata = true,
		showCourseDuration = true,
		showStudentsCount = true,
		showLessonsCount = true,
		showCardFooter = true,
		showPrice = true,
		showEnrollButton = true,
	} = attributes;

	const categoryOptions = useMemo(() => {
		return (
			_MASTERIYO_BLOCKS_DATA_?.categories?.map((category: any) => ({
				label: category.name,
				value: category.slug,
			})) || []
		);
	}, []);

	const handleCategoryChange = (selectedOptions: any) => {
		const updatedCategoryIds = selectedOptions
			? selectedOptions.map((option: any) => option.value)
			: [];
		setAttributes({ categoryIds: updatedCategoryIds });
	};

	const handleViewTypeChange = (val: string) => {
		setAttributes({ viewType: val });
	};

	const viewModeOptions = [
		{ value: 'grid', icon: ThreeCoursesGridView },
		{ value: 'list', icon: ThreeCoursesListView },
	];

	// Always call hooks unconditionally to follow React rules of hooks
	const { getRootProps, getRadioProps } = useRadioGroup({
		name: 'viewType',
		value: viewType,
		onChange: handleViewTypeChange,
	});
	const group = getRootProps();

	// Pre-render radio options to avoid conditional hook calls
	const radioOptions = viewModeOptions.map((opt) => {
		const radio = getRadioProps({ value: opt.value });
		const { getInputProps, getRadioProps: getItemRadioProps } = useRadio(radio);
		return {
			value: opt.value,
			icon: opt.icon,
			inputProps: getInputProps(),
			radioProps: getItemRadioProps(),
		};
	});

	const templates = [
		{
			value: 'simple',
			label: __('Simple', 'learning-management-system'),
			image: CourseDefaultLayout,
		},
		{
			value: 'modern',
			label: __('Modern', 'learning-management-system'),
			image: CourseLayout1tLayout,
		},
		{
			value: 'overlay',
			label: __('Overlay', 'learning-management-system'),
			image: CourseLayout2tLayout,
		},
	];

	return (
		<InspectorControls>
			<TabPanel>
				<Tab tabTitle={__('Design', 'learning-management-system')}>
					<div style={{ padding: '16px' }}>
						<label
							className="components-base-control__label"
							style={{ marginBottom: '8px', display: 'block' }}
						>
							{__('Choose Layout', 'learning-management-system')}
						</label>

						<div
							style={{
								display: 'grid',
								gridTemplateColumns: '1fr',
								gap: '12px',
							}}
						>
							{templates.map((tmpl) => (
								<div
									key={tmpl.value}
									onClick={() => setAttributes({ template: tmpl.value })}
									className={`masteriyo-design-card__items ${
										template === tmpl.value
											? 'masteriyo-design-card__items--active'
											: ''
									}`}
									style={{
										cursor: 'pointer',
										border:
											template === tmpl.value
												? '2px solid #2271b1'
												: '2px solid #ddd',
										borderRadius: '4px',
										padding: '12px',
										background: template === tmpl.value ? '#f0f6fc' : '#fff',
										transition: 'all 0.2s',
									}}
								>
									<div
										className="preview-image"
										style={{ marginBottom: '8px' }}
									>
										<img
											src={tmpl.image}
											alt={tmpl.label}
											style={{ width: '100%', borderRadius: '2px' }}
										/>
									</div>
									<div
										className="status"
										style={{
											display: 'flex',
											justifyContent: 'space-between',
											alignItems: 'center',
										}}
									>
										<span className="title" style={{ fontWeight: '500' }}>
											{tmpl.label}
										</span>
										{template === tmpl.value && (
											<span
												className="active-label"
												style={{
													background: '#2271b1',
													color: '#fff',
													padding: '2px 8px',
													borderRadius: '3px',
													fontSize: '11px',
												}}
											>
												{__('Active', 'learning-management-system')}
											</span>
										)}
									</div>
								</div>
							))}
						</div>
					</div>
				</Tab>

				<Tab tabTitle={__('Settings', 'learning-management-system')}>
					<ToolsPanel
						label={__('Content', 'learning-management-system')}
						resetAll={() => {
							setAttributes({
								count: 12,
								categoryIds: [],
								sortBy: 'date',
								sortOrder: 'desc',
							});
						}}
					>
						<ToolsPanelItem
							hasValue={() => attributes.count !== 12}
							label={__('No. of Courses', 'learning-management-system')}
							onDeselect={() => setAttributes({ count: 12 })}
							isShownByDefault
						>
							<RangeControl
								label={__('No. of Courses', 'learning-management-system')}
								value={count}
								onChange={(val) => setAttributes({ count: val || 1 })}
								min={1}
								max={100}
								step={1}
							/>
						</ToolsPanelItem>

						<ToolsPanelItem
							hasValue={() =>
								attributes.categoryIds && attributes.categoryIds.length > 0
							}
							label={__('Categories', 'learning-management-system')}
							onDeselect={() => setAttributes({ categoryIds: [] })}
							isShownByDefault
						>
							<div>
								<label
									className="components-base-control__label"
									style={{ marginBottom: '8px', display: 'block' }}
								>
									{__('Categories', 'learning-management-system')}
								</label>
								<Select
									styles={reactSelectStyles}
									isMulti
									closeMenuOnSelect={false}
									placeholder={__(
										'Select Categories',
										'learning-management-system',
									)}
									value={categoryOptions.filter((cate) =>
										categoryIds?.includes(cate.value),
									)}
									options={categoryOptions}
									onChange={handleCategoryChange}
								/>
							</div>
						</ToolsPanelItem>

						<ToolsPanelItem
							hasValue={() => attributes.sortBy !== 'date'}
							label={__('Sort By', 'learning-management-system')}
							onDeselect={() => setAttributes({ sortBy: 'date' })}
							isShownByDefault
						>
							<SelectControl
								label={__('Sort By', 'learning-management-system')}
								value={sortBy}
								onChange={(val) => setAttributes({ sortBy: val })}
								options={[
									{
										label: __('Date', 'learning-management-system'),
										value: 'date',
									},
									{
										label: __('Title', 'learning-management-system'),
										value: 'title',
									},
									{
										label: __('Price', 'learning-management-system'),
										value: 'price',
									},
									{
										label: __('Rating', 'learning-management-system'),
										value: 'rating',
									},
								]}
							/>
						</ToolsPanelItem>

						<ToolsPanelItem
							hasValue={() => attributes.sortOrder !== 'desc'}
							label={__('Sort Order', 'learning-management-system')}
							onDeselect={() => setAttributes({ sortOrder: 'desc' })}
							isShownByDefault
						>
							<SelectControl
								label={__('Sort Order', 'learning-management-system')}
								value={sortOrder}
								onChange={(val) => setAttributes({ sortOrder: val })}
								options={[
									{
										label: __('Descending', 'learning-management-system'),
										value: 'desc',
									},
									{
										label: __('Ascending', 'learning-management-system'),
										value: 'asc',
									},
								]}
							/>
						</ToolsPanelItem>
					</ToolsPanel>

					<ToolsPanel
						label={__('Display', 'learning-management-system')}
						resetAll={() => {
							setAttributes({
								viewType: 'grid',
								columns: 3,
								showSearch: true,
								showThumbnail: true,
								showDifficultyBadge: true,
								showCategories: true,
								showCourseTitle: true,
								showAuthor: true,
								showAuthorAvatar: true,
								showAuthorName: true,
								showRating: true,
								showCourseDescription: true,
								showMetadata: true,
								showCourseDuration: true,
								showStudentsCount: true,
								showLessonsCount: true,
								showCardFooter: true,
								showPrice: true,
								showEnrollButton: true,
							});
						}}
					>
						<ToolsPanelItem
							hasValue={() => attributes.showSearch === false}
							label={__('Search', 'learning-management-system')}
							onDeselect={() => setAttributes({ showSearch: true })}
							isShownByDefault
						>
							<ToggleControl
								label={__('Search', 'learning-management-system')}
								checked={showSearch}
								onChange={(value) => setAttributes({ showSearch: value })}
							/>
						</ToolsPanelItem>

						{template === 'simple' && (
							<ToolsPanelItem
								hasValue={() => attributes.viewType !== 'grid'}
								label={__('Display Mode', 'learning-management-system')}
								onDeselect={() => setAttributes({ viewType: 'grid' })}
								isShownByDefault
							>
								<div>
									<label
										className="components-base-control__label"
										style={{ marginBottom: '12px', display: 'block' }}
									>
										{__('Display Mode', 'learning-management-system')}
									</label>

									<HStack spacing={4} {...group}>
										{radioOptions.map((opt) => {
											const isChecked = viewType === opt.value;

											return (
												<Box as="label" key={opt.value} flex="1">
													<input {...opt.inputProps} hidden />
													<Box
														{...opt.radioProps}
														cursor="pointer"
														borderWidth="2px"
														borderRadius="8px"
														p="6"
														bg={isChecked ? '#f0f6fc' : 'white'}
														borderColor={isChecked ? '#2271b1' : '#dcdcde'}
														boxShadow={
															isChecked
																? '0 0 0 1px #2271b1'
																: '0 1px 1px rgba(0, 0, 0, 0.04)'
														}
														transition="all 0.15s ease-in-out"
														_hover={{
															borderColor: isChecked ? '#2271b1' : '#949494',
															boxShadow: isChecked
																? '0 0 0 1px #2271b1'
																: '0 2px 4px rgba(0, 0, 0, 0.08)',
														}}
													>
														<Icon
															as={opt.icon}
															boxSize="48px"
															display="block"
															color={isChecked ? '#2271b1' : '#50575e'}
														/>
													</Box>
												</Box>
											);
										})}
									</HStack>
								</div>
							</ToolsPanelItem>
						)}

						{viewType === 'grid' && (
							<ToolsPanelItem
								hasValue={() => attributes.columns !== 3}
								label={__('Courses Per Row', 'learning-management-system')}
								onDeselect={() => setAttributes({ columns: 3 })}
								isShownByDefault
							>
								<RangeControl
									label={__('Courses Per Row', 'learning-management-system')}
									value={columns}
									onChange={(val) => setAttributes({ columns: val || 3 })}
									min={1}
									max={4}
									step={1}
								/>
							</ToolsPanelItem>
						)}

						<ToolsPanelItem
							hasValue={() => attributes.showThumbnail === false}
							label={__('Thumbnail', 'learning-management-system')}
							onDeselect={() => setAttributes({ showThumbnail: true })}
							isShownByDefault
						>
							<ToggleControl
								label={__('Thumbnail', 'learning-management-system')}
								checked={showThumbnail}
								onChange={(value) => setAttributes({ showThumbnail: value })}
							/>
						</ToolsPanelItem>

						{showThumbnail && (
							<ToolsPanelItem
								hasValue={() => attributes.showDifficultyBadge === false}
								label={__('Difficulty Badge', 'learning-management-system')}
								onDeselect={() => setAttributes({ showDifficultyBadge: true })}
								isShownByDefault
							>
								<ToggleControl
									label={__('Difficulty Badge', 'learning-management-system')}
									checked={showDifficultyBadge}
									onChange={(value) =>
										setAttributes({ showDifficultyBadge: value })
									}
								/>
							</ToolsPanelItem>
						)}

						<ToolsPanelItem
							hasValue={() => attributes.showCategories === false}
							label={__('Categories', 'learning-management-system')}
							onDeselect={() => setAttributes({ showCategories: true })}
							isShownByDefault
						>
							<ToggleControl
								label={__('Categories', 'learning-management-system')}
								checked={showCategories}
								onChange={(value) => setAttributes({ showCategories: value })}
							/>
						</ToolsPanelItem>

						<ToolsPanelItem
							hasValue={() => attributes.showCourseTitle === false}
							label={__('Course Title', 'learning-management-system')}
							onDeselect={() => setAttributes({ showCourseTitle: true })}
							isShownByDefault
						>
							<ToggleControl
								label={__('Course Title', 'learning-management-system')}
								checked={showCourseTitle}
								onChange={(value) => setAttributes({ showCourseTitle: value })}
							/>
						</ToolsPanelItem>

						<ToolsPanelItem
							hasValue={() => attributes.showAuthor === false}
							label={__('Author', 'learning-management-system')}
							onDeselect={() => setAttributes({ showAuthor: true })}
							isShownByDefault
						>
							<ToggleControl
								label={__('Author', 'learning-management-system')}
								checked={showAuthor}
								onChange={(value) => setAttributes({ showAuthor: value })}
							/>
						</ToolsPanelItem>

						{showAuthor && (
							<>
								<ToolsPanelItem
									hasValue={() => attributes.showAuthorAvatar === false}
									label={__('Avatar of Author', 'learning-management-system')}
									onDeselect={() => setAttributes({ showAuthorAvatar: true })}
									isShownByDefault
								>
									<ToggleControl
										label={__('Avatar of Author', 'learning-management-system')}
										checked={showAuthorAvatar}
										onChange={(value) =>
											setAttributes({ showAuthorAvatar: value })
										}
									/>
								</ToolsPanelItem>

								<ToolsPanelItem
									hasValue={() => attributes.showAuthorName === false}
									label={__('Name of Author', 'learning-management-system')}
									onDeselect={() => setAttributes({ showAuthorName: true })}
									isShownByDefault
								>
									<ToggleControl
										label={__('Name of Author', 'learning-management-system')}
										checked={showAuthorName}
										onChange={(value) =>
											setAttributes({ showAuthorName: value })
										}
									/>
								</ToolsPanelItem>
							</>
						)}

						<ToolsPanelItem
							hasValue={() => attributes.showRating === false}
							label={__('Rating', 'learning-management-system')}
							onDeselect={() => setAttributes({ showRating: true })}
							isShownByDefault
						>
							<ToggleControl
								label={__('Rating', 'learning-management-system')}
								checked={showRating}
								onChange={(value) => setAttributes({ showRating: value })}
							/>
						</ToolsPanelItem>

						<ToolsPanelItem
							hasValue={() => attributes.showCourseDescription === false}
							label={__(
								'Highlights / Description',
								'learning-management-system',
							)}
							onDeselect={() => setAttributes({ showCourseDescription: true })}
							isShownByDefault
						>
							<ToggleControl
								label={__(
									'Highlights / Description',
									'learning-management-system',
								)}
								checked={showCourseDescription}
								onChange={(value) =>
									setAttributes({ showCourseDescription: value })
								}
							/>
						</ToolsPanelItem>
					</ToolsPanel>

					<ToolsPanel
						label={__('Filters and Sorting', 'learning-management-system')}
						resetAll={() => {
							setAttributes({
								enableCourseFilters: false,
								enableCategoryFilter: false,
								enableDifficultyLevelFilter: false,
								enablePriceTypeFilter: false,
								enablePriceFilter: false,
								enableRatingFilter: false,
								enableSorting: false,
								enableSortByDate: false,
								enableSortByPrice: false,
								enableSortByRating: false,
								enableSortByTitle: false,
							});
						}}
					>
						<ToolsPanelItem
							hasValue={() => attributes.enableCourseFilters === true}
							label={__('Enable Course Filters', 'learning-management-system')}
							onDeselect={() =>
								setAttributes({
									enableCourseFilters: false,
									enableCategoryFilter: true,
									enableDifficultyLevelFilter: true,
									enablePriceTypeFilter: true,
									enablePriceFilter: true,
									enableRatingFilter: true,
								})
							}
							isShownByDefault
						>
							<ToggleControl
								label={__(
									'Enable Course Filters',
									'learning-management-system',
								)}
								help={__(
									'Shows filters on the frontend. These settings work independently for this block.',
									'learning-management-system',
								)}
								checked={enableCourseFilters}
								onChange={(value) =>
									setAttributes({ enableCourseFilters: value })
								}
							/>
						</ToolsPanelItem>

						{enableCourseFilters && (
							<>
								<ToolsPanelItem
									hasValue={() => attributes.enableCategoryFilter === true}
									label={__(
										'Enable Category Filter',
										'learning-management-system',
									)}
									onDeselect={() =>
										setAttributes({ enableCategoryFilter: true })
									}
									isShownByDefault
								>
									<ToggleControl
										label={__(
											'Enable Category Filter',
											'learning-management-system',
										)}
										checked={enableCategoryFilter}
										onChange={(value) =>
											setAttributes({ enableCategoryFilter: value })
										}
									/>
								</ToolsPanelItem>

								<ToolsPanelItem
									hasValue={() =>
										attributes.enableDifficultyLevelFilter === true
									}
									label={__(
										'Enable Difficulty Level Filter',
										'learning-management-system',
									)}
									onDeselect={() =>
										setAttributes({ enableDifficultyLevelFilter: true })
									}
									isShownByDefault
								>
									<ToggleControl
										label={__(
											'Enable Difficulty Level Filter',
											'learning-management-system',
										)}
										checked={enableDifficultyLevelFilter}
										onChange={(value) =>
											setAttributes({ enableDifficultyLevelFilter: value })
										}
									/>
								</ToolsPanelItem>

								<ToolsPanelItem
									hasValue={() => attributes.enablePriceTypeFilter === true}
									label={__(
										'Enable Price Type Filter',
										'learning-management-system',
									)}
									onDeselect={() =>
										setAttributes({ enablePriceTypeFilter: true })
									}
									isShownByDefault
								>
									<ToggleControl
										label={__(
											'Enable Price Type Filter',
											'learning-management-system',
										)}
										checked={enablePriceTypeFilter}
										onChange={(value) =>
											setAttributes({ enablePriceTypeFilter: value })
										}
									/>
								</ToolsPanelItem>

								<ToolsPanelItem
									hasValue={() => attributes.enablePriceFilter === true}
									label={__(
										'Enable Price Filter',
										'learning-management-system',
									)}
									onDeselect={() => setAttributes({ enablePriceFilter: true })}
									isShownByDefault
								>
									<ToggleControl
										label={__(
											'Enable Price Filter',
											'learning-management-system',
										)}
										checked={enablePriceFilter}
										onChange={(value) =>
											setAttributes({ enablePriceFilter: value })
										}
									/>
								</ToolsPanelItem>

								<ToolsPanelItem
									hasValue={() => attributes.enableRatingFilter === true}
									label={__(
										'Enable Rating Filter',
										'learning-management-system',
									)}
									onDeselect={() => setAttributes({ enableRatingFilter: true })}
									isShownByDefault
								>
									<ToggleControl
										label={__(
											'Enable Rating Filter',
											'learning-management-system',
										)}
										checked={enableRatingFilter}
										onChange={(value) =>
											setAttributes({ enableRatingFilter: value })
										}
									/>
								</ToolsPanelItem>
							</>
						)}

						<ToolsPanelItem
							hasValue={() => attributes.enableSorting === true}
							label={__('Enable Sorting', 'learning-management-system')}
							onDeselect={() =>
								setAttributes({
									enableSorting: false,
									enableSortByDate: true,
									enableSortByPrice: true,
									enableSortByRating: true,
									enableSortByTitle: true,
								})
							}
							isShownByDefault
						>
							<ToggleControl
								label={__('Enable Sorting', 'learning-management-system')}
								help={__(
									'Shows sorting options on the frontend. These settings work independently for this block.',
									'learning-management-system',
								)}
								checked={enableSorting}
								onChange={(value) => setAttributes({ enableSorting: value })}
							/>
						</ToolsPanelItem>

						{enableSorting && (
							<>
								<ToolsPanelItem
									hasValue={() => attributes.enableSortByDate === true}
									label={__(
										'Enable sorting by date',
										'learning-management-system',
									)}
									onDeselect={() => setAttributes({ enableSortByDate: true })}
									isShownByDefault
								>
									<ToggleControl
										label={__(
											'Enable sorting by date',
											'learning-management-system',
										)}
										checked={enableSortByDate}
										onChange={(value) =>
											setAttributes({ enableSortByDate: value })
										}
									/>
								</ToolsPanelItem>

								<ToolsPanelItem
									hasValue={() => attributes.enableSortByPrice === true}
									label={__(
										'Enable sorting by price',
										'learning-management-system',
									)}
									onDeselect={() => setAttributes({ enableSortByPrice: true })}
									isShownByDefault
								>
									<ToggleControl
										label={__(
											'Enable sorting by price',
											'learning-management-system',
										)}
										checked={enableSortByPrice}
										onChange={(value) =>
											setAttributes({ enableSortByPrice: value })
										}
									/>
								</ToolsPanelItem>

								<ToolsPanelItem
									hasValue={() => attributes.enableSortByRating === true}
									label={__(
										'Enable sorting by average rating',
										'learning-management-system',
									)}
									onDeselect={() => setAttributes({ enableSortByRating: true })}
									isShownByDefault
								>
									<ToggleControl
										label={__(
											'Enable sorting by average rating',
											'learning-management-system',
										)}
										checked={enableSortByRating}
										onChange={(value) =>
											setAttributes({ enableSortByRating: value })
										}
									/>
								</ToolsPanelItem>

								<ToolsPanelItem
									hasValue={() => attributes.enableSortByTitle === true}
									label={__(
										'Enable sorting by course title',
										'learning-management-system',
									)}
									onDeselect={() => setAttributes({ enableSortByTitle: true })}
									isShownByDefault
								>
									<ToggleControl
										label={__(
											'Enable sorting by course title',
											'learning-management-system',
										)}
										checked={enableSortByTitle}
										onChange={(value) =>
											setAttributes({ enableSortByTitle: value })
										}
									/>
								</ToolsPanelItem>
							</>
						)}
					</ToolsPanel>
				</Tab>
			</TabPanel>
		</InspectorControls>
	);
};

export default BlockSettings;
