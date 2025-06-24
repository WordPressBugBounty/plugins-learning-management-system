import { useEffect, useMemo } from 'react';

export function useBlockCSS(props: any) {
	const { clientId, attributes, setAttributes } = props;
	const { clientId: persistedClientId, fontSize, textColor } = attributes;
	const BLOCK_WRAPPER = `#block-${clientId}`;
	const MASTERIYO_WRAPPER = `.masteriyo-course-featured-image--${persistedClientId}`;
	const fontSizeValue = fontSize ? fontSize.value + fontSize.unit : '';
	const editorCSS = useMemo(() => {
		let css: string[] = [];
		if (textColor) {
			css.push(`${BLOCK_WRAPPER} { color: ${textColor}; }`);
		}

		return css.join('\n');
	}, [BLOCK_WRAPPER, fontSizeValue, textColor]);

	const cssToSave = useMemo(() => {
		let css: string[] = [];
		if (fontSizeValue) {
			css.push(`${MASTERIYO_WRAPPER} { font-size: ${fontSizeValue}; }`);
		}
		if (textColor) {
			css.push(`${MASTERIYO_WRAPPER} { color: ${textColor}; }`);
		}

		return css.join('\n');
	}, [MASTERIYO_WRAPPER, fontSizeValue, textColor]);

	useEffect(() => {
		setAttributes({ blockCSS: cssToSave });
	}, [cssToSave, setAttributes]);

	return { editorCSS, cssToSave };
}
