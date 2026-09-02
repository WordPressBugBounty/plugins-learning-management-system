import * as React from 'react';

const Save: React.FC<any> = (props) => {
	const { clientId } = props.attributes;

	return (
		<div className={`masteriyo-current-date-block--${clientId}`}>
			{`{{masteriyo_current_date}}`}
		</div>
	);
};

export default Save;
