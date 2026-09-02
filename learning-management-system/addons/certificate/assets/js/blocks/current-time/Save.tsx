import * as React from 'react';

const Save: React.FC<any> = (props) => {
	const { clientId } = props.attributes;

	return (
		<div className={`masteriyo-current-time-block--${clientId}`}>
			{`{{masteriyo_current_time}}`}
		</div>
	);
};

export default Save;
