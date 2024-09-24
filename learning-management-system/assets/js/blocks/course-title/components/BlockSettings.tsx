import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Panel, Slider } from '../../components';
import AdvanceSelect from '../../components/advance-select';
import Color from '../../components/color';

const BlockSettings: React.FC<any> = (props) => {
	const {
		attributes: { alignment, textColor, fontSize },
		setAttributes,
	} = props;

	return (
		<InspectorControls>
			<Panel title={__('Text', 'masteriyo')} initialOpen>
				<AdvanceSelect
					value={alignment}
					onChange={(val) => setAttributes({ alignment: val })}
					responsive={false}
					label={__('Alignment', 'masteriyo')}
					options={[
						{
							label: __('Left', 'masteriyo'),
							value: 'left',
							icon: 'text-align-left',
						},
						{
							label: __('Center', 'masteriyo'),
							value: 'center',
							icon: 'text-align-center',
						},
						{
							label: __('Right', 'masteriyo'),
							value: 'right',
							icon: 'text-align-right',
						},
						{
							label: __('Justify', 'masteriyo'),
							value: 'justify',
							icon: 'text-align-justify',
						},
					]}
				/>
				<Color
					onChange={(val) => setAttributes({ textColor: val })}
					label={__('Color', 'masteriyo')}
					value={textColor || ''}
				/>
				<Slider
					value={fontSize}
					onChange={(val) => setAttributes({ fontSize: val })}
					responsive={false}
					min={0}
					max={100}
					inline={true}
					units={['px']}
					defaultUnit="px"
					label={__('Font Size', 'masteriyo')}
				/>
			</Panel>
		</InspectorControls>
	);
};

export default BlockSettings;
