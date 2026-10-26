import React, { ReactElement } from 'react';
import { getString } from '../utils/i18n';

interface BlockPreviewContentProps {
	/** BEM-ish class prefix for this module, e.g. "masteriyo_divi5_course_list". */
	classPrefix: string;
	/** Module name shown in the inline error message, e.g. "Course List". */
	label: string;
	fetchError: string;
	isPending: boolean;
	renderedHtml: string;
}

/**
 * The canvas-preview markup every native module's edit.tsx renders inside
 * its ModuleContainer — identical across modules apart from the class
 * prefix and label.
 */
export const BlockPreviewContent = ({
	classPrefix,
	label,
	fetchError,
	isPending,
	renderedHtml,
}: BlockPreviewContentProps): ReactElement => (
	<>
		{fetchError && (
			<div className={`${classPrefix}__error`}>
				{getString('previewFailed').replace('%s', label)} {fetchError}
			</div>
		)}
		{/* Dims the last-known preview instead of blanking it while a new one loads. */}
		<div
			className={`${classPrefix}__preview`}
			style={isPending ? { opacity: 0.5 } : undefined}
		>
			{/* Same-origin server render from our own block-renderer call, not user input. */}
			{/* eslint-disable-next-line @eslint-react/dom-no-dangerously-set-innerhtml */}
			<div dangerouslySetInnerHTML={{ __html: renderedHtml }} />
		</div>
	</>
);
