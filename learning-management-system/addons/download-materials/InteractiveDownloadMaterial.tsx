import { Box } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import DocPreview from '../add-ons/components/DocPreview';

interface Props {
	items: DownloadMaterials;
}

const InteractiveDownloadMaterial: React.FC<Props> = (props) => {
	const { items: attachments } = props;

	return (
		<Box>
			<DocPreview
				files={attachments}
				isDownloadable
				docPreviewNotice={__(
					'Preview for download materials may not work if you are on local server.',
					'learning-management-system',
				)}
				hidePreviewNotice={true}
			/>
		</Box>
	);
};

export default InteractiveDownloadMaterial;
