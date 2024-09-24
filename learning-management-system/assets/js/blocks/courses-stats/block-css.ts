import { useEffect, useMemo } from 'react';

export function useBlockCSS(props: any) {
	const { clientId, attributes, setAttributes } = props;
	const {
		clientId: persistedClientId,

		minWidth,
	} = attributes;
	const BLOCK_WRAPPER = `#block-${clientId}`;
	const MASTERIYO_WRAPPER = `.masteriyo-course-stats-block--${persistedClientId}`;

	const editorCSS = useMemo(() => {
		let css: string[] = [];

		css.push(
			`.masteriyo-single-course .masteriyo-block .masteriyo-course-stats-block--${clientId} .masteriyo-single-course-stats {
				min-width: ${minWidth.value}px; }`,
		);
		return css.join('\n');
	}, [BLOCK_WRAPPER, minWidth]);

	const cssToSave = useMemo(() => {
		let css: string[] = [];

		return css.join('\n');
	}, [MASTERIYO_WRAPPER, minWidth]);

	useEffect(() => {
		setAttributes({ blockCSS: cssToSave });
	}, [cssToSave, setAttributes]);
	return { editorCSS, cssToSave };
}
