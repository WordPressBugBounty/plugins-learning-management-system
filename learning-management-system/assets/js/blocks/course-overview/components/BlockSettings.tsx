import { InspectorControls } from '@wordpress/block-editor';
import React from 'react';

const BlockSettings: React.FC<any> = (props) => {
	const {
		attributes: { clientId },
		setAttributes,
	} = props;

	return <InspectorControls>{null}</InspectorControls>;
};

export default BlockSettings;
