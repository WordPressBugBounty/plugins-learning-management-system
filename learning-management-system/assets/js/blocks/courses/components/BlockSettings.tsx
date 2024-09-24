import { Image } from '@chakra-ui/react';
import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import React from 'react';
import Select from 'react-select';
import { reactSelectStyles } from '../../../../../assets/js/back-end/config/styles';
import { CoursesBlockGridDesign } from '../../../back-end/constants/images';
import { Panel, Slider, Tab, TabPanel } from '../../components';
import BorderSetting from './BorderSetting';

const categoryOptions =
	_MASTERIYO_BLOCKS_DATA_?.categories?.map((category) => ({
		label: category.name,
		value: category.slug,
	})) || [];

const BlockSettings = (props: any) => {
	const {
		attributes: {
			count,
			columns,
			startCourseButtonBorder,
			categoryIds,
			clientId,
		},
		setAttributes,
	} = props;

	const handleCategoryChange = (selectedOptions) => {
		const updatedCategoryIds = selectedOptions
			? selectedOptions.map((option) => option.value)
			: [];
		setAttributes({ categoryIds: updatedCategoryIds });
	};
	return (
		<InspectorControls>
			<TabPanel>
				<Tab tabTitle={__('Design', 'learning-management-system')}>
					<div className="masteriyo-design-card">
						<div className="masteriyo-design-card__items masteriyo-design-card__items--active">
							<div className="preview-image">
								<Image src={CoursesBlockGridDesign} alt="Grid Design" />
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
						<Slider
							onChange={(val: number) =>
								setAttributes({ count: val ? val : 1 })
							}
							label={__('No. of Courses', 'learning-management-system')}
							min={1}
							step={1}
							value={count}
						/>
					</Panel>
					<Panel title={__('Layout', 'learning-management-system')}>
						<Slider
							onChange={(val: number) =>
								setAttributes({ columns: val ? val : 1 })
							}
							label={__('Columns', 'learning-management-system')}
							min={1}
							max={4}
							step={1}
							value={columns}
						/>
					</Panel>
					<Panel title={__('Filter', 'learning-management-system')}>
						<div className="masteriyo-control masteriyo-select">
							<div className="masteriyo-control-head masteriyo-select-head">
								<label
									htmlFor={`masteriyo-select-button-${clientId}`}
									className="masteriyo-control-label masteriyo-select-label"
									style={{ marginBottom: '12px' }}
								>
									{__('Categories', 'learning-management-system')}
								</label>
							</div>
							<div className="masteriyo-control-body masteriyo-select-body">
								<Select
									styles={reactSelectStyles}
									isMulti
									closeMenuOnSelect={false}
									placeholder={__('Select', 'learning-management-system')}
									defaultValue={categoryOptions.filter(
										(cate) => categoryIds?.includes(cate.value),
									)}
									options={categoryOptions}
									noOptionsMessage={({ inputValue }) => {
										if (inputValue.length > 0) {
											return __(
												'No categories found.',
												'learning-management-system',
											);
										}
										return __('No categories.', 'learning-management-system');
									}}
									onChange={handleCategoryChange}
								/>
							</div>
						</div>
					</Panel>
					<Panel title={__('Button', 'learning-management-system')}>
						<BorderSetting
							value={startCourseButtonBorder}
							onChange={handleCategoryChange}
						/>
					</Panel>
				</Tab>
			</TabPanel>
		</InspectorControls>
	);
};

export default BlockSettings;
