import { ChakraProvider, extendTheme } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import Select from 'react-select';
import { reactSelectStyles } from '../../../../back-end/config/styles';
import { Color, Panel, Slider, Tab, Toggle } from '../../../components';
import CourseFilterForBlocks from '../../../components/select-course/select-wrapper';

const theme = extendTheme({});

const categoryOptions = [
	{ value: 'grid', label: 'Grid' },
	{ value: 'list', label: 'List' },
];

const BlockSettings = (props: any) => {
	const {
		attributes: {
			clientId,
			fontSize,
			textColor,
			enableCourseDuration,
			enableStudentCount,
			enableAvailableSeatsCount,
			enableDateUpdated,
			enableDateStarted,
			courseId,
			layoutOption,
		},
		setAttributes,
	} = props;

	return (
		<Tab Title={__('Settings', 'learning-management-system')}>
			<Panel title={__('General', 'learning-management-system')} initialOpen>
				<ChakraProvider theme={theme} resetCSS>
					<CourseFilterForBlocks
						value={courseId}
						setAttributes={setAttributes}
						setCourseId={props.setSingleCourseId}
					/>
				</ChakraProvider>
			</Panel>

			<Panel title={__('Show/Hide Components', 'learning-management-system')}>
				<Toggle
					label={__('Course Duration ', 'learning-management-system')}
					checked={enableCourseDuration}
					onChange={(value) => setAttributes({ enableCourseDuration: value })}
				/>
				<Toggle
					label={__('Student Count', 'learning-management-system')}
					checked={enableStudentCount}
					onChange={(value) => setAttributes({ enableStudentCount: value })}
				/>
				<Toggle
					label={__('Available Seats Count', 'learning-management-system')}
					checked={enableAvailableSeatsCount}
					onChange={(value) =>
						setAttributes({ enableAvailableSeatsCount: value })
					}
				/>
				<Toggle
					label={__('Date Updated', 'learning-management-system')}
					checked={enableDateUpdated}
					onChange={(value) => setAttributes({ enableDateUpdated: value })}
				/>
				<Toggle
					label={__('Date Started', 'learning-management-system')}
					checked={enableDateStarted}
					onChange={(value) => setAttributes({ enableDateStarted: value })}
				/>
			</Panel>

			<Panel title={__('Styles', 'learning-management-system')}>
				<Color
					onChange={(val) => setAttributes({ textColor: val })}
					label={__('Color', 'learning-management-system')}
					value={textColor || ''}
				/>
				<Slider
					l
					value={fontSize}
					onChange={(val) => setAttributes({ fontSize: val })}
					responsive={false}
					min={0}
					max={100}
					inline={true}
					units={['px']}
					defaultUnit="px"
					label={__('Font Size', 'learning-management-system')}
				/>

				<div style={{ marginBottom: '16px' }}>
					<label
						className="masteriyo-control-label masteriyo-slider-label"
						style={{ display: 'block', marginBottom: '4px' }}
					>
						{__('Layout', 'learning-management-system')}
					</label>
					<Select
						styles={reactSelectStyles}
						isMulti={false}
						closeMenuOnSelect={true}
						placeholder={__('Select layout', 'learning-management-system')}
						defaultValue={categoryOptions.find(
							(cate) => (layoutOption || 'grid') === cate.value,
						)}
						options={categoryOptions}
						onChange={(selectedOption) => {
							setAttributes({ layoutOption: selectedOption?.value });
						}}
					/>
				</div>
			</Panel>
		</Tab>
	);
};

export default BlockSettings;
