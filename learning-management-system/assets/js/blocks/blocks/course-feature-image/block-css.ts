import { useEffect, useMemo } from 'react';

export function useBlockCSS(props: any) {
	const { clientId, attributes, setAttributes } = props ?? {};
	const { clientId: persistedClientId, height_n_width = {} } = attributes ?? {};

	// ✅ Use correct block class
	const MASTERIYO_WRAPPER = `.masteriyo-course-featured-image--${persistedClientId}`;

	const getSizeCSS = (
		device: 'desktop' | 'tablet' | 'mobile' | null = null,
	) => {
		const size = device ? height_n_width?.[device] || {} : height_n_width;

		const width = size?.width;
		const height = size?.height;
		const unit = size?.unit || 'px';

		if (!width && !height) return '';

		const styles = `
			${MASTERIYO_WRAPPER} .masteriyo-feature-img img {
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
		return getSizeCSS(null);
	}, [MASTERIYO_WRAPPER, height_n_width]);

	const cssToSave = useMemo(() => {
		return [getSizeCSS('desktop'), getSizeCSS('tablet'), getSizeCSS('mobile')]
			.filter(Boolean)
			.join('\n');
	}, [MASTERIYO_WRAPPER, height_n_width]);

	useEffect(() => {
		setAttributes?.({ blockCSS: cssToSave });
	}, [cssToSave, setAttributes]);

	return { editorCSS, cssToSave };
}
