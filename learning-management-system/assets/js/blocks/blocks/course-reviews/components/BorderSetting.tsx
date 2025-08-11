import { __ } from '@wordpress/i18n';
import React from 'react';
import { Dimensions } from './../../../components';

interface SideValue {
	top?: string | number;
	right?: string | number;
	bottom?: string | number;
	left?: string | number;
	unit?: string;
	lock?: boolean;
}

interface ResponsiveRadius {
	desktop?: SideValue;
	tablet?: SideValue;
	mobile?: SideValue;
	unit?: string;
}

interface BorderSettingProps {
	value?: {
		radius?: ResponsiveRadius;
		[key: string]: any;
	};
	onChange: (value: any) => void;
}

const fillDefaultRadius = (input: ResponsiveRadius = {}): ResponsiveRadius => {
	const fallbackSide: SideValue = {
		top: 0,
		right: 0,
		bottom: 0,
		left: 0,
		unit: input.unit || 'px',
		lock: false,
	};

	return {
		unit: input.unit || 'px',
		desktop: { ...fallbackSide, ...(input.desktop || {}) },
		tablet: { ...fallbackSide, ...(input.tablet || {}) },
		mobile: { ...fallbackSide, ...(input.mobile || {}) },
	};
};

const BorderSetting: React.FC<BorderSettingProps> = ({ value, onChange }) => {
	const safeValue = value || {};
	const radius: ResponsiveRadius = fillDefaultRadius(safeValue.radius);

	const setSetting = (key: string, val: any) => {
		onChange({
			...safeValue,
			[key]: val,
		});
	};

	return (
		<div className="masteriyo-control masteriyo-border">
			<div className="masteriyo-control-body masteriyo-border-body">
				<Dimensions
					label={__('Radius', 'learning-management-system')}
					value={radius}
					responsive
					units={['px', 'em', '%']}
					defaultUnit="px"
					min={0}
					max={100}
					onChange={(val: any) => setSetting('radius', val)}
					dimensionLabels={{
						top: __('Top Left', 'learning-management-system'),
						right: __('Top Right', 'learning-management-system'),
						bottom: __('Bottom Right', 'learning-management-system'),
						left: __('Bottom Left', 'learning-management-system'),
					}}
				/>
			</div>
		</div>
	);
};

export default BorderSetting;
