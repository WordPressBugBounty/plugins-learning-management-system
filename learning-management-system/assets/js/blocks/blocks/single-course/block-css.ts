import { useEffect, useMemo } from 'react';

export function useBlockCSS(props: any) {
	const { clientId, attributes, setAttributes } = props;
	const {
		clientId: persistedClientId,
		alignment,
		fontSize,
		textColor,
		margin,
		padding,
		gap,
	} = attributes;

	const BLOCK_WRAPPER = `#block-${clientId}`;
	const MASTERIYO_WRAPPER = `.masteriyo-single-course-block--${persistedClientId}`;
	const fontSizeValue = fontSize ? fontSize.value + fontSize.unit : '';

	const editorCSS = useMemo(() => {
		let css: string[] = [];

		if (alignment) css.push(`${BLOCK_WRAPPER} { text-align: ${alignment}; }`);
		if (fontSizeValue)
			css.push(`${BLOCK_WRAPPER} { font-size: ${fontSizeValue}; }`);
		if (textColor) css.push(`${BLOCK_WRAPPER} { color: ${textColor}; }`);
		if (gap) css.push(`${BLOCK_WRAPPER} { gap: ${gap}; }`);
		if (padding) {
			Object.keys(padding.padding).forEach((device) => {
				const p = padding.padding[device];
				css.push(`${BLOCK_WRAPPER} {
					padding-top: ${p.top}${p.unit};
					padding-right: ${p.right}${p.unit};
					padding-bottom: ${p.bottom}${p.unit};
					padding-left: ${p.left}${p.unit};
				}`);
			});
		}

		// Layout styling
		css.push(`
			.wp-block-columns {
				gap: 2rem;
			}

			.masteriyo-main-content {
				padding-right: 1rem;
			}

			.masteriyo-sidebar {
				border: 1px solid #e2e8f0;
				border-radius: 8px;
				padding: 20px;
				background-color: #ffffff;
				margin-top: 1.5rem;
				word-break: normal;
				white-space: normal;
				overflow-wrap: anywhere;
				max-width: 100%;
				width: 100%;
			}

			.masteriyo-sidebar ul li {
				margin-bottom: 0.5rem;
			}

			.masteriyo-course--content h2 {
				font-size: 1.75rem;
				font-weight: 600;
				margin-top: 1rem;
				margin-bottom: 0.5rem;
			}

			.masteriyo-tabs {
				margin-top: 1rem;
				border-top: 1px solid #e2e8f0;
				padding-top: 1rem;
			}
		`);

		return css.join('\n');
	}, [
		BLOCK_WRAPPER,
		alignment,
		fontSizeValue,
		textColor,
		margin,
		padding,
		gap,
	]);

	const cssToSave = useMemo(() => {
		let css: string[] = [];

		if (alignment)
			css.push(`${MASTERIYO_WRAPPER} { text-align: ${alignment}; }`);
		if (fontSizeValue)
			css.push(`${MASTERIYO_WRAPPER} { font-size: ${fontSizeValue}; }`);
		if (textColor) css.push(`${MASTERIYO_WRAPPER} { color: ${textColor}; }`);
		if (gap) css.push(`${MASTERIYO_WRAPPER} { gap: ${gap}; }`);
		if (padding) {
			const d = padding.padding.desktop;
			const t = padding.padding.tablet;
			const m = padding.padding.mobile;

			css.push(`${MASTERIYO_WRAPPER} {
				padding-top: ${d.top}${d.unit};
				padding-right: ${d.right}${d.unit};
				padding-bottom: ${d.bottom}${d.unit};
				padding-left: ${d.left}${d.unit};
			}
			@media (max-width: 960px) {
				${MASTERIYO_WRAPPER} {
					padding-top: ${t.top}${t.unit};
					padding-right: ${t.right}${t.unit};
					padding-bottom: ${t.bottom}${t.unit};
					padding-left: ${t.left}${t.unit};
				}
			}
			@media (max-width: 768px) {
				${MASTERIYO_WRAPPER} {
					padding-top: ${m.top}${m.unit};
					padding-right: ${m.right}${m.unit};
					padding-bottom: ${m.bottom}${m.unit};
					padding-left: ${m.left}${m.unit};
				}
			}`);
		}

		// Same layout styles for frontend
		css.push(`
			.wp-block-columns {
				gap: 2rem;
			}

			.masteriyo-main-content {
				padding-right: 1rem;
			}

			.masteriyo-sidebar {
				border: 1px solid #e2e8f0;
				border-radius: 8px;
				padding: 20px;
				background-color: #ffffff;
				margin-top: 1.5rem;
				word-break: normal;
				white-space: normal;
				overflow-wrap: anywhere;
				max-width: 100%;
				width: 100%;
			}

			.masteriyo-sidebar ul li {
				margin-bottom: 0.5rem;
			}

			.masteriyo-course--content h2 {
				font-size: 1.75rem;
				font-weight: 600;
				margin-top: 1rem;
				margin-bottom: 0.5rem;
			}

			.masteriyo-tabs {
				margin-top: 1rem;
				border-top: 1px solid #e2e8f0;
				padding-top: 1rem;
			}
		`);

		return css.join('\n');
	}, [MASTERIYO_WRAPPER, alignment, fontSizeValue, textColor, padding, gap]);

	useEffect(() => {
		setAttributes({ blockCSS: cssToSave });
	}, [cssToSave, setAttributes]);

	return { editorCSS, cssToSave };
}
