import { Box, ChakraProvider, Container, extendTheme } from '@chakra-ui/react';
import createCache from '@emotion/cache';
import { CacheProvider } from '@emotion/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import {
	BlockContextProvider,
	InnerBlocks,
	useBlockProps,
} from '@wordpress/block-editor';
import { dispatch, select } from '@wordpress/data';
import React, { useEffect, useState } from 'react';

import CourseFilterForBlocks from './../../components/select-course/select-wrapper';
import useClientId from './../../hooks/useClientId';
import { useBlockCSS } from './block-css';

const queryClient = new QueryClient();
const theme = extendTheme({});

const layoutTemplate = [
	[
		'core/group',
		{ className: 'masteriyo-w-100 masteriyo-container' },
		[
			[
				'core/group',
				{
					id: 'course-{courseId}',
					className: 'masteriyo-single-course masteriyo-single-course--wrapper',
					'data-layout': 'default',
				},
				[
					[
						'core/columns',
						{},
						[
							[
								'core/column',
								{
									className: 'masteriyo-col-8 masteriyo-main-content-area',
									width: '66.66%',
								},
								[
									[
										'core/group',
										{
											className:
												'masteriyo-single-course--main masteriyo-course--content',
										},
										[
											[
												'core/group',
												{
													className: 'masteriyo-course--img-wrap',
												},
												[['masteriyo/course-feature-image']],
											],
											[
												'masteriyo/course-category',
												{
													className:
														'masteriyo-course--content__category masteriyo-course-category',
												},
											],
											[
												'core/group',
												{ className: 'masteriyo-course-title-wrapper' },
												[
													[
														'masteriyo/single-course-title',
														{
															className:
																'masteriyo-course-title masteriyo-single-course--title',
														},
													],
												],
											],
											[
												'core/group',
												{
													className:
														'masteriyo-course--content__rt masteriyo-course-author-rating-wrapper',
													style: {
														spacing: {
															padding: '0px 16px',
														},
													},
												},
												[
													[
														'masteriyo/course-author',
														{ className: 'masteriyo-course-author' },
													],
												],
											],
											[
												'core/group',
												{
													className: 'masteriyo-tabs',
													style: {
														border: {
															width: '0px',
															style: 'none',
														},
														spacing: {
															padding: '0',
														},
													},
												},
												[['masteriyo/course-contents', {}]],
											],
										],
									],
								],
							],
							[
								'core/column',
								{
									className: 'masteriyo-col-4 masteriyo-right-sidebar-area',
									width: '33.33%',
									style: {
										spacing: {
											padding: '0',
											margin: '0',
										},
									},
								},
								[
									[
										'core/group',
										{
											className:
												'masteriyo-sidebar masteriyo-single-course--aside masteriyo-course--content ',
										},
										[
											[
												'core/group',
												{
													className:
														'masteriyo-time-btn masteriyo-course-pricing--wrapper ',
												},
												[
													[
														'masteriyo/course-price',
														{ className: 'masteriyo-price-block' },
													],
													[
														'masteriyo/course-enroll-button',
														{
															className: 'masteriyo-enroll-btn-block',
														},
													],
												],
											],
											[
												'masteriyo/group-price-button',
												{
													className: 'masteriyo-single-course-group-price-btn',
												},
											],
											[
												'core/group',
												{
													className: 'masteriyo-single-course-stats-wrapper',
												},
												[['masteriyo/course-stats', {}]],
											],
											[
												'core/group',
												{
													className:
														'masteriyo-single-course-highlights-wrapper',
												},
												[['masteriyo/course-highlights', {}]],
											],
										],
									],
								],
							],
						],
					],
				],
			],
		],
	],
];

const Edit = (props) => {
	const {
		attributes: { courseId, template = '' },
		setAttributes,
		clientId,
	} = props;

	const blockProps = useBlockProps({
		className: 'masteriyo-block-editor-wrapper',
	});
	const { editorCSS } = useBlockCSS(props);
	useClientId(clientId, setAttributes, props.attributes);

	const handleTemplateSelect = (selectedTemplate: string) => {
		setAttributes({ template: selectedTemplate });
	};

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
			let selectedTemplate = layoutTemplate;
			const blocks =
				wp.blocks.createBlocksFromInnerBlocksTemplate(selectedTemplate);
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

	useEffect(() => {
		const styleId = 'masteriyo-editor-width-fix';
		const css = `
			.editor-styles-wrapper .wp-block {
				max-width: 1160px !important;
			}
			.editor-styles-wrapper .wp-block[data-align="wide"] {
				max-width: 1600px !important;
			}
			.editor-styles-wrapper .wp-block[data-align="full"] {
				max-width: 100% !important;
			}
		`;

		const inject = (targetDoc: Document) => {
			if (!targetDoc || targetDoc.getElementById(styleId)) return;
			const style = targetDoc.createElement('style');
			style.id = styleId;
			style.innerHTML = css;
			targetDoc.head.appendChild(style);
		};

		const cleanup = (targetDoc: Document) => {
			if (!targetDoc) return;
			const style = targetDoc.getElementById(styleId);
			if (style) style.remove();
		};

		inject(document);

		const iframe = document.querySelector(
			'iframe[name="editor-canvas"]',
		) as HTMLIFrameElement;
		if (iframe && iframe.contentDocument) {
			inject(iframe.contentDocument);
		}

		return () => {
			cleanup(document);
			if (iframe && iframe.contentDocument) {
				cleanup(iframe.contentDocument);
			}
		};
	}, []);

	if (!emotionCache || !inspectorCache) return null;

	// Show template picker if no template is selected
	// if (!template) {
	// 	return (
	// 		<div className="masteriyo" style={{ maxWidth: '1140px' }}>
	// 			<div {...blockProps}>
	// 				<TemplatePicker onSelectTemplate={handleTemplateSelect} />
	// 			</div>
	// 		</div>
	// 	);
	// }

	return (
		<>
			{/* <InspectorControls>
				<CacheProvider value={inspectorCache}>
					<ChakraProvider theme={theme} resetCSS>
						<BlockSettings setAttributes={setAttributes} {...props} />
					</ChakraProvider>
				</CacheProvider>
			</InspectorControls> */}

			{!courseId ? (
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
