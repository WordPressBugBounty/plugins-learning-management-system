import { __ } from '@wordpress/i18n';
import React from 'react';
import { Dimensions } from './../../../components';

interface BorderRadius {
	top?: string;
	right?: string;
	bottom?: string;
	left?: string;
}

interface BorderSettingProps {
	value?: {
		radius?: BorderRadius;
		[key: string]: any;
	};
	onChange: (value: any) => void;
}

const BorderSetting: React.FC<BorderSettingProps> = ({
	value = {},
	onChange,
}) => {
	const { radius = {} } = value;

	const setSetting = (genre: string, val: any) => {
		const data = { [genre]: val };
		onChange({ ...value, ...data });
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
