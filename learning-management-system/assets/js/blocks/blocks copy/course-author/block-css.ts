import { useEffect, useMemo } from 'react';

export function useBlockCSS(props: any) {
	const { clientId, attributes, setAttributes } = props;
	const { clientId: persistedClientId, margin } = attributes;
	const BLOCK_WRAPPER = `#block-${clientId}`;
	const MASTERIYO_WRAPPER = `.masteriyo-course-author-block--${persistedClientId}`;

	const editorCSS = useMemo(() => {
		let css: string[] = [];
		if (margin) {
			css.push(
				`${BLOCK_WRAPPER} { margin${margin ? '-' : null}${margin}: 'auto'; }`,
			);
		}
		return css.join('\n');
	}, [BLOCK_WRAPPER, margin]);

	const cssToSave = useMemo(() => {
		let css: string[] = [];
		if (margin) {
			css.push(
				`${MASTERIYO_WRAPPER} { margin${
					margin ? '-' : null
				}${margin}: 'auto'; }`,
			);
		}
		return css.join('\n');
	}, [MASTERIYO_WRAPPER, margin]);

	useEffect(() => {
		setAttributes({ blockCSS: cssToSave });
	}, [cssToSave, setAttributes]);
	return { editorCSS, cssToSave };
}
