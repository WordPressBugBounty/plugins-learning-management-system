import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { Notice } from '@wordpress/components';
import { Fragment, useEffect, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import React from 'react';
import useClientId from '../../hooks/useClientId';
import { useBlockCSS } from './block-css';
import BlockSettings from './components/BlockSettings';
const Edit: React.FC<any> = (props) => {
	const {
		attributes: {
			clientId,
			alignment,
			blockCSS,
			fontSize,
			textColor,
			nameFormat,
			title,
			courseId,
		},
		context,
		setAttributes,
	} = props;

	const ServerSideRender = wp.serverSideRender
		? wp.serverSideRender
		: wp.components.ServerSideRender;

	const [singleCourseId, setSingleCourseId] = useState(courseId || '');
	const { editorCSS } = useBlockCSS(props);
	const [shouldRender, setShouldRender] = useState(false);

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
					{...useBlockProps({
						className: 'masteriyo-block-editor-wrapper',
					})}
					onClick={(e) => e.preventDefault()}
				>
					{shouldRender ? (
						<ServerSideRender
							block="masteriyo/single-course-title"
							attributes={{
								clientId,
								fontSize,
								blockCSS,
								textColor,
								nameFormat,
								alignment,
								title,
								courseId:
									singleCourseId ||
									courseId ||
									context['masteriyo/course_id'] ||
									0,
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
