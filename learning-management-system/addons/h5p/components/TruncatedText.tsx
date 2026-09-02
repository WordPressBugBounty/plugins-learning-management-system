import { Text, TextProps, Tooltip } from '@chakra-ui/react';
import React, { useCallback, useLayoutEffect, useRef, useState } from 'react';

interface Props extends TextProps {
	/** Full text shown in the tooltip and rendered in the line. */
	label: string;
}

/**
 * Single-line text that ellipsis-truncates and only reveals a tooltip with the
 * full text when it is actually clipped — so a title that already fits doesn't
 * get a redundant tooltip repeating what's on screen.
 */
const TruncatedText: React.FC<Props> = ({ label, ...rest }) => {
	const ref = useRef<HTMLParagraphElement>(null);
	const [isTruncated, setIsTruncated] = useState(false);

	const measure = useCallback(() => {
		const el = ref.current;
		if (el) {
			setIsTruncated(el.scrollWidth > el.clientWidth);
		}
	}, []);

	useLayoutEffect(() => {
		measure();
		const el = ref.current;
		if (!el || typeof ResizeObserver === 'undefined') {
			return;
		}
		const observer = new ResizeObserver(measure);
		observer.observe(el);
		return () => observer.disconnect();
	}, [measure, label]);

	return (
		<Tooltip
			label={label}
			hasArrow
			placement="top-start"
			isDisabled={!isTruncated}
		>
			<Text ref={ref} isTruncated {...rest}>
				{label}
			</Text>
		</Tooltip>
	);
};

export default TruncatedText;
