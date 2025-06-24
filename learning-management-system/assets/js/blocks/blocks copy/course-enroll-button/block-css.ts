import { useEffect, useMemo } from 'react';

export function useBlockCSS(props: any) {
	const { clientId, attributes, setAttributes } = props;
	const {
		clientId: persistedClientId,
		alignment,
		fontSize,
		textColor,
	} = attributes;
	const BLOCK_WRAPPER = `#block-${clientId}`;
	const MASTERIYO_WRAPPER = `.masteriyo-enroll-button-block--${persistedClientId}`;
	const fontSizeValue = fontSize ? fontSize.value + fontSize.unit : '';

	const editorCSS = useMemo(() => {
		let css: string[] = [];
		if (alignment) {
			css.push(`${BLOCK_WRAPPER} { text-align: ${alignment}; }`);
		}
		if (fontSizeValue) {
			css.push(`${BLOCK_WRAPPER} { font-size: ${fontSizeValue}; }`);
		}
		if (textColor) {
			css.push(`${BLOCK_WRAPPER} { color: ${textColor}; }`);
		}
		// if (startCourseButtonBorder) {
		// 	Object.keys(startCourseButtonBorder.radius).forEach((device) => {
		// 		const deviceBorderRadius = startCourseButtonBorder.radius[device];
		// 		css.push(
		// 			`${BLOCK_WRAPPER}.masteriyo-course--content .masteriyo-time-btn .masteriyo-btn {
		//          border-radius-top-left: ${deviceBorderRadius.top}${deviceBorderRadius.unit};
		//          border-radius-top-right: ${deviceBorderRadius.right}${deviceBorderRadius.unit};
		//          border-radius-bottom-right: ${deviceBorderRadius.bottom}${deviceBorderRadius.unit};
		//          border-radius-bottom-left: ${deviceBorderRadius.left}${deviceBorderRadius.unit};
		//        }`,
		// 		);
		// 	});
		// }
		return css.join('\n');
	}, [BLOCK_WRAPPER, alignment, fontSizeValue, textColor]);

	const cssToSave = useMemo(() => {
		let css: string[] = [];
		css.push(`${MASTERIYO_WRAPPER} { margin: 10px 0px 10px 0px; }`);
		if (alignment) {
			css.push(`${MASTERIYO_WRAPPER} { text-align: ${alignment}; }`);
		}
		if (fontSizeValue) {
			css.push(`${MASTERIYO_WRAPPER} { font-size: ${fontSizeValue}; }`);
		}
		if (textColor) {
			css.push(`${MASTERIYO_WRAPPER} { color: ${textColor}; }`);
		}
		// if (startCourseButtonBorder) {
		// 	css.push(
		// 		`${MASTERIYO_WRAPPER} .masteriyo-course--content .masteriyo-time-btn .masteriyo-btn {`,
		// 	);
		// 	// Desktop styles (no media query needed)
		// 	const desktopButtonBorder = startCourseButtonBorder.radius.desktop;

		// 	css.push(`
		// 		  border-radius-top: ${desktopButtonBorder.top}${desktopButtonBorder.unit};
		// 		  border-radius-right: ${desktopButtonBorder.right}${desktopButtonBorder.unit};
		// 		  border-radius-bottom: ${desktopButtonBorder.bottom}${desktopButtonBorder.unit};
		// 		  border-radius-left: ${desktopButtonBorder.left}${desktopButtonBorder.unit};
		// 	 `);

		// 	// Tablet styles with media query
		// 	const tabletButtonBorder = startCourseButtonBorder.radius.tablet;
		// 	css.push(`
		// 		@media (max-width: 960px) {
		// 			 border-radius-top: ${tabletButtonBorder.top}${tabletButtonBorder.unit};
		// 			 border-radius-right: ${tabletButtonBorder.right}${tabletButtonBorder.unit};
		// 			 border-radius-bottom: ${tabletButtonBorder.bottom}${tabletButtonBorder.unit};
		// 			 border-radius-left: ${tabletButtonBorder.left}${tabletButtonBorder.unit};
		// 		}
		// 	`);

		// 	// Mobile styles with media query
		// 	const mobileButtonBorder = startCourseButtonBorder.radius.mobile;
		// 	css.push(`
		// 		@media (max-width: 768px) {
		// 			border-radius-top: ${mobileButtonBorder.top}${mobileButtonBorder.unit};
		// 			border-radius-right: ${mobileButtonBorder.right}${mobileButtonBorder.unit};
		// 			border-radius-bottom: ${mobileButtonBorder.bottom}${mobileButtonBorder.unit};
		// 			border-radius-left: ${mobileButtonBorder.left}${mobileButtonBorder.unit};
		// 		}
		// 	`);

		// 	css.push(`}`); // Close outer container rule
		// }
		return css.join('\n');
	}, [MASTERIYO_WRAPPER, alignment, fontSizeValue, textColor]);

	useEffect(() => {
		setAttributes({ blockCSS: cssToSave });
	}, [cssToSave, setAttributes]);

	return { editorCSS, cssToSave };
}
