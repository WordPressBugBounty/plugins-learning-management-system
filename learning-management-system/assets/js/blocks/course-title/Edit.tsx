import { Fragment } from '@wordpress/element';
import React, { useEffect, useState } from 'react';
import useClientId from '../hooks/useClientId';
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

	useClientId(props.clientId, setAttributes, props.attributes);
	const { editorCSS } = useBlockCSS(props);
	const [shouldRender, setShouldRender] = useState(false);

	useEffect(() => {
		setAttributes({ courseId: context['masteriyo/course_id'] });
		// Force re-render once courseId has a value
		if (courseId) {
			setShouldRender(true);
		}
	}, [context['masteriyo/course_id'], courseId]);

	return (
		<Fragment>
			<BlockSettings {...props} />
			<style>{editorCSS}</style>
			<div
				className="masteriyo-block-editor-wrapper"
				onClick={(e) => e.preventDefault()}
			>
				{shouldRender && (
					<ServerSideRender
						block="masteriyo/single-course-title"
						attributes={{
							clientId: clientId,
							fontSize: fontSize,
							blockCSS: blockCSS,
							textColor: textColor,
							nameFormat: nameFormat,
							alignment,
							title: title,
							courseId: courseId ?? 0,
						}}
					/>
				)}
			</div>
		</Fragment>
	);
};

export default Edit;
