import { useEffect, useMemo } from 'react';
import { useDeviceType } from '../../hooks/useDeviceType';

export function useBlockCSS(props: any) {
	const { clientId, attributes, setAttributes } = props;
	const {
		clientId: persistedClientId,
		alignment,
		fontSize,
		textColor,
		borderRadius,
		padding,
		startCategoryBorder = {},
	} = attributes;
	const [deviceType] = useDeviceType();
	const BLOCK_WRAPPER = `#block-${clientId}`;
	const MASTERIYO_WRAPPER = `.masteriyo-course-category-block--${persistedClientId} .masteriyo-course--content__category`;
	const CATEGORY = `${MASTERIYO_WRAPPER} a.masteriyo-course--content__category-items`;
	const fontSizeValue = fontSize ? fontSize.value + fontSize.unit : '';
	const radius = startCategoryBorder?.radius?.[deviceType.toLowerCase()] || {};
	const unit = startCategoryBorder?.radius?.unit || 'px';
	const borderRadiusValue = borderRadius
		? borderRadius.value + borderRadius.unit
		: '';

	const radiusCSS = Object.entries(radius)
		.map(([key, val]) => {
			if (val === undefined || val === null) return '';

			const cssMap: Record<string, string> = {
				top: 'border-top',
				right: 'border-right',
				bottom: 'border-bottom',
				left: 'border-left',
			};

			const cssProp = cssMap[key];
			if (!cssProp) return '';

			const valueString = typeof val === 'number' ? `${val}${unit}` : `${val}`;
			return `${CATEGORY} { ${cssProp}: ${valueString}; }`;
		})
		.filter(Boolean)
		.join('\n');

	const editorCSS = useMemo(() => {
		let css: string[] = [];
		if (alignment) {
			css.push(`${BLOCK_WRAPPER} { justify-content: ${alignment}; }`);
		}
		if (fontSizeValue) {
			css.push(`${CATEGORY} { font-size: ${fontSizeValue}; }`);
		}
		if (textColor) {
			css.push(`${CATEGORY} { color: ${textColor}; }`);
		}
		if (borderRadius) {
			css.push(`${CATEGORY} { border-radius: ${borderRadiusValue}; }`);
		}
		if (padding) {
			const d = padding.padding.desktop;
			const t = padding.padding.tablet;
			const m = padding.padding.mobile;

			css.push(`${CATEGORY} {
				padding-top: ${d.top}${d.unit};
				padding-right: ${d.right}${d.unit};
				padding-bottom: ${d.bottom}${d.unit};
				padding-left: ${d.left}${d.unit};
			}
			@media (max-width: 960px) {
				${CATEGORY} {
					padding-top: ${t.top}${t.unit};
					padding-right: ${t.right}${t.unit};
					padding-bottom: ${t.bottom}${t.unit};
					padding-left: ${t.left}${t.unit};
				}
			}
			@media (max-width: 768px) {
				${CATEGORY} {
					padding-top: ${m.top}${m.unit};
					padding-right: ${m.right}${m.unit};
					padding-bottom: ${m.bottom}${m.unit};
					padding-left: ${m.left}${m.unit};
				}
			}`);
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
		borderRadiusValue,
		radiusCSS,
		padding,
	]);

	const cssToSave = useMemo(() => {
		let css: string[] = [];

		if (alignment) {
			css.push(`${MASTERIYO_WRAPPER} { justify-content : ${alignment}; }`);
		}
		if (fontSizeValue) {
			css.push(`${CATEGORY} { font-size: ${fontSizeValue}; }`);
		}
		if (textColor) {
			css.push(`${CATEGORY} { color: ${textColor}; }`);
		}
		if (borderRadius) {
			css.push(`${CATEGORY} { border-radius: ${borderRadiusValue}; }`);
		}
		if (padding) {
			const d = padding.padding.desktop;
			const t = padding.padding.tablet;
			const m = padding.padding.mobile;

			css.push(`${CATEGORY} {
				padding-top: ${d.top}${d.unit};
				padding-right: ${d.right}${d.unit};
				padding-bottom: ${d.bottom}${d.unit};
				padding-left: ${d.left}${d.unit};
			}
			@media (max-width: 960px) {
				${CATEGORY} {
					padding-top: ${t.top}${t.unit};
					padding-right: ${t.right}${t.unit};
					padding-bottom: ${t.bottom}${t.unit};
					padding-left: ${t.left}${t.unit};
				}
			}
			@media (max-width: 768px) {
				${CATEGORY} {
					padding-top: ${m.top}${m.unit};
					padding-right: ${m.right}${m.unit};
					padding-bottom: ${m.bottom}${m.unit};
					padding-left: ${m.left}${m.unit};
				}
			}`);
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
		borderRadiusValue,
		radiusCSS,
		padding,
	]);

	useEffect(() => {
		setAttributes({ blockCSS: cssToSave });
	}, [cssToSave, setAttributes]);

	return { editorCSS, cssToSave };
}
