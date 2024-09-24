import { Box, Divider } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { isAddonActive } from '../../add-ons/api/addons';
import DocUploader from '../../add-ons/components/DocUploader';

interface Props {
	defaultValue?: DownloadMaterials;
}

const DownloadMaterial: React.FC<Props> = (props) => {
	const { defaultValue } = props;

	if (isAddonActive('download-materials')) {
		return (
			<>
				<Box py="3">
					<Divider />
				</Box>
				<DocUploader
					name={{
						title: __('Download Materials', 'learning-management-system'),
						keyIndex: 'download_materials',
					}}
					defaultValue={defaultValue}
					docPreviewNotice={__(
						'Preview for download materials may not work if you are on local server.',
						'learning-management-system',
					)}
					useWPLibrary={true}
				/>
			</>
		);
	}

	return null;
};

export default DownloadMaterial;
