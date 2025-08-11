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
	const MASTERIYO_WRAPPER = `.masteriyo-course-content-block--${persistedClientId}`;
	const MASTERIYO_ALIGNMENT = `.masteriyo-course-content-block--${persistedClientId} .masteriyo-stab`;
	const fontSizeValue = fontSize ? fontSize.value + fontSize.unit : '';
	const textColorValue = `${MASTERIYO_WRAPPER} .course-overview`;

	const editorCSS = useMemo(() => {
		let css: string[] = [];

		if (alignment) {
			css.push(`${MASTERIYO_ALIGNMENT} { justify-content: ${alignment}; }`);
		}
		if (fontSizeValue) {
			css.push(`${BLOCK_WRAPPER} { font-size: ${fontSizeValue}; }`);
		}
		if (textColor) {
			css.push(`${textColorValue} { color: ${textColor}; }`);
		}

		return css.join('\n');
	}, [BLOCK_WRAPPER, alignment, fontSizeValue, textColor]);

	const cssToSave = useMemo(() => {
		let css: string[] = [];

		if (alignment) {
			css.push(`${MASTERIYO_ALIGNMENT} { justify-content: ${alignment}; }`);
		}
		if (fontSizeValue) {
			css.push(`${MASTERIYO_WRAPPER} { font-size: ${fontSizeValue}; }`);
		}
		if (textColor) {
			css.push(`${textColorValue} { color: ${textColor}; }`);
		}

		return css.join('\n');
	}, [MASTERIYO_WRAPPER, alignment, fontSizeValue, textColor]);

	useEffect(() => {
		setAttributes({ blockCSS: cssToSave });
	}, [cssToSave, setAttributes]);

	return { editorCSS, cssToSave };
}
