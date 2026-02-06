import { Box, ChakraProvider, extendTheme } from '@chakra-ui/react';
import createCache from '@emotion/cache';
import { CacheProvider } from '@emotion/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Fragment, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import React from 'react';
import CourseFilterForBlocks from '../../components/select-course/select-wrapper';
import BlockSettings from './components/BlockSettings';

const queryClient = new QueryClient();
const theme = extendTheme({});

const Edit: React.FC<any> = (props) => {
	const {
		attributes: { clientId, courseId, blockCSS },
		context,
		setAttributes,
	} = props;

	const ServerSideRender = wp.serverSideRender
		? wp.serverSideRender
		: wp.components.ServerSideRender;

	const [singleCourseId, setSingleCourseId] = useState(courseId || '');
	const [shouldRender, setShouldRender] = useState(false);
	const [emotionCache, setEmotionCache] = useState(null);
	const blockProps = useBlockProps({ className: 'masteriyo-block-editor-wrapper' });

	// Update attribute when user selects a course
	useEffect(() => {
		if (singleCourseId) {
			setAttributes({ courseId: singleCourseId });
		}
	}, [singleCourseId]);

	// Fallback to context if not manually set
	useEffect(() => {
		if (!courseId && context['masteriyo/course_id']) {
			setAttributes({ courseId: context['masteriyo/course_id'] });
		}

		if (singleCourseId || courseId || context['masteriyo/course_id']) {
			setShouldRender(true);
		}
	}, [singleCourseId, courseId, context['masteriyo/course_id']]);

	// Ensure clientId is saved
	useEffect(() => {
		if (!clientId && props.clientId) {
			setAttributes({ clientId: props.clientId });
		}
	}, [clientId, props.clientId]);

	// Setup Emotion cache for Chakra
	useEffect(() => {
		const iframe = document.querySelector('iframe[name="editor-canvas"]');
		const waitForHead = setInterval(() => {
			const iframeHead = iframe?.contentDocument?.head;
			if (iframeHead) {
				setEmotionCache(
					createCache({ key: 'chakra-editor', container: iframeHead }),
				);
				clearInterval(waitForHead);
			}
		}, 150);

		return () => clearInterval(waitForHead);
	}, []);

	// Log attributes for SSR
	console.log('ServerSideRender attributes:', {
		clientId,
		blockCSS,
		courseId,
	});

	return (
		<>
			<InspectorControls>
				<BlockSettings setSingleCourseId={setSingleCourseId} {...props} />
			</InspectorControls>
			<Fragment>
				<div
					{...blockProps}
					onClick={(e) => e.preventDefault()}
				>
					{shouldRender ? (
						<ServerSideRender
							key={`course-coming-soon-${singleCourseId || courseId || context['masteriyo/course_id'] || 0}`}
							block="masteriyo/course-coming-soon"
							attributes={{
								clientId,
								blockCSS,
								courseId,
							}}
						/>
					) : emotionCache ? (
						<CacheProvider value={emotionCache}>
							<ChakraProvider theme={theme} resetCSS>
								<QueryClientProvider client={queryClient}>
									<Box
										p={4}
										bg="#f9f9f9"
										border="1px solid #ddd"
										borderRadius="4px"
										textAlign="center"
									>
										<Box mb={3} fontSize="14px" fontWeight="600">
											{__(
												'Select Course',
												'learning-management-system',
											)}
										</Box>
										<Box maxW="400px" margin="0 auto">
											<CourseFilterForBlocks
												value={courseId}
												setAttributes={setAttributes}
												setCourseId={setSingleCourseId}
											/>
										</Box>
									</Box>
								</QueryClientProvider>
							</ChakraProvider>
						</CacheProvider>
					) : null}
				</div>
			</Fragment>
		</>
	);
};

export default Edit;
