import * as React from 'react';
import { camelToKebab } from '../../utils/blocks';

const Save: React.FC<any> = (props) => {
	const { clientId, fontFamily } = props.attributes;
	return (
		<div
			className={`masteriyo-student-name-block--${clientId}${
				fontFamily && 'Default' !== fontFamily
					? ` has-${camelToKebab(fontFamily)}-font-family`
					: ''
			}`}
		>
			{`{{masteriyo_student_name}}`}
		</div>
	);
};

export default Save;
