import { useEffect, useMemo } from 'react';

export function useBlockCSS(props: any) {
	const { clientId, attributes, setAttributes } = props;
	const {
		clientId: persistedClientId,
		alignment,
		fontSize,
		textColor,
		margin,
		padding,
		gap,
	} = attributes;
	const BLOCK_WRAPPER = `#block-${clientId}`;
	const MASTERIYO_WRAPPER = `.masteriyo-single-course-block--${persistedClientId}`;
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
		if (gap) {
			css.push(`${BLOCK_WRAPPER} { gap: ${gap}; }`);
		}
		// if (margin) {
		// 	Object.keys(margin.margin).forEach((device) => {
		// 		const deviceMargin = margin.margin[device];
		// 		css.push(
		// 			`${BLOCK_WRAPPER} {
		//          margin-top: ${deviceMargin.top}${deviceMargin.unit};
		//          margin-right: ${deviceMargin.right}${deviceMargin.unit};
		//          margin-bottom: ${deviceMargin.bottom}${deviceMargin.unit};
		//          margin-left: ${deviceMargin.left}${deviceMargin.unit};
		//        }`,
		// 		);
		// 	});
		// }
		if (padding) {
			Object.keys(padding.padding).forEach((device) => {
				const devicePadding = padding.padding[device];
				css.push(
					`${BLOCK_WRAPPER} {
		         padding-top: ${devicePadding.top}${devicePadding.unit};
		         padding-right: ${devicePadding.right}${devicePadding.unit};
		         padding-bottom: ${devicePadding.bottom}${devicePadding.unit};
		         padding-left: ${devicePadding.left}${devicePadding.unit};
		       }`,
				);
			});
		}
		return css.join('\n');
	}, [
		BLOCK_WRAPPER,
		alignment,
		fontSizeValue,
		textColor,
		margin,
		padding,
		gap,
	]);

	const cssToSave = useMemo(() => {
		let css: string[] = [];

		if (alignment) {
			css.push(`${MASTERIYO_WRAPPER} { text-align: ${alignment}; }`);
		}
		if (fontSizeValue) {
			css.push(`${MASTERIYO_WRAPPER} { font-size: ${fontSizeValue}; }`);
		}
		if (textColor) {
			css.push(`${MASTERIYO_WRAPPER} { color: ${textColor}; }`);
		}
		if (gap) {
			css.push(`${MASTERIYO_WRAPPER} { gap: ${gap}; }`);
		}
		// if (margin) {
		// 	css.push(`${MASTERIYO_WRAPPER} {`);
		// 	// Desktop styles (no media query needed)
		// 	const desktopMargin = margin.margin.desktop;

		// 	css.push(`
		// 		 margin-top: ${desktopMargin.top}${desktopMargin.unit};
		// 		 margin-right: ${desktopMargin.right}${desktopMargin.unit};
		// 		 margin-bottom: ${desktopMargin.bottom}${desktopMargin.unit};
		// 		 margin-left: ${desktopMargin.left}${desktopMargin.unit};
		// 	 `);

		// 	// Tablet styles with media query
		// 	const tabletMargin = margin.margin.tablet;
		// 	css.push(`
		// 		@media (max-width: 960px) {
		// 			margin-top: ${tabletMargin.top}${tabletMargin.unit};
		// 			margin-right: ${tabletMargin.right}${tabletMargin.unit};
		// 			margin-bottom: ${tabletMargin.bottom}${tabletMargin.unit};
		// 			margin-left: ${tabletMargin.left}${tabletMargin.unit};
		// 		}
		// 	`);

		// 	// Mobile styles with media query
		// 	const mobileMargin = margin.margin.mobile;
		// 	css.push(`
		// 		@media (max-width: 768px) {
		// 			margin-top: ${mobileMargin.top}${mobileMargin.unit};
		// 			margin-right: ${mobileMargin.right}${mobileMargin.unit};
		// 			margin-bottom: ${mobileMargin.bottom}${mobileMargin.unit};
		// 			margin-left: ${mobileMargin.left}${mobileMargin.unit};
		// 		}
		// 	`);

		// 	css.push(`}`); // Close outer container rule
		// }

		if (padding) {
			css.push(`${MASTERIYO_WRAPPER} {`);
			// Desktop styles (no media query needed)
			const desktopPadding = padding.padding.desktop;

			css.push(`
				 padding-top: ${desktopPadding.top}${desktopPadding.unit};
				 padding-right: ${desktopPadding.right}${desktopPadding.unit};
				 padding-bottom: ${desktopPadding.bottom}${desktopPadding.unit};
				 padding-left: ${desktopPadding.left}${desktopPadding.unit};
			 `);

			// Tablet styles with media query
			const tabletPadding = padding.padding.tablet;
			css.push(`
				@media (max-width: 960px) {
					padding-top: ${tabletPadding.top}${tabletPadding.unit};
					padding-right: ${tabletPadding.right}${tabletPadding.unit};
					padding-bottom: ${tabletPadding.bottom}${tabletPadding.unit};
					padding-left: ${tabletPadding.left}${tabletPadding.unit};
				}
			`);

			// Mobile styles with media query
			const mobilePadding = padding.padding.mobile;
			css.push(`
				@media (max-width: 768px) {
					padding-top: ${mobilePadding.top}${mobilePadding.unit};
					padding-right: ${mobilePadding.right}${mobilePadding.unit};
					padding-bottom: ${mobilePadding.bottom}${mobilePadding.unit};
					padding-left: ${mobilePadding.left}${mobilePadding.unit};
				}
			`);

			css.push(`}`); // Close outer container rule
		}
		return css.join('\n');
	}, [
		MASTERIYO_WRAPPER,
		alignment,
		fontSizeValue,
		textColor,
		margin,
		padding,
		gap,
	]);

	useEffect(() => {
		setAttributes({ blockCSS: cssToSave });
	}, [cssToSave, setAttributes]);

	return { editorCSS, cssToSave };
}
