import * as React from 'react';

const Save: React.FC<any> = (props) => {
	const { clientId } = props.attributes;

	return (
		<div className={`masteriyo-course-start-date-block--${clientId}`}>
			{`{{masteriyo_course_start_date}}`}
		</div>
	);
};

export default Save;
