import { ChakraProvider, extendTheme } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import { Panel, Tab } from '../../../components';
import CourseFilterForBlocks from '../../../components/select-course/select-wrapper';

const theme = extendTheme({});

const BlockSettings = (props: any) => {
	const {
		attributes: { clientId, courseId },
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
						setCourseId={(id: number) => {
							console.log('Selected courseId:', id);
							props.setSingleCourseId(id);
						}}
					/>
				</ChakraProvider>
			</Panel>
		</Tab>
	);
};

export default BlockSettings;
