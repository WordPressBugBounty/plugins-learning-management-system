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
		attributes: { alignment, textColor, fontSize, dateFormat = 'F j, Y' },
		setAttributes,
	} = props;

	return (
		<InspectorControls>
			<Panel title={__('Text', 'learning-management-system')} initialOpen>
				<Select
					value={dateFormat}
					label={__('Date Format', 'learning-management-system')}
					options={[
						{ label: 'December 15, 2022', value: 'F j, Y' },
						{ label: '2022-12-15', value: 'Y-m-d' },
						{ label: '12/15/2022', value: 'm/d/Y' },
						{ label: '15/12/2022', value: 'd/m/Y' },
					]}
					onChange={(val) => setAttributes({ dateFormat: val })}
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
