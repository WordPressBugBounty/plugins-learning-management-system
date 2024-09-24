import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Panel, Slider, Tab } from '../../components';
const BlockSettings = (props: any) => {
	const {
		attributes: { clientId, minWidth },
		setAttributes,
	} = props;

	return (
		<InspectorControls>
			<Tab tabTitle={__('Settings', 'masteriyo')}>
				<Panel title={__('Layout', 'masteriyo')}>
					<Slider
						value={minWidth}
						onChange={(val) => setAttributes({ minWidth: val })}
						responsive={false}
						min={0}
						max={500}
						inline={true}
						units={['px']}
						defaultUnit="px"
						label={__('Minimum Width of Inner Container', 'masteriyo')}
					/>
				</Panel>
			</Tab>
		</InspectorControls>
	);
};

export default BlockSettings;
