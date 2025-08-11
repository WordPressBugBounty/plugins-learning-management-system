import { ChakraProvider, extendTheme } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Panel, Slider, Tab } from '../../../components';
import PaddingSetting from '../../../components/PaddingSetting';
import Color from '../../../components/color';
import CourseFilterForBlocks from '../../../components/select-course/select-wrapper';
const theme = extendTheme({});
const BlockSettings: React.FC<any> = (props) => {
	const {
		attributes: {
			backgroundColor,
			fontSize,
			courseId,
			borderRadius,
			padding,
			textGroupColor,
			buyButtonTextColor,
			buttonFontSize,
			buyButtonBackgroundColor,
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
				<Color
					onChange={(val) => setAttributes({ backgroundColor: val })}
					label={__('Background Color', 'learning-management-system')}
					value={backgroundColor || ''}
				/>
				<Color
					onChange={(val) => setAttributes({ buyButtonBackgroundColor: val })}
					label={__(
						'Buy Button Background Color',
						'learning-management-system',
					)}
					value={buyButtonBackgroundColor || ''}
				/>
				<Color
					onChange={(val) => setAttributes({ textGroupColor: val })}
					label={__('Group Course Title', 'learning-management-system')}
					value={textGroupColor || ''}
				/>
				<Color
					onChange={(val) => setAttributes({ buyButtonTextColor: val })}
					label={__('Button Text Color', 'learning-management-system')}
					value={buyButtonTextColor || ''}
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
					label={__('Course Group Font Size', 'learning-management-system')}
				/>
				<Slider
					value={buttonFontSize}
					onChange={(val) => setAttributes({ buttonFontSize: val })}
					responsive={false}
					min={0}
					max={100}
					inline={true}
					units={['px', 'em', '%']}
					defaultUnit="px"
					label={__('Button Font Size', 'learning-management-system')}
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
					label={__('Border Radius', 'learning-management-system')}
				/>
				<PaddingSetting
					value={padding}
					onChange={(val) => setAttributes({ padding: val })}
				/>
			</Panel>
		</Tab>
	);
};

export default BlockSettings;
