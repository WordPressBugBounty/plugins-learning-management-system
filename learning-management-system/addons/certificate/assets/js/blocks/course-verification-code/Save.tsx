import * as React from 'react';

const Save: React.FC<any> = (props) => {
	const { clientId } = props.attributes;
	return (
		<div
			className={`masteriyo-certificate-verification-code-block--${clientId}`}
		>
			{`{{masteriyo_certificate_verification_code}}`}
		</div>
	);
};

export default Save;
