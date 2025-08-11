import { useEffect, useMemo } from 'react';
import { useDeviceType } from '../../hooks/useDeviceType';

export function useBlockCSS(props: any) {
	const { clientId, attributes, setAttributes } = props;
	const {
		clientId: persistedClientId,
		alignment,
		backgroundColor,
		fontSize,
		borderRadius,
		padding,
		textGroupColor,
		buyButtonTextColor,
		buttonFontSize,
		buyButtonBackgroundColor,
	} = attributes;
	const [deviceType] = useDeviceType();
	const BLOCK_WRAPPER = `#block-${clientId}`;
	const MASTERIYO_WRAPPER = `.masteriyo-group-price-button-block--${persistedClientId} .masteriyo-group-course__group-button`;
	const COURSE_TITLE = `${MASTERIYO_WRAPPER} .masteriyo-group-course__group-title`;
	const BUY_BUTTON = `${MASTERIYO_WRAPPER} a.masteriyo-group-course__buy-now-button`;
	const fontSizeValue = fontSize ? fontSize.value + fontSize.unit : '';
	const buttonFontSizeValue = buttonFontSize
		? buttonFontSize.value + buttonFontSize.unit
		: '';

	const borderRadiusValue = borderRadius
		? borderRadius.value + borderRadius.unit
		: '';

	const editorCSS = useMemo(() => {
		let css: string[] = [];

		if (fontSizeValue) {
			css.push(`${COURSE_TITLE} { font-size: ${fontSizeValue}; }`);
		}
		if (buttonFontSizeValue) {
			css.push(`${BUY_BUTTON} { font-size: ${buttonFontSizeValue}; }`);
		}
		if (backgroundColor) {
			css.push(`${BLOCK_WRAPPER} { background-color: ${backgroundColor}; }`);
		}
		if (borderRadius) {
			css.push(`${BLOCK_WRAPPER} { border-radius: ${borderRadiusValue}; }`);
		}
		if (buyButtonBackgroundColor) {
			css.push(
				`${BUY_BUTTON} { background-color: ${buyButtonBackgroundColor}; }`,
			);
		}

		if (buyButtonTextColor) {
			css.push(`${BUY_BUTTON} { color: ${buyButtonTextColor}; }`);
		}

		if (textGroupColor) {
			css.push(`${COURSE_TITLE} { color: ${textGroupColor}; }`);
		}

		if (padding) {
			const d = padding.padding.desktop;
			const t = padding.padding.tablet;
			const m = padding.padding.mobile;

			css.push(`${BLOCK_WRAPPER} {
				padding-top: ${d.top}${d.unit};
				padding-right: ${d.right}${d.unit};
				padding-bottom: ${d.bottom}${d.unit};
				padding-left: ${d.left}${d.unit};
			}
			@media (max-width: 960px) {
				${BLOCK_WRAPPER} {
					padding-top: ${t.top}${t.unit};
					padding-right: ${t.right}${t.unit};
					padding-bottom: ${t.bottom}${t.unit};
					padding-left: ${t.left}${t.unit};
				}
			}
			@media (max-width: 768px) {
				${BLOCK_WRAPPER} {
					padding-top: ${m.top}${m.unit};
					padding-right: ${m.right}${m.unit};
					padding-bottom: ${m.bottom}${m.unit};
					padding-left: ${m.left}${m.unit};
				}
			}`);
		}

		return css.join('\n');
	}, [
		BLOCK_WRAPPER,
		alignment,
		fontSizeValue,
		backgroundColor,
		borderRadiusValue,
		buyButtonBackgroundColor,
		buyButtonTextColor,
		textGroupColor,
		buttonFontSizeValue,
		padding,
	]);

	const cssToSave = useMemo(() => {
		let css: string[] = [];

		if (alignment) {
			css.push(`${MASTERIYO_WRAPPER} { justify-content : ${alignment}; }`);
		}
		if (fontSizeValue) {
			css.push(`${COURSE_TITLE} { font-size: ${fontSizeValue}; }`);
		}
		if (buttonFontSizeValue) {
			css.push(`${BUY_BUTTON} { font-size: ${buttonFontSizeValue}; }`);
		}
		if (backgroundColor) {
			css.push(
				`${MASTERIYO_WRAPPER} { background-color: ${backgroundColor}; }`,
			);
		}
		if (borderRadius) {
			css.push(`${MASTERIYO_WRAPPER} { border-radius: ${borderRadiusValue}; }`);
		}

		if (buyButtonBackgroundColor) {
			css.push(
				`${BUY_BUTTON} { background-color: ${buyButtonBackgroundColor}; }`,
			);
		}

		if (buyButtonTextColor) {
			css.push(`${BUY_BUTTON} { color: ${buyButtonTextColor}; }`);
		}
		if (textGroupColor) {
			css.push(`${COURSE_TITLE} { color: ${textGroupColor}; }`);
		}
		if (padding) {
			const d = padding.padding.desktop;
			const t = padding.padding.tablet;
			const m = padding.padding.mobile;

			css.push(`${MASTERIYO_WRAPPER} {
				padding-top: ${d.top}${d.unit};
				padding-right: ${d.right}${d.unit};
				padding-bottom: ${d.bottom}${d.unit};
				padding-left: ${d.left}${d.unit};
			}
			@media (max-width: 960px) {
				${MASTERIYO_WRAPPER} {
					padding-top: ${t.top}${t.unit};
					padding-right: ${t.right}${t.unit};
					padding-bottom: ${t.bottom}${t.unit};
					padding-left: ${t.left}${t.unit};
				}
			}
			@media (max-width: 768px) {
				${MASTERIYO_WRAPPER} {
					padding-top: ${m.top}${m.unit};
					padding-right: ${m.right}${m.unit};
					padding-bottom: ${m.bottom}${m.unit};
					padding-left: ${m.left}${m.unit};
				}
			}`);
		}

		return css.join('\n');
	}, [
		MASTERIYO_WRAPPER,
		alignment,
		fontSizeValue,
		backgroundColor,
		borderRadiusValue,
		buyButtonBackgroundColor,
		buyButtonTextColor,
		textGroupColor,
		buttonFontSizeValue,
		padding,
	]);

	useEffect(() => {
		setAttributes({ blockCSS: cssToSave });
	}, [cssToSave, setAttributes]);

	return { editorCSS, cssToSave };
}
