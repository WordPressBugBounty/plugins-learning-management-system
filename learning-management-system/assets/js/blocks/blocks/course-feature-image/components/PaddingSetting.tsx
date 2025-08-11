import { __ } from '@wordpress/i18n';
import React from 'react';
import { Dimensions } from './../../../components';

const PaddingSetting: React.FC<{
	value: any;
	// eslint-disable-next-line no-unused-vars
	onChange: (value: any) => void;
}> = (props) => {
	const {
		value: { padding },
		onChange,
	} = props;
	const setSetting = (genre: any, val: any) => {
		const data = { [genre]: val };
		onChange(Object.assign({}, props.value, data));
	};

	return (
		<div className="masteriyo-control masteriyo-border">
			<div className="masteriyo-control-body masteriyo-border-body">
				<Dimensions
					label={__('Padding', 'learning-management-system')}
					value={padding || {}}
					responsive
					units={['px', 'em', '%']}
					defaultUnit="px"
					min={0}
					max={100}
					onChange={(val: any) => setSetting('padding', val)}
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

export default PaddingSetting;
