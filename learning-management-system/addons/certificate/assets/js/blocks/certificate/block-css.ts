import { useMemo } from 'react';

export function useBlockCSS(props: any) {
	const { attributes } = props;
	const {
		containerWidth,
		pageOrientation = 'L',
		pageSize = 'Letter',
		paddingTop = { value: 100, unit: 'px' },
	} = attributes;
	const paddingTopValue = paddingTop ? paddingTop.value + paddingTop.unit : '';
	const paperRatio = pageSize === 'Letter' ? 1.29411764706 : 1.41428571429;
	const aspectRatio =
		pageOrientation === 'L' ? `${paperRatio} / 1` : `1 / ${paperRatio}`;
	const containerWidthValue =
		(typeof containerWidth === 'number'
			? containerWidth
			: containerWidth?.value || '100') + '%';

	const widths = {
		A4: {
			P: '796px',
			L: '1128px',
		},
		Letter: {
			P: '822px',
			L: '1065px',
		},
	};
	const currentWidth = widths[pageSize][pageOrientation]
		? widths[pageSize][pageOrientation]
		: '100%';

	// CSS for block editor.
	const editorCSS = useMemo(() => {
		const certificateBlock = '.wp-block[data-type="masteriyo/certificate"]';
		let css: string[] = [];

		css.push(`${certificateBlock} { aspect-ratio: ${aspectRatio}; }`);
		css.push(`${certificateBlock} { width: ${currentWidth} !important; }`);
		css.push(`${certificateBlock} { min-width: ${currentWidth} !important; }`);
		css.push(`${certificateBlock} { max-width: ${currentWidth} !important; }`);
		css.push(`.certificate-content-wrapper { width: ${containerWidthValue}; }`);
		css.push(
			`.certificate-content-wrapper { padding-top: ${paddingTopValue}; }`,
		);

		return css.join('\n');
	}, [aspectRatio, containerWidthValue, paddingTopValue, currentWidth]);

	return { editorCSS };
}
