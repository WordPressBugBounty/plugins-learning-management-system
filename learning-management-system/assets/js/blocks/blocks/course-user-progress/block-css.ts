import { useEffect, useMemo } from 'react';
import { useDeviceType } from '../../hooks/useDeviceType';

export function useBlockCSS(props: any) {
	const { clientId, attributes, setAttributes } = props;
	const {
		clientId: persistedClientId,
		alignment,
		fontSize,
		textColor,
	} = attributes;
	const [deviceType] = useDeviceType();
	const BLOCK_WRAPPER = `#block-${clientId}`;
	const MASTERIYO_WRAPPER = `.masteriyo-user-course-progress-block--${persistedClientId} .masteriyo-single-course-stats`;
	const USER_PROGRESS_LABEL = `${MASTERIYO_WRAPPER} .progress-label`;
	const USER_PROGRESS_PERCENTAGE = `${MASTERIYO_WRAPPER} .progress-percent`;
	const USER_PROGRESS_COMPLETE_INFO = `${MASTERIYO_WRAPPER} .completed-info`;
	const fontSizeValue = fontSize ? fontSize.value + fontSize.unit : '';

	const editorCSS = useMemo(() => {
		let css: string[] = [];
		if (alignment) {
			css.push(`${BLOCK_WRAPPER} { justify-content: ${alignment}; }`);
		}
		if (fontSizeValue) {
			css.push(`${USER_PROGRESS_LABEL} { font-size: ${fontSizeValue}; }`);
		}
		if (fontSizeValue) {
			css.push(`${USER_PROGRESS_PERCENTAGE} { font-size: ${fontSizeValue}; }`);
		}
		if (fontSizeValue) {
			css.push(
				`${USER_PROGRESS_COMPLETE_INFO} { font-size: ${fontSizeValue}; }`,
			);
		}
		if (textColor) {
			css.push(`${USER_PROGRESS_LABEL} { color: ${textColor}; }`);
		}
		if (textColor) {
			css.push(`${USER_PROGRESS_PERCENTAGE} { color: ${textColor}; }`);
		}
		if (textColor) {
			css.push(`${USER_PROGRESS_COMPLETE_INFO} { color: ${textColor}; }`);
		}

		return css.join('\n');
	}, [BLOCK_WRAPPER, alignment, fontSizeValue, textColor]);

	const cssToSave = useMemo(() => {
		let css: string[] = [];

		if (alignment) {
			css.push(`${MASTERIYO_WRAPPER} { justify-content : ${alignment}; }`);
		}
		if (fontSizeValue) {
			css.push(`${USER_PROGRESS_LABEL} { font-size: ${fontSizeValue}; }`);
		}
		if (fontSizeValue) {
			css.push(`${USER_PROGRESS_PERCENTAGE} { font-size: ${fontSizeValue}; }`);
		}
		if (fontSizeValue) {
			css.push(
				`${USER_PROGRESS_COMPLETE_INFO} { font-size: ${fontSizeValue}; }`,
			);
		}
		if (textColor) {
			css.push(`${USER_PROGRESS_LABEL} { color: ${textColor}; }`);
		}
		if (textColor) {
			css.push(`${USER_PROGRESS_PERCENTAGE} { color: ${textColor}; }`);
		}
		if (textColor) {
			css.push(`${USER_PROGRESS_COMPLETE_INFO} { color: ${textColor}; }`);
		}

		return css.join('\n');
	}, [MASTERIYO_WRAPPER, alignment, fontSizeValue, textColor]);

	useEffect(() => {
		setAttributes({ blockCSS: cssToSave });
	}, [cssToSave, setAttributes]);

	return { editorCSS, cssToSave };
}
