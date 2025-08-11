import { ChakraProvider, extendTheme } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import { Color, Panel, Slider, Tab } from '../../../components';
import CourseFilterForBlocks from '../../../components/select-course/select-wrapper';
const theme = extendTheme({});
const BlockSettings = (props: any) => {
	const {
		attributes: { clientId, courseId, textColor, fontSize },
		setAttributes,
	} = props;
	const [isStylesPanelOpen, setIsStylesPanelOpen] = useState(true);
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
			<Panel
				title={__('Styles', 'learning-management-system')}
				isOpen={isStylesPanelOpen}
				onToggle={() => setIsStylesPanelOpen(!isStylesPanelOpen)}
			>
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
			</Panel>
		</Tab>
	);
};

export default BlockSettings;
