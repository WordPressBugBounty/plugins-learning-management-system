import { ChakraProvider, extendTheme } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Panel, Slider, Tab } from '../../../components';
import Color from '../../../components/color';
import CourseFilterForBlocks from '../../../components/select-course/select-wrapper';
const theme = extendTheme({});
const BlockSettings: React.FC<any> = (props) => {
	const {
		attributes: { alignment, textColor, fontSize, courseId },
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
				<Color
					onChange={(val) => setAttributes({ textColor: val })}
					label={__('Text Color', 'learning-management-system')}
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
			</Panel>
		</Tab>
	);
};

export default BlockSettings;
