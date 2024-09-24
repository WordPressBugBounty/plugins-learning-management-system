import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Panel, Tab } from '../../components';

const BlockSettings: React.FC<any> = (props) => {
	const {
		attributes: { clientId, margin, padding },
		setAttributes,
	} = props;

	return (
		<InspectorControls>
			<Tab tabTitle={__('Settings', 'masteriyo')}>
				<Panel title={__('Layout', 'masteriyo')}>
					{/* <BorderDimensions
						height={height_n_width ? height_n_width.height : 0}
						width={height_n_width ? height_n_width.width : 0}
						onChange={(val) => {
							setAttributes({ height_n_width: val });
						}}
					/> */}
				</Panel>
			</Tab>
		</InspectorControls>
	);
};

export default BlockSettings;
