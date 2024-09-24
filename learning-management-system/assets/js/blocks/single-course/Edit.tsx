import { Fragment } from '@wordpress/element';

import { ChakraProvider } from '@chakra-ui/react';
import {
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-ignore
	BlockContextProvider,
	InnerBlocks,
} from '@wordpress/block-editor';
import React, { useEffect, useState } from 'react';
import { QueryClient, QueryClientProvider } from 'react-query';
import CourseFilterForBlocks from '../components/select-course/select-wrapper';
import useClientId from '../hooks/useClientId';
import { useBlockCSS } from './block-css';
import BlockSettings from './components/BlockSettings';
const queryClient = new QueryClient();
const Edit: React.FC<any> = (props) => {
	const {
		attributes: { clientId, courseId, margin, padding },
		setAttributes,
	} = props;
	const [singleCourseId, setSingleCourseId] = useState(courseId || '');
	useClientId(props.clientId, setAttributes, props.attributes);
	const { editorCSS } = useBlockCSS(props);

	useEffect(() => {
		setAttributes({ courseId: singleCourseId });
	}, [singleCourseId]);
	return (
		<Fragment>
			<style>{editorCSS}</style>
			<BlockSettings {...props} />
			<BlockContextProvider value={{ courseId: courseId }}>
				<div>
					{'' === courseId ? (
						<ChakraProvider>
							<QueryClientProvider client={queryClient}>
								<div style={{ width: '50%', margin: 'auto' }}>
									<CourseFilterForBlocks
										setAttributes={setAttributes}
										setCourseId={setSingleCourseId}
									/>
								</div>
							</QueryClientProvider>
						</ChakraProvider>
					) : (
						<InnerBlocks
							template={[
								[
									'core/columns', // Main Columns Block
									{},
									[
										[
											'core/column', // Left Column
											{ className: 'masteriyo-col-8' },
											[
												[
													'core/group', // Main Content Group
													{
														className:
															'masteriyo-single-course--main masteriyo-course--content',
													},
													[
														['masteriyo/course-feature-image', {}],
														['masteriyo/single-course-title', {}],
														['masteriyo/course-author', {}],
														['masteriyo/course-contents', {}],
													],
												],
											],
										],
										[
											'core/column', // Right Column
											{ className: 'masteriyo-col-4' },
											[
												[
													'core/columns', // Aside Group
													{
														className:
															'masteriyo-single-course--aside masteriyo-course--content',
													},
													[
														[
															'core/column', // Inner Columns for price and button
															{ className: 'masteriyo-time-btn' },
															[
																['masteriyo/course-price', {}],
																['masteriyo/course-enroll-button', {}],
															],
														],
														[
															'core/column', // Course Stats Column
															{},
															[['masteriyo/course-stats', {}]],
														],
														[
															'core/column', // Course Highlights Column
															{},
															[['masteriyo/course-highlights', {}]],
														],
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
				</div>
			</BlockContextProvider>
		</Fragment>
	);
};

export default Edit;
