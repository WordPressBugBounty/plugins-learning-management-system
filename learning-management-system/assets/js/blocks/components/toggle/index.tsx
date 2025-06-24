import { useInstanceId } from '@wordpress/compose';
import classnames from 'classnames';
import React from 'react';
import './editor.scss';

interface PropTypes {
	checked: boolean;
	onChange: CallableFunction;
	label?: string;
	help?: string;
}

const Toggle: React.FC<PropTypes> = ({ checked, onChange, label, help }) => {
	const id = useInstanceId(Toggle);

	return (
		<div
			className={classnames(
				'masteriyo-control',
				'masteriyo-toggle',
				'masteriyo-setting-row',
				{ 'is-checked': checked },
			)}
		>
			<div className="masteriyo-setting-labels">
				{label && (
					<label
						htmlFor={`masteriyo-toggle-${id}`}
						className="masteriyo-control-label masteriyo-toggle-label"
					>
						{label}
					</label>
				)}
				{help && <div className="masteriyo-toggle-description">{help}</div>}
			</div>

			<div className="masteriyo-control-body masteriyo-toggle-body">
				<input
					id={`masteriyo-toggle-${id}`}
					type="checkbox"
					className="masteriyo-toggle-checkbox"
					checked={checked}
					onChange={(e) => onChange(e.target.checked)}
				/>
				<span className="masteriyo-toggle-track" />
				<span className="masteriyo-toggle-thumb" />
			</div>
		</div>
	);
};

export default Toggle;
