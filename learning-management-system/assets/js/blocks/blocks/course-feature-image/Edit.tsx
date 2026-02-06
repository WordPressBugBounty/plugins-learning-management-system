import { Box, ChakraProvider, extendTheme } from '@chakra-ui/react';
import createCache from '@emotion/cache';
import { CacheProvider } from '@emotion/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Fragment, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import React from 'react';
import CourseFilterForBlocks from '../../components/select-course/select-wrapper';
import useClientId from '../../hooks/useClientId';
import { useBlockCSS } from './block-css';
import BlockSettings from './components/BlockSettings';

const queryClient = new QueryClient();
const theme = extendTheme({});

const Edit: React.FC<any> = (props) => {
	const {
		attributes: { clientId, blockCSS, courseId },
		context,
		setAttributes,
	} = props;

	const ServerSideRender = wp.serverSideRender
		? wp.serverSideRender
		: wp.components.ServerSideRender;

	const [singleCourseId, setSingleCourseId] = useState(courseId || '');
	const { editorCSS } = useBlockCSS(props);
	const [shouldRender, setShouldRender] = useState(false);
	const [emotionCache, setEmotionCache] = useState(null);
	const blockProps = useBlockProps({ className: 'masteriyo-block-editor-wrapper' });

	useEffect(() => {
		setAttributes({ courseId: singleCourseId });
	}, [singleCourseId]);

	useEffect(() => {
		if (!courseId && context['masteriyo/course_id']) {
			setAttributes({ courseId: context['masteriyo/course_id'] });
		}

		if (singleCourseId || courseId || context['masteriyo/course_id']) {
			setShouldRender(true);
		}
	}, [singleCourseId, courseId, context['masteriyo/course_id']]);
	useClientId(props.clientId, setAttributes, props.attributes);

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

	useEffect(() => {
		if (editorCSS) {
			const styleEl = document.createElement('style');
			styleEl.textContent = editorCSS;
			styleEl.setAttribute('data-masteriyo-block-css', clientId);
			document.head.appendChild(styleEl);

			return () => {
				styleEl.remove();
			};
		}
	}, [editorCSS, clientId]);

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
							key={`course-feature-image-${singleCourseId || courseId || context['masteriyo/course_id'] || 0}`}
							block="masteriyo/course-feature-image"
							attributes={{
								clientId: clientId,
								blockCSS: blockCSS,
								courseId: courseId ?? 0,
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
