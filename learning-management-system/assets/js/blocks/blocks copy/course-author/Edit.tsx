import { Fragment } from '@wordpress/element';
import React, { useEffect, useState } from 'react';
import useClientId from './../../hooks/useClientId';
import { useBlockCSS } from './block-css';
import BlockSettings from './components/BlockSettings';

const Edit: React.FC<any> = (props) => {
	const {
		attributes: {
			clientId,
			hideAuthorsAvatar,
			hideAuthorsName,
			height_n_width,
			margin,
			blockCSS,
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
						block="masteriyo/course-author"
						attributes={{
							clientId: clientId,
							hideAuthorsAvatar: hideAuthorsAvatar,
							hideAuthorsName: hideAuthorsName,
							height_n_width: height_n_width,
							margin: margin,
							blockCSS: blockCSS,
							courseId: courseId ?? 0,
						}}
						// httpMethod="post"
					/>
				)}
			</div>
		</Fragment>
	);
};

export default Edit;
