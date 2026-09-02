import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import React from 'react';
import {
	AdvanceSelect,
	Color,
	Panel,
	Select,
	Slider,
} from '../../../../../../assets/js/blocks/components';

const BlockSettings: React.FC<any> = (props) => {
	const {
		attributes: {
			alignment,
			textColor,
			fontSize,
			timestampFormat = 'F j, Y g:i a',
		},
		setAttributes,
	} = props;

	return (
		<InspectorControls>
			<Panel title={__('Text', 'learning-management-system')} initialOpen>
				<Select
					value={timestampFormat}
					label={__('DateTime Format', 'learning-management-system')}
					options={[
						{ label: 'December 15, 2022 12:34 PM', value: 'F j, Y g:i A' },
						{ label: '2022-12-15 12:34:56', value: 'Y-m-d H:i:s' },
						{ label: '12/15/2022 12:34', value: 'm/d/Y H:i' },
						{ label: '15/12/2022 12:34', value: 'd/m/Y H:i' },
					]}
					onChange={(val) => setAttributes({ timestampFormat: val })}
					inline={false}
				/>
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
						{
							label: __('Justify', 'learning-management-system'),
							value: 'justify',
							icon: 'text-align-justify',
						},
					]}
				/>
				<Color
					onChange={(val) => setAttributes({ textColor: val })}
					label={__('Color', 'learning-management-system')}
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
					label={__('Font Size', 'learning-management-system')}
				/>
			</Panel>
		</InspectorControls>
	);
};

export default BlockSettings;
