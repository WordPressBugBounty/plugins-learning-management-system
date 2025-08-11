import { useEffect, useMemo } from 'react';

export function useBlockCSS(props: any) {
	const { clientId, attributes, setAttributes } = props ?? {};
	const {
		clientId: persistedClientId,
		minWidth,
		fontSize,
		textColor,
		height_n_width = {},
	} = attributes ?? {};

	const MASTERIYO_WRAPPER = `.masteriyo-title-block--${persistedClientId}`;

	const fontSizeValue =
		fontSize?.value && fontSize?.unit
			? `${fontSize.value}${fontSize.unit}`
			: '';

	const textColorValue = textColor || '';

	const generateFontSizeCSS = () => {
		if (!fontSizeValue) return '';
		return `
			${MASTERIYO_WRAPPER} .masteriyo-course-author--name {
				font-size: ${fontSizeValue};
			}
		`;
	};

	const generateTextColorCSS = () => {
		if (!textColorValue) return '';
		return `
			${MASTERIYO_WRAPPER} .masteriyo-course-author--name {
				color: ${textColorValue};
			}
		`;
	};

	const getSizeCSS = (
		device: 'desktop' | 'tablet' | 'mobile' | null = null,
	) => {
		const size = device ? height_n_width?.[device] || {} : height_n_width;

		const width = size?.width;
		const height = size?.height;
		const unit = size?.unit || 'px';

		if (!width && !height) return '';

		const styles = `
			${MASTERIYO_WRAPPER} .masteriyo-course-author img {
				${width ? `width: ${width}${unit};` : ''}
				${height ? `height: ${height}${unit};` : ''}
			}
		`;

		if (!device || device === 'desktop') {
			return styles;
		}

		const mediaQuery =
			device === 'tablet'
				? '@media (max-width: 1024px)'
				: '@media (max-width: 767px)';

		return `
			${mediaQuery} {
				${styles}
			}
		`;
	};

	const editorCSS = useMemo(() => {
		return [generateFontSizeCSS(), generateTextColorCSS(), getSizeCSS(null)]
			.filter(Boolean)
			.join('\n');
	}, [
		clientId,
		MASTERIYO_WRAPPER,
		minWidth?.value,
		fontSizeValue,
		textColorValue,
		height_n_width,
	]);

	const cssToSave = useMemo(() => {
		return [
			generateFontSizeCSS(),
			generateTextColorCSS(),
			getSizeCSS('desktop'),
			getSizeCSS('tablet'),
			getSizeCSS('mobile'),
		]
			.filter(Boolean)
			.join('\n');
	}, [MASTERIYO_WRAPPER, fontSizeValue, textColorValue, height_n_width]);

	useEffect(() => {
		setAttributes?.({ blockCSS: cssToSave });
	}, [cssToSave, setAttributes]);

	return { editorCSS, cssToSave };
}
