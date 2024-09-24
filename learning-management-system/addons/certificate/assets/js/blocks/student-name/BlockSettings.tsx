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
			nameFormat = 'fullname',
			fontFamily = 'Default',
		},
		setAttributes,
	} = props;

	return (
		<InspectorControls>
			<Panel title={__('Text', 'learning-management-system')} initialOpen>
				<Select
					value={nameFormat}
					label={__('Name Format', 'learning-management-system')}
					options={[
						{
							label: __('Fullname', 'learning-management-system'),
							value: 'fullname',
						},
						{
							label: __('First Name', 'learning-management-system'),
							value: 'first-name',
						},
						{
							label: __('Last Name', 'learning-management-system'),
							value: 'last-name',
						},
						{
							label: __('Display Name', 'learning-management-system'),
							value: 'display-name',
						},
					]}
					onChange={(val) => setAttributes({ nameFormat: val })}
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
				<Select
					value={fontFamily}
					label={__('Font Family', 'learning-management-system')}
					options={[
						{
							label: __('Default', 'learning-management-system'),
							value: 'Default',
						},
						{
							label: __('Cinzel', 'learning-management-system'),
							value: 'Cinzel',
						},
						{
							label: __('DejaVu Sans Condensed', 'learning-management-system'),
							value: 'DejaVuSansCondensed',
						},
						{
							label: __('DM Sans', 'learning-management-system'),
							value: 'DMSans',
						},
						{
							label: __('Great Vibes', 'learning-management-system'),
							value: 'GreatVibes',
						},
						{
							label: __('Grenze Gotisch', 'learning-management-system'),
							value: 'GrenzeGotisch',
						},
						{ label: __('Lora', 'learning-management-system'), value: 'Lora' },
						{
							label: __('Poppins', 'learning-management-system'),
							value: 'Poppins',
						},
						{
							label: __('Roboto', 'learning-management-system'),
							value: 'Roboto',
						},
						{
							label: __('Abhaya Libre', 'learning-management-system'),
							value: 'AbhayaLibre',
						},
						{
							label: __('Adine Kirnberg', 'learning-management-system'),
							value: 'AdineKirnberg',
						},
						{
							label: __('Alex Brush', 'learning-management-system'),
							value: 'AlexBrush',
						},
						{
							label: __('Allura', 'learning-management-system'),
							value: 'Allura',
						},
					]}
					onChange={(val) => setAttributes({ fontFamily: val })}
					inline={false}
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
