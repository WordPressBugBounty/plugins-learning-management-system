import { useState } from '@wordpress/element';
import classnames from 'classnames';
import React from 'react';
import Icon from '../icon';
import './editor.scss';

interface PropsType {
	title: string;
	initialOpen?: boolean;
	isOpen?: boolean;
	onToggle?: () => void;
	children?: React.ReactNode;
}

const Panel: React.FC<PropsType> = (props) => {
	const {
		children,
		title,
		initialOpen = false,
		isOpen: controlledIsOpen,
		onToggle,
	} = props;

	const [internalOpen, setInternalOpen] = useState(initialOpen);
	const isOpen =
		controlledIsOpen !== undefined ? controlledIsOpen : internalOpen;

	const handleToggle = () => {
		if (onToggle) {
			onToggle();
		} else {
			setInternalOpen(!internalOpen);
		}
	};

	return (
		<div className={classnames('masteriyo-panel', { 'is-open': isOpen })}>
			<div className="masteriyo-panel-head">
				<button
					onClick={handleToggle}
					className="masteriyo-panel-toggle-button"
				>
					<span className="masteriyo-panel-title">{title || ''}</span>
					<span className="masteriyo-panel-icon">
						{isOpen ? (
							<Icon type="controlIcon" name="chevron-up-circle" />
						) : (
							<Icon type="controlIcon" name="chevron-down-circle" />
						)}
					</span>
				</button>
			</div>
			{isOpen && <div className="masteriyo-panel-body">{children}</div>}
		</div>
	);
};

export default Panel;
