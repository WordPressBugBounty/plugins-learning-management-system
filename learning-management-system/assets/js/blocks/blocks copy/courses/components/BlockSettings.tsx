import { Box, HStack, Icon, useRadio, useRadioGroup } from '@chakra-ui/react';
import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import React, { useMemo } from 'react';
import Select from 'react-select';

import { reactSelectStyles } from './../../../../../../assets/js/back-end/config/styles';
import {
	CoursesBlockGridDesign,
	ThreeCoursesGridView,
	ThreeCoursesListView,
} from './../../../../back-end/constants/images';

// import { ToggleControl } from '@wordpress/components';
import { Panel, Slider, Tab, TabPanel, Toggle } from './../../../components';
import BorderSetting from './BorderSetting';

const BlockSettings = ({ attributes, setAttributes }) => {
	const {
		count,
		columns,
		startCourseButtonBorder,
		categoryIds,
		clientId,
		viewType,
		enableCourseFilters,
	} = attributes;

	const categoryOptions = useMemo(() => {
		return (
			_MASTERIYO_BLOCKS_DATA_?.categories?.map((category) => ({
				label: category.name,
				value: category.slug,
			})) || []
		);
	}, []);

	const handleCategoryChange = (selectedOptions) => {
		const updatedCategoryIds = selectedOptions
			? selectedOptions.map((option) => option.value)
			: [];
		setAttributes({ categoryIds: updatedCategoryIds });
	};

	const handleViewTypeChange = (val) => {
		setAttributes({ viewType: val });
	};

	const viewModeOptions = [
		{ value: 'grid', icon: ThreeCoursesGridView },
		{ value: 'list', icon: ThreeCoursesListView },
	];

	const { getRootProps, getRadioProps } = useRadioGroup({
		name: 'viewType',
		value: viewType,
		onChange: handleViewTypeChange,
	});
	const group = getRootProps();

	return (
		<InspectorControls>
			<TabPanel>
				<Tab tabTitle={__('Design', 'learning-management-system')}>
					<div className="masteriyo-design-card">
						<div className="masteriyo-design-card__items masteriyo-design-card__items--active">
							<div className="preview-image">
								<img src={CoursesBlockGridDesign} alt="Grid Design" />
							</div>
							<div className="status">
								<span className="title">
									{__('Grid', 'learning-management-system')}
								</span>
								<span className="active-label">
									{__('Active', 'learning-management-system')}
								</span>
							</div>
						</div>
					</div>
					<div className="coming-soon-notice">
						<span>{__('New Design', 'learning-management-system')}</span>
						<span>{__('Coming Soon', 'learning-management-system')}</span>
					</div>
				</Tab>

				<Tab tabTitle={__('Settings', 'learning-management-system')}>
					<Panel
						title={__('General', 'learning-management-system')}
						initialOpen
					>
						<Toggle
							label={__('Enable Course Filters ', 'learning-management-system')}
							checked={enableCourseFilters}
							onChange={(value) =>
								setAttributes({ enableCourseFilters: value })
							}
						/>

						<Slider
							onChange={(val) => setAttributes({ count: val || 1 })}
							label={__('No. of Courses', 'learning-management-system')}
							min={1}
							step={1}
							value={count}
						/>

						<Box mb="4">
							<label
								className="masteriyo-control-label"
								style={{ marginBottom: '8px', display: 'block' }}
							>
								{__('View Mode', 'learning-management-system')}
							</label>

							<HStack spacing={4} {...group}>
								{viewModeOptions.map((opt) => {
									const radio = getRadioProps({ value: opt.value });
									const {
										getInputProps,
										getRadioProps: getItemRadioProps,
										state,
									} = useRadio(radio);
									const input = getInputProps();
									const checkbox = getItemRadioProps();

									return (
										<Box as="label" key={opt.value}>
											<input {...input} hidden />
											<Box
												{...checkbox}
												cursor="pointer"
												borderWidth="2px"
												borderRadius="md"
												p="2"
												bg={state.isChecked ? 'blue.50' : 'gray.50'}
												borderColor={state.isChecked ? 'blue.500' : 'gray.200'}
												boxShadow={
													state.isChecked
														? '0 0 0 2px rgba(66, 153, 225, 0.6)'
														: 'none'
												}
												transition="all 0.2s"
												width="100px"
											>
												<Icon
													as={opt.icon}
													width="100%"
													height="auto"
													display="block"
												/>
											</Box>
										</Box>
									);
								})}
							</HStack>
							<input
								type="hidden"
								value={viewType}
								readOnly
								data-debug="view-type"
							/>
						</Box>
					</Panel>

					<Panel title={__('Layout', 'learning-management-system')}>
						<Slider
							onChange={(val) => setAttributes({ columns: val || 1 })}
							label={__('Columns', 'learning-management-system')}
							min={1}
							max={4}
							step={1}
							value={columns}
						/>
					</Panel>

					<Panel title={__('Filter', 'learning-management-system')}>
						<div className="masteriyo-control masteriyo-select">
							<label
								htmlFor={`masteriyo-select-button-${clientId}`}
								className="masteriyo-control-label"
								style={{ marginBottom: '12px' }}
							>
								{__('Categories', 'learning-management-system')}
							</label>
							<Select
								styles={reactSelectStyles}
								isMulti
								closeMenuOnSelect={false}
								placeholder={__('Select', 'learning-management-system')}
								defaultValue={categoryOptions.filter((cate) =>
									categoryIds?.includes(cate.value),
								)}
								options={categoryOptions}
								onChange={handleCategoryChange}
							/>
						</div>
					</Panel>

					<Panel title={__('Button', 'learning-management-system')}>
						<BorderSetting
							value={startCourseButtonBorder}
							onChange={(val) =>
								setAttributes({ startCourseButtonBorder: val })
							}
						/>
					</Panel>
				</Tab>
			</TabPanel>
		</InspectorControls>
	);
};

export default BlockSettings;
