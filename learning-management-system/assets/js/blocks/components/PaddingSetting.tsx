import { __ } from '@wordpress/i18n';
import React from 'react';
import Dimensions from './dimensions';

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
					label={__('Padding', 'masteriyo')}
					value={padding || {}}
					responsive
					units={['px', 'em', '%']}
					defaultUnit="px"
					min={0}
					max={100}
					onChange={(val: any) => setSetting('padding', val)}
					dimensionLabels={{
						top: __('Top', 'masteriyo'),
						right: __('Right', 'masteriyo'),
						bottom: __('Bottom', 'masteriyo'),
						left: __('Left', 'masteriyo'),
					}}
				/>
			</div>
		</div>
	);
};

export default PaddingSetting;
