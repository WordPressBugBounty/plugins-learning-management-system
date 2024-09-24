import { InnerBlocks } from '@wordpress/block-editor';
import React from 'react';

const Save: React.FC<any> = () => {
	return (
		<div id="certificate" className="certificate">
			<div className="certificate-content-wrapper">
				<InnerBlocks.Content />
			</div>
		</div>
	);
};

export default Save;
