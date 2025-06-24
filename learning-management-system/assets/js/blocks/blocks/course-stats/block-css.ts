import { useEffect, useMemo } from 'react';

type MinWidthSetting = {
	value: number;
};

interface BlockCSSProps {
	clientId: string;
	attributes: {
		clientId: string;
		minWidth?: MinWidthSetting;
	};
	setAttributes: (attrs: any) => void;
}

export function useBlockCSS(props: BlockCSSProps) {
	const { clientId, attributes, setAttributes } = props;
	const { clientId: persistedClientId, minWidth } = attributes;

	const BLOCK_WRAPPER = `#block-${clientId}`;
	const MASTERIYO_WRAPPER = `.masteriyo-course-stats-block--${persistedClientId}`;

	const editorCSS = useMemo(() => {
		let css: string[] = [];

		if (minWidth?.value !== undefined && minWidth?.value !== null) {
			css.push(
				`.masteriyo-single-course .masteriyo-block .masteriyo-course-stats-block--${clientId} .masteriyo-single-course-stats {
					min-width: ${minWidth.value}px;
				}`,
			);
		}

		return css.join('\n');
	}, [BLOCK_WRAPPER, clientId, minWidth]);

	const cssToSave = useMemo(() => {
		let css: string[] = [];

		// You can add similar minWidth condition here if needed
		// Example:
		// if (minWidth?.value !== undefined && minWidth?.value !== null) {
		//     css.push(`${MASTERIYO_WRAPPER} { min-width: ${minWidth.value}px; }`);
		// }

		return css.join('\n');
	}, [MASTERIYO_WRAPPER, minWidth]);

	useEffect(() => {
		setAttributes({ blockCSS: cssToSave });
	}, [cssToSave, setAttributes]);

	return { editorCSS, cssToSave };
}
