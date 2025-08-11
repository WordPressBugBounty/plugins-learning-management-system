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
	const MASTERIYO_WRAPPER = `.masteriyo-course-highlights-block--${persistedClientId}`;
	const MASTERIYO_DESCRIPTION_WRAPPER = `.masteriyo-course-highlights-block--${persistedClientId} .masteriyo-course--content__description`;
	const MASTERIYO_DESCRIPTION_TITLE_WRAPPER = `.masteriyo-course-highlights-block--${persistedClientId} .masteriyo-course--content__description h5`;
	const fontSizeValue = fontSize ? fontSize.value + fontSize.unit : '';

	const editorCSS = useMemo(() => {
		let css: string[] = [];

		if (alignment) {
			css.push(`${MASTERIYO_WRAPPER} { text-align: ${alignment}; }`);
		}
		if (fontSizeValue) {
			css.push(`${MASTERIYO_WRAPPER} { font-size: ${fontSizeValue}; }`);
		}
		if (textColor) {
			css.push(`${MASTERIYO_DESCRIPTION_WRAPPER} { color: ${textColor}; }`);
		}

		if (fontSizeValue) {
			css.push(
				`${MASTERIYO_DESCRIPTION_TITLE_WRAPPER} { font-size: ${fontSizeValue}; }`,
			);
		}
		if (textColor) {
			css.push(
				`${MASTERIYO_DESCRIPTION_TITLE_WRAPPER} { color: ${textColor}; }`,
			);
		}

		return css.join('\n');
	}, [BLOCK_WRAPPER, alignment, fontSizeValue, textColor]);

	const cssToSave = useMemo(() => {
		let css: string[] = [];

		if (alignment) {
			css.push(`${MASTERIYO_WRAPPER} { text-align: ${alignment}; }`);
		}
		if (fontSizeValue) {
			css.push(`${MASTERIYO_WRAPPER} { font-size: ${fontSizeValue}; }`);
		}
		if (textColor) {
			css.push(`${MASTERIYO_DESCRIPTION_WRAPPER} { color: ${textColor}; }`);
		}

		if (fontSizeValue) {
			css.push(
				`${MASTERIYO_DESCRIPTION_TITLE_WRAPPER} { font-size: ${fontSizeValue}; }`,
			);
		}
		if (textColor) {
			css.push(
				`${MASTERIYO_DESCRIPTION_TITLE_WRAPPER} { color: ${textColor}; }`,
			);
		}

		return css.join('\n');
	}, [MASTERIYO_WRAPPER, alignment, fontSizeValue, textColor]);

	useEffect(() => {
		setAttributes({ blockCSS: cssToSave });
	}, [cssToSave, setAttributes]);

	return { editorCSS, cssToSave };
}
