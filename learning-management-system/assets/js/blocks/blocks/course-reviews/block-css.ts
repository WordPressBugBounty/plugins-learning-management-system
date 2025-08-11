import { useEffect, useMemo } from 'react';
import { useDeviceType } from './../../hooks/useDeviceType';
export function useBlockCSS(props: any) {
	const { clientId, attributes, setAttributes } = props;
	const {
		clientId: persistedClientId,
		alignment,
		fontSize,
		textColor,
		reviwesInputBorder = {},
		formBackgroundColor,
	} = attributes;

	const [deviceType] = useDeviceType();
	const BLOCK_WRAPPER = `#block-${clientId}`;
	const MASTERIYO_WRAPPER = `.masteriyo-course-reviews-block--${persistedClientId}`;
	const MASTERIYO_WRAPPER_CSS_TITLE = `${MASTERIYO_WRAPPER} label`;
	const REVIEWS_INPUT = `${MASTERIYO_WRAPPER} .masteriyo-input`;
	const REVIEWS_ICON = `${MASTERIYO_WRAPPER} .masteriyo-stab-rs`;
	const fontSizeValue = fontSize ? fontSize.value + fontSize.unit : '';

	const radius = reviwesInputBorder?.radius?.[deviceType.toLowerCase()] || {};
	const unit = reviwesInputBorder?.radius?.unit || 'px';

	const radiusCSS = Object.entries(radius)
		.map(([key, val]) => {
			if (val === undefined || val === null) return '';

			const cssMap: Record<string, string> = {
				top: 'border-top-left-radius',
				right: 'border-top-right-radius',
				bottom: 'border-bottom-right-radius',
				left: 'border-bottom-left-radius',
			};

			const cssProp = cssMap[key];
			if (!cssProp) return '';

			const valueString = typeof val === 'number' ? `${val}${unit}` : `${val}`;
			return `${REVIEWS_INPUT} { ${cssProp}: ${valueString}; }`;
		})
		.filter(Boolean)
		.join('\n');

	const editorCSS = useMemo(() => {
		let css: string[] = [];

		if (alignment) {
			css.push(`${BLOCK_WRAPPER} { text-align: ${alignment}; }`);
		}
		if (fontSizeValue) {
			css.push(
				`${MASTERIYO_WRAPPER_CSS_TITLE} { font-size: ${fontSizeValue}; }`,
			);
		}
		if (textColor) {
			css.push(`${MASTERIYO_WRAPPER_CSS_TITLE} { color: ${textColor}; }`);
		}

		if (formBackgroundColor) {
			css.push(
				`${BLOCK_WRAPPER} { background-color: ${formBackgroundColor}; }`,
			);
		}

		if (radiusCSS) {
			css.push(radiusCSS);
		}

		return css.join('\n');
	}, [
		BLOCK_WRAPPER,
		alignment,
		fontSizeValue,
		textColor,
		radiusCSS,
		formBackgroundColor,
	]);

	const cssToSave = useMemo(() => {
		let css: string[] = [];

		if (alignment) {
			css.push(`${REVIEWS_ICON} { justify-content: ${alignment}; }`);
		}

		if (alignment) {
			css.push(`${MASTERIYO_WRAPPER} { text-align: ${alignment}; }`);
		}

		if (fontSizeValue) {
			css.push(
				`${MASTERIYO_WRAPPER_CSS_TITLE} { font-size: ${fontSizeValue}; }`,
			);
		}
		if (textColor) {
			css.push(`${MASTERIYO_WRAPPER_CSS_TITLE} { color: ${textColor}; }`);
		}

		if (formBackgroundColor) {
			css.push(
				`${MASTERIYO_WRAPPER} { background-color: ${formBackgroundColor}; }`,
			);
		}

		if (radiusCSS) {
			css.push(radiusCSS);
		}

		return css.join('\n');
	}, [
		MASTERIYO_WRAPPER,
		alignment,
		fontSizeValue,
		textColor,
		radiusCSS,
		formBackgroundColor,
	]);

	useEffect(() => {
		setAttributes({ blockCSS: cssToSave });
	}, [cssToSave, setAttributes]);

	return { editorCSS, cssToSave };
}
