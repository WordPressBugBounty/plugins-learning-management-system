import * as React from 'react';

const Save: React.FC<any> = (props) => {
	const { clientId } = props.attributes;
	return (
		<div className={`masteriyo-qr-code--${clientId}`}>
			{`{{masteriyo_course_certificate_verification}}`}
		</div>
	);
};

export default Save;
