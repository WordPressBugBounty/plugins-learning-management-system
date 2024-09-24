import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import React from 'react';

function Save() {
	const blockProps = useBlockProps.save();
	return (
		<div {...blockProps}>
			<InnerBlocks.Content />
		</div>
	);
}

export default Save;
