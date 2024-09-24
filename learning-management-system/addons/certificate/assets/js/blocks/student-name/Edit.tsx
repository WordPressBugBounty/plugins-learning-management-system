import { __ } from '@wordpress/i18n';
import React from 'react';
import useClientId from '../../../../../../assets/js/blocks/hooks/useClientId';
import { camelToKebab } from '../../utils/blocks';
import BlockSettings from './BlockSettings';
import { useBlockCSS } from './block-css';

const Edit: React.FC<any> = (props) => {
	const {
		attributes: { clientId, fontFamily },
		setAttributes,
	} = props;

	useClientId(props.clientId, setAttributes, props.attributes);

	const { editorCSS } = useBlockCSS(props);

	return (
		<React.Fragment>
			<BlockSettings {...props} />
			<style>{editorCSS}</style>
			<div
				className={`masteriyo-block-${clientId} masteriyo-student-name-block--${clientId}${
					fontFamily && 'Default' !== fontFamily
						? ` has-${camelToKebab(fontFamily)}-font-family`
						: ''
				}`}
			>
				{__('Student Name', 'learning-management-system')}
			</div>
		</React.Fragment>
	);
};

export default Edit;
