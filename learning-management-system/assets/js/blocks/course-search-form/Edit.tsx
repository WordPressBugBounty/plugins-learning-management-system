import { Fragment } from '@wordpress/element';
import React from 'react';
import useClientId from '../hooks/useClientId';
import BlockSettings from './components/BlockSettings';

const Edit: React.FC<any> = (props) => {
	const {
		attributes: { clientId, height_n_width },
		setAttributes,
	} = props;

	const ServerSideRender = wp.serverSideRender
		? wp.serverSideRender
		: wp.components.ServerSideRender;

	useClientId(props.clientId, setAttributes, props.attributes);
	// const { editorCSS } = useBlockCSS(props);

	return (
		<Fragment>
			<BlockSettings {...props} />
			{/* <style>{editorCSS}</style> */}
			<div
				className="masteriyo-block-editor-wrapper"
				onClick={(e) => e.preventDefault()}
			>
				<ServerSideRender
					block="masteriyo/course-search-form"
					attributes={{
						clientId: clientId ? clientId : '',
						height_n_width: height_n_width,
					}}
				/>
			</div>
		</Fragment>
	);
};

export default Edit;
