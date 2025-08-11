import { useEffect, useMemo } from 'react';

export function useBlockCSS(props: any) {
	const { clientId, attributes, setAttributes } = props ?? {};
	const {
		clientId: persistedClientId,
		minWidth,
		fontSize,
		textColor,
		layoutOption,
	} = attributes ?? {};

	const MASTERIYO_WRAPPER = `.masteriyo-course-stats-block--${persistedClientId}`;
	const fontSizeValue =
		fontSize?.value && fontSize?.unit
			? `${fontSize.value}${fontSize.unit}`
			: '';
	const textColorValue = textColor || '';
	const layoutOptionValue = layoutOption === 'grid' ? 'grid' : 'list';

	const generateLayoutCSS = () => {
		if (layoutOptionValue === 'grid') {
			return `
				${MASTERIYO_WRAPPER} .masteriyo-single-course-stats {
					    display: flex;
						flex-direction: row;
						flex-wrap: wrap;
						padding: 0 24px;
						margin-top: 16px;
				}
			`;
		} else {
			return `
				${MASTERIYO_WRAPPER} .masteriyo-single-course-stats {
				         display: flex;
						flex-direction: column;
						flex-wrap: wrap;
						padding: 0 24px;
						margin-top: 16px;
				}
			`;
		}
	};

	const generateFontSizeCSS = () => {
		if (!fontSizeValue) return '';
		return `
			${MASTERIYO_WRAPPER} .duration span,
			${MASTERIYO_WRAPPER} .student span,
			${MASTERIYO_WRAPPER} .last-updated span,
			${MASTERIYO_WRAPPER} .masteriyo-available-seats-for-students span,
			${MASTERIYO_WRAPPER} .course-started-at span {
				font-size: ${fontSizeValue};
			}
		`;
	};

	const generateTextColorCSS = () => {
		if (!textColorValue) return '';
		return `
			${MASTERIYO_WRAPPER} .duration span,
			${MASTERIYO_WRAPPER} .student span,
			${MASTERIYO_WRAPPER} .last-updated span,
			${MASTERIYO_WRAPPER} .course-started-at span {
				color: ${textColorValue};
			}
		`;
	};

	const generateMinWidthCSS = () => {
		if (minWidth?.value === undefined || minWidth?.value === null) return '';
		return `
			.masteriyo-single-course .masteriyo-block .masteriyo-course-stats-block--${clientId} .masteriyo-single-course-stats {
				min-width: ${minWidth.value}px;
			}
		`;
	};

	const editorCSS = useMemo(() => {
		return [
			generateLayoutCSS(),
			generateMinWidthCSS(),
			generateFontSizeCSS(),
			generateTextColorCSS(),
		]
			.filter(Boolean)
			.join('\n');
	}, [
		clientId,
		MASTERIYO_WRAPPER,
		layoutOptionValue,
		minWidth?.value,
		fontSizeValue,
		textColorValue,
	]);

	const cssToSave = useMemo(() => {
		return [generateLayoutCSS(), generateFontSizeCSS(), generateTextColorCSS()]
			.filter(Boolean)
			.join('\n');
	}, [MASTERIYO_WRAPPER, layoutOptionValue, fontSizeValue, textColorValue]);

	useEffect(() => {
		setAttributes?.({ blockCSS: cssToSave });
	}, [cssToSave, setAttributes]);

	return { editorCSS, cssToSave };
}
