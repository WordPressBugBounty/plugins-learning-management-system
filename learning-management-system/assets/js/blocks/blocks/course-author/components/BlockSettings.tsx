import { InspectorControls } from '@wordpress/block-editor';
import React from 'react';
const BlockSettings = (props: any) => {
	const {
		attributes: {
			height_n_width,
			margin,
			padding,
			hideAuthorsAvatar,
			hideAuthorsName,
			clientId,
		},
		setAttributes,
	} = props;
	return <InspectorControls>{null}</InspectorControls>;
};

export default BlockSettings;
