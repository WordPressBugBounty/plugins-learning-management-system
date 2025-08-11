import { useEffect, useMemo } from 'react';
import { useDeviceType } from './../../hooks/useDeviceType';

export function useBlockCSS(props: any) {
	const { clientId, attributes, setAttributes } = props;

	const {
		clientId: persistedClientId,
		alignment,
		fontSize,
		textColor,
		backgroundColor,
		startCourseButtonBorder = {},
	} = attributes;

	const [deviceType] = useDeviceType();

	const BLOCK_WRAPPER = `#block-${clientId}`;
	const MASTERIYO_WRAPPER = `.masteriyo-enroll-button-block--${persistedClientId}`;
	const EnrollButton = `${MASTERIYO_WRAPPER} .masteriyo-enroll-btn`;
	const EnrollButtonAlignment = `${MASTERIYO_WRAPPER} .masteriyo-time-btn`;
	const fontSizeValue = fontSize ? fontSize.value + fontSize.unit : '';

	const radius =
		startCourseButtonBorder?.radius?.[deviceType.toLowerCase()] || {};
	const unit = startCourseButtonBorder?.radius?.unit || 'px';

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
			return `${EnrollButton} { ${cssProp}: ${valueString}; }`;
		})
		.filter(Boolean)
		.join('\n');

	const editorCSS = useMemo(() => {
		let css: string[] = [];

		if (alignment) {
			css.push(`${BLOCK_WRAPPER} { text-align: ${alignment}; }`);
		}
		if (fontSizeValue) {
			css.push(`${EnrollButton} { font-size: ${fontSizeValue}; }`);
		}
		if (textColor) {
			css.push(`${EnrollButton} { color: ${textColor}; }`);
		}
		if (backgroundColor) {
			css.push(`${EnrollButton} { background-color: ${backgroundColor}; }`);
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
		backgroundColor,
		radiusCSS,
	]);

	const cssToSave = useMemo(() => {
		let css: string[] = [];

		css.push(`${MASTERIYO_WRAPPER} { margin: 10px 0; }`);
		if (alignment) {
			css.push(`${EnrollButtonAlignment} { justify-content: ${alignment}; }`);
		}
		if (fontSizeValue) {
			css.push(`${EnrollButton} { font-size: ${fontSizeValue}; }`);
		}
		if (textColor) {
			css.push(`${EnrollButton} { color: ${textColor}; }`);
		}
		if (backgroundColor) {
			css.push(
				`${EnrollButton} { background-color: ${backgroundColor} !important; }`,
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
		backgroundColor,
		EnrollButton,
		radiusCSS,
		EnrollButtonAlignment,
	]);

	useEffect(() => {
		setAttributes({ blockCSS: cssToSave });
	}, [cssToSave, setAttributes]);

	return { editorCSS, cssToSave };
}
