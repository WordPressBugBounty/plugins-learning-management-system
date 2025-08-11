import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { useDeviceType } from '../../hooks/useDeviceType';
import DeviceSelector from '../device-selector';
import './editor.scss';

interface DeviceValue {
	width?: number | '';
	height?: number | '';
	unit?: string;
}

interface ResponsiveValue {
	desktop?: DeviceValue;
	tablet?: DeviceValue;
	mobile?: DeviceValue;
}

interface ImageSizeControlProps {
	value?: DeviceValue & ResponsiveValue;
	onChange: (val: any) => void;
	label?: string;
	units?: string[];
	responsive?: boolean;
}

const ImageSizeControl: React.FC<ImageSizeControlProps> = ({
	value = {},
	onChange,
	label = __('Image Size', 'learning-management-system'),
	units = ['px', '%', 'em'],
	responsive = false,
}) => {
	const [deviceType] = useDeviceType();
	const deviceKey = responsive ? deviceType : null;
	const deviceValue: DeviceValue = responsive
		? value?.[deviceType] || {}
		: value;

	const currentUnit = deviceValue?.unit || units[0];
	const currentWidth = deviceValue?.width ?? '';
	const currentHeight = deviceValue?.height ?? '';

	const handleChange = (field: 'width' | 'height' | 'unit', newVal: string) => {
		const parsedVal =
			field === 'unit' ? newVal : newVal === '' ? '' : Number(newVal);

		const updated: DeviceValue = {
			...deviceValue,
			[field]: parsedVal,
		};

		onChange({
			...value,
			...(deviceKey ? { [deviceKey]: updated } : updated),
		});
	};

	return (
		<div className="masteriyo-control masteriyo-image-size-control">
			<div className="masteriyo-control-head">
				{label && (
					<label className="masteriyo-control-label masteriyo-image-size-label">
						{label}
					</label>
				)}
				{responsive && <DeviceSelector />}
			</div>
			<div className="masteriyo-control-body">
				<div className="masteriyo-input-control">
					<label className="components-input-control__label">
						{__('Width', 'learning-management-system')}
					</label>
					<input
						type="number"
						value={currentWidth}
						onChange={(e) => handleChange('width', e.target.value)}
						className="components-text-control__input"
						placeholder="e.g. 96"
					/>
				</div>

				<div className="masteriyo-input-control">
					<label className="components-input-control__label">
						{__('Height', 'learning-management-system')}
					</label>
					<input
						type="number"
						value={currentHeight}
						onChange={(e) => handleChange('height', e.target.value)}
						className="components-text-control__input"
						placeholder="e.g. 96"
					/>
				</div>

				<SelectControl
					label={__('Unit', 'learning-management-system')}
					value={currentUnit}
					options={units.map((unit) => ({ label: unit, value: unit }))}
					onChange={(val) => handleChange('unit', val)}
				/>
			</div>
		</div>
	);
};

export default ImageSizeControl;
