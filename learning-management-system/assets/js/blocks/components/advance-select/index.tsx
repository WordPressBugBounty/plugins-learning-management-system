import { useInstanceId } from '@wordpress/compose';
import classnames from 'classnames';
import React from 'react';
import { useDeviceType } from '../../hooks/useDeviceType';
import DeviceSelector from '../device-selector';
import { Icon } from '../index';
import './editor.scss';

interface Props {
	value: number | string | object;
	options: { icon: string; value: any; label: string }[];
	onChange: CallableFunction;
	label?: string;
	responsive?: boolean;
	inline?: boolean;
	hasLabel?: boolean;
}

const AdvanceSelect: React.FC<Props> = (props) => {
	const {
		value = {},
		onChange,
		responsive = false,
		label,
		options,
		inline = false,
		hasLabel = false,
	} = props;
	const id = useInstanceId(AdvanceSelect);
	const [deviceType] = useDeviceType();

	return (
		<div
			className={classnames(
				'masteriyo-control',
				'masteriyo-advance-select',
				{ 'masteriyo-responsive': responsive },
				{ 'masteriyo-inline': !responsive && inline },
			)}
		>
			<div className="masteriyo-control-head masteriyo-advance-select-head">
				{label && (
					<label
						htmlFor={`masteriyo-advance-select-${id}`}
						className="masteriyo-control-label masteriyo-advance-select-label"
					>
						{label}
					</label>
				)}
				{responsive && <DeviceSelector />}
			</div>
			<div className="masteriyo-control-body masteriyo-advance-select-body">
				<div className="masteriyo-advance-select-items" role="group">
					{responsive
						? ['desktop', 'tablet', 'mobile'].map(
								(device) =>
									deviceType === device &&
									options.map((option) => (
										<div
											key={option.value}
											className="masteriyo-advance-select-item"
										>
											<button
												id={'masteriyo-button-' + option.value}
												className={classnames('masteriyo-button', {
													'is-active': value[device] === option.value,
												})}
												onClick={() => {
													onChange(
														Object.assign({}, value, {
															[device]:
																option.value === value[device]
																	? undefined
																	: option.value,
														}),
													);
												}}
											>
												<Icon type="controlIcon" name={option.icon} />
											</button>
											{hasLabel && option.hasOwnProperty('label') ? (
												<label htmlFor={'masteriyo-button-' + option.value}>
													{option.label}
												</label>
											) : (
												''
											)}
										</div>
									)),
							)
						: options.map((option) => (
								<div
									key={option.value}
									className="masteriyo-advance-select-item"
								>
									<button
										id={'masteriyo-button-' + option.value}
										className={classnames(
											'masteriyo-button',
											'masteriyo-advance-select-item',
											{ 'is-active': value === option.value },
										)}
										onClick={() =>
											onChange(
												value === option.value ? undefined : option.value,
											)
										}
									>
										<Icon type="controlIcon" name={option.icon} />
									</button>
									{hasLabel && option.hasOwnProperty('label') ? (
										<label htmlFor={'masteriyo-button-' + option.value}>
											{option.label}
										</label>
									) : (
										''
									)}
								</div>
							))}
				</div>
			</div>
		</div>
	);
};

export default AdvanceSelect;
