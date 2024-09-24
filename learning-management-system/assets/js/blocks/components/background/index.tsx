import { applyFilters } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import React from 'react';
import Color from '../color';
import Icon from '../icon';
import './editor.scss';

interface Props {
	value?: any;
	label?: string;
	onChange: CallableFunction;
}

const Background: React.FC<Props> = (props) => {
	const { value: { type, color } = {}, label = '', onChange } = props;
	const bgTypes: any = applyFilters('masteriyo.background.type', [
		'color',
		'image',
	]);

	const setSetting = (genre, val) => {
		const data = { [genre]: val };
		onChange(Object.assign({}, props.value, data));
	};

	return (
		<div className="masteriyo-control masteriyo-background">
			<div className="masteriyo-control-head masteriyo-background-head">
				<label
					htmlFor="masteriyo-background"
					className="masteriyo-control-label"
				>
					{label || ''}
				</label>
				<div className="masteriyo-background-types">
					{bgTypes.map((bgType) => (
						<button
							key={bgType}
							className={classnames('masteriyo-background-type', {
								'is-active': (type || '') === bgType,
							})}
							onClick={() => setSetting('type', bgType)}
						>
							<Icon type="controlIcon" name={bgType} size={22} />
						</button>
					))}
				</div>
			</div>
			<div className="masteriyo-control-body masteriyo-background-body">
				<Color
					label={__('Color', 'learning-management-system')}
					onChange={(val) => setSetting('color', val)}
					value={color || ''}
				/>
			</div>
		</div>
	);
};

export default Background;
