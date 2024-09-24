import { __ } from '@wordpress/i18n';
import React from 'react';
import useClientId from '../../../../../../assets/js/blocks/hooks/useClientId';
import BlockSettings from './BlockSettings';
import { useBlockCSS } from './block-css';

const Edit: React.FC<any> = (props) => {
	const {
		attributes: { clientId },
		setAttributes,
	} = props;

	useClientId(props.clientId, setAttributes, props.attributes);

	const { editorCSS } = useBlockCSS(props);

	return (
		<React.Fragment>
			<BlockSettings {...props} />
			<style>{editorCSS}</style>
			<div
				className={`masteriyo-block-${clientId} masteriyo-course-completion-date-block--${clientId}`}
			>
				{__('Completion Date', 'learning-management-system')}
			</div>
		</React.Fragment>
	);
};

export default Edit;
