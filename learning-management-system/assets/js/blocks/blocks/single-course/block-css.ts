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

			.masteriyo-course--img-wrap {
				margin-bottom: 30px;
			}

			.masteriyo-course-title-wrapper {
				padding-left: 30px;
				padding-right: 30px;
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

			.masteriyo-course-pricing--wrapper {
				display: flex;
				flex-wrap: wrap;
				align-items: center;
				gap: 10px;
			}

			.masteriyo-course-pricing--wrapper {
				margin: 0px !important;
				padding: 0px !important;
				border-bottom: none !important;
			}

			.masteriyo-single-course--aside > .masteriyo-course-pricing--wrapper {
				border-bottom: none !important;
			}

			.masteriyo-course-pricing--wrapper .masteriyo-price-block {
				flex: 0 1 auto;
			}

			.masteriyo-course-pricing--wrapper .masteriyo-enroll-btn-block {
				flex: 1 1 auto;
			}

			.masteriyo-col-4 {
				margin-top: 16px !important;
			}

			.masteriyo-col-8 .masteriyo-course--content{
				padding: 0;
			}

			// /* Disable WordPress default layout flow margins within single course block */
			// .masteriyo-single-course.is-layout-flow > *,
			// .masteriyo-single-course.is-layout-constrained > *,
			// .masteriyo-single-course .is-layout-flow > *,
			// .masteriyo-single-course .is-layout-constrained > *,
			// .masteriyo-single-course .block-editor-block-list__block {
			// 	margin-block-start: 0 !important;
			// 	margin-block-end: 0 !important;
			// }

			.masteriyo-course-author-rating-wrapper{
				margin-bottom: 0 !important;
			}

			.masteriyo-single-course--main .masteriyo-course-author-rating-wrapper {
				padding: 0px 16px;
			}

			.masteriyo-single-course-stats-wrapper .masteriyo-course-statistics{
				padding-bottom: 30px !important;
			}

			.masteriyo-price-currencySymbol{
				margin: 0 4px;
			}

			.masteriyo-single-course-stats.masteriyo-course-statistics{
				padding: 0 0 32px 0;
				border-bottom: 1px solid #e2e8f0 !important;
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

			.masteriyo-course--img-wrap {
				margin-bottom: 30px;
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

			.masteriyo-course-pricing--wrapper {
				display: flex;
				flex-wrap: wrap;
				align-items: center;
				gap: 10px;
				margin: 0px;
				padding: 0px;
			}

			 .masteriyo-course-title-wrapper {
				padding-left: 30px !important;
				padding-right: 30px !important;
			}

			.masteriyo-course-pricing--wrapper {
				margin: 0px !important;
				padding: 0px !important;
			}

			.masteriyo-single-course--aside > .masteriyo-course-pricing--wrapper {
				border-bottom: none !important;
			}

			.masteriyo-single-course--aside > .masteriyo-single-course-stats-wrapper{
				margin: 0px !important;
				padding-top: 12px !important;
				padding-bottom: 12px !important;
				border-top: 1px solid #e2e8f0 !important;
			}

			.masteriyo-single-course-stats-wrapper > .masteriyo-single-course{
				margin: 0 0 30px !important;
			}

			.masteriyo-single-course-stats-wrapper > .masteriyo-single-course-stats{
				border-bottom: none !important;
			}

			.masteriyo-course-pricing--wrapper .masteriyo-price-block {
				flex: 0 1 auto;
			}

			.masteriyo-course-pricing--wrapper .masteriyo-enroll-btn-block {
				flex: 1 1 auto;
			}

			.masteriyo-single-course-group-price-btn > .masteriyo-group-course__group-button {
				padding: 0px !important;
			}

			/* Disable WordPress default layout flow margins within single course block */
			.masteriyo-single-course.is-layout-flow > *,
			.masteriyo-single-course.is-layout-constrained > *,
			.masteriyo-single-course .is-layout-flow > *,
			.masteriyo-single-course .is-layout-constrained > *,
			.masteriyo-single-course .block-editor-block-list__block {
				margin-block-start: 0 !important;
				margin-block-end: 0 !important;
			}

			.masteriyo-col-8 .masteriyo-course--content{
				font-size: 16px !important;
			}

			.masteriyo-course-author-rating-wrapper{
					margin-bottom: 0 !important;
			}

			.masteriyo-single-course--main .masteriyo-course-author-rating-wrapper {
				padding: 0px 16px;
			}

			.masteriyo-single-course-stats-wrapper .masteriyo-course-statistics{
				padding-bottom: 30px !important;
			}

			.masteriyo-price-currencySymbol{
				margin: 0 4px;
			}

			.masteriyo-single-course-stats.masteriyo-course-statistics{
				padding: 0 0 32px 0;
				border-bottom: 1px solid #e2e8f0 !important;
			}

			.masteriyo-single-course-highlights-wrapper .masteriyo-single-course-social-share {
				padding-top: 36px !important;
				margin-top: 36px !important;
				border-top: 1px solid #e2e8f0 !important;
			} 

		`);

		return css.join('\n');
	}, [MASTERIYO_WRAPPER, alignment, fontSizeValue, textColor, padding, gap]);

	useEffect(() => {
		setAttributes({ blockCSS: cssToSave });
	}, [cssToSave, setAttributes]);

	return { editorCSS, cssToSave };
}
