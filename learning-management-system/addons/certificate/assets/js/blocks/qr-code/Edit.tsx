import { Image } from '@chakra-ui/react';
import React from 'react';
import { QrCode } from '../../../../../../assets/js/back-end/constants/images';
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
				className={`masteriyo-block-${clientId} masteriyo-qr-code-block--${clientId}`}
			>
				<Image
					display="inline-block"
					boxSize="80px"
					objectFit="cover"
					src={QrCode}
					alt="qr code"
				/>
			</div>
		</React.Fragment>
	);
};

export default Edit;
