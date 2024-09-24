import { Fragment } from '@wordpress/element';
import React, { useEffect, useState } from 'react';
import useClientId from '../hooks/useClientId';
import { useDeviceType } from '../hooks/useDeviceType';
import { useBlockCSS } from './block-css';
import BlockSettings from './components/BlockSettings';

const Edit: React.FC<any> = (props) => {
	const {
		attributes: {
			clientId,
			alignment,
			blockCSS,
			courseId,
			// startCourseButtonBorder,
		},
		context,
		setAttributes,
	} = props;
	const ServerSideRender = wp.serverSideRender
		? wp.serverSideRender
		: wp.components.ServerSideRender;
	useClientId(props.clientId, setAttributes, props.attributes);
	const { editorCSS } = useBlockCSS(props);
	const [deviceType] = useDeviceType();
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
						block="masteriyo/course-enroll-button"
						attributes={{
							clientId: clientId ? clientId : '',
							alignment: alignment,
							blockCSS: blockCSS,
							courseId: courseId ?? 0,
							// startCourseButtonBorder,
						}}
					/>
				)}
			</div>
		</Fragment>
	);
};

export default Edit;
