import { Fragment } from '@wordpress/element';
import React, { useEffect, useState } from 'react';
import useClientId from '../hooks/useClientId';
import { useDeviceType } from '../hooks/useDeviceType';
import { useBlockCSS } from './block-css';
import BlockSettings from './components/BlockSettings';

const Edit: React.FC<any> = (props) => {
	const {
		attributes: { clientId, blockCSS, courseId, minWidth },
		context,
		setAttributes,
	} = props;
	const [deviceType] = useDeviceType();

	const ServerSideRender = wp.serverSideRender
		? wp.serverSideRender
		: wp.components.ServerSideRender;

	useClientId(props.clientId, setAttributes, props.attributes);
	const { editorCSS } = useBlockCSS({ ...props, deviceType });
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
						block="masteriyo/course-stats"
						attributes={{
							clientId: clientId,
							blockCSS: blockCSS,
							courseId: courseId ?? 0,
							minWidth,
						}}
						// httpMethod="post"
					/>
				)}
			</div>
		</Fragment>
	);
};

export default Edit;
