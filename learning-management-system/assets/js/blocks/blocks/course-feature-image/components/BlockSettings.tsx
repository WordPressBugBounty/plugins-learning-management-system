import { ChakraProvider, extendTheme } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { ImageSizeControl, Panel, Tab } from '../../../components';
import CourseFilterForBlocks from '../../../components/select-course/select-wrapper';
const theme = extendTheme({});
const BlockSettings = (props: any) => {
	const {
		attributes: { height_n_width, clientId, courseId },
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
				<ImageSizeControl
					value={height_n_width}
					onChange={(val) => setAttributes({ height_n_width: val })}
					label={__('Image Size', 'learning-management-system')}
					units={['px']}
					responsive={true}
				/>
			</Panel>
		</Tab>
	);
};

export default BlockSettings;
