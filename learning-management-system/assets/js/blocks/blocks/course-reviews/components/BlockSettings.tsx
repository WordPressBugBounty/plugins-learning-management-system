import { ChakraProvider, extendTheme } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { AdvanceSelect, Color, Panel, Slider, Tab } from '../../../components';
import CourseFilterForBlocks from '../../../components/select-course/select-wrapper';
const theme = extendTheme({});
const BlockSettings = (props: any) => {
	const {
		attributes: {
			clientId,
			fontSize,
			textColor,
			alignment,
			courseId,
			formBackgroundColor,
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
			<Panel title={__('Styles', 'learning-management-system')}>
				<AdvanceSelect
					value={alignment}
					onChange={(val) => setAttributes({ alignment: val })}
					responsive={false}
					label={__('Text Alignment', 'learning-management-system')}
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
					label={__('Text Color', 'learning-management-system')}
					value={textColor}
				/>
				<Slider
					value={fontSize}
					onChange={(val) => setAttributes({ fontSize: val })}
					responsive={false}
					min={0}
					max={100}
					inline={true}
					units={['px']}
					defaultUnit="px"
					label={__('Text Font Size', 'learning-management-system')}
				/>
				<Color
					onChange={(val) => setAttributes({ formBackgroundColor: val })}
					label={__('Form Background Color', 'learning-management-system')}
					value={formBackgroundColor}
				/>
			</Panel>
		</Tab>
	);
};

export default BlockSettings;
