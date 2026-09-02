import * as React from 'react';
import { camelToKebab } from '../../utils/blocks';

const Save: React.FC<any> = (props) => {
	const { clientId, fontFamily } = props.attributes;
	return (
		<div
			className={`masteriyo-co-instructors-name-block--${clientId}${
				fontFamily && 'Default' !== fontFamily
					? ` has-${camelToKebab(fontFamily)}-font-family`
					: ''
			}`}
		>
			{`{{masteriyo_co_instructors_names}}`}
		</div>
	);
};

export default Save;
