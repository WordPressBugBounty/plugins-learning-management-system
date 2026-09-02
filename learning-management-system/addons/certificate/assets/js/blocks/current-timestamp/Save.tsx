import * as React from 'react';

const Save: React.FC<any> = (props) => {
	const { clientId } = props.attributes;

	return (
		<div className={`masteriyo-current-timestamp-block--${clientId}`}>
			{`{{masteriyo_current_timestamp}}`}
		</div>
	);
};

export default Save;
