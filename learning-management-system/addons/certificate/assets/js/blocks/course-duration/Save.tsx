import * as React from 'react';
import { camelToKebab } from '../../utils/blocks';

const Save: React.FC<any> = (props) => {
	const { clientId, fontFamily } = props.attributes;

	return (
		<div
			className={`masteriyo-course-duration-block--${clientId}${
				fontFamily && 'Default' !== fontFamily
					? ` has-${camelToKebab(fontFamily)}-font-family`
					: ''
			}`}
		>
			{`{{masteriyo_course_duration}}`}
		</div>
	);
};

export default Save;
