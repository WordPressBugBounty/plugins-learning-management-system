import { ChakraProvider, extendTheme } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import {
	Color,
	ImageSizeControl,
	Panel,
	Slider,
	Tab,
	Toggle,
} from '../../../components';
import CourseFilterForBlocks from '../../../components/select-course/select-wrapper';
const theme = extendTheme({});
const BlockSettings = (props: any) => {
	const {
		attributes: {
			textColor,
			fontSize,
			height_n_width,
			margin,
			padding,
			enableAuthorsAvatar,
			enableAuthorsName,
			clientId,
			courseId,
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
					label={__("Author's Avatar", 'learning-management-system')}
					checked={enableAuthorsAvatar}
					onChange={(value) => setAttributes({ enableAuthorsAvatar: value })}
				/>
				<Toggle
					label={__("Author's Name", 'learning-management-system')}
					checked={enableAuthorsName}
					onChange={(value) => setAttributes({ enableAuthorsName: value })}
				/>
			</Panel>
			<Panel title={__('Styles', 'learning-management-system')}>
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
					units={['px']}
					defaultUnit="px"
					label={__('Font Size', 'learning-management-system')}
				/>
				<ImageSizeControl
					value={height_n_width}
					onChange={(val) => setAttributes({ height_n_width: val })}
					label={__('Image Size', 'learning-management-system')}
					units={['px', '%', 'em']}
					responsive={true}
				/>
			</Panel>
		</Tab>
	);
};

export default BlockSettings;
