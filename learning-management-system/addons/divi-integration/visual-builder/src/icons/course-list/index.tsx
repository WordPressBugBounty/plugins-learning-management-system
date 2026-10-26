import React, { ReactElement } from 'react';

// Same artwork as the legacy Divi 4 module's icon (svg/course-list-module-icon.svg).
export const name = 'masteriyo/course-list';
// The Add Module panel hard-codes viewBox="0 0 16 16" for every icon
// regardless of what a module declares, so this has to actually match that
// box (not just say so) — the shapes below are the same 3x2 grid, rescaled
// and padded to fit 16x16 the way Divi's own module icons do.
export const viewBox = '0 0 16 16';
export const component = (): ReactElement => (
	<g fill="#2b87da">
		<rect x="2" y="2" width="3" height="5" />
		<rect x="6.5" y="2" width="3" height="5" />
		<rect x="11" y="2" width="3" height="5" />
		<rect x="2" y="9" width="3" height="5" />
		<rect x="6.5" y="9" width="3" height="5" />
		<rect x="11" y="9" width="3" height="5" />
	</g>
);
