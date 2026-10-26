import React, { ReactElement } from 'react';

// Same artwork as the legacy Divi 4 module's icon (svg/course-categories-module-icon.svg).
export const name = 'masteriyo/course-categories-list';
// The Add Module panel hard-codes viewBox="0 0 16 16" for every icon
// regardless of what a module declares, so this has to actually match that
// box (not just say so).
export const viewBox = '0 0 16 16';
export const component = (): ReactElement => (
	// The original artwork (unchanged below) was drawn for a 0-24 box;
	// scale+translate it down to fit 16x16 with padding, the way Divi's own
	// module icons do, instead of hand-recomputing every path coordinate.
	<g fill="#2b87da" transform="translate(0.8, 0.8) scale(0.6)">
		<path
			d="M10.9,2H2v8.9h8.9V2z M9,9H4V4h5V9z M22,2h-8.9v8.9H22V2z M20,9h-5V4h5V9z M10.9,13.1H2V22h8.9L10.9,13.1z
	 M9,20H4v-5h5V20z M19.9,13.7c-2.1-1.3-4.9-0.6-6.2,1.5c-0.9,1.4-0.9,3.3,0,4.7c0.3,0.6,0.8,1.1,1.4,1.4c2.1,1.3,4.9,0.6,6.2-1.5
	c0.9-1.4,0.9-3.3,0-4.7C21,14.5,20.5,14,19.9,13.7L19.9,13.7z M18.9,19.3c-1,0.8-2.4,0.6-3.2-0.4c-0.6-0.8-0.6-1.9,0-2.7
	c0.1-0.2,0.2-0.3,0.4-0.4c1-0.8,2.4-0.6,3.2,0.4c0.6,0.8,0.6,1.9,0,2.7C19.2,19.1,19.1,19.2,18.9,19.3L18.9,19.3z"
		/>
	</g>
);
