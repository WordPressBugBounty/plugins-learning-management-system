import { InspectorControls } from '@wordpress/block-editor';
import React from 'react';
import { QueryClient } from 'react-query';

const queryClient = new QueryClient();

const BlockSettings: React.FC<any> = (props) => {
	const {
		attributes: { clientId, course },
		setAttributes,
	} = props;

	return <InspectorControls>{null}</InspectorControls>;
};

export default BlockSettings;
