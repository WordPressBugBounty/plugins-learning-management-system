import { Box, ChakraProvider, Container, extendTheme } from '@chakra-ui/react';
import createCache from '@emotion/cache';
import { CacheProvider } from '@emotion/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import {
	// @ts-ignore
	BlockContextProvider,
	InnerBlocks,
} from '@wordpress/block-editor';
import React, { useEffect, useState } from 'react';

import CourseFilterForBlocks from './../../components/select-course/select-wrapper';
import useClientId from './../../hooks/useClientId';
import { useBlockCSS } from './block-css';
import BlockSettings from './components/BlockSettings';

const queryClient = new QueryClient();
const theme = extendTheme({});

const Edit: React.FC<any> = (props) => {
	const {
		attributes: { clientId, courseId },
		setAttributes,
	} = props;

	const [singleCourseId, setSingleCourseId] = useState(courseId || '');
	const [emotionCache, setEmotionCache] = useState<any>(null);

	useClientId(props.clientId, setAttributes, props.attributes);
	const { editorCSS } = useBlockCSS(props);

	useEffect(() => {
		setAttributes({ courseId: singleCourseId });
	}, [singleCourseId]);

	useEffect(() => {
		const iframe = document.querySelector(
			'iframe[name="editor-canvas"]',
		) as HTMLIFrameElement;

		const waitForHead = setInterval(() => {
			const iframeHead = iframe?.contentDocument?.head;
			if (iframeHead) {
				const cache = createCache({
					key: 'chakra',
					container: iframeHead,
				});
				setEmotionCache(cache);
				clearInterval(waitForHead);
			}
		}, 150);

		return () => clearInterval(waitForHead);
	}, []);

	if (!emotionCache) return null;

	return (
		<CacheProvider value={emotionCache}>
			<ChakraProvider theme={theme} resetCSS>
				<QueryClientProvider client={queryClient}>
					<style>{editorCSS}</style>
					<BlockSettings {...props} />
					<BlockContextProvider value={{ courseId }}>
						<Container maxW="100%" p={0}>
							{!courseId ? (
								<Box width="50%" margin="auto" mt="6">
									<CourseFilterForBlocks
										setAttributes={setAttributes}
										setCourseId={setSingleCourseId}
									/>
								</Box>
							) : (
								<InnerBlocks
									template={[
										[
											'core/columns',
											{},
											[
												[
													'core/column',
													{ className: 'masteriyo-col-8' },
													[
														[
															'core/group',
															{ className: 'masteriyo-main-content' },
															[
																['masteriyo/course-feature-image', {}],
																['masteriyo/single-course-title', {}],
																['masteriyo/course-author', {}],
																[
																	'core/group',
																	{ className: 'masteriyo-tabs' },
																	[['masteriyo/course-contents', {}]],
																],
															],
														],
													],
												],
												[
													'core/column',
													{ className: 'masteriyo-col-4' },
													[
														[
															'core/group',
															{ className: 'masteriyo-sidebar' },
															[
																['masteriyo/course-price', {}],
																['masteriyo/course-enroll-button', {}],
																['masteriyo/course-stats', {}],
																['masteriyo/course-highlights', {}],
															],
														],
													],
												],
											],
										],
									]}
									templateLock={false}
								/>
							)}
						</Container>
					</BlockContextProvider>
				</QueryClientProvider>
			</ChakraProvider>
		</CacheProvider>
	);
};

export default Edit;
