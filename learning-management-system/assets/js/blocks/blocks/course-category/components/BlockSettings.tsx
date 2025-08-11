import { ChakraProvider, extendTheme } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import PaddingSetting from '../../../components/PaddingSetting';
import CourseFilterForBlocks from '../../../components/select-course/select-wrapper';
import { Panel, Slider, Tab } from './../../../components';
import AdvanceSelect from './../../../components/advance-select';
import Color from './../../../components/color';
import BorderSetting from './BorderSetting';
const theme = extendTheme({});
const BlockSettings: React.FC<any> = (props) => {
	const {
		attributes: {
			alignment,
			textColor,
			fontSize,
			courseId,
			borderRadius,
			padding,
			startCategoryBorder,
		},
		setAttributes,
	} = props;

	return (
		<Tab tabTitle={__('Settings', 'learning-management-system')}>
			<Panel title={__('General', 'learning-management-system')} initialOpen>
				<ChakraProvider theme={theme} resetCSS>
					<CourseFilterForBlocks
						value={courseId}
						setAttributes={setAttributes}
						setCourseId={props.setSingleCourseId}
					/>
				</ChakraProvider>
			</Panel>

			<Panel title={__('Styles', 'learning-management-system')} initialOpen>
				<AdvanceSelect
					value={alignment}
					onChange={(val) => setAttributes({ alignment: val })}
					responsive={false}
					label={__('Alignment', 'learning-management-system')}
					options={[
						{
							label: __('Left', 'learning-management-system'),
							value: 'left',
							icon: 'text-align-left',
						},
						{
							label: __('Center', 'learning-management-system'),
							value: 'center',
							icon: 'text-align-center',
						},
						{
							label: __('Right', 'learning-management-system'),
							value: 'right',
							icon: 'text-align-right',
						},
						{
							label: __('Justify', 'learning-management-system'),
							value: 'justify',
							icon: 'text-align-justify',
						},
					]}
				/>
				<Color
					onChange={(val) => setAttributes({ textColor: val })}
					label={__('Color', 'learning-management-system')}
					value={textColor || ''}
				/>
				<Slider
					value={fontSize}
					onChange={(val) => setAttributes({ fontSize: val })}
					responsive={false}
					min={0}
					max={100}
					inline={true}
					units={['px', 'em', '%']}
					defaultUnit="px"
					label={__('Font Size', 'learning-management-system')}
				/>
				<Slider
					value={borderRadius}
					onChange={(val) => setAttributes({ borderRadius: val })}
					responsive={false}
					min={0}
					max={100}
					inline={true}
					units={['px', 'em', '%']}
					defaultUnit="px"
					label={__('Border', 'learning-management-system')}
				/>
				<PaddingSetting
					value={padding}
					onChange={(val) => setAttributes({ padding: val })}
				/>
				<BorderSetting
					value={startCategoryBorder}
					onChange={(val) => setAttributes({ startCategoryBorder: val })}
				/>
			</Panel>
		</Tab>
	);
};

export default BlockSettings;
