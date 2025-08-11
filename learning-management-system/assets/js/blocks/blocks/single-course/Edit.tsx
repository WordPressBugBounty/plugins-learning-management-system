import { Box, ChakraProvider, Container, extendTheme } from '@chakra-ui/react';
import createCache from '@emotion/cache';
import { CacheProvider } from '@emotion/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import {
	BlockContextProvider,
	InnerBlocks,
	InspectorControls,
	useBlockProps,
} from '@wordpress/block-editor';
import { dispatch, select } from '@wordpress/data';
import React, { useEffect, useState } from 'react';

import CourseFilterForBlocks from './../../components/select-course/select-wrapper';
import useClientId from './../../hooks/useClientId';
import { useBlockCSS } from './block-css';
import BlockSettings from './components/BlockSettings';

const queryClient = new QueryClient();
const theme = extendTheme({});

const layoutTemplate = [
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
						{
							className:
								'masteriyo-sidebar masteriyo-single-course--aside masteriyo-course--content ',
						},
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
];

const Edit = (props) => {
	const {
		attributes: { courseId },
		setAttributes,
		clientId,
	} = props;

	const blockProps = useBlockProps();
	const { editorCSS } = useBlockCSS(props);
	useClientId(clientId, setAttributes, props.attributes);

	const [emotionCache, setEmotionCache] = useState(null);
	const [inspectorCache, setInspectorCache] = useState(null);

	// Setup Emotion caches for Chakra
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

		setInspectorCache(
			createCache({ key: 'chakra-inspector', container: document.head }),
		);

		return () => clearInterval(waitForHead);
	}, []);

	// Step 1: Inject layout template
	useEffect(() => {
		if (!courseId) return;

		const innerBlocks = select('core/block-editor').getBlocks(clientId);
		const hasInnerBlocks = innerBlocks.length > 0;

		if (!hasInnerBlocks) {
			const blocks =
				wp.blocks.createBlocksFromInnerBlocksTemplate(layoutTemplate);
			dispatch('core/block-editor').replaceInnerBlocks(clientId, blocks);
		}
	}, [courseId, clientId]);

	// Step 2: Wait a short delay, then propagate courseId to children
	useEffect(() => {
		if (!courseId) return;

		const timer = setTimeout(() => {
			const innerBlocks = select('core/block-editor').getBlocks(clientId);

			const propagate = (blocks) => {
				blocks.forEach((block) => {
					if ('courseId' in block.attributes) {
						dispatch('core/block-editor').updateBlockAttributes(
							block.clientId,
							{
								courseId,
							},
						);
					}
					if (block.innerBlocks?.length) {
						propagate(block.innerBlocks);
					}
				});
			};

			propagate(innerBlocks);
		}, 100); // wait 100ms for inner blocks to register

		return () => clearTimeout(timer);
	}, [courseId, clientId]);

	if (!emotionCache || !inspectorCache) return null;

	const BlockSettingsWithProviders = () => (
		<CacheProvider value={inspectorCache}>
			<ChakraProvider theme={theme} resetCSS>
				<BlockSettings setAttributes={setAttributes} {...props} />
			</ChakraProvider>
		</CacheProvider>
	);

	const CourseFilterUI = () => (
		<CacheProvider value={emotionCache}>
			<ChakraProvider theme={theme} resetCSS>
				<QueryClientProvider client={queryClient}>
					<style>{editorCSS}</style>
					<BlockContextProvider value={{ courseId }}>
						<Container maxW="100%" p={0} {...blockProps}>
							<Box width="50%" margin="auto" mt="6">
								<CourseFilterForBlocks
									setAttributes={setAttributes}
									setCourseId={(id) => setAttributes({ courseId: id })}
								/>
							</Box>
						</Container>
					</BlockContextProvider>
				</QueryClientProvider>
			</ChakraProvider>
		</CacheProvider>
	);

	return (
		<>
			<InspectorControls>
				<BlockSettingsWithProviders />
			</InspectorControls>

			{!courseId ? (
				<CourseFilterUI />
			) : (
				<BlockContextProvider value={{ courseId }}>
					<Container maxW="100%" p={0} {...blockProps}>
						<InnerBlocks />
					</Container>
				</BlockContextProvider>
			)}
		</>
	);
};

export default Edit;
