import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import React from 'react';
import {
	AdvanceSelect,
	Panel,
} from '../../../../../../assets/js/blocks/components';

const BlockSettings: React.FC<any> = (props) => {
	const {
		attributes: { alignment, textColor, fontSize, nameFormat = 'fullname' },
		setAttributes,
	} = props;

	return (
		<InspectorControls>
			<Panel title={__('Placement', 'learning-management-system')} initialOpen>
				<AdvanceSelect
					value={alignment}
					onChange={(val) => setAttributes({ alignment: val })}
					responsive={false}
					label={__('Alignment', 'learning-management-system')}
					options={[
						{
							label: __('Left', 'learning-management-system'),
							value: 'left',
							icon: 'text-align-left',
						},
						{
							label: __('Center', 'learning-management-system'),
							value: 'center',
							icon: 'text-align-center',
						},
						{
							label: __('Right', 'learning-management-system'),
							value: 'right',
							icon: 'text-align-right',
						},
					]}
				/>
			</Panel>
		</InspectorControls>
	);
};

export default BlockSettings;
