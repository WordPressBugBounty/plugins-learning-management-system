import { ColorPalette } from '@wordpress/block-editor';
import { ColorPicker, Popover } from '@wordpress/components';
import { useInstanceId } from '@wordpress/compose';
import { useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import classnames from 'classnames';
import React from 'react';
import { Icon } from '..';
import './editor.scss';

interface Props {
	label: string;
	onChange: CallableFunction;
	value: string;
}

const Color: React.FC<Props> = (props) => {
	const { label, value, onChange } = props;
	const [isOpen, setIsOpen] = useState(false);
	const toggleRef: any = useRef();
	const colorPickerRef: any = useRef(null);
	const id = useInstanceId(Color);

	const setSetting = (val) => {
		const newVal =
			val.rgb.a !== 1
				? 'rgba(' +
					val.rgb.r +
					',' +
					val.rgb.g +
					',' +
					val.rgb.b +
					',' +
					val.rgb.a +
					')'
				: val.hex;
		onChange(newVal);
	};

	return (
		<div className="masteriyo-control masteriyo-color">
			<div className="masteriyo-control-head masteriyo-color-head">
				{label && (
					<label
						htmlFor={`masteriyo-color-${id}`}
						className="masteriyo-control-label masteriyo-color-label"
					>
						{label}
					</label>
				)}
				<div className="masteriyo-color-buttons">
					{value && (
						<button
							className="masteriyo-color-clear-button"
							onClick={() => {
								onChange('');
							}}
						>
							<Icon type="controlIcon" name="reset" size={20} />
						</button>
					)}
					<button
						id={`masteriyo-color-${id}`}
						className="masteriyo-color-toggle-button"
						onClick={() => setIsOpen(!isOpen)}
						ref={toggleRef}
					>
						<span
							className={classnames('masteriyo-color-indicator', {
								'is-empty': !value,
							})}
							style={{
								width: '24px',
								height: '24px',
								background:
									value ||
									'repeating-conic-gradient(#999 0% 25%, #eee 0% 50%) center center / 8px 8px',
								display: 'inline-block',
								borderRadius: '50%',
							}}
						/>
						{value && <span className="masteriyo-color-text">{value}</span>}
					</button>
				</div>
			</div>
			{isOpen && (
				<Popover
					position="bottom center"
					onFocusOutside={(e) => {
						if (e.relatedTarget !== toggleRef.current) {
							setIsOpen(false);
						}
					}}
					focusOnMount="container"
				>
					<div className="masteriyo-control-body masteriyo-color-body">
						<div className="masteriyo-color-picker">
							<div className="masteriyo-color-palette">
								<ColorPalette
									value={value}
									onChange={(color) => {
										const { commitValues } = colorPickerRef?.current || false;

										if (color && commitValues) {
											commitValues({ hex: color, source: 'hex' });
										}

										onChange(color);
									}}
									disableCustomColors={true}
									clearable={false}
									colors={[
										{ name: __('Blue', 'masteriyo'), color: '#2871ff' },
										{ name: __('Dark Golden', 'masteriyo'), color: '#e89623' },
										{ name: __('Black', 'masteriyo'), color: '#000000' },
										{ name: __('White', 'masteriyo'), color: '#ffffff' },
									]}
								/>
							</div>
							<ColorPicker
								color={value || ''}
								onChangeComplete={(color) => {
									setSetting(color);
								}}
								// {...{ ref: colorPickerRef }}
							/>
						</div>
					</div>
				</Popover>
			)}
		</div>
	);
};

export default Color;
