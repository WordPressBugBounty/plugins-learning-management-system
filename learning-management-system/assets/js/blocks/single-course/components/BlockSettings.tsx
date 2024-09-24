import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Panel, Tab } from '../../components';
import PaddingSetting from '../../components/PaddingSetting';

const BlockSettings: React.FC<any> = (props) => {
	const {
		attributes: { height_n_width, margin, padding, clientId },
		setAttributes,
	} = props;

	return (
		<InspectorControls>
			<Tab tabTitle={__('Settings', 'masteriyo')}>
				<Panel title={__('Layout', 'masteriyo')}>
					<PaddingSetting
						value={padding}
						onChange={(val) => setAttributes({ padding: val })}
					/>
				</Panel>
			</Tab>
		</InspectorControls>
	);
};

export default BlockSettings;
