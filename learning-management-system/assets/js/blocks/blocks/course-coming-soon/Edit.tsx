import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Notice } from '@wordpress/components';
import { Fragment, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import React from 'react';
import BlockSettings from './components/BlockSettings';

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
					{...useBlockProps({
						className: 'masteriyo-block-editor-wrapper',
					})}
					onClick={(e) => e.preventDefault()}
				>
					{shouldRender ? (
						<ServerSideRender
							block="masteriyo/course-coming-soon"
							attributes={{
								clientId,
								blockCSS,
								courseId,
							}}
						/>
					) : (
						<Notice status="warning" isDismissible={false}>
							{__(
								'Please choose the course from settings or use inside a Single Course block.',
								'learning-management-system',
							)}
						</Notice>
					)}
				</div>
			</Fragment>
		</>
	);
};

export default Edit;
