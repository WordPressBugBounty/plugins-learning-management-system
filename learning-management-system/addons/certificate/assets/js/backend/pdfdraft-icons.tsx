/**
 * Minimal icon stubs used by masteriyo-fields.ts as element-picker icons.
 * These replace lucide-react (not installed in free plugin).
 * SVG paths are simplified but recognizable in the designer's panel.
 */
import React from 'react';

type IconProps = React.SVGProps<SVGSVGElement> & { size?: number | string };

function icon(path: string) {
	return function Icon({ width = 16, height = 16, size, ...rest }: IconProps) {
		const sz = size ?? width;
		return (
			<svg
				xmlns="http://www.w3.org/2000/svg"
				width={sz}
				height={sz}
				viewBox="0 0 24 24"
				fill="none"
				stroke="currentColor"
				strokeWidth={2}
				strokeLinecap="round"
				strokeLinejoin="round"
				{...rest}
			>
				<path d={path} />
			</svg>
		);
	};
}

export const BookOpen = icon(
	'M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2zM22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z',
);
export const Calendar = icon(
	'M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z',
);
export const CalendarCheck = icon(
	'M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2zM9 16l2 2 4-4',
);
export const CalendarDays = icon(
	'M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2zM8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01',
);
export const Clock = icon(
	'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zM12 6v6l4 2',
);
export const Globe = icon(
	'M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20zM2 12h20M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z',
);
export const GraduationCap = icon(
	'M22 10v6M2 10l10-5 10 5-10 5zM6 12v5c3 3 9 3 12 0v-5',
);
export const QrCode = icon(
	'M3 3h6v6H3zM15 3h6v6h-6zM3 15h6v6H3zM15 15h.01M21 15h.01M15 21h.01M21 21h.01M21 18h-3v-3',
);
export const Shield = icon('M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z');
export const Star = icon(
	'M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z',
);
export const Timer = icon(
	'M10 2h4M12 14l4-4M12 22a8 8 0 1 0 0-16 8 8 0 0 0 0 16z',
);
export const User = icon(
	'M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2M12 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8z',
);
export const Users = icon(
	'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75',
);
